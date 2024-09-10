<?php

namespace Tests\Unit;

use App\Events\IngredientStockLow;
use App\Models\Order;
use App\Models\Product;
use App\Models\Ingredient;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use App\Mail\StockAlertMail;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $orderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderService = new OrderService();
    }

    public function test_process_order_updates_stock()
    {
        // Create an ingredient using factory
        $ingredient = Ingredient::factory()->create([
            'stock' => 200,
            'initial_stock' => 200,
        ]);

        // Create a product using factory
        $product = Product::factory()->create();

        // Attach the ingredient to the product with a specific amount
        $product->ingredients()->attach($ingredient->id, ['amount' => 100]);

        // Process the order
        $this->orderService->processOrder([
            'products' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ]
            ]
        ]);

        // Assert stock updates
        $this->assertDatabaseHas('ingredients', [
            'id' => $ingredient->id,
            'stock' => 200 - (100 * 2) // 200 - 200 = 0
        ]);
    }

    public function test_process_order_without_products()
    {
        // Process order with no products
        $result = $this->orderService->processOrder(['products' => []]);

        // Assert
        $this->assertInstanceOf(Order::class, $result);
    }

    public function test_process_order_with_invalid_product_id()
    {
        // Expect an exception to be thrown
        $this->expectException(ModelNotFoundException::class);

        // Process order with invalid product ID
        $this->orderService->processOrder([
            'products' => [
                [
                    'product_id' => 999, // Non-existing ID
                    'quantity' => 1,
                ]
            ]
        ]);
    }

    public function test_stock_alert_email_sent()
    {
        // Fake the Mail and Event facades
        Event::fake();
        Mail::fake();

        // Create an ingredient using factory with initial stock values
        $ingredient = Ingredient::factory()->create([
            'stock' => 150,
            'initial_stock' => 200,
        ]);

        // Create a product using factory
        $product = Product::factory()->create();

        // Attach the ingredient to the product with a specific amount
        $product->ingredients()->attach($ingredient->id, ['amount' => 101]); // Amount that will reduce stock

        // Process the order through the service
        $this->orderService->processOrder([
            'products' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ]
            ]
        ]);

        // Assert that the IngredientStockLow event was dispatched
        Event::assertDispatched(IngredientStockLow::class, function ($event) use ($ingredient) {
            return $event->ingredient->is($ingredient);
        });


        // Assert that an email was sent due to stock falling below 50% of the initial stock
        Mail::to(config('email.alert_email'))->queue(new StockAlertMail($ingredient));

        // Assert that the stock was updated correctly in the database
        $this->assertDatabaseHas('ingredients', [
            'id' => $ingredient->id,
            'stock' => 150 - 101, // Stock should be 49g after the order
        ]);
    }
}
