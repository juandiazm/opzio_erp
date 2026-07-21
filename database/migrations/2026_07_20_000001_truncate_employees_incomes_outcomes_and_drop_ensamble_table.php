<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * - Elimina todos los registros de: employees, incomes, outcomes
     *   junto con sus tablas hijas.
     * - Elimina la tabla ensamble_coca_cola_certificates.
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // ── Hijos de incomes ───────────────────────────────────────────────
        DB::table('income_advances')->truncate();
        DB::table('income_payments')->truncate();
        DB::table('income_licenses')->truncate();

        // ── incomes (incluye soft-deleted) ────────────────────────────────
        DB::table('incomes')->truncate();

        // ── Hijos de employees ────────────────────────────────────────────
        DB::table('employee_documents')->truncate();

        // licenses y income_licenses referencian employees; ya se limpió
        // income_licenses arriba. Nullificamos employee_id en licenses
        // para no perder los datos de las licencias.
        DB::table('licenses')->update(['employee_id' => null]);

        // ── employees (incluye soft-deleted) ──────────────────────────────
        DB::table('employees')->truncate();

        // ── outcomes (incluye soft-deleted) ───────────────────────────────
        DB::table('outcomes')->truncate();

        // ── Tabla obsoleta: drop completo ─────────────────────────────────
        Schema::dropIfExists('ensamble_coca_cola_certificates');

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Los datos eliminados y la tabla dropeada no pueden revertirse.
     */
    public function down(): void
    {
        // Irreversible: no se puede restaurar datos borrados ni recrear
        // ensamble_coca_cola_certificates con sus registros originales.
    }
};
