<?php

namespace Tests\Unit\Outcomes;

use App\Models\outcome;
use App\Models\outcome_type;
use App\Models\user;
use App\traits\outcomes_trait;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Tests\TestCase;

class OutcomeListTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_filters_by_type_and_sums_all_matching_outcomes_before_pagination(): void
    {
        $manager = new class {
            use outcomes_trait;
        };

        $firstType = outcome_type::create(['name' => 'Servicios de prueba']);
        $secondType = outcome_type::create(['name' => 'Arriendo de prueba']);
        $testUser = user::forceCreate([
            'unique_id' => uniqid('user-', true),
            'name' => 'Usuario',
            'lastname' => 'Prueba',
            'username' => uniqid('test-', true),
            'email' => uniqid('test-', true).'@example.test',
            'identification' => uniqid('id-', true),
            'password' => 'password',
        ]);

        $this->createOutcome($testUser->id, $firstType->id, 10.25, 'Servicio uno');
        $this->createOutcome($testUser->id, $firstType->id, 20.25, 'Servicio dos');
        $this->createOutcome($testUser->id, $secondType->id, 99.99, 'Arriendo');

        $response = $manager->Outcome_GetOutcomes(new Request([
            'page' => 1,
            'size' => 1,
            'outcome_type_id' => $firstType->id,
        ]));

        $this->assertSame(1, $response['status']);
        $this->assertSame(2, $response['pagination']['total']);
        $this->assertCount(1, $response['data']);
        $this->assertSame(30.5, (float) $response['totals']['amount']);
        $this->assertSame(2, $response['totals']['records']);

        $userFiltered = $manager->Outcome_GetOutcomes(new Request([
            'page' => 1,
            'size' => 1,
            'user_id' => $testUser->id,
        ]));
        $this->assertSame(3, $userFiltered['pagination']['total']);
        $this->assertSame(130.49, (float) $userFiltered['totals']['amount']);

        $unassignedProvider = $manager->Outcome_GetOutcomes(new Request([
            'page' => 1,
            'size' => 1,
            'outcome_type_id' => $firstType->id,
            'provider_id' => 'none',
        ]));
        $this->assertSame(2, $unassignedProvider['pagination']['total']);
    }

    public function test_form_catalogs_include_types_and_association_updates_are_scoped(): void
    {
        $manager = new class {
            use outcomes_trait;
        };

        $type = outcome_type::create(['name' => 'Catálogo de prueba']);
        $testUser = user::forceCreate([
            'unique_id' => uniqid('user-', true),
            'name' => 'Usuario',
            'lastname' => 'Catálogo',
            'username' => uniqid('catalog-', true),
            'email' => uniqid('catalog-', true).'@example.test',
            'identification' => uniqid('catalog-id-', true),
            'password' => 'password',
        ]);
        $outcome = $this->createOutcome($testUser->id, $type->id, 15, 'Asociación');

        $formData = $manager->Outcome_GetOutcomeFormData();
        $this->assertSame(1, $formData['status']);
        $this->assertTrue(collect($formData['data']['types'])->contains('id', $type->id));

        $updated = $manager->Outcome_UpdateOutcomeAssociation($outcome->id, 'client_id', null);
        $this->assertSame(1, $updated['status']);

        $invalid = $manager->Outcome_UpdateOutcomeAssociation($outcome->id, 'user_id', $testUser->id);
        $this->assertSame(0, $invalid['status']);
    }

    private function createOutcome(int $userId, int $typeId, float $amount, string $name): outcome
    {
        $outcome = new outcome();
        $outcome->unique_id = uniqid('outcome-', true);
        $outcome->outcome_type_id = $typeId;
        $outcome->name = $name;
        $outcome->description = $name;
        $outcome->amount = $amount;
        $outcome->date = '2026-08-18 10:00:00';
        $outcome->user_id = $userId;
        $outcome->save();

        return $outcome;
    }
}