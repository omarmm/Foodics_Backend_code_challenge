<?php
namespace App\Http\Controllers;

use App\Http\Requests\OrderRequest;
use App\Services\OrderService;
use App\Services\InsufficientStockException;
use App\Services\OrderProcessingException;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function store(OrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->processOrder($request->validated());

            return response()->json([
                'message' => 'Order processed successfully',
                'order' => $order,
            ], 201);
        } catch (InsufficientStockException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400); // Bad request due to validation error (insufficient stock)
        } catch (OrderProcessingException $e) {
            Log::error('Order processing failed: ' . $e->getMessage());

            return response()->json([
                'message' => 'Order processing failed: ' . $e->getMessage(),
            ], 500); // Internal server error for critical failures
        } catch (Exception $e) {
            Log::error('Unexpected error: ' . $e->getMessage());

            return response()->json([
                'message' => 'An unexpected error occurred.',
            ], 500); // Generic error catch for unexpected failures
        }
    }
}

