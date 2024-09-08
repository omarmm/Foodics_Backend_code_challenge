<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderRequest;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function store(OrderRequest $request): JsonResponse
    {
        $order = $this->orderService->processOrder($request->validated());

        return response()->json([
            'message' => 'Order processed successfully',
            'order' => $order,
        ], 201);
    }
}
