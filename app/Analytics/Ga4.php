<?php

namespace App\Analytics;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Ga4
{
    /**
     * Sends up to 25 events for one visitor through the GA4 Measurement Protocol.
     *
     * @param  array<int, array{name: string, params: array<string, mixed>}>  $events
     */
    public function send(string $clientId, array $events, bool $adConsent = false, ?string $userId = null): Response
    {
        $path = config('ga4.debug') ? '/debug/mp/collect' : '/mp/collect';

        $payload = array_filter([
            'client_id' => $clientId,
            'user_id' => $userId,
            'consent' => [
                'ad_user_data' => $adConsent ? 'GRANTED' : 'DENIED',
                'ad_personalization' => $adConsent ? 'GRANTED' : 'DENIED',
            ],
            'events' => $events,
            // The validation server is strictest with ENFORCE_RECOMMENDATIONS; it only applies in debug mode.
            'validation_behavior' => config('ga4.debug') ? 'ENFORCE_RECOMMENDATIONS' : null,
        ], fn ($value) => $value !== null);

        $response = Http::timeout(5)
            ->withQueryParameters([
                'measurement_id' => config('ga4.measurement_id'),
                'api_secret' => config('ga4.api_secret'),
            ])
            ->post(rtrim(config('ga4.endpoint'), '/').$path, $payload)
            ->throw();

        // The live endpoint answers 2xx even for a malformed event; only the validation server says what is wrong.
        if (config('ga4.debug')) {
            Log::info('GA4 validation', ['payload' => $payload, 'messages' => $response->json('validationMessages')]);
        }

        return $response;
    }

    /** The cookies gtag sets for this stream: _ga, and _ga_<ID> for G-<ID>. */
    public static function cookieNames(): array
    {
        $streamId = Str::after((string) config('ga4.measurement_id'), 'G-');

        return array_values(array_filter(['_ga', $streamId !== '' ? "_ga_{$streamId}" : null]));
    }

    /** A client ID for a visitor who consented but whose browser never ran the tag (an ad blocker, for example). */
    public static function newClientId(): string
    {
        return random_int(100_000_000, 2_147_483_647).'.'.time();
    }
}
