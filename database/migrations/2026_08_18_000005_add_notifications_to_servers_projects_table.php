<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNotificationsToServersProjectsTable extends Migration
{
    public function up()
    {
        Schema::table('servers_projects', function (Blueprint $table) {
            $table->foreignId('client_id')
                ->nullable()
                ->after('host_id')
                ->constrained('clients')
                ->nullOnDelete();
            $table->boolean('notifications_enabled')->default(false)->after('enabled');
        });

        Schema::create('servers_project_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('servers_projects')->cascadeOnDelete();
            $table->string('source_type', 40);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_key', 255);
            $table->string('channel', 20);
            $table->string('value', 255);
            $table->string('recipient_name', 255)->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'channel', 'value']);
            $table->index(['project_id', 'source_key']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('servers_project_notifications');

        Schema::table('servers_projects', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn(['client_id', 'notifications_enabled']);
        });
    }
}
