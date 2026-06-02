<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class RecaptchaService
{
    public static function verify(string $token): bool
    {
        try {
            \Log::info('reCAPTCHA verification started with token: ' . substr($token, 0, 50) . '...');

            $projectId = config('services.recaptcha.project_id');
            $apiKey = config('services.recaptcha.api_key');
            $siteKey = config('services.recaptcha.site_key');

            if ($projectId && $apiKey) {
                // reCAPTCHA Enterprise verification
                $url = "https://recaptchaenterprise.googleapis.com/v1/projects/{$projectId}/assessments?key={$apiKey}";
                $response = Http::timeout(10)->post($url, [
                    'event' => [
                        'token' => $token,
                        'siteKey' => $siteKey,
                    ]
                ]);

                \Log::info('reCAPTCHA Enterprise response status: ' . $response->status());

                if (!$response->successful()) {
                    \Log::error('reCAPTCHA Enterprise API failed: ' . $response->body());
                    return false;
                }

                $result = $response->json();
                \Log::info('reCAPTCHA Enterprise result: ' . json_encode($result));

                return ($result['tokenProperties']['valid'] ?? false);
            } else {
                // Legacy reCAPTCHA v2 / v3 verification
                $response = Http::asForm()->timeout(10)->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => config('services.recaptcha.secret_key'),
                    'response' => $token,
                ]);

                \Log::info('reCAPTCHA response status: ' . $response->status());

                if (!$response->successful()) {
                    \Log::error('reCAPTCHA API failed: ' . $response->body());
                    return false;
                }

                $result = $response->json();
                \Log::info('reCAPTCHA result: ' . json_encode($result));

                return ($result['success'] ?? false);
            }
        } catch (\Exception $e) {
            \Log::error('reCAPTCHA verification exception: ' . $e->getMessage());
            return false;
        }
    }
}

