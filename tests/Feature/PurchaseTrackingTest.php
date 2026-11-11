<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PurchaseTrackingTest extends TestCase
{
    use RefreshDatabase;

    private function checkout(array $ids, string $consent = 'granted'): Order
    {
        $this->withUnencryptedCookies(['analytics_consent' => $consent])
            ->post('/checkout', $ids)
            ->assertRedirect();

        return Order::latest('id')->firstOrFail();
    }

    public function test_paying_sends_one_purchase_as_the_same_visitor_and_session(): void
    {
        Http::fake(['*' => Http::response('', 204)]);
        $order = $this->checkout(['ga_client_id' => '1234567890.1731300000', 'ga_session_id' => '1731300000']);

        $this->post("/orders/{$order->id}/pay")->assertRedirect();
        $this->post("/orders/{$order->id}/pay")->assertRedirect();   // a second webhook call

        Http::assertSentCount(1);
        Http::assertSent(function (Request $request) use ($order) {
            $event = $request['events'][0];

            return str_starts_with($request->url(), 'https://www.google-analytics.com/mp/collect?')
                && str_contains($request->url(), 'measurement_id=G-TEST1234')
                && str_contains($request->url(), 'api_secret=test-secret')
                && $request['client_id'] === '1234567890.1731300000'
                && $event['name'] === 'purchase'
                && $event['params']['transaction_id'] === (string) $order->id
                && $event['params']['value'] === 499.0
                && $event['params']['currency'] === 'INR'
                && $event['params']['session_id'] === '1731300000'
                && $request['consent']['ad_user_data'] === 'DENIED';
        });
        $this->assertNotNull($order->fresh()->ga_sent_at);
    }

    public function test_nothing_is_sent_without_analytics_consent(): void
    {
        Http::fake();
        $order = $this->checkout(['ga_client_id' => '1234567890.1731300000'], consent: 'denied');

        $this->post("/orders/{$order->id}/pay");

        Http::assertNothingSent();
        $this->assertSame('paid', $order->fresh()->status);
    }

    public function test_a_blocked_tag_still_counts_the_sale_under_a_new_client_id(): void
    {
        Http::fake(['*' => Http::response('', 204)]);
        $order = $this->checkout([]);   // consent given, but no gtag IDs reached the form

        $this->post("/orders/{$order->id}/pay");

        Http::assertSent(fn (Request $request) => preg_match('/^\d+\.\d+$/', $request['client_id'])
            && ! isset($request['events'][0]['params']['session_id']));
    }

    public function test_malformed_ids_are_rejected(): void
    {
        $this->withUnencryptedCookies(['analytics_consent' => 'granted'])
            ->post('/checkout', ['ga_client_id' => '<script>', 'ga_session_id' => 'abc'])
            ->assertSessionHasErrors(['ga_client_id', 'ga_session_id']);

        $this->assertSame(0, Order::count());
    }
}
