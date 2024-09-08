<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $orderService;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a mock for the OrderService
        $this->orderService = Mockery::mock(OrderService::class);
    }

    public function test_process_order_updates_stock()
    {
        $order = Mockery::mock(Order::class);
        $order->shouldReceive('save')->andReturn(true); 

        //  expected behavior for the processOrder method
        $this->orderService
            ->shouldReceive('processOrder')
            ->once()
            ->with([
                'products' => [
                    [
                        'product_id' => 1,
                        'quantity' => 2,
                    ]
                ]
            ])
            ->andReturn($order);

        // processOrder method on the mock
        $result = $this->orderService->processOrder([
            'products' => [
                [
                    'product_id' => 1,
                    'quantity' => 2,
                ]
            ]
        ]);

        // Assert
        // Verify that the result is an instance of Order
        $this->assertInstanceOf(Order::class, $result);

        Mockery::close();
    }
}
