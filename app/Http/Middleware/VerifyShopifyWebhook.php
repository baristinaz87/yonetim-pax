<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Shopify\App;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Webhook gövdesinin, ilgili uygulamanın Shopify client_secret'ı ile imzalandığını doğrular.
 * Shopify'ın kendi webhook imzasıyla aynı yöntem:
 *   X-Shopify-Hmac-Sha256 = base64(hmac_sha256(ham gövde, client_secret))
 *
 * services.shopify.webhook.verify_hmac=false ise (yalnızca yerel geliştirme) doğrulama atlanır.
 */
class VerifyShopifyWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('services.shopify.webhook.verify_hmac')) {
            return $next($request);
        }

        $app    = $request->route('app');
        $secret = $app instanceof App ? (string) $app->client_secret : '';
        $hmac   = (string) $request->header('X-Shopify-Hmac-Sha256');

        $calculated = base64_encode(hash_hmac('sha256', $request->getContent(), $secret, true));

        if ($secret === '' || $hmac === '' || ! hash_equals($calculated, $hmac)) {
            Log::warning('[webhook] imza doğrulanamadı: app='.($app instanceof App ? $app->handle : '?').', ip='.$request->ip());
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        return $next($request);
    }
}
