<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClientUserPermissionsRows extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_user_permissions', function (Blueprint $table) {
            //add my companies permissions
            DB::table('client_user_permissions')->insert([
                'name' => 'Mis Empresas',
                'url' => 'client/my-companies/',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            //add users permissions
            DB::table('client_user_permissions')->insert([
                'name' => 'Modulo Usuarios',
                'url' => 'client/users/',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            //add licenses permissions
            DB::table('client_user_permissions')->insert([
                'name' => 'Modulo Licencias',
                'url' => 'client/licenses/',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            //add payments permissions
            DB::table('client_user_permissions')->insert([
                'name' => 'Modulo Pagos',
                'url' => 'client/payments/',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            //add traceability permissions
            DB::table('client_user_permissions')->insert([
                'name' => 'Modulo Trazabilidad',
                'url' => 'client/traceability/',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            //assoc all permissions to all client_users
            $client_users = DB::table('client_users')->get();
            $client_user_permissions = DB::table('client_user_permissions')->get();
            foreach($client_users as $client_user){
                foreach($client_user_permissions as $client_user_permission){
                    DB::table('client_user_permission_assocs')->insert([
                        'client_user_id' => $client_user->id,
                        'client_user_permission_id' => $client_user_permission->id,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                }
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
        Schema::table('client_user_permissions', function (Blueprint $table) {
            //delete my companies permissions
            DB::table('client_user_permissions')->where('url', 'client/my-companies/')->delete();
            //delete users permissions
            DB::table('client_user_permissions')->where('url', 'client/users/')->delete();
            //delete licenses permissions
            DB::table('client_user_permissions')->where('url', 'client/licenses/')->delete();
            //delete payments permissions
            DB::table('client_user_permissions')->where('url', 'client/payments/')->delete();
            //delete traceability permissions
            DB::table('client_user_permissions')->where('url', 'client/traceability/')->delete();
            //delete all permissions from all client_users
            DB::table('client_user_permission_assocs')->truncate();
            //enable truncate on client_user_permissions
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('client_user_permissions')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            
        });
    }
}
