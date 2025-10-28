<?php

namespace App\Support\Sms;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class EasySendSmsClient
{
    private string $apiKey;
    private string $sender;
    private string $baseUrl;

    public function __construct(
        ?string $apiKey = null,
        ?string $sender = null,
        ?string $baseUrl = null
    ) {
        $this->apiKey = (string) ($apiKey ?? config('services.easysendsms.api_key', ''));
        $this->sender = (string) ($sender ?? config('services.easysendsms.sender', ''));
        $this->baseUrl = rtrim((string) ($baseUrl ?? config('services.easysendsms.base_url', '')), '/');

        if ($this->apiKey === '' || $this->sender === '' || $this->baseUrl === '') {
            throw new RuntimeException('EasySendSMS configuration is incomplete.');
        }
    }

    public function sendMessage(string $rawPhoneNumber, string $message, ?Carbon $scheduledFor = null, string $type = '0'): array
    {
        $to = $this->normalizePhoneNumber($rawPhoneNumber);

        if ($to === '') {
            throw new RuntimeException('Cannot send SMS without a valid recipient phone number.');
        }

        $payload = [
            'from' => $this->sender,
            'to'   => $to,
            'text' => $message,
            'type' => $type,
        ];

        if ($scheduledFor instanceof Carbon) {
            $payload['scheduled'] = $scheduledFor
                ->clone()
                ->setTimezone('UTC')
                ->format('Y-m-d\TH:i:s');
        }

        $response = $this->request()->post('/sms/send', $payload);

        if ($response->failed()) {
            throw new RuntimeException('EasySendSMS request failed: '.$response->body());
        }

        $data = $response->json();

        if (!is_array($data) || ($data['status'] ?? null) !== 'OK') {
            $error = $data['description'] ?? $response->body();
            throw new RuntimeException('EasySendSMS returned an error: '.$error);
        }

        return $data;
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withHeaders([
                'apikey' => $this->apiKey,
                'Accept' => 'application/json',
            ])
            ->asJson();
    }

    private function normalizePhoneNumber(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if ($digits === '') {
            return '';
        }

        if (Str::startsWith($digits, '00')) {
            $digits = substr($digits, 2);
        }

        return $digits;
    }
}
