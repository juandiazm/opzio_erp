<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MoveContractSchedulesToContractRecurrence extends Migration
{
    private function hasIndex($table, $index)
    {
        if (DB::getDriverName() === 'mysql') {
            return count(DB::select('SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?', [$index])) > 0;
        }
        if (DB::getDriverName() === 'sqlite') {
            return collect(DB::select('PRAGMA index_list("'.$table.'")'))
                ->contains(fn ($row) => ($row->name ?? '') === $index);
        }

        return false;
    }

    private function hasForeignKey($table, $column)
    {
        if (DB::getDriverName() === 'mysql') {
            return (bool) DB::selectOne(
                'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
                [$table, $column]
            );
        }
        if (DB::getDriverName() === 'sqlite') {
            return collect(DB::select('PRAGMA foreign_key_list("'.$table.'")'))
                ->contains(fn ($row) => ($row->from ?? '') === $column);
        }

        return true;
    }

    public function up()
    {
        if (!Schema::hasColumn('contracts', 'recurrence_enabled')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->boolean('recurrence_enabled')->default(false);
                $table->string('recurrence_frequency', 20)->nullable();
                $table->unsignedInteger('recurrence_interval')->default(1);
                $table->dateTime('recurrence_next_at')->nullable();
                $table->dateTime('recurrence_ends_at')->nullable();
                $table->boolean('recurrence_send_automatically')->default(false);
                $table->dateTime('recurrence_last_at')->nullable();
                $table->longText('recurrence_error')->nullable();
                $table->unsignedBigInteger('recurrence_parent_id')->nullable();
                $table->string('send_status', 20)->default('not_sent');
                $table->dateTime('pdf_generated_at')->nullable();
                $table->index(['recurrence_enabled', 'recurrence_next_at']);
                $table->index('recurrence_parent_id');
            });
        }

        if (Schema::hasTable('contract_schedules')) {
            $schedules = DB::table('contract_schedules')->orderBy('id')->get();
            foreach ($schedules as $schedule) {
                $contract = DB::table('contracts')
                    ->where('schedule_id', $schedule->id)
                    ->first();
                if (!$contract) {
                    continue;
                }

                $frequency = strtolower((string) ($schedule->frequency ?? 'monthly'));
                $interval = max(1, (int) ($schedule->interval_value ?? 1));
                if ((bool) ($schedule->sync_license_recurrence ?? false)) {
                    $license = $schedule->license_id
                        ? DB::table('licenses')->where('id', $schedule->license_id)->first()
                        : null;
                    $frequency = 'monthly';
                    $interval = max(1, (int) ($license->recurrence_months ?? $interval));
                }

                DB::table('contracts')
                    ->where('id', $contract->id)
                    ->update([
                        'recurrence_enabled' => (bool) ($schedule->active ?? false),
                        'recurrence_frequency' => in_array($frequency, ['daily', 'weekly', 'monthly', 'yearly'], true) ? $frequency : 'monthly',
                        'recurrence_interval' => $interval,
                        'recurrence_next_at' => $schedule->next_run_at,
                        'recurrence_ends_at' => $schedule->ends_at,
                        'recurrence_send_automatically' => (bool) ($schedule->send_automatically ?? false),
                        'recurrence_last_at' => $schedule->last_run_at,
                        'recurrence_error' => $schedule->last_error,
                    ]);
            }
        }

        $contracts = DB::table('contracts')
            ->where('sync_license_recurrence', true)
            ->whereNotNull('license_id')
            ->get(['id', 'license_id', 'end_date']);
        foreach ($contracts as $contract) {
            $license = DB::table('licenses')->where('id', $contract->license_id)->first();
            if (!$license || (int) ($license->type ?? 0) !== 1 || (int) ($license->recurrence_months ?? 0) < 1) {
                continue;
            }

            DB::table('contracts')
                ->where('id', $contract->id)
                ->update([
                    'recurrence_enabled' => true,
                    'recurrence_frequency' => 'monthly',
                    'recurrence_interval' => max(1, (int) $license->recurrence_months),
                    'recurrence_next_at' => $contract->end_date,
                ]);
        }

        DB::table('contracts')
            ->where('status', 'sent')
            ->update([
                'status' => 'pending_signature',
                'send_status' => 'sent',
            ]);
        DB::table('contracts')
            ->where('status', 'signed')
            ->update(['send_status' => 'sent']);
        DB::table('contracts')
            ->where('status', 'draft')
            ->update(['status' => 'generated']);
        DB::table('contracts')
            ->whereNull('send_status')
            ->update(['send_status' => 'not_sent']);
        DB::table('contracts')
            ->whereNull('pdf_generated_at')
            ->whereNotNull('generated_at')
            ->update(['pdf_generated_at' => DB::raw('generated_at')]);

        if (Schema::hasColumn('contracts', 'schedule_id')) {
            if ($this->hasForeignKey('contracts', 'schedule_id')) {
                Schema::table('contracts', function (Blueprint $table) {
                    $table->dropForeign(['schedule_id']);
                });
            }
            if ($this->hasIndex('contracts', 'contracts_schedule_id_foreign')) {
                Schema::table('contracts', function (Blueprint $table) {
                    $table->dropIndex('contracts_schedule_id_foreign');
                });
            }
            if ($this->hasIndex('contracts', 'contracts_scheduled_for_index')) {
                Schema::table('contracts', function (Blueprint $table) {
                    $table->dropIndex('contracts_scheduled_for_index');
                });
            }
            if ($this->hasIndex('contracts', 'contracts_schedule_key_unique')) {
                Schema::table('contracts', function (Blueprint $table) {
                    $table->dropUnique('contracts_schedule_key_unique');
                });
            }
            if (!$this->hasIndex('contracts', 'contracts_license_id_index')) {
                Schema::table('contracts', function (Blueprint $table) {
                    $table->index('license_id');
                });
            }
            if ($this->hasIndex('contracts', 'contracts_license_id_sync_license_recurrence_index')) {
                Schema::table('contracts', function (Blueprint $table) {
                    $table->dropIndex('contracts_license_id_sync_license_recurrence_index');
                });
            }
            $columns = array_values(array_filter([
                'schedule_id',
                'scheduled_for',
                'schedule_key',
                'sync_license_recurrence',
            ], fn ($column) => Schema::hasColumn('contracts', $column)));
            if ($columns) {
                Schema::table('contracts', function (Blueprint $table) use ($columns) {
                    $table->dropColumn($columns);
                });
            }
        }

        Schema::dropIfExists('contract_schedules');
    }

    public function down()
    {
        if (Schema::hasColumn('contracts', 'recurrence_enabled')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->dropIndex(['recurrence_enabled', 'recurrence_next_at']);
                $table->dropIndex(['recurrence_parent_id']);
                $table->dropColumn([
                    'recurrence_enabled',
                    'recurrence_frequency',
                    'recurrence_interval',
                    'recurrence_next_at',
                    'recurrence_ends_at',
                    'recurrence_send_automatically',
                    'recurrence_last_at',
                    'recurrence_error',
                    'recurrence_parent_id',
                    'send_status',
                    'pdf_generated_at',
                ]);
            });
        }
    }
}
