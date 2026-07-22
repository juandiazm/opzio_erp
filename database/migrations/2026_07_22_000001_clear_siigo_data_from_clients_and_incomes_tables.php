<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Limpia todos los datos de sincronización con Siigo:
     *
     * - clients.siigo_id            → NULL
     * - clients.electronic_invoice  → false
     * - incomes.siigo_invoice_id    → NULL
     * - incomes.siigo_document_id   → NULL
     * - incomes.siigo_invoice_url   → NULL
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // ── clients ───────────────────────────────────────────────────────
        DB::table('clients')->update([
            'siigo_id'           => null,
            'electronic_invoice' => false,
        ]);

        // ── incomes ───────────────────────────────────────────────────────
        DB::table('incomes')->update([
            'siigo_invoice_id'  => null,
            'siigo_document_id' => null,
            'siigo_invoice_url' => null,
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Los datos borrados no pueden recuperarse automáticamente.
     */
    public function down(): void
    {
        // Irreversible — los valores originales no se almacenan.
    }
};
