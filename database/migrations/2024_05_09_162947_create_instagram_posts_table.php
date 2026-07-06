<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInstagramPostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('instagram_posts', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id');
            $table->bigInteger('user_id')->unsigned()->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('no action');
            $table->string('user_name', 200);
            $table->string('audio_name')->nullable();
            $table->longText('subject');
            $table->longText('caption');
            $table->string('collaborators', 200)->nullable();
            $table->string('children',1000)->nullable();
            $table->string('cover_url', 1000)->nullable();
            $table->string('image_url', 1000)->nullable();
            $table->boolean('is_carousel_item')->default(0);
            $table->string('location_id', 100)->nullable();
            $table->string('media_type', 50)->nullable();
            $table->string('product_tags', 200)->nullable();
            $table->boolean('share_to_feed')->default(0);
            $table->integer('thumb_offset')->nullable();
            $table->longText('user_tags')->nullable();
            $table->string('video_url', 1000)->nullable();
            $table->string('ig_container_id', 100)->nullable();
            $table->boolean('authorized')->default(0);
            $table->boolean('published')->default(0);
            $table->dateTime('published_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('instagram_posts');
    }
}
