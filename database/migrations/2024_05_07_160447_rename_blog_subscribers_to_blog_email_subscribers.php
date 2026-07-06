<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameBlogSubscribersToBlogEmailSubscribers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('blog_subscribers', function (Blueprint $table) {
            $table->rename('blog_email_subscribers');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('blog_email_subscribers', function (Blueprint $table) {
            $table->rename('blog_subscribers');
        });
    }
}
