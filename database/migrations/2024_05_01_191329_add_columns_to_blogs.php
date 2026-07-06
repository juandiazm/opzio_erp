<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToBlogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->string('unique_id', 50)->unique()->after('id');
            $table->string('keywords', 200)->nullable()->after('type');
            $table->string('reduce_title', 200)->nullable()->after('title');
            $table->boolean('approved')->default(0)->after('reduce_title');
            /*update title to 300*/
            $table->string('title', 300)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->dropColumn('unique_id');
            $table->dropColumn('keywords');
            $table->dropColumn('reduce_title');
            $table->dropColumn('approved');
            
        });
    }
}
