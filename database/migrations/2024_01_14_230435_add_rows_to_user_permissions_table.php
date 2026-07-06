<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRowsToUserPermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_permissions', function (Blueprint $table) {
            //add eps permissions
            DB::table('user_permissions')->insert([
                'name' => 'CRUD EPS',
                'url' => 'admin/eps/',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            //add arl permissions
            DB::table('user_permissions')->insert([
                'name' => 'CRUD ARL',
                'url' => 'admin/arl/',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            //add afp permissions
            DB::table('user_permissions')->insert([
                'name' => 'CRUD AFP',
                'url' => 'admin/afp/',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
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
            //delete eps permissions
            DB::table('user_permissions')->where('url', 'admin/eps/')->delete();
            //delete arl permissions
            DB::table('user_permissions')->where('url', 'admin/arl/')->delete();
            //delete afp permissions
            DB::table('user_permissions')->where('url', 'admin/afp/')->delete();
            
        });
    }
}
