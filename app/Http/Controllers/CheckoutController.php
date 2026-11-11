<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    // One product keeps the example short; your cart takes its place.
    public const PRODUCT = ['id' => 'SKU-101', 'name' => 'Laravel GA4 course', 'price' => 499.00, 'currency' => 'INR'];

    public function show(): View
    {
        return view('checkout', [
            'product' => self::PRODUCT,
            'measurementId' => config('ga4.measurement_id'),
            'consentCookie' => config('ga4.consent_cookie'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // gtag's client and session IDs, copied into hidden fields by the page. Empty when the tag never ran.
        $ids = $request->validate([
            'ga_client_id' => ['nullable', 'regex:/^\d{1,20}\.\d{1,20}$/'],
            'ga_session_id' => ['nullable', 'regex:/^\d{1,20}$/'],
        ]);

        $order = Order::create([
            'product' => self::PRODUCT['id'],
            'amount' => self::PRODUCT['price'],
            'currency' => self::PRODUCT['currency'],
            'analytics_consent' => $request->cookie(config('ga4.consent_cookie')) === 'granted',
            ...$ids,
        ]);

        // A real shop redirects to its payment gateway here.
        return redirect()->route('orders.show', $order);
    }
}
