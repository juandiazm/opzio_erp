<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AdddActiveRowToClientUserAssocs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_user_assocs', function (Blueprint $table) {
            $table->boolean('active')->default(1)->after('client_user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('client_user_assocs', function (Blueprint $table) {
            $table->dropColumn('active');
        });
    }
}
