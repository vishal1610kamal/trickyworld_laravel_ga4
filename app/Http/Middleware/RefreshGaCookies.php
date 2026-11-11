<?php

namespace App\Http\Middleware;

use App\Analytics\Ga4;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Safari caps cookies written by JavaScript (document.cookie) to seven days. gtag writes _ga that way, so a
 * Safari visitor who returns after a week looks like a new user. Sending the same cookie back in a Set-Cookie
 * header from your own server is not capped, so the visitor keeps one client ID.
 */
class RefreshGaCookies
{
    // gtag's cookie values: "GA1.1.123.456" for _ga, "GS1.1...." or "GS2.1.s...$o1$g0..." for _ga_<ID>.
    private const VALUE = '/^G[AS]\d\.\d\.[A-Za-z0-9.$_-]{1,200}$/';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->cookie(config('ga4.consent_cookie')) !== 'granted') {
            return $response;
        }

        foreach (Ga4::cookieNames() as $name) {
            $value = $request->cookie($name);

            if (is_string($value) && preg_match(self::VALUE, $value)) {
                $response->headers->setCookie(new Cookie(
                    $name, $value, now()->addDays(400), '/', config('ga4.cookie_domain'),
                    $request->isSecure(), httpOnly: false, raw: true, sameSite: Cookie::SAMESITE_LAX,
                ));
            }
        }

        return $response;
    }
}
