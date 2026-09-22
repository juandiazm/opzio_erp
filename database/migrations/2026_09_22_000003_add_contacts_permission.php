<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permission = DB::table('user_permissions')->where('url', 'admin/contacts/')->first();
        $permissionId = $permission?->id;
        if ($permissionId === null) {
            $permissionId = DB::table('user_permissions')->insertGetId([
                'name' => 'Modulo Contactos',
                'url' => 'admin/contacts/',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (DB::table('users')->pluck('id') as $userId) {
            DB::table('user_permission_assocs')->updateOrInsert(
                ['user_id' => $userId, 'user_permission_id' => $permissionId],
                ['created_at' => now(), 'updated_at' => now()],
            );
        }
    }

    public function down(): void
    {
        $permission = DB::table('user_permissions')->where('url', 'admin/contacts/')->first();
        if ($permission === null) {
            return;
        }

        DB::table('user_permission_assocs')->where('user_permission_id', $permission->id)->delete();
        DB::table('user_permissions')->where('id', $permission->id)->delete();
    }
};