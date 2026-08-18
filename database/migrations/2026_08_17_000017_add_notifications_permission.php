<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddNotificationsPermission extends Migration
{
    public function up()
    {
        $permission = DB::table('user_permissions')->where('url', 'admin/notifications/')->first();
        $permissionId = $permission?->id;
        if (!$permissionId) {
            $permissionId = DB::table('user_permissions')->insertGetId([
                'name' => 'Modulo Notificaciones',
                'url' => 'admin/notifications/',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (DB::table('users')->pluck('id') as $userId) {
            $exists = DB::table('user_permission_assocs')
                ->where('user_id', $userId)
                ->where('user_permission_id', $permissionId)
                ->exists();
            if (!$exists) {
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
        $permission = DB::table('user_permissions')->where('url', 'admin/notifications/')->first();
        if ($permission) {
            DB::table('user_permission_assocs')->where('user_permission_id', $permission->id)->delete();
            DB::table('user_permissions')->where('id', $permission->id)->delete();
        }
    }
}