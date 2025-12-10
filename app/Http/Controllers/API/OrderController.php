<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {
    }

    /**
     * GET /api/orders
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Order::query()
            ->where('user_id', $user->id)
            ->when($request->filled('symbol'), fn ($q) => $q->where('symbol', strtoupper($request->string('symbol'))))
            ->when($request->filled('status'), fn ($q) => $q->where('status', (int) $request->status))
            ->when($request->filled('side'), fn ($q) => $q->where('side', strtolower($request->side)))
            ->orderByDesc('created_at');

        return response()->json($query->get());
    }

    /**
     * POST /api/orders
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        try {
            $order = $this->orderService->createOrder($user, $data);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to create order'], 500);
        }

        return response()->json($order, 201);
    }

    /**
     * POST /api/orders/{id}/cancel
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $order = Order::where('user_id', $user->id)
            ->where('id', $id)
            ->lockForUpdate()
            ->first();

        if (! $order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($order->status !== Order::STATUS_OPEN) {
            return response()->json(['message' => 'Order cannot be cancelled'], 422);
        }

        DB::transaction(function () use ($order) {
            if ($order->isBuy()) {
                // Refund locked USD
                $user = $order->user()->lockForUpdate()->first();
                $refund = $order->price * $order->amount;
                $user->balance += $refund;
                $user->save();
            } else {
                // Release locked asset
                $asset = $order->user->assets()
                    ->where('symbol', $order->symbol)
                    ->lockForUpdate()
                    ->first();

                if ($asset) {
                    $asset->locked_amount -= $order->amount;
                    $asset->amount += $order->amount;
                    $asset->save();
                }
            }

            // Mark order as cancelled
            $order->status = Order::STATUS_CANCELLED;
            $order->save();
        });

        return response()->json($order);
    }
}
