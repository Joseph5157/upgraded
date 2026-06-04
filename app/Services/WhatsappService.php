<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    public function send(string $phone, string $templateName, array $params = []): bool
    {
        $apiKey   = config('services.aisensy.api_key');
        $userName = config('services.aisensy.user_name', 'PlagExpert');

        if (! $apiKey) {
            Log::warning('WhatsappService: AiSensy API key not configured.', ['phone' => $phone]);
            return false;
        }

        $phone = ltrim($phone, '+');
        if (strlen($phone) === 10) {
            $phone = '91' . $phone;
        }

        $payload = [
            'apiKey'         => $apiKey,
            'campaignName'   => $templateName,
            'destination'    => $phone,
            'userName'       => $userName,
            'templateParams' => $params,
            'source'         => 'plagexpert-signup',
            'media'          => (object) [],
            'buttons'        => [],
        ];

        try {
            $response = Http::timeout(15)
                ->post('https://backend.aisensy.com/campaign/t1/api/v2', $payload);

            if ($response->successful()) {
                Log::info('WhatsappService: sent.', ['phone' => $phone, 'template' => $templateName]);
                return true;
            }

            Log::warning('WhatsappService: failed.', ['status' => $response->status(), 'body' => $response->body()]);
            return false;

        } catch (\Throwable $e) {
            Log::error('WhatsappService: exception.', ['message' => $e->getMessage()]);
            return false;
        }
    }
}
