<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('licenses')->update(['active' => false]);
    }

    public function down(): void
    {
        DB::table('licenses')->update(['active' => true]);
    }
};
