<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Integration;
use App\Services\Tenancy\TenantManager;

class AmoCrmAuthController extends Controller
{
    public function redirect(Request $request)
    {
        $id = $request->input('id');
        if ($id) {
            $integration = Integration::withoutGlobalScopes()->find($id);
        } else {
            // Fallback to searching by type, but ideally we should always have an ID for multi-tenancy
            $integration = Integration::where('type', 'amocrm')->first();
        }

        if (!$integration) {
            return redirect()->route('integrations')
                ->with('status', 'Ошибка: интеграция не найдена.');
        }

        $domain   = $integration->credentials['domain'] ?? config('services.amocrm.base_domain');
        $clientId = $integration->credentials['client_id'] ?? config('services.amocrm.client_id');

        if (empty($clientId) || empty($domain)) {
            return redirect()->route('integrations')
                ->with('status', 'Ошибка: client_id или domain не заданы в настройках интеграции.');
        }

        // Ensure domain is full hostname
        if (!str_contains($domain, '.')) {
            $domain .= '.amocrm.ru';
        }

        $redirectUri = route('amocrm.callback');
        $state = csrf_token() . '|' . $integration->id;
        
        // Using the universal authorization URL (www.amocrm.ru) instead of the subdomain.
        // This is more robust and avoids 404 errors on subdomains that don't have the integration active yet.
        $url = "https://www.amocrm.ru/oauth?client_id={$clientId}&state=" . urlencode($state) . "&redirect_uri=" . urlencode($redirectUri);

        return redirect($url);
    }


    public function callback(Request $request)
    {
        $code  = $request->input('code');
        $state = $request->input('state');
        
        if (!$code) {
            return redirect()->route('integrations')
                ->with('status', 'Ошибка авторизации AmoCRM: код не получен.');
        }

        // Parse state to find integration
        $integrationId = null;
        if ($state && str_contains($state, '|')) {
            $parts = explode('|', $state);
            $integrationId = end($parts);
        }

        if ($integrationId) {
            $integration = Integration::withoutGlobalScopes()->find($integrationId);
        } else {
            $integration = Integration::where('type', 'amocrm')->first();
        }

        if (!$integration) {
            return redirect()->route('integrations')
                ->with('status', 'Ошибка: интеграция для завершения авторизации не найдена.');
        }

        $domain       = $request->input('referer') ?? ($integration->credentials['domain'] ?? config('services.amocrm.base_domain'));
        $clientId     = $integration->credentials['client_id'] ?? config('services.amocrm.client_id');
        $clientSecret = $integration->credentials['client_secret'] ?? config('services.amocrm.client_secret');
        $redirectUri  = route('amocrm.callback');

        if (empty($clientId) || empty($clientSecret)) {
             Log::error('AmoCRM OAuth failed: client_id or client_secret missing for integration ' . $integration->id);
             return redirect()->route('integrations')->with('status', 'Ошибка: в настройках интеграции отсутствуют ключи (ID или Secret).');
        }

        $response = Http::withoutVerifying()->post("https://{$domain}/oauth2/access_token", [
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $redirectUri,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            
            $credentials = $integration->credentials;
            $credentials['access_token']  = $data['access_token'];
            $credentials['refresh_token'] = $data['refresh_token'];
            $credentials['expires_at']    = now()->addSeconds($data['expires_in'])->toDateTimeString();
            $credentials['domain']        = $domain;

            $integration->update([
                'credentials' => $credentials,
                'is_active'   => true,
                'user_id'     => auth()->id() ?? $integration->user_id,
            ]);

            return redirect()->route('integrations')
                ->with('status', 'AmoCRM успешно подключена!');
        }

        Log::error('AmoCRM OAuth failed: ' . $response->body());
        return redirect()->route('integrations')
            ->with('status', 'Ошибка обмена токена AmoCRM: ' . $response->json('hint', $response->body()));
    }

}
