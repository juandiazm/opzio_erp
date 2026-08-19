<?php

namespace Tests\Unit\Outcomes;

use App\Services\Outcomes\OutcomeTextNormalizer;
use Tests\TestCase;

class OutcomeTextNormalizerTest extends TestCase
{
    public function test_it_groups_dynamic_card_codes_without_merging_transaction_categories(): void
    {
        $normalizer = new OutcomeTextNormalizer();

        $purchase = 'COMPRA CON TARJETA DEBITO EN FACEBK *MWH8ASMGW2';
        $samePurchase = 'COMPRA CON TARJETA DEBITO EN FACEBK *3GW6DURGW2';
        $commission = 'COMISION POR COMPRA CON TARJETA DEBITO EN FACEBK *MWH8ASMGW2';

        $this->assertSame('COMPRA|FACEBK', $normalizer->merchantKey($purchase));
        $this->assertSame($normalizer->merchantKey($purchase), $normalizer->merchantKey($samePurchase));
        $this->assertNotSame($normalizer->merchantKey($purchase), $normalizer->merchantKey($commission));
        $this->assertGreaterThan(0.9, $normalizer->similarity($purchase, $samePurchase));
    }
}