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
    /**
     * Process an order with the given order data.
     *
     * @param array $orderData
     * @return Order
     * @throws InsufficientStockException
     * @throws OrderProcessingException
     */
    public function processOrder(array $orderData): Order
    {
        return DB::transaction(function () use ($orderData) {
            $order = Order::create();

            // Batch process the products to prevent memory overload and long processing times
            foreach (array_chunk($orderData['products'], 100) as $productChunk) {
                $this->processProductChunk($order, $productChunk);
            }

            return $order;
        });
    }

    /**
     * Process a chunk of products.
     *
     * @param Order $order
     * @param array $productChunk
     * @throws InsufficientStockException
     * @throws OrderProcessingException
     */
    protected function processProductChunk(Order $order, array $productChunk): void
    {
        foreach ($productChunk as $productData) {
            $product = Product::with('ingredients')->findOrFail($productData['product_id']);
            $quantity = $productData['quantity'];

            // Check for sufficient stock
            $this->checkStock($product, $quantity);

            // Simulate a critical error for testing rollback
            if ($product->name === 'Simulate Error') {
                throw new OrderProcessingException("Simulated error to test rollback");
            }

            // Add products to the order
            $order->products()->attach($product->id, ['quantity' => $quantity]);

            // Update stock levels
            $this->updateStock($product, $quantity);
        }
    }

    /**
     * Check if there is sufficient stock for the product.
     *
     * @param Product $product
     * @param int $quantity
     * @throws InsufficientStockException
     */
    protected function checkStock(Product $product, int $quantity): void
    {
        foreach ($product->ingredients as $ingredient) {
            $requiredStock = $ingredient->pivot->amount * $quantity;

            if ($ingredient->stock < $requiredStock) {
                throw new InsufficientStockException("Insufficient stock for ingredient: {$ingredient->name}");
            }
        }
    }

    /**
     * Update the stock levels of the product's ingredients.
     *
     * @param Product $product
     * @param int $quantity
     */
    protected function updateStock(Product $product, int $quantity): void
    {
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
}
