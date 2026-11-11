<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout</title>
</head>
<body>
    <div id="consent" hidden>
        <p>We use Google Analytics to see which pages lead to sales. Is that OK?</p>
        <button type="button" data-consent="granted">Accept analytics</button>
        <button type="button" data-consent="denied">Decline</button>
    </div>

    <h1>{{ $product['name'] }}</h1>
    <p>₹{{ number_format($product['price'], 2) }}</p>

    <form method="POST" action="{{ route('checkout.store') }}">
        @csrf
        <input type="hidden" name="ga_client_id">
        <input type="hidden" name="ga_session_id">
        <button>Buy now</button>
    </form>

    <script>
        const MEASUREMENT_ID = @json($measurementId);
        const CONSENT_COOKIE = @json($consentCookie);
        const consent = document.cookie.match(new RegExp(`(?:^|; )${CONSENT_COOKIE}=(granted|denied)`))?.[1];

        function startAnalytics() {
            if (!MEASUREMENT_ID) return;
            window.dataLayer = window.dataLayer || [];
            window.gtag = function () { dataLayer.push(arguments); };
            gtag('js', new Date());
            gtag('config', MEASUREMENT_ID);

            const script = document.createElement('script');
            script.async = true;
            script.src = `https://www.googletagmanager.com/gtag/js?id=${MEASUREMENT_ID}`;
            document.head.append(script);

            // Copy gtag's IDs into the form, so the server can report the sale as this visitor and session.
            for (const field of ['client_id', 'session_id']) {
                gtag('get', MEASUREMENT_ID, field, (value) => {
                    document.querySelector(`[name="ga_${field}"]`).value = value ?? '';
                });
            }
        }

        if (consent === 'granted') startAnalytics();

        if (!consent) {
            const banner = document.getElementById('consent');
            banner.hidden = false;
            banner.addEventListener('click', (event) => {
                const choice = event.target.dataset.consent;
                if (!choice) return;
                document.cookie = `${CONSENT_COOKIE}=${choice}; path=/; max-age=${60 * 60 * 24 * 180}; samesite=lax`;
                banner.hidden = true;
                if (choice === 'granted') startAnalytics();
            });
        }
    </script>
</body>
</html>
