<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outcomes', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_id')->nullable()->after('provider_id');
            $table->foreign('employee_id')->references('id')->on('employees');

            $table->unsignedBigInteger('department_id')->nullable()->after('employee_id');
            $table->foreign('department_id')->references('id')->on('departments');

            $table->unsignedBigInteger('client_id')->nullable()->after('department_id');
            $table->foreign('client_id')->references('id')->on('clients');
        });
    }

    public function down(): void
    {
        Schema::table('outcomes', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropForeign(['department_id']);
            $table->dropForeign(['client_id']);
            $table->dropColumn(['employee_id', 'department_id', 'client_id']);
        });
    }
};