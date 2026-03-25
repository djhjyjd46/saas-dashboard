<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Integration;

class YandexAuthController extends Controller
{
    public function redirect()
    {
        $clientId = config('services.yandex.client_id');

        if (empty($clientId)) {
            return redirect()->route('integrations')
                ->with('yandex_status', 'Ошибка: YANDEX_CLIENT_ID не задан в .env');
        }

        $redirectUri = route('yandex.callback');
        $scope = implode(' ', [
            'direct:api',    // Яндекс.Директ
            'metrika:read',  // Яндекс.Метрика — чтение целей
        ]);
        $url = "https://oauth.yandex.ru/authorize?response_type=code&client_id={$clientId}&state=" . csrf_token()
            . "&redirect_uri=" . urlencode($redirectUri)
            . "&scope=" . urlencode($scope)
            . "&force_confirm=1";

        return redirect($url);
    }

    public function callback(Request $request)
    {
        $code = $request->input('code');
        $clientId = config('services.yandex.client_id');
        $clientSecret = config('services.yandex.client_secret');

        if (!$code) {
            return redirect()->route('integrations')
                ->with('yandex_status', 'Ошибка авторизации Яндекс: код не получен.');
        }

        $response = Http::asForm()->withoutVerifying()->post('https://oauth.yandex.ru/token', [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $tenantId = auth()->user()->tenant_id;

            Integration::updateOrCreate(
                ['tenant_id' => $tenantId, 'type' => 'yandex'],
                [
                    'credentials' => [
                        'access_token'  => $data['access_token'],
                        'refresh_token' => $data['refresh_token'] ?? null,
                        'expires_at'    => isset($data['expires_in']) ? now()->addSeconds($data['expires_in'])->toDateTimeString() : null,
                    ],
                    'is_active' => true,
                ]
            );

            return redirect()->route('integrations')
                ->with('yandex_status', 'Яндекс.Директ успешно подключён!');
        }

        Log::error('Yandex OAuth failed: ' . $response->body());
        return redirect()->route('integrations')
            ->with('yandex_status', 'Ошибка обмена токена: ' . $response->json('error_description', $response->body()));
    }
}
