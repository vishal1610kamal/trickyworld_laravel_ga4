<?php

namespace Tests\Feature;

use Tests\TestCase;

class RefreshGaCookiesTest extends TestCase
{
    // A session cookie in gtag's newer format, with the "$" separators that must survive unencoded.
    private const SESSION = 'GS2.1.s1731300000$o1$g1$t1731300100$j60$l0$h0';

    private function setCookieHeaders(array $cookies): array
    {
        return $this->withUnencryptedCookies($cookies)->get('/')->assertOk()->headers->all('set-cookie');
    }

    public function test_consented_visitors_get_their_ga_cookies_back_from_the_server(): void
    {
        $headers = $this->setCookieHeaders([
            'analytics_consent' => 'granted',
            '_ga' => 'GA1.1.1234567890.1731300000',
            '_ga_TEST1234' => self::SESSION,
        ]);

        $ga = collect($headers)->first(fn ($h) => str_starts_with($h, '_ga=GA1.1.1234567890.1731300000;'));
        $session = collect($headers)->first(fn ($h) => str_starts_with($h, '_ga_TEST1234='.self::SESSION.';'));

        $this->assertNotNull($ga, 'the _ga cookie is re-issued, unencrypted');
        $this->assertNotNull($session, 'the session cookie keeps its $ separators');
        $this->assertStringContainsStringIgnoringCase('max-age=34560000', $ga);   // 400 days
        $this->assertStringNotContainsStringIgnoringCase('httponly', $ga);   // gtag must still read it
    }

    public function test_nothing_is_refreshed_without_consent(): void
    {
        $headers = $this->setCookieHeaders(['_ga' => 'GA1.1.1234567890.1731300000']);

        $this->assertEmpty(array_filter($headers, fn ($h) => str_starts_with($h, '_ga')));
    }

    public function test_a_tampered_cookie_is_not_echoed_back(): void
    {
        $headers = $this->setCookieHeaders(['analytics_consent' => 'granted', '_ga' => 'GA1.1.1<script>']);

        $this->assertEmpty(array_filter($headers, fn ($h) => str_starts_with($h, '_ga=')));
    }
}
