<?php

namespace App\Support\Sms;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TwilioSmsClient
{
    private Client $client;
    private string $from;

    public function __construct(?string $sid = null, ?string $token = null, ?string $from = null)
    {
        $sid   = $sid   ?? config('services.twilio.sid');
        $token = $token ?? config('services.twilio.token');
        $from  = $from  ?? config('services.twilio.from');

        if (!$sid || !$token || !$from) {
            throw new RuntimeException('Twilio configuration is incomplete.');
        }

        $this->client = new Client($sid, $token);
        $this->from   = $from;
    }

    public function sendMessage(string $to, string $message): array
    {
        try {
            $to = $this->normalizePhone($to);
            $sms = $this->client->messages->create($to, [
                'from' => $this->from,
                'body' => $message,
            ]);

            Log::info('Twilio SMS sent', [
                'sid' => $sms->sid,
                'status' => $sms->status,
                'to' => $to,
            ]);

            return ['sid' => $sms->sid, 'status' => $sms->status];
        } catch (\Throwable $e) {
            Log::error('Twilio SMS failed', [
                'error' => $e->getMessage(),
                'to' => $to,
            ]);
            throw $e;
        }
    }

    private function normalizePhone(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value);
        return '+' . ltrim($digits, '+');
    }
}
