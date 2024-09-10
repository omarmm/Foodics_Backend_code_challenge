<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Events\IngredientStockLow;
use Illuminate\Support\Facades\DB;
use Exception;

class InsufficientStockException extends Exception {}
class OrderProcessingException extends Exception {}

class OrderService
{
    public function processOrder(array $orderData): Order
    {
        return DB::transaction(function () use ($orderData) {
            $order = Order::create();

            foreach ($orderData['products'] as $productData) {
                $product = Product::findOrFail($productData['product_id']);
                $quantity = $productData['quantity'];

                // Check for sufficient stock
                foreach ($product->ingredients as $ingredient) {
                    $requiredStock = $ingredient->pivot->amount * $quantity;

                    if ($ingredient->stock < $requiredStock) {
                        throw new InsufficientStockException("Insufficient stock for ingredient: {$ingredient->name}");
                    }
                }

                // Simulate a critical error for testing rollback
                if ($product->name === 'Simulate Error') {
                    throw new OrderProcessingException("Simulated error to test rollback");
                }

                // Add products to the order
                $order->products()->attach($product->id, ['quantity' => $quantity]);

                // Update stock levels
                foreach ($product->ingredients as $ingredient) {
                    $ingredient->stock -= $ingredient->pivot->amount * $quantity;
                    $ingredient->save();

                    // Trigger event if stock falls below 50% and alert hasn't been sent
                    if ($ingredient->stock < $ingredient->initial_stock * 0.5 && !$ingredient->alert_sent) {
                        event(new IngredientStockLow($ingredient));
                        $ingredient->alert_sent = true;
                        $ingredient->save();
                    }
                }
            }

            return $order;
        });
    }
}
