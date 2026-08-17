<?php

require_once __DIR__ . '/2026_08_14_000000_create_observability_tables.php';

use Illuminate\Support\Facades\Schema;

class CreateServersTables extends CreateObservabilityTables
{
    public function up()
    {
        if (Schema::hasTable('servers_hosts')) {
            return;
        }

        parent::up();
    }
}