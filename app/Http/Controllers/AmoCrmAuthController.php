<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Integration;
use App\Services\Tenancy\TenantManager;

class AmoCrmAuthController extends Controller
{
    public function redirect()
    {
        $domain   = config('services.amocrm.base_domain');
        $clientId = config('services.amocrm.client_id');

        if (empty($clientId) || empty($domain)) {
            return redirect()->route('integrations')
                ->with('status', 'Ошибка: AMOCRM_CLIENT_ID или AMOCRM_BASE_DOMAIN не заданы в .env');
        }

        $redirectUri = route('amocrm.callback');
        $url = "https://www.amocrm.ru/oauth?client_id={$clientId}&state=" . csrf_token() . "&redirect_uri=" . urlencode($redirectUri);

        return redirect($url);
    }

    public function callback(Request $request)
    {
        $code     = $request->input('code');
        $domain   = $request->input('referer') ?? config('services.amocrm.base_domain');
        $clientId = config('services.amocrm.client_id');
        $clientSecret = config('services.amocrm.client_secret');
        $redirectUri  = route('amocrm.callback');

        if (!$code) {
            return redirect()->route('integrations')
                ->with('status', 'Ошибка авторизации AmoCRM: код не получен.');
        }

        $response = Http::withoutVerifying()->post("https://{$domain}/oauth2/access_token", [
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $redirectUri,
        ]);

        if ($response->successful()) {
            $data   = $response->json();
            $tenant = app(TenantManager::class)->getTenant();

            Integration::updateOrCreate(
                ['tenant_id' => $tenant->id, 'type' => 'amocrm'],
                [
                    'credentials' => [
                        'access_token'  => $data['access_token'],
                        'refresh_token' => $data['refresh_token'],
                        'expires_at'    => now()->addSeconds($data['expires_in'])->toDateTimeString(),
                        'domain'        => $domain,
                    ],
                    'is_active' => true,
                ]
            );

            return redirect()->route('integrations')
                ->with('status', 'AmoCRM успешно подключена!');
        }

        Log::error('AmoCRM OAuth failed: ' . $response->body());
        return redirect()->route('integrations')
            ->with('status', 'Ошибка обмена токена AmoCRM: ' . $response->json('hint', $response->body()));
    }
}
