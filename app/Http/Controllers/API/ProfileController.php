<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * GET /api/profile
     */
    public function show(): JsonResponse
    {
        $user = request()->user()->load('assets');

        return response()->json($user);
    }

    /**
     * POST /api/profile/add-balance
     * Add balance to user's account (for testing purposes)
     */
    public function addBalance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:1000000'],
        ]);

        $user = $request->user();

        DB::transaction(function () use ($user, $validated) {
            $user->lockForUpdate();
            $user->balance += $validated['amount'];
            $user->save();
        });

        // Reload user with assets
        $user->refresh()->load('assets');

        return response()->json([
            'message' => 'Balance added successfully',
            'user' => $user,
        ]);
    }

    /**
     * POST /api/profile/add-asset
     * Add asset to user's account (for testing purposes)
     */
    public function addAsset(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'symbol' => ['required', 'string', 'in:BTC,ETH'],
            'amount' => ['required', 'numeric', 'min:0.00000001', 'max:1000'],
        ]);

        $user = $request->user();

        DB::transaction(function () use ($user, $validated) {
            $asset = \App\Models\Asset::where('user_id', $user->id)
                ->where('symbol', $validated['symbol'])
                ->lockForUpdate()
                ->first();

            if (!$asset) {
                $asset = \App\Models\Asset::create([
                    'user_id' => $user->id,
                    'symbol' => $validated['symbol'],
                    'amount' => 0,
                    'locked_amount' => 0,
                ]);
            }

            $asset->amount += $validated['amount'];
            $asset->save();
        });

        // Reload user with assets
        $user->refresh()->load('assets');

        return response()->json([
            'message' => 'Asset added successfully',
            'user' => $user,
        ]);
    }
}
