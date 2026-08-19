<?php

namespace Tests\Unit\Outcomes;

use App\Models\outcome_type;
use App\Services\Outcomes\OutcomeClassifier;
use App\traits\outcome_type_trait;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OutcomeTypeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_manages_specific_outcome_types_without_generic_fallbacks(): void
    {
        $manager = new class {
            use outcome_type_trait;
        };

        $this->assertSame(0, $manager->OutcomeType_AddType('Otro')['status']);

        $created = $manager->OutcomeType_AddType('Servicios tecnológicos');

        $this->assertSame(1, $created['status']);
        $typeId = $created['data']->id;

        $updated = $manager->OutcomeType_UpdateType($typeId, 'Servicios de tecnología');
        $this->assertSame(1, $updated['status']);
        $this->assertSame('Servicios de tecnología', $updated['data']->name);

        $deleted = $manager->OutcomeType_DeleteType($typeId);
        $this->assertSame(1, $deleted['status']);
        $this->assertDatabaseMissing('outcome_types', ['id' => $typeId]);
    }

    public function test_classifier_reuses_a_matching_catalog_type_before_ai(): void
    {
        $type = outcome_type::create(['name' => 'Servicios de internet']);

        $classification = (new OutcomeClassifier())->classify([
            'date' => '2026-08-18',
            'description' => 'Pago servicios de internet',
            'amount' => '-100000.00',
        ]);

        $this->assertSame($type->id, $classification['outcome_type_id']);
        $this->assertSame('catalog', $classification['classification_source']);
    }
}