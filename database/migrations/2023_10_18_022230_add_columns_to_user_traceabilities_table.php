<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToUserTraceabilitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_traceabilities', function (Blueprint $table) {
            $table->string('path')->nullable();
            $table->string('ip')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('payload')->nullable();
            $table->string('parameters')->nullable();
            $table->longtext('description')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_traceabilities', function (Blueprint $table) {
            $table->dropColumn('path');
            $table->dropColumn('ip');
            $table->dropColumn('user_agent');
            $table->dropColumn('payload');
            $table->dropColumn('parameters');
            $table->longtext('description')->change();
        });
    }
}
