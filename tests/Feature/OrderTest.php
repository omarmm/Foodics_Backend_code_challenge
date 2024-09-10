<?php

namespace Tests\Feature;

use App\Enums\UnitEnum;
use App\Mail\StockAlertMail;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test if an order is correctly stored and the stock is updated.
     *
     * @return void
     */
    public function test_order_storage_and_stock_update()
    {
        // Seed the database
        $this->seed();

        // Retrieve product and ingredients
        $product = Product::where('name', 'Burger')->first();
        $beef    = Ingredient::where('name', 'Beef')->first();
        $cheese  = Ingredient::where('name', 'Cheese')->first();
        $onion   = Ingredient::where('name', 'Onion')->first();

        // Create an order
        $orderData = [
            'products' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ]
            ]
        ];

        // Send the request to store the order
        $response = $this->postJson('/api/orders', $orderData);

        // Assert the order was created
        $response->assertStatus(201);
        $this->assertDatabaseHas('orders', [
            'id' => 1, // or use another method to get the order ID
        ]);

        // Assert the ingredients' stock was updated
        $this->assertDatabaseHas('ingredients', [
            'id' => $beef->id,
            'stock' => 19700, // 20000 - 150*2
        ]);
        $this->assertDatabaseHas('ingredients', [
            'id' => $cheese->id,
            'stock' => 4940, // 5000 - 30*2
        ]);
        $this->assertDatabaseHas('ingredients', [
            'id' => $onion->id,
            'stock' => 960, // 1000 - 20*2
        ]);
    }

    /**
     * Test stock alert email is sent when stock falls below threshold.
     *
     * @return void
     */
    public function test_stock_alert_email()
    {
        // Arrange
        $product = Product::create(['name' => 'Burger']);

        // Create ingredient with stock just above threshold
        $ingredient = Ingredient::create([
            'name' => 'Beef',
            'stock' => 160, // 160g initial stock, 50% is 80g
            'initial_stock' => 160,
            'unit' => 'g',
        ]);

        // Attach ingredients to the product
        $product->ingredients()->attach([
            $ingredient->id => ['amount' => 90], // Each burger requires 90g of Beef
        ]);

        // Order data
        $orderData = [
            'products' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1, // This will reduce stock to 160 - 90 = 70g, below 50% threshold
                ]
            ]
        ];

        // Mock the Mail facade to intercept the email
        Mail::fake();

        // Act
        $response = $this->postJson('/api/orders', $orderData);

        // Assert response status
        $response->assertStatus(201);

        // Assert that an email was sent due to stock falling below 50% of the initial stock
        Mail::to(config('email.alert_email'))->queue(new StockAlertMail($ingredient));
        
        // Assert that the stock was updated correctly in the database
        $this->assertDatabaseHas('ingredients', [
            'id' => $ingredient->id,
            'stock' => 160 - 90, // Stock should be 70g after the order
        ]);
    }



    /**
     * Test that order creation fails when there is insufficient stock.
     *
     * @return void
     */
    public function test_order_fails_due_to_insufficient_stock()
    {
        $this->seed();

        // Create a product with insufficient stock
        $product = Product::create(['name' => 'Burger']);
        $ingredient = Ingredient::create([
            'name' => 'Beef',
            'stock' => 50, // Insufficient stock for required amount
            'initial_stock' => 200,
            'unit' => 'g'
        ]);

        // Attach ingredient to the product
        $product->ingredients()->attach($ingredient->id, ['amount' => 100]);

        $orderData = [
            'products' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ]
            ]
        ];

        $response = $this->postJson('/api/orders', $orderData);

        // Assert that the response indicates a failure due to insufficient stock
        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Insufficient stock for ingredient: Beef',
        ]);
    }


    /**
     * Test order rollback on error (e.g., stock update failure).
     *
     * @return void
     */
    public function test_order_rollback_on_error()
    {
        $this->seed();

        // Create a product that simulates an error
        $product = Product::create(['name' => 'Simulate Error']);
        $ingredient = Ingredient::create([
            'name' => 'Beef',
            'stock' => 200,
            'initial_stock' => 200,
            'unit' => 'g'
        ]);

        // Attach ingredient to the product
        $product->ingredients()->attach($ingredient->id, ['amount' => 100]);

        $orderData = [
            'products' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ]
            ]
        ];

        $response = $this->postJson('/api/orders', $orderData);

        $response->assertStatus(500);

        // Ensure that the order was not created due to rollback
        $this->assertDatabaseMissing('orders', ['id' => 1]);

        // Ensure that the stock was not modified due to rollback
        $this->assertEquals(200, $ingredient->fresh()->stock);
    }


    /**
     * Test multiple products in a single order and stock updates.
     *
     * @return void
     */
    public function test_multiple_products_in_order()
    {
        // Seed the database
        $this->seed();

        // Retrieve products
        $burger = Product::where('name', 'Burger')->first();
        $pizza = Product::create(['name' => 'Pizza']);

        // Ingredients for Pizza
        $dough = Ingredient::create([
            'name' => 'Dough',
            'stock' => 500,
            'initial_stock' => 500,
            'unit' => 'g',
        ]);

        $pizza->ingredients()->attach([
            $dough->id => ['amount' => 200], // 200g Dough per pizza
        ]);

        // Create an order with multiple products
        $orderData = [
            'products' => [
                [
                    'product_id' => $burger->id,
                    'quantity' => 1,
                ],
                [
                    'product_id' => $pizza->id,
                    'quantity' => 2,
                ],
            ]
        ];

        // Act
        $response = $this->postJson('/api/orders', $orderData);

        // Assert
        $response->assertStatus(201);

        // Assert Burger stock
        $this->assertDatabaseHas('ingredients', [
            'id' => Ingredient::where('name', 'Beef')->first()->id,
            'stock' => 19850, // 20000 - 150
        ]);

        // Assert Pizza stock
        $this->assertDatabaseHas('ingredients', [
            'id' => $dough->id,
            'stock' => 100, // 500 - 200*2
        ]);
    }
}
