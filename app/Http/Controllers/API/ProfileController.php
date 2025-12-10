<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
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
}
