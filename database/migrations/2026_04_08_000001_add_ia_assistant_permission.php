<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIaAssistantPermission extends Migration
{
    public function up()
    {
        Schema::table('user_permissions', function (Blueprint $table) {
            DB::table('user_permissions')->insert([
                'name' => 'Modulo IA Assistant',
                'url' => 'admin/ia-assistant/',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        });
    }

    public function down()
    {
        Schema::table('user_permissions', function (Blueprint $table) {
            DB::table('user_permissions')->where('url', 'admin/ia-assistant/')->delete();
        });
    }
}
