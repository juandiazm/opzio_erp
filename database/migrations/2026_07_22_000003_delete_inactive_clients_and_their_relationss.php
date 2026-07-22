<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Elimina clientes que cumplan al menos una condición:
     *   a) active = 0  (cliente inactivo), O
     *   b) no tienen ninguna licencia activa (active=1, no soft-deleted).
     *
     * Antes de eliminar cada cliente borra en cascada TODOS sus lazos:
     *   license_documents, license_notifications,
     *   income_licenses, income_payments, income_advances,
     *   incomes (incluye soft-deleted), licenses (incluye soft-deleted),
     *   ia_turns, ia_conversations,
     *   client_user_traceabilities, client_user_permission_assocs,
     *   client_user_assocs, client_users (solo los exclusivos),
     *   client_documents, client_chats,
     *   y finalmente clients.
     */
    public function up(): void
    {
        // ── 1. Identificar clientes a eliminar ───────────────────────────────
        $clientIds = DB::table('clients')
            ->where('active', 0)
            ->orWhereNotIn('id', function ($q) {
                $q->select('client_id')
                  ->from('licenses')
                  ->where('active', 1)
                  ->whereNull('deleted_at');
            })
            ->pluck('id')
            ->toArray();

        if (empty($clientIds)) {
            return; // Nada que eliminar
        }

        // ── 2. Recolectar IDs de hijos ───────────────────────────────────────
        $licenseIds = DB::table('licenses')
            ->whereIn('client_id', $clientIds)
            ->pluck('id')
            ->toArray();

        $incomeIds = DB::table('incomes')
            ->whereIn('client_id', $clientIds)
            ->pluck('id')
            ->toArray();

        $iaConversationIds = DB::table('ia_conversations')
            ->whereIn('client_id', $clientIds)
            ->pluck('id')
            ->toArray();

        // client_users EXCLUSIVOS de estos clientes (no vinculados a otros)
        $sharedClientUserIds = DB::table('client_user_assocs')
            ->whereNotIn('client_id', $clientIds)
            ->pluck('client_user_id')
            ->toArray();

        $exclusiveClientUserIds = DB::table('client_user_assocs')
            ->whereIn('client_id', $clientIds)
            ->whereNotIn('client_user_id', $sharedClientUserIds)
            ->pluck('client_user_id')
            ->unique()
            ->toArray();

        // ── 3. Borrar en orden (FK checks desactivados por seguridad) ────────
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Hijos de licenses
        if (!empty($licenseIds)) {
            DB::table('license_documents')->whereIn('license_id', $licenseIds)->delete();
            DB::table('license_notifications')->whereIn('license_id', $licenseIds)->delete();
            DB::table('income_licenses')->whereIn('license_id', $licenseIds)->delete();
        }

        // Hijos de incomes
        if (!empty($incomeIds)) {
            DB::table('income_licenses')->whereIn('income_id', $incomeIds)->delete();
            DB::table('income_payments')->whereIn('income_id', $incomeIds)->delete();
            DB::table('income_advances')->whereIn('income_id', $incomeIds)->delete();
        }

        // incomes y licenses (incluye soft-deleted)
        DB::table('incomes')->whereIn('client_id', $clientIds)->delete();
        DB::table('licenses')->whereIn('client_id', $clientIds)->delete();

        // Hijos de ia_conversations
        if (!empty($iaConversationIds)) {
            DB::table('ia_turns')->whereIn('ia_conversation_id', $iaConversationIds)->delete();
        }
        DB::table('ia_conversations')->whereIn('client_id', $clientIds)->delete();

        // client_users exclusivos y sus hijos
        if (!empty($exclusiveClientUserIds)) {
            DB::table('client_user_traceabilities')
                ->whereIn('client_user_id', $exclusiveClientUserIds)->delete();
            DB::table('client_user_permission_assocs')
                ->whereIn('client_user_id', $exclusiveClientUserIds)->delete();
        }
        DB::table('client_user_assocs')->whereIn('client_id', $clientIds)->delete();
        if (!empty($exclusiveClientUserIds)) {
            DB::table('client_users')->whereIn('id', $exclusiveClientUserIds)->delete();
        }

        // Resto de hijos directos de clients
        DB::table('client_documents')->whereIn('client_id', $clientIds)->delete();

        // client_chats usa client_id como string
        DB::table('client_chats')
            ->whereIn('client_id', array_map('strval', $clientIds))
            ->delete();

        // Finalmente: clients
        DB::table('clients')->whereIn('id', $clientIds)->delete();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Irreversible — los registros eliminados no pueden restaurarse.
     */
    public function down(): void
    {
        //
    }
};
