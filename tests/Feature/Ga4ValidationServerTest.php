<?php

namespace Tests\Feature;

use App\Analytics\Ga4;
use Tests\TestCase;

/**
 * Sends a real purchase to Google's validation server (/debug/mp/collect), which checks the payload and keeps it
 * out of reports. It needs network access, so it only runs with GA4_LIVE_TEST=1.
 */
class Ga4ValidationServerTest extends TestCase
{
    public function test_google_accepts_the_purchase_payload(): void
    {
        if (! getenv('GA4_LIVE_TEST')) {
            $this->markTestSkipped('Set GA4_LIVE_TEST=1 to call Google\'s validation server.');
        }

        config(['ga4.debug' => true]);

        $response = app(Ga4::class)->send('1234567890.1731300000', [[
            'name' => 'purchase',
            'params' => [
                'transaction_id' => 'T-1001',
                'value' => 499.0,
                'currency' => 'INR',
                'items' => [['item_id' => 'SKU-101', 'item_name' => 'Laravel GA4 course', 'price' => 499.0, 'quantity' => 1]],
                'session_id' => '1731300000',
                'engagement_time_msec' => 100,
            ],
        ]]);

        $this->assertSame([], $response->json('validationMessages'), json_encode($response->json()));
    }
}
