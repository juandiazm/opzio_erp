<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\user;
use App\Models\user_permission;
use App\Models\user_permission_assoc;

class AddDefaultUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $user = new user();
            $user->unique_id = '8fd31ad1-d311-409c-9659-ecb9e8259d1b';
            $user->name = 'Juan Carlos';
            $user->lastname = 'Diaz Mosquera';
            $user->username = 'juandiazm';
            $user->email = 'juandiazm@opzio.com.co';
            $user->identification = '1018468726';
            $user->password = '$2y$10$aWsQTXMqpIloPvQmS5CNhOt6BwhmvW2yRSgCG.vCayn336TVohtfG';
            $user->photo = '';
            $user->color = '#000000';
            $user->save();
            $user_permissions = user_permission::all();
            foreach($user_permissions as $permission){
                $assoc = new user_permission_assoc();
                $assoc->user_id = $user->id;
                $assoc->user_permission_id = $permission->id;
                $assoc->save();
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
        $user = user::where('username', 'juandiazm')->first();
        $user->delete();
    }
}
