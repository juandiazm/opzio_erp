<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permission = DB::table('user_permissions')->where('url', 'admin/github/')->first();
        $permissionId = $permission?->id;
        if ($permissionId === null) {
            $permissionId = DB::table('user_permissions')->insertGetId([
                'name' => 'Modulo GitHub',
                'url' => 'admin/github/',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (DB::table('users')->pluck('id') as $userId) {
            DB::table('user_permission_assocs')->updateOrInsert(
                ['user_id' => $userId, 'user_permission_id' => $permissionId],
                ['updated_at' => now(), 'created_at' => now()],
            );
        }
    }

    public function down(): void
    {
        $permission = DB::table('user_permissions')->where('url', 'admin/github/')->first();
        if ($permission === null) {
            return;
        }

        DB::table('user_permission_assocs')->where('user_permission_id', $permission->id)->delete();
        DB::table('user_permissions')->where('id', $permission->id)->delete();
    }
};