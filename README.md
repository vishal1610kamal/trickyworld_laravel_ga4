# Laravel GA4 server-side tracking

A Laravel 13 shop checkout that reports purchases to Google Analytics 4 from the server, so a sale still counts when
the customer never returns to the thank-you page, and that keeps Safari visitors recognisable after seven days.

This is the code from the TrickyWorld tutorial
[Laravel GA4 server-side tracking for lost sales](https://www.trickyworld.in/programming/laravel/laravel-ga4-server-side-tracking-lost-sales/),
which explains each piece.

## What it does

1. **Captures the visitor's GA4 IDs at checkout.** After the visitor accepts analytics, the page reads `client_id`
   and `session_id` with gtag's documented `get` command and posts them with the order.
2. **Sends `purchase` from the server when payment is confirmed**, through the GA4 Measurement Protocol, from a
   queued job. The job runs once per order, and only for visitors who accepted analytics.
3. **Re-issues gtag's cookies from the server.** Safari caps cookies that JavaScript writes to seven days. The same
   cookies sent back in a `Set-Cookie` header from your own domain aren't capped, so a returning Safari visitor
   keeps one client ID. This also only happens after consent.

## Files

| File | Role |
|---|---|
| `config/ga4.php` | Measurement ID, API secret, EU endpoint, debug mode, consent cookie |
| `app/Analytics/Ga4.php` | Measurement Protocol client |
| `app/Http/Middleware/RefreshGaCookies.php` | Re-issues `_ga` and `_ga_<ID>` as server-set cookies |
| `app/Providers/AppServiceProvider.php` | Stops Laravel encrypting gtag's cookies and the consent cookie |
| `app/Jobs/SendPurchaseToGa4.php` | The queued purchase event |
| `app/Http/Controllers/CheckoutController.php` | Stores the order with the GA4 IDs |
| `app/Http/Controllers/OrderController.php` | Payment confirmation, standing in for your gateway's webhook |
| `resources/views/checkout.blade.php` | Consent banner, gtag, and the hidden ID fields |

## Run it

Requires PHP 8.3+ and Composer.

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
```

In `.env`, set `GA4_MEASUREMENT_ID` and `GA4_API_SECRET`. The secret is created under Admin > Data collection and
modification > Data streams > your web stream > Measurement Protocol API secrets. While testing, keep
`GA4_DEBUG=true`: events then go to Google's validation server only, never into your reports, and its answer is
written to `storage/logs/laravel.log`.

```bash
php artisan serve
php artisan queue:work     # in a second terminal; or set QUEUE_CONNECTION=sync while trying it out
```

Open http://localhost:8000, accept analytics, buy, and simulate the payment.

## Tests

```bash
php artisan test                          # payload, consent, retries and cookie refresh, with HTTP faked
GA4_LIVE_TEST=1 php artisan test          # also sends one purchase to Google's validation server
```

## Before you use this in production

- **Payment confirmation:** replace `OrderController::pay` with your payment gateway's webhook. Exclude that route
  from CSRF, verify the gateway's signature, and dispatch `SendPurchaseToGa4` there.
- **Don't double-count:** send `purchase` from the server only, not from the thank-you page as well.
- **Cookie domain:** set `GA4_COOKIE_DOMAIN` to the domain gtag uses for its cookies, such as `.example.com`.
- **Timing:** Google joins Measurement Protocol events to the browser's session only if they arrive within 48 hours,
  so keep the queue running.
- **Consent:** the consent banner here is a minimal example. Use one that meets the law where your customers are.

Tested with Laravel 13.42.0 on PHP 8.3, real gtag.js in headless Chrome, and Google's Measurement Protocol
validation server.
