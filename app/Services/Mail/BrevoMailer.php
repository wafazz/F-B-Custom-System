<?php

namespace App\Services\Mail;

use Illuminate\Support\Facades\Http;

class BrevoMailer
{
    public function isConfigured(): bool
    {
        return $this->apiKey() !== '' && $this->senderEmail() !== '';
    }

    /**
     * Send a transactional email via Brevo's HTTP API.
     *
     * @param  array<int, array{email: string, name?: string}>|string  $to
     * @return array{ok: bool, status: int|null, message_id?: string, error?: string}
     */
    public function send(array|string $to, string $subject, string $htmlContent, ?string $textContent = null): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'status' => null, 'error' => 'Brevo is not configured.'];
        }

        $recipients = is_string($to)
            ? [['email' => $to]]
            : $to;

        $payload = [
            'sender' => array_filter([
                'name' => $this->senderName() ?: null,
                'email' => $this->senderEmail(),
            ]),
            'to' => $recipients,
            'subject' => $subject,
            'htmlContent' => $htmlContent,
        ];
        if ($textContent !== null && $textContent !== '') {
            $payload['textContent'] = $textContent;
        }

        $response = Http::withHeaders([
            'api-key' => $this->apiKey(),
            'accept' => 'application/json',
            'content-type' => 'application/json',
        ])
            ->timeout(15)
            ->post('https://api.brevo.com/v3/smtp/email', $payload);

        if ($response->successful()) {
            return [
                'ok' => true,
                'status' => $response->status(),
                'message_id' => (string) ($response->json('messageId') ?? ''),
            ];
        }

        return [
            'ok' => false,
            'status' => $response->status(),
            'error' => (string) ($response->json('message') ?? $response->body()),
        ];
    }

    protected function apiKey(): string
    {
        return (string) config('services.brevo.api_key');
    }

    protected function senderEmail(): string
    {
        return (string) config('services.brevo.sender_email');
    }

    protected function senderName(): string
    {
        return (string) config('services.brevo.sender_name');
    }
}
