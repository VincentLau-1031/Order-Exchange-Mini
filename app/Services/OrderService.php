<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class OrderService
{
    public function createOrder(User $user, array $data): Order
    {
        $symbol = strtoupper($data['symbol'] ?? '');
        $side = strtolower($data['side'] ?? '');
        $price = (float) ($data['price'] ?? 0);
        $amount = (float) ($data['amount'] ?? 0);

        // Basic validation safeguards (controllers should also validate)
        if (! in_array($symbol, ['BTC', 'ETH'], true)) {
            throw new InvalidArgumentException('Unsupported symbol.');
        }

        if (! in_array($side, [Order::SIDE_BUY, Order::SIDE_SELL], true)) {
            throw new InvalidArgumentException('Invalid side.');
        }

        if ($price <= 0 || $amount <= 0) {
            throw new InvalidArgumentException('Price and amount must be greater than zero.');
        }

        return DB::transaction(function () use ($user, $symbol, $side, $price, $amount) {
            if ($side === Order::SIDE_BUY) {
                return $this->handleBuy($user, $symbol, $price, $amount);
            }

            return $this->handleSell($user, $symbol, $price, $amount);
        });
    }

    /**
     * Handle buy order: lock USD balance.
     */
    protected function handleBuy(User $user, string $symbol, float $price, float $amount): Order
    {
        $cost = $price * $amount;

        // Lock the user row
        $lockedUser = User::whereKey($user->id)->lockForUpdate()->first();

        if ($lockedUser->balance < $cost) {
            throw new RuntimeException('Insufficient USD balance.');
        }

        // Deduct funds
        $lockedUser->balance -= $cost;
        $lockedUser->save();

        // Create order as open
        return Order::create([
            'user_id' => $lockedUser->id,
            'symbol' => $symbol,
            'side' => Order::SIDE_BUY,
            'price' => $price,
            'amount' => $amount,
            'status' => Order::STATUS_OPEN,
        ]);
    }

    /**
     * Handle sell order: move assets to locked_amount.
     */
    protected function handleSell(User $user, string $symbol, float $price, float $amount): Order
    {
        // Lock or create the asset row
        $asset = Asset::where('user_id', $user->id)
            ->where('symbol', $symbol)
            ->lockForUpdate()
            ->first();

        if (! $asset) {
            $asset = Asset::create([
                'user_id' => $user->id,
                'symbol' => $symbol,
                'amount' => 0,
                'locked_amount' => 0,
            ]);
        }

        if ($asset->amount < $amount) {
            throw new RuntimeException('Insufficient asset balance.');
        }

        // Move amount to locked
        $asset->amount -= $amount;
        $asset->locked_amount += $amount;
        $asset->save();

        // Create order as open
        return Order::create([
            'user_id' => $user->id,
            'symbol' => $symbol,
            'side' => Order::SIDE_SELL,
            'price' => $price,
            'amount' => $amount,
            'status' => Order::STATUS_OPEN,
        ]);
    }
}


