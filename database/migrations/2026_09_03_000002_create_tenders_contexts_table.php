<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateTendersContextsTable extends Migration
{
    public function up()
    {
        Schema::create('tenders_contexts', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id', 100)->unique();
            $table->string('company_name', 255);
            $table->string('identification', 100)->nullable();
            $table->longText('description')->nullable();
            $table->json('services')->nullable();
            $table->json('technologies')->nullable();
            $table->json('sectors')->nullable();
            $table->json('geography')->nullable();
            $table->json('excluded_terms')->nullable();
            $table->decimal('min_contract_value', 15, 2)->nullable();
            $table->decimal('max_contract_value', 15, 2)->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        DB::table('tenders_contexts')->insert([
            'tenant_id' => 'opzio',
            'company_name' => 'OPZIO S.A.S.',
            'description' => 'OPZIO desarrolla soluciones de software, automatizacion, integraciones, analitica, inteligencia artificial, infraestructura cloud, soporte y ciberseguridad.',
            'services' => json_encode([
                'Desarrollo de software a la medida',
                'Plataformas web e integraciones',
                'Automatizacion de procesos',
                'Inteligencia artificial',
                'Infraestructura cloud y DevOps',
                'Ciberseguridad y soporte',
            ]),
            'technologies' => json_encode(['Python', 'Laravel', 'JavaScript', 'Cloud', 'IA']),
            'sectors' => json_encode(['Gobierno', 'Educacion', 'Salud', 'Empresas']),
            'geography' => json_encode(['Colombia', 'Remoto', 'Hibrido']),
            'excluded_terms' => json_encode(['Hardware puro', 'Cableado', 'Suministro sin software']),
            'version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('tenders_contexts');
    }
}
