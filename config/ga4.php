<?php

return [

    // The web stream's measurement ID (G-XXXXXXXXXX) and a Measurement Protocol API secret created on that
    // stream: Admin > Data collection and modification > Data streams > your stream > Measurement Protocol API
    // secrets. Keep the secret on the server; never put it in JavaScript.
    'measurement_id' => env('GA4_MEASUREMENT_ID'),
    'api_secret' => env('GA4_API_SECRET'),

    // https://region1.google-analytics.com keeps collection in the EU.
    'endpoint' => env('GA4_ENDPOINT', 'https://www.google-analytics.com'),

    // true: send to /debug/mp/collect, which validates events and keeps them out of reports.
    'debug' => (bool) env('GA4_DEBUG', false),

    // The cookie your consent banner sets to "granted" when the visitor accepts analytics.
    'consent_cookie' => env('GA4_CONSENT_COOKIE', 'analytics_consent'),

    // The domain gtag writes its cookies on, such as ".example.com". Leave empty to use the current host.
    'cookie_domain' => env('GA4_COOKIE_DOMAIN'),

];
