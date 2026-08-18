<?php

namespace Tests\Feature;

use App\traits\income_goals_trait;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class income_goals_test extends TestCase
{
    use income_goals_trait;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');

        Schema::create('income_goals', function (Blueprint $table) {
            $table->id();
            $table->decimal('target_amount', 20, 2);
            $table->unsignedInteger('frequency_months');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_income_goals_support_create_update_delete_and_restore(): void
    {
        $first = $this->IncomeGoal_Create(1500000, 1, '2026-01-01', '2026-12-31');
        $second = $this->IncomeGoal_Create(4500000, 12, '2026-01-01', '2026-12-31');

        $this->assertSame(1, $first['status']);
        $this->assertSame(1, $second['status']);
        $this->assertCount(2, $this->IncomeGoal_GetGoals()['data']);
        $this->assertSame('2026-01-01', $first['data']->start_date->format('Y-m-d'));
        $this->assertSame('2026-12-31', $first['data']->end_date->format('Y-m-d'));

        $goalId = $first['data']->id;
        $updated = $this->IncomeGoal_Update($goalId, 2000000, 2, '2026-01-01', '2026-06-30');
        $this->assertSame(1, $updated['status']);
        $this->assertSame('2000000.00', (string) $updated['data']->target_amount);
        $this->assertSame(2, $updated['data']->frequency_months);
        $this->assertSame('2026-06-30', $updated['data']->end_date->format('Y-m-d'));

        $invalidRange = $this->IncomeGoal_Create(1000000, 5, '2026-01-01', '2026-12-31');
        $this->assertSame(0, $invalidRange['status']);

        $deleted = $this->IncomeGoal_Delete($goalId);
        $this->assertSame(1, $deleted['status']);
        $this->assertNotNull($deleted['data']->deleted_at);
        $this->assertNotNull($this->IncomeGoal_GetGoals()['data']->firstWhere('id', $goalId)->deleted_at);

        $restored = $this->IncomeGoal_Restore($goalId);
        $this->assertSame(1, $restored['status']);
        $this->assertNull($restored['data']->deleted_at);
        $this->assertNull($this->IncomeGoal_GetGoals()['data']->firstWhere('id', $goalId)->deleted_at);
    }
}
