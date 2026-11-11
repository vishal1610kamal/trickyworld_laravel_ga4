<?php

namespace App\Http\Controllers;

use App\Jobs\SendPurchaseToGa4;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function show(Order $order): View
    {
        return view('order', ['order' => $order]);
    }

    /**
     * Stands in for your payment gateway's webhook, which is where a real shop learns the payment succeeded.
     * A real webhook route is excluded from CSRF and must verify the gateway's signature before trusting it.
     */
    public function pay(Order $order): RedirectResponse
    {
        if ($order->status !== 'paid') {
            $order->update(['status' => 'paid', 'paid_at' => now()]);
            SendPurchaseToGa4::dispatch($order);
        }

        return redirect()->route('orders.show', $order);
    }
}
