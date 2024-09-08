<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Events\IngredientStockLow;

class OrderService
{
    public function processOrder(array $orderData): Order
    {
        $order = Order::create();

        foreach ($orderData['products'] as $productData) {
            $product = Product::findOrFail($productData['product_id']);
            $quantity = $productData['quantity'];

            // Add products to the order
            $order->products()->attach($product->id, ['quantity' => $quantity]);

            // Update stock levels
            foreach ($product->ingredients as $ingredient) {
                $ingredient->stock -= $ingredient->pivot->amount * $quantity;
                $ingredient->save();

                // Trigger event if stock falls below 50% and alert hasn't been sent
                if (
                    $ingredient->stock < $ingredient->initial_stock * 0.5
                    && !$ingredient->alert_sent
                ) {
                    event(new IngredientStockLow($ingredient));
                    $ingredient->alert_sent = true;
                    $ingredient->save();
                }
            }
        }

        return $order;
    }
}
