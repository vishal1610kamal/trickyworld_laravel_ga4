<?php

namespace App\Jobs;

use App\Analytics\Ga4;
use App\Http\Controllers\CheckoutController;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendPurchaseToGa4 implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 60];

    public function __construct(public Order $order) {}

    public function handle(Ga4 $ga4): void
    {
        $order = $this->order;

        // No analytics consent: send nothing. Already sent: a retry must not count the sale twice.
        if (! $order->analytics_consent || $order->ga_sent_at) {
            return;
        }

        $params = [
            'transaction_id' => (string) $order->id,
            'value' => (float) $order->amount,
            'currency' => $order->currency,
            'items' => [[
                'item_id' => $order->product,
                'item_name' => CheckoutController::PRODUCT['name'],
                'price' => (float) $order->amount,
                'quantity' => 1,
            ]],
            // Google recommends both, so the event counts in engagement and Realtime reports.
            'engagement_time_msec' => 100,
        ];

        if ($order->ga_session_id) {
            $params['session_id'] = $order->ga_session_id;
        }

        // A visitor who consented but whose browser never ran the tag still counts as a sale, without a source.
        $ga4->send($order->ga_client_id ?? Ga4::newClientId(), [['name' => 'purchase', 'params' => $params]]);

        $order->update(['ga_sent_at' => now()]);
    }
}
