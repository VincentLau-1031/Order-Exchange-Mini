<?php

namespace App\Services;

use App\Events\OrderMatched;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Trade;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MatchingService
{
    /**
     * Attempt to match a newly created order.
     */
    public function matchOrder(Order $order): ?Trade
    {
        if (! $order->isOpen()) {
            return null;
        }

        return DB::transaction(function () use ($order) {
            // Reload the order with lock
            $order = Order::whereKey($order->id)->lockForUpdate()->first();
            if (! $order || ! $order->isOpen()) {
                return null;
            }

            $counter = $this->findCounterOrder($order);
            if (! $counter) {
                return null;
            }

            // Lock counter order
            $counter = Order::whereKey($counter->id)->lockForUpdate()->first();
            if (! $counter || ! $counter->isOpen()) {
                return null;
            }

            // Determine buyer/seller
            $buyOrder = $order->isBuy() ? $order : $counter;
            $sellOrder = $order->isSell() ? $order : $counter;

            // Commission 1.5% paid by buyer in USD
            $volume = $sellOrder->amount * $sellOrder->price;
            $commission = $volume * 0.015;

            // Settle funds/assets with locking
            $this->settleTrade($buyOrder, $sellOrder, $volume, $commission);

            // Create Trade record (optional but stored)
            $trade = Trade::create([
                'buy_order_id' => $buyOrder->id,
                'sell_order_id' => $sellOrder->id,
                'symbol' => $buyOrder->symbol,
                'price' => $sellOrder->price,
                'amount' => $sellOrder->amount,
                'buyer_id' => $buyOrder->user_id,
                'seller_id' => $sellOrder->user_id,
                'commission_usd' => $commission,
            ]);

            // Update order statuses
            $buyOrder->status = Order::STATUS_FILLED;
            $sellOrder->status = Order::STATUS_FILLED;
            $buyOrder->save();
            $sellOrder->save();

            // Broadcast event
            event(new OrderMatched($buyOrder, $sellOrder));

            return $trade;
        });
    }

    /**
     * Find the first matching counter order.
     */
    protected function findCounterOrder(Order $order): ?Order
    {
        if ($order->isBuy()) {
            return Order::query()
                ->where('symbol', $order->symbol)
                ->where('status', Order::STATUS_OPEN)
                ->where('side', Order::SIDE_SELL)
                ->where('price', '<=', $order->price)
                ->orderBy('price', 'asc')
                ->orderBy('created_at', 'asc')
                ->first();
        }

        // SELL order: find buy with price >= sell price
        return Order::query()
            ->where('symbol', $order->symbol)
            ->where('status', Order::STATUS_OPEN)
            ->where('side', Order::SIDE_BUY)
            ->where('price', '>=', $order->price)
            ->orderBy('price', 'desc')
            ->orderBy('created_at', 'asc')
            ->first();
    }

    /**
     * Settle the trade: buyer pays (including commission), seller receives funds,
     * buyer receives assets, seller releases locked assets.
     */
    protected function settleTrade(Order $buyOrder, Order $sellOrder, float $volume, float $commission): void
    {
        // Lock users
        $buyer = $buyOrder->user()->lockForUpdate()->first();
        $seller = $sellOrder->user()->lockForUpdate()->first();

        // Buyer balance check (in case commission pushes over)
        if ($buyer->balance < $commission) {
            throw new RuntimeException('Insufficient balance to cover commission.');
        }

        // Buyer receives asset
        $buyerAsset = Asset::where('user_id', $buyer->id)
            ->where('symbol', $buyOrder->symbol)
            ->lockForUpdate()
            ->first();

        if (!$buyerAsset) {
            $buyerAsset = Asset::create([
                'user_id' => $buyer->id,
                'symbol' => $buyOrder->symbol,
                'amount' => 0,
                'locked_amount' => 0,
            ]);
        }

        $buyerAsset->amount += $sellOrder->amount;
        $buyerAsset->save();

        // Seller releases locked asset
        $sellerAsset = Asset::where('user_id', $seller->id)
            ->where('symbol', $sellOrder->symbol)
            ->lockForUpdate()
            ->first();

        if ($sellerAsset) {
            $sellerAsset->locked_amount -= $sellOrder->amount;
            $sellerAsset->save();
        }

        // Funds movement
        $buyer->balance -= $commission; // commission charged to buyer
        $seller->balance += $volume;

        $buyer->save();
        $seller->save();
    }
}


