<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class HomologateServersPermission extends Migration
{
    public function up()
    {
        $serversPermission = DB::table('user_permissions')
            ->where('url', 'admin/servers/')
            ->first();
        $legacyPermission = DB::table('user_permissions')
            ->where('url', 'admin/observability/')
            ->first();

        if ($legacyPermission && $serversPermission && $legacyPermission->id !== $serversPermission->id) {
            $legacyUserIds = DB::table('user_permission_assocs')
                ->where('user_permission_id', $legacyPermission->id)
                ->pluck('user_id');

            foreach ($legacyUserIds as $userId) {
                $alreadyAssociated = DB::table('user_permission_assocs')
                    ->where('user_id', $userId)
                    ->where('user_permission_id', $serversPermission->id)
                    ->exists();

                if (! $alreadyAssociated) {
                    DB::table('user_permission_assocs')->insert([
                        'user_id' => $userId,
                        'user_permission_id' => $serversPermission->id,
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

        if (! $serversPermission && $legacyPermission) {
            $serversPermissionId = $legacyPermission->id;
            DB::table('user_permissions')
                ->where('id', $serversPermissionId)
                ->update([
                    'name' => 'Servidores',
                    'url' => 'admin/servers/',
                    'updated_at' => now(),
                ]);
        } elseif (! $serversPermission) {
            $serversPermissionId = DB::table('user_permissions')->insertGetId([
                'name' => 'Servidores',
                'url' => 'admin/servers/',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $serversPermissionId = $serversPermission->id;
            DB::table('user_permissions')
                ->where('id', $serversPermissionId)
                ->update([
                    'name' => 'Servidores',
                    'url' => 'admin/servers/',
                    'updated_at' => now(),
                ]);
        }

        foreach (DB::table('users')->pluck('id') as $userId) {
            $alreadyAssociated = DB::table('user_permission_assocs')
                ->where('user_id', $userId)
                ->where('user_permission_id', $serversPermissionId)
                ->exists();

            if (! $alreadyAssociated) {
                DB::table('user_permission_assocs')->insert([
                    'user_id' => $userId,
                    'user_permission_id' => $serversPermissionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down()
    {
        DB::table('user_permissions')
            ->where('url', 'admin/servers/')
            ->update([
                'name' => 'Observability',
                'url' => 'admin/observability/',
                'updated_at' => now(),
            ]);
    }
}