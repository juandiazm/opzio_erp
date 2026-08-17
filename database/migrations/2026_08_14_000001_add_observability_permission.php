<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddServersPermission extends Migration
{
    public function up()
    {
        $permission = DB::table('user_permissions')
            ->where('url', 'admin/servers/')
            ->first();

        if (! $permission) {
            $permissionId = DB::table('user_permissions')->insertGetId([
                'name' => 'Servidores',
                'url' => 'admin/servers/',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $permissionId = $permission->id;
        }

        foreach (DB::table('users')->pluck('id') as $userId) {
            $exists = DB::table('user_permission_assocs')
                ->where('user_id', $userId)
                ->where('user_permission_id', $permissionId)
                ->exists();

            if (! $exists) {
                DB::table('user_permission_assocs')->insert([
                    'user_id' => $userId,
                    'user_permission_id' => $permissionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down()
    {
        $permission = DB::table('user_permissions')
            ->where('url', 'admin/servers/')
            ->first();

        if (! $permission) {
            return;
        }

        DB::table('user_permission_assocs')
            ->where('user_permission_id', $permission->id)
            ->delete();
        DB::table('user_permissions')->where('id', $permission->id)->delete();
    }
}
