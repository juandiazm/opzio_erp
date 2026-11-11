<?php

namespace Tests\Unit\Outcomes;

use App\Exportable\outcomes_export;
use App\Models\outcome;
use App\Models\outcome_type;
use App\Models\user;
use App\traits\outcomes_trait;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
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

    public function test_filtered_export_query_includes_all_matches_and_summarizes_deleted_outcomes_separately(): void
    {
        $manager = new class {
            use outcomes_trait;
        };

        $firstType = outcome_type::create(['name' => 'Servicios para exportar']);
        $secondType = outcome_type::create(['name' => 'Otro tipo para exportar']);
        $testUser = user::forceCreate([
            'unique_id' => uniqid('user-', true),
            'name' => 'Usuario',
            'lastname' => 'Exportación',
            'username' => uniqid('export-', true),
            'email' => uniqid('export-', true).'@example.test',
            'identification' => uniqid('export-id-', true),
            'password' => 'password',
        ]);

        $activeOutcome = $this->createOutcome($testUser->id, $firstType->id, 10.25, 'Servicio contable');
        $deletedOutcome = $this->createOutcome($testUser->id, $firstType->id, 20.25, 'Servicio contable eliminado');
        $deletedOutcome->delete();
        $this->createOutcome($testUser->id, $secondType->id, 99.99, 'Servicio excluido por tipo');

        $query = $manager->Outcome_BuildFilteredOutcomesQuery(new Request([
            'page' => 1,
            'size' => 1,
            'from' => '2026-08-18',
            'to' => '2026-08-18',
            'search' => 'Servicio',
            'outcome_type_id' => $firstType->id,
        ]));

        $this->assertEqualsCanonicalizing(
            [$activeOutcome->id, $deletedOutcome->id],
            $query->pluck('id')->all()
        );

        $export = new outcomes_export($query);
        $sheets = $export->sheets();
        $this->assertSame(['Resumen', 'Detalle'], array_map(function ($sheet) {
            return $sheet->title();
        }, $sheets));

        $summaryRows = $sheets[0]->collection()->all();
        $this->assertContains(['Servicios para exportar', 'Vigente', 1, 10.25], $summaryRows);
        $this->assertContains(['Servicios para exportar', 'Eliminado', 1, 20.25], $summaryRows);
        $this->assertContains(['TOTAL VIGENTE', 'Vigente', 1, 10.25], $summaryRows);
        $this->assertContains(['TOTAL ELIMINADO', 'Eliminado', 1, 20.25], $summaryRows);
        $this->assertContains(['TOTAL GENERAL (incluye eliminados)', 'Todos', 2, 30.5], $summaryRows);

        $formulaCell = (new Spreadsheet())->getActiveSheet()->getCell('A1');
        $sheets[1]->bindValue($formulaCell, '=1+1');
        $this->assertSame(DataType::TYPE_STRING, $formulaCell->getDataType());

        $xlsx = Excel::raw($export, ExcelFormat::XLSX);
        $this->assertStringStartsWith('PK', $xlsx);
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