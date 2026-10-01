<?php

namespace Tests\Unit;

use App\traits\nini_integration_trait;
use Tests\TestCase;

class NiniRechargeInvoicePreferenceTest extends TestCase
{
    public function test_missing_invoice_preference_keeps_generation_enabled_and_false_disables_it(): void
    {
        $integration = new class {
            use nini_integration_trait {
                NiniIntegration_ShouldGenerateSiigoInvoice as public shouldGenerateSiigoInvoice;
                NiniIntegration_ValidatePayload as public validatePayload;
            }
        };

        $this->assertTrue($integration->shouldGenerateSiigoInvoice([]));
        $this->assertTrue($integration->shouldGenerateSiigoInvoice(['generate_electronic_invoice' => true]));
        $this->assertFalse($integration->shouldGenerateSiigoInvoice(['generate_electronic_invoice' => false]));

        $payload = [
            'company_nit' => '900123456-7',
            'company_name' => 'Empresa de prueba',
            'total_amount' => 10000,
            'payment_date' => '2026-09-30',
            'nini_transaction_reference' => 'recharge-test',
            'generate_electronic_invoice' => false,
        ];
        $this->assertSame(1, $integration->validatePayload($payload)['status']);

        $payload['generate_electronic_invoice'] = 'false';
        $this->assertSame(0, $integration->validatePayload($payload)['status']);
    }
}