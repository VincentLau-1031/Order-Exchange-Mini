<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Http\Request;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\OrderController;


Route::post('/broadcasting/auth', function (Request $request) {
    $bearerToken = $request->bearerToken();
    
    if (!$bearerToken) {
        $authHeader = $request->header('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            $bearerToken = substr($authHeader, 7);
        }
    }
    
    if (!$bearerToken) {
        \Log::warning('Broadcasting auth failed: No token provided', [
            'headers' => $request->headers->all(),
        ]);
        abort(403, 'Unauthorized: No authentication token provided');
    }
    
    // Find the token (Sanctum handles the hash lookup)
    $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($bearerToken);
    
    if (!$accessToken || !$accessToken->tokenable) {
        \Log::warning('Broadcasting auth failed: Invalid token', [
            'token_prefix' => substr($bearerToken, 0, 10) . '...',
        ]);
        abort(403, 'Unauthorized: Invalid authentication token');
    }
    
    $user = $accessToken->tokenable;
    
    $request->setUserResolver(function () use ($user) {
        return $user;
    });
    
    auth()->setUser($user);
    
    return Broadcast::auth($request);
});

// Public auth routes
Route::post('/login', [AuthController::class, 'login'])->name('api.login');
Route::post('/register', [AuthController::class, 'register'])->name('api.register');

// Protected routes (Sanctum)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');

    Route::get('/profile', [ProfileController::class, 'show'])->name('api.profile');

    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/orders', [OrderController::class, 'index'])->name('api.orders.index');
        Route::post('/orders', [OrderController::class, 'store'])->name('api.orders.store');
        Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel'])->name('api.orders.cancel');
    });
});

