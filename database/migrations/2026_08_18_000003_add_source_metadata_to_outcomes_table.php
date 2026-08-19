<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outcomes', function (Blueprint $table) {
            $table->string('source', 32)->nullable()->after('unique_id');
            $table->string('source_identifier', 150)->nullable()->after('source');
            $table->string('source_hash', 64)->nullable()->after('source_identifier');
            $table->string('classification_source', 16)->nullable()->after('source_hash');
            $table->decimal('classification_confidence', 5, 4)->nullable()->after('classification_source');
            $table->index(['source', 'source_identifier'], 'outcomes_source_identifier_index');
            $table->unique('source_hash', 'outcomes_source_hash_unique');
        });
    }

    public function down(): void
    {
        Schema::table('outcomes', function (Blueprint $table) {
            $table->dropUnique('outcomes_source_hash_unique');
            $table->dropIndex('outcomes_source_identifier_index');
            $table->dropColumn(['source', 'source_identifier', 'source_hash', 'classification_source', 'classification_confidence']);
        });
    }
};