<?php

namespace App\traits;

use Illuminate\Support\Collection;

trait mail_senders_trait
{
    public function Mail_SenderDirectory(): array
    {
        return [
            'info@opzio.co' => [
                'address' => 'info@opzio.co',
                'name' => 'OPZIO SAS - Información',
            ],
            'legal@opzio.co' => [
                'address' => 'legal@opzio.co',
                'name' => 'OPZIO SAS - Legal',
            ],
            'soporte@opzio.co' => [
                'address' => 'soporte@opzio.co',
                'name' => 'OPZIO SAS - Soporte',
            ],
            'contabilidad@opzio.co' => [
                'address' => 'contabilidad@opzio.co',
                'name' => 'OPZIO SAS - Contabilidad',
            ],
            'comunicaciones@opzio.co' => [
                'address' => 'comunicaciones@opzio.co',
                'name' => 'OPZIO SAS - Comunicaciones',
            ],
        ];
    }

    public function Mail_GetSenderForView(string $view, $requested = null): array
    {
        $directory = $this->Mail_SenderDirectory();
        $requestedAddress = $this->Mail_RequestedAddress($requested);
        $view = strtolower($view);

        $recommended = null;
        if ($view === 'mail.contract' || str_contains($view, 'contract')) {
            $recommended = 'legal@opzio.co';
        } elseif ($view === 'mail.notification') {
            $recommended = $requestedAddress;
        } elseif (str_contains($view, 'servers.monthly_report') || str_contains($view, 'backup_failed')) {
            $recommended = 'soporte@opzio.co';
        } elseif (str_contains($view, 'chat_request') || str_contains($view, 'client_user') || str_contains($view, 'restore_password') || str_contains($view, 'user_restore')) {
            $recommended = 'soporte@opzio.co';
        } elseif (str_contains($view, 'purchase_order') || str_contains($view, 'quotation_order') || str_contains($view, 'pay_remaining') || str_contains($view, 'income_payment') || str_contains($view, 'payment_finished')) {
            $recommended = 'contabilidad@opzio.co';
        } elseif (str_contains($view, 'approve_') || str_contains($view, 'blog') || str_contains($view, 'facebook') || str_contains($view, 'instagram') || str_contains($view, 'linkedin') || str_contains($view, 'twitter') || str_contains($view, 'marketing_report')) {
            $recommended = 'comunicaciones@opzio.co';
        } elseif (str_contains($view, 'web-contact') || str_contains($view, 'client_registered')) {
            $recommended = 'info@opzio.co';
        }

        $address = $recommended ?: $requestedAddress ?: 'info@opzio.co';
        return $directory[$address] ?? $directory['info@opzio.co'];
    }

    public function Mail_GetReplyTo(): array
    {
        return $this->Mail_SenderDirectory()['info@opzio.co'];
    }

    public function Mail_AddEnvelopeMetadata($mailData, array $sender, array $replyTo): array
    {
        if ($mailData instanceof Collection) {
            $mailData = $mailData->toArray();
        } elseif (is_object($mailData) && method_exists($mailData, 'toArray')) {
            $mailData = $mailData->toArray();
        } elseif (!is_array($mailData)) {
            $mailData = [];
        }

        $mailData['_from'] = $sender;
        $mailData['_reply_to'] = $replyTo;
        return $mailData;
    }

    private function Mail_RequestedAddress($requested): ?string
    {
        if (is_array($requested)) {
            $requested = $requested['address'] ?? null;
        }

        $requested = strtolower(trim((string) $requested));
        return filter_var($requested, FILTER_VALIDATE_EMAIL) ? $requested : null;
    }
}
