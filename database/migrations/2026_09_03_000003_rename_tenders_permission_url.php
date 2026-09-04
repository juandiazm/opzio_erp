<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class RenameTendersPermissionUrl extends Migration
{
    public function up()
    {
        $legacyPermission = DB::table('user_permissions')
            ->where('url', 'admin/licitaciones/')
            ->first();
        $currentPermission = DB::table('user_permissions')
            ->where('url', 'admin/tenders/')
            ->first();

        if (! $legacyPermission) {
            return;
        }

        if (! $currentPermission) {
            DB::table('user_permissions')
                ->where('id', $legacyPermission->id)
                ->update([
                    'name' => 'Licitaciones',
                    'url' => 'admin/tenders/',
                    'updated_at' => now(),
                ]);

            return;
        }

        $legacyAssociations = DB::table('user_permission_assocs')
            ->where('user_permission_id', $legacyPermission->id)
            ->pluck('user_id');

        foreach ($legacyAssociations as $userId) {
            $exists = DB::table('user_permission_assocs')
                ->where('user_id', $userId)
                ->where('user_permission_id', $currentPermission->id)
                ->exists();

            if (! $exists) {
                DB::table('user_permission_assocs')->insert([
                    'user_id' => $userId,
                    'user_permission_id' => $currentPermission->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        DB::table('user_permission_assocs')
            ->where('user_permission_id', $legacyPermission->id)
            ->delete();
        DB::table('user_permissions')
            ->where('id', $legacyPermission->id)
            ->delete();
    }

    public function down()
    {
        $permission = DB::table('user_permissions')
            ->where('url', 'admin/tenders/')
            ->first();

        if ($permission) {
            DB::table('user_permissions')
                ->where('id', $permission->id)
                ->update([
                    'name' => 'Licitaciones',
                    'url' => 'admin/licitaciones/',
                    'updated_at' => now(),
                ]);
        }
    }
}
