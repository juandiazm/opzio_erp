<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCountryIdSectorIdOnClients extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clients', function (Blueprint $table) {
            //Add country_id and sector_id
            $table->unsignedBigInteger('country_id')->nullable()->after('photo');
            $table->foreign('country_id')->references('id')->on('countries');
            $table->unsignedBigInteger('sector_id')->nullable()->after('country_id');
            $table->foreign('sector_id')->references('id')->on('sectors');
            //Remove country and sector columns
            $table->dropColumn('country');
            $table->dropColumn('sector');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('clients', function (Blueprint $table) {
            //Add country and sector columns
            $table->string('country')->nullable()->after('photo');
            $table->string('sector')->nullable()->after('country');
            //Remove country_id and sector_id
            $table->dropForeign(['country_id']);
            $table->dropColumn('country_id');
            $table->dropForeign(['sector_id']);
            $table->dropColumn('sector_id');
        });
    }
}
