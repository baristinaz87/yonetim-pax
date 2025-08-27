<?php

namespace App\Http\Controllers;

use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\RedirectResponse;

class GoogleAuthController extends Controller
{
    protected GoogleCalendarService $googleCalendarService;

    public function __construct(GoogleCalendarService $googleCalendarService)
    {
        $this->googleCalendarService = $googleCalendarService;
    }

    /**
     * Handle Google OAuth callback
     */
    public function callback(Request $request): RedirectResponse
    {
        try {
            $code = $request->get('code');

            if (!$code) {
                return redirect('/merchants')->with('error', 'Google yetkilendirme kodu alınamadı.');
            }

            $accessToken = $this->googleCalendarService->handleCallback($code);

            // Store token in session
            Session::put('google_access_token', $accessToken);

            return redirect('/merchants')->with('success', 'Google Calendar bağlantısı başarılı. Şimdi takvim olaylarını ekleyebilirsiniz.');

        } catch (\Exception $e) {
            return redirect('/merchants')->with('error', 'Google yetkilendirme hatası: ' . $e->getMessage());
        }
    }
}
