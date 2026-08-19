<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outcome_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });

        Schema::table('outcomes', function (Blueprint $table) {
            $table->foreignId('outcome_type_id')
                ->nullable()
                ->after('unique_id')
                ->constrained('outcome_types')
                ->restrictOnDelete();
        });

        Schema::table('outcomes', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }

    public function down(): void
    {
        Schema::table('outcomes', function (Blueprint $table) {
            $table->dropForeign(['outcome_type_id']);
            $table->dropColumn('outcome_type_id');
            $table->tinyInteger('type')->default(-1)->after('unique_id');
        });

        Schema::dropIfExists('outcome_types');
    }
};