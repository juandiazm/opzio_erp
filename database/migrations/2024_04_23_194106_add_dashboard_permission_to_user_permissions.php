<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDashboardPermissionToUserPermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_permissions', function (Blueprint $table) {
            //add dashboard permissions
            DB::table('user_permissions')->insert([
                'name' => 'Dashboard',
                'url' => 'admin/dashboard/',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            //add permision to all current users
            $permission = DB::table('user_permissions')->where('url', 'admin/dashboard/')->first();
            $users = DB::table('users')->get();
            foreach ($users as $user) {
                DB::table('user_permission_assocs')->insert([
                    'user_id' => $user->id,
                    'user_permission_id' => $permission->id,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_permissions', function (Blueprint $table) {
            //delete dashboard permissions
            DB::table('user_permissions')->where('url', 'admin/dashboard/')->delete();
        });
    }
}
