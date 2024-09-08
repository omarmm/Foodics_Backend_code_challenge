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
        
        // Create ingredients
        $ingredient = Ingredient::create([
            'name' => 'Beef',
            'stock' => 100, // Set to trigger alert
            'initial_stock' => 100,
            'unit' => 'g',
        ]);

        // Other ingredients
        Ingredient::create([
            'name' => 'Cheese',
            'stock' => 5000,
            'initial_stock' => 5000,
            'unit' => 'g',
        ]);

        Ingredient::create([
            'name' => 'Onion',
            'stock' => 1000,
            'initial_stock' => 1000,
            'unit' => 'g',
        ]);

        $product->ingredients()->attach([
            $ingredient->id => ['amount' => 150], // 150g Beef per burger
        ]);

        $orderData = [
            'products' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ]
            ]
        ];

        // Mock the Mail facade
        Mail::fake();

        // Act
        $response = $this->postJson('/api/orders', $orderData);

        // Assert
        $response->assertStatus(201);

        // Assert that an email was sent
        Mail::assertQueued(StockAlertMail::class, function ($mail) use ($ingredient) {
            return $mail->hasTo(config('email.alert_email')) &&
                $mail->ingredient->is($ingredient);
        });

        // Assert ingredient stock
        $this->assertDatabaseHas('ingredients', [
            'id' => $ingredient->id,
            'stock' => 100 - 150, // 100 - 150 = -50
        ]);
    }
}
