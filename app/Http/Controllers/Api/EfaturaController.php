<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Client\EFaturaClient;
use App\Http\Controllers\Controller;
use App\Services\BrevoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Efatura Controller
 */
class EfaturaController extends Controller
{
    /**
     * Static token for external integration.
     * This token is shared with the partner and must be sent in the header with every request.
     * Loaded from config('mantle.static_token') / MANTLE_STATIC_TOKEN env variable.
     */

    /**
     * POST /api/efatura/creditBalanceZero
     *
     * Logs incoming credit balance zero notifications.
     */
    public function creditBalanceZero(Request $request): JsonResponse
    {
        // Token verification
        $token = $request->bearerToken();

        if ($token !== config('mantle.static_token')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Invalid or missing token.',
            ], 401);
        }

        // Log incoming JSON data
        $data = $request->all();

        Log::channel('mantle_requests')->info('creditBalanceZero request received', [
            'ip'         => $request->ip(),
            'timestamp'  => now()->toDateTimeString(),
            'data'       => $data,
        ]);

        // Fetch merchant info by domain and send WP message
        $merchantDomain = $data['merchant_domain'] ?? null;
        $templateId     = $data['template_id'] ?? null;

        if ($merchantDomain && $templateId) {
            $eFaturaClient = new EFaturaClient();
            $merchant = $eFaturaClient->getMerchantByDomain($merchantDomain);

            // Extract phone numbers from setting (may not exist)
            $setting = $merchant['data']['setting'] ?? null;
            $phone   = $setting['phone'] ?? null;
            $mobile  = $setting['mobile'] ?? null;

            // Build unique phone list with 90 prefix
            $phones = array_filter([$phone, $mobile]); // remove nulls

            if (empty($phones)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No phone numbers found for this merchant.',
                ], 422);
            }

            $phones = array_unique($phones);
            $phones = array_values(array_map(function ($p) {
                $p = preg_replace('/\D/', '', $p);   // remove non-digit characters
                $p = preg_replace('/^(90|0)/', '', $p); // remove leading 90 or 0
                return '90' . $p;
            }, $phones));

            // Send WhatsApp template message via Brevo
            $brevoService = new BrevoService();
            $response = $brevoService->sendTemplateMessage($phones, $templateId);

            return response()->json([
                'success'    => true,
                'message'    => 'WhatsApp message sent successfully.',
                'phones'     => $phones,
                'response'   => $response,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Missing required parameters: merchant_domain and/or template_id.',
        ], 422);
    }
}
