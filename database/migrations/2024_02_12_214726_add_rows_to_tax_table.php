<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRowsTotaxTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('taxes', function (Blueprint $table) {
            //IVA
            $register = new \App\Models\tax();
            $register->name = 'IVA';
            $register->value = 0.19;
            $register->save();
            //ReteICA
            $register = new \App\Models\tax();
            $register->name = 'ReteICA';
            $register->value = 0.03;
            $register->save();
            //ReteFuente
            $register = new \App\Models\tax();
            $register->name = 'ReteFuente';
            $register->value = 0.15;
            $register->save();
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('taxes', function (Blueprint $table) {
            //IVA
            \App\Models\tax::where('name', 'IVA')->delete();
            //ReteICA
            \App\Models\tax::where('name', 'ReteICA')->delete();
            //ReteFuente
            \App\Models\tax::where('name', 'ReteFuente')->delete();
            
        });
    }
}
