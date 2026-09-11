<?php

namespace Tests\Unit;

use App\Models\client;
use App\Models\income;
use App\Support\SanitizedHtml;
use App\traits\incomes_trait;
use Tests\TestCase;

class IncomeRichTextAndSiigoTest extends TestCase
{
    public function test_rich_text_is_sanitized_and_keeps_allowed_formatting(): void
    {
        $html = SanitizedHtml::clean('<p><strong>Servicio</strong> <script>alert(1)</script><a href="javascript:alert(2)">ver</a></p>');

        $this->assertStringContainsString('<strong>Servicio</strong>', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertSame('Servicio ver', SanitizedHtml::plainText($html));
    }

    public function test_incomplete_client_is_not_ready_for_siigo(): void
    {
        $client = new client([
            'name' => 'Cliente de prueba',
            'identification' => '900123456-1',
            'email' => '',
            'phone' => null,
            'address' => '',
        ]);

        $this->assertFalse($client->isReadyForSiigo());
        $this->assertSame(['correo', 'teléfono', 'dirección'], $client->siigo_missing_fields);
    }

    public function test_siigo_invoice_is_deferred_for_incomplete_client(): void
    {
        $income = new income();
        $income->state = 2;
        $income->client_id = 1;
        $income->setRelation('client', new client([
            'name' => 'Cliente de prueba',
            'identification' => '900123456-1',
            'email' => '',
            'phone' => null,
            'address' => '',
        ]));
        $owner = new class {
            use incomes_trait;
        };

        $response = $owner->Income_CreateSiigoInvoice($income);

        $this->assertSame(0, $response['status']);
        $this->assertSame(['correo', 'teléfono', 'dirección'], $response['data']['missing_fields']);
    }
}