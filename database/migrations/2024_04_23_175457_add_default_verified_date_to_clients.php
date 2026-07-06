<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use App\Models\client;
use Carbon\Carbon;

class AddDefaultVerifiedDateToClients extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clients', function (Blueprint $table) {
            //set default value for verified_date if client is verified
            $clients = client::where('verified', 1)->get();
            foreach($clients as $client){
                $client->verified_date = Carbon::now();
                $client->save();
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
        Schema::table('clients', function (Blueprint $table) {
            //set default value for verified_date if client is verified
            $clients = client::where('verified', 1)->get();
            foreach($clients as $client){
                $client->verified_date = null;
                $client->save();
            }
        });
    }
}
