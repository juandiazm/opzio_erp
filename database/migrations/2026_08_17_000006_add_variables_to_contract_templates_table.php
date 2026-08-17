<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVariablesToContractTemplatesTable extends Migration
{
    public function up()
    {
        Schema::table('contract_templates', function (Blueprint $table) {
            $table->json('variables')->nullable()->after('content');
        });
    }

    public function down()
    {
        Schema::table('contract_templates', function (Blueprint $table) {
            $table->dropColumn('variables');
        });
    }
}