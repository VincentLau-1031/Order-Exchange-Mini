<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * GET /api/orders
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet'], 501);
    }

    /**
     * POST /api/orders
     */
    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet'], 501);
    }

    /**
     * POST /api/orders/{id}/cancel
     */
    public function cancel(int $id): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet'], 501);
    }
}
