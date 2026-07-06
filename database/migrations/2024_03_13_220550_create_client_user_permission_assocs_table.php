<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientUserPermissionAssocsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('client_user_permission_assocs', function (Blueprint $table) {
            $table->id();
            $table->foreignId("client_user_id")->constrained("client_users");
            $table->foreignId("client_user_permission_id")->constrained("client_user_permissions");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('client_user_permission_assocs');
    }
}
