<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order #{{ $order->id }}</title>
</head>
<body>
    <h1>Order #{{ $order->id }}</h1>
    <p>{{ $order->currency }} {{ $order->amount }}, {{ $order->status }}</p>

    @if ($order->status === 'pending')
        <form method="POST" action="{{ route('orders.pay', $order) }}">
            @csrf
            <button>Simulate a successful payment</button>
        </form>
    @elseif ($order->ga_sent_at)
        <p>Purchase sent to GA4 at {{ $order->ga_sent_at }}.</p>
    @elseif (! $order->analytics_consent)
        <p>Paid. Nothing was sent to GA4: the visitor declined analytics.</p>
    @else
        <p>Paid. The purchase event is waiting in the queue.</p>
    @endif
</body>
</html>
