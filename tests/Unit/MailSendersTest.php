<?php

namespace Tests\Unit;

use App\traits\mail_senders_trait;
use Tests\TestCase;

class MailSendersTest extends TestCase
{
    private function senderPolicy()
    {
        return new class {
            use mail_senders_trait;
        };
    }

    public function test_sender_directory_contains_official_names_and_info_reply_to()
    {
        $policy = $this->senderPolicy();
        $directory = $policy->Mail_SenderDirectory();

        $this->assertSame('OPZIO SAS - Información', $directory['info@opzio.co']['name']);
        $this->assertSame('OPZIO SAS - Legal', $directory['legal@opzio.co']['name']);
        $this->assertSame('OPZIO SAS - Soporte', $directory['soporte@opzio.co']['name']);
        $this->assertSame('OPZIO SAS - Contabilidad', $directory['contabilidad@opzio.co']['name']);
        $this->assertSame('OPZIO SAS - Comunicaciones', $directory['comunicaciones@opzio.co']['name']);
        $this->assertSame('info@opzio.co', $policy->Mail_GetReplyTo()['address']);
    }

    public function test_sender_policy_selects_by_email_purpose_and_falls_back_to_info()
    {
        $policy = $this->senderPolicy();

        $this->assertSame('legal@opzio.co', $policy->Mail_GetSenderForView('mail.contract')['address']);
        $this->assertSame('soporte@opzio.co', $policy->Mail_GetSenderForView('mail.chat_request')['address']);
        $this->assertSame('contabilidad@opzio.co', $policy->Mail_GetSenderForView('mail.purchase_order')['address']);
        $this->assertSame('comunicaciones@opzio.co', $policy->Mail_GetSenderForView('mail.ia_marketing_report')['address']);
        $this->assertSame('soporte@opzio.co', $policy->Mail_GetSenderForView('mail.servers.monthly_report')['address']);
        $this->assertSame('info@opzio.co', $policy->Mail_GetSenderForView('mail.notification', 'unknown@example.test')['address']);
        $this->assertSame('legal@opzio.co', $policy->Mail_GetSenderForView('mail.notification', 'legal@opzio.co')['address']);
    }

    public function test_sender_metadata_always_contains_the_info_reply_to()
    {
        $policy = $this->senderPolicy();
        $sender = $policy->Mail_GetSenderForView('mail.contract');
        $metadata = $policy->Mail_AddEnvelopeMetadata(['content' => 'test'], $sender, $policy->Mail_GetReplyTo());

        $this->assertSame('legal@opzio.co', $metadata['_from']['address']);
        $this->assertSame('OPZIO SAS - Legal', $metadata['_from']['name']);
        $this->assertSame('info@opzio.co', $metadata['_reply_to']['address']);
        $this->assertSame('OPZIO SAS - Información', $metadata['_reply_to']['name']);
    }
}
