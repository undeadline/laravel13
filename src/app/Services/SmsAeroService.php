<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SmsAeroService
{
    private string $baseUrl = 'https://gate.smsaero.ru/v2';

    public function send(string $phone, string $message): bool
    {
        $response = Http::withBasicAuth(
            config('services.smsaero.email'),
            config('services.smsaero.api_key')
        )->get("{$this->baseUrl}/sms/send", [
            'number'  => $phone,
            'text'    => $message,
            'sign'    => config('services.smsaero.sign'), // имя отправителя
        ]);

        return $response->successful() && $response->json('success');
    }
}
