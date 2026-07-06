<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDefaultUserPermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_permissions', function (Blueprint $table) {
            //add user permissions
            DB::table('user_permissions')->insert([
                'name' => 'Modulo Usuarios',
                'url' => 'admin/users/',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            //add client permissions
            DB::table('user_permissions')->insert([
                'name' => 'Modulo Clientes',
                'url' => 'admin/clients/',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            //add employee permissions
            DB::table('user_permissions')->insert([
                'name' => 'Modulo Usuarios',
                'url' => 'admin/employees/',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            //add provider permissions
            DB::table('user_permissions')->insert([
                'name' => 'Modulo Proveedores',
                'url' => 'admin/providers/',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            //add department permissions
            DB::table('user_permissions')->insert([
                'name' => 'Modulo Departamentos',
                'url' => 'admin/departments/',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            //add license permissions
            DB::table('user_permissions')->insert([
                'name' => 'Modulo Licencias',
                'url' => 'admin/licenses/',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            //add income permissions
            DB::table('user_permissions')->insert([
                'name' => 'Modulo Ingresos',
                'url' => 'admin/incomes/',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            //add outcome permissions
            DB::table('user_permissions')->insert([
                'name' => 'Modulo Egresos',
                'url' => 'admin/outcomes/',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            //add report permissions
            DB::table('user_permissions')->insert([
                'name' => 'Modulo Reportes',
                'url' => 'admin/reports/',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            //add web-page permissions
            DB::table('user_permissions')->insert([
                'name' => 'Modulo Página web',
                'url' => 'admin/web-pages/',
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
        //Drob added rows
        DB::table('user_permissions')->Truncate();
    }
}
