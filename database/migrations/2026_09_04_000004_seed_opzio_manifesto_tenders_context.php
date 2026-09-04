<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const TENANT_ID = 'opzio';

    private const LEGACY_DESCRIPTION = 'OPZIO desarrolla soluciones de software, automatizacion, integraciones, analitica, inteligencia artificial, infraestructura cloud, soporte y ciberseguridad.';

    public function up(): void
    {
        $context = DB::table('tenders_contexts')
            ->where('tenant_id', self::TENANT_ID)
            ->first();

        $manifesto = $this->manifesto();

        if ($context === null) {
            DB::table('tenders_contexts')->insert([
                ...$manifesto,
                'tenant_id' => self::TENANT_ID,
                'company_name' => 'OPZIO S.A.S.',
                'version' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return;
        }

        $isInitialContext = (int) $context->version <= 1
            || $context->description === self::LEGACY_DESCRIPTION;

        if (! $isInitialContext) {
            return;
        }

        DB::table('tenders_contexts')
            ->where('tenant_id', self::TENANT_ID)
            ->update([
                ...$manifesto,
                'version' => max(2, (int) $context->version + 1),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        $context = DB::table('tenders_contexts')
            ->where('tenant_id', self::TENANT_ID)
            ->first();

        if ($context === null || $context->description !== $this->manifesto()['description']) {
            return;
        }

        DB::table('tenders_contexts')
            ->where('tenant_id', self::TENANT_ID)
            ->update([
                'description' => self::LEGACY_DESCRIPTION,
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
                'updated_at' => now(),
            ]);
    }

    private function manifesto(): array
    {
        return [
            'description' => implode(' ', [
                'OPZIO nace de la palabra optimizacion y representa eficiencia, precision, mejora continua e innovacion.',
                'Ayudamos a organizaciones a transformar su operacion mediante tecnologia, automatizacion, inteligencia artificial y estrategias digitales.',
                'Mision: impulsar el crecimiento de personas y organizaciones mediante soluciones tecnologicas, estrategias digitales e innovacion continua, optimizando procesos para hacerlos mas eficientes, inteligentes y sostenibles.',
                'Vision: ser una empresa referente en Latinoamerica en transformacion digital, automatizacion, inteligencia artificial y desarrollo de soluciones tecnologicas, reconocida por generar impacto real en el crecimiento de las organizaciones.',
                'Pilares: tecnologia; inteligencia y automatizacion; crecimiento empresarial; experiencias digitales.',
                'Valores: optimizacion, innovacion, eficiencia, precision, escalabilidad, aprendizaje continuo, calidad, transparencia y orientacion al cliente.',
                'Promesa: cada proyecto debe responder como ayudara a que nuestro cliente crezca.',
                'Metodologia: comprender, optimizar, construir, automatizar, medir y evolucionar.',
                'OPZIO convierte la tecnologia en una ventaja competitiva con procesos inteligentes y crecimiento sostenible. Basado en el objeto social de OPZIO S.A.S.',
            ]),
            'services' => json_encode([
                'Desarrollo de software y plataformas SaaS',
                'Inteligencia artificial y automatizacion',
                'Consultoria tecnologica',
                'Marketing estrategico y publicidad',
                'Produccion multimedia y UX/UI',
                'Comercio electronico, analitica y capacitacion',
                'Transformacion digital',
            ]),
            'technologies' => json_encode([
                'Tecnologia',
                'Inteligencia y automatizacion',
                'Crecimiento empresarial',
                'Experiencias digitales',
                'Desarrollo de plataformas SaaS',
                'Integraciones y APIs',
                'Analitica de datos',
                'UX/UI',
                'Produccion multimedia',
                'Comercio electronico',
                'Marketing digital',
                'Capacitacion tecnologica',
                'Optimizacion',
                'Innovacion',
                'Eficiencia',
                'Precision',
                'Escalabilidad',
                'Aprendizaje continuo',
                'Calidad',
                'Transparencia',
                'Orientacion al cliente',
                'Comprender',
                'Optimizar',
                'Construir',
                'Automatizar',
                'Medir',
                'Evolucionar',
            ]),
            'sectors' => json_encode([
                'Gobierno',
                'Educacion',
                'Salud',
                'Empresas',
                'Organizaciones publicas y privadas',
                'Transformacion digital',
                'Crecimiento empresarial',
            ]),
            'geography' => json_encode(['Colombia', 'Latinoamerica', 'Remoto', 'Hibrido']),
            'excluded_terms' => json_encode([
                'Hardware puro',
                'Cableado',
                'Suministro sin software',
                'Obra civil sin componente tecnologico',
            ]),
        ];
    }
};