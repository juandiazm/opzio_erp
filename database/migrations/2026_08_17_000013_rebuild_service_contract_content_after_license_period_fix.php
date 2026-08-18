<?php

use App\Models\contract;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class RebuildServiceContractContentAfterLicensePeriodFix extends Migration
{
    private function serviceTemplate()
    {
        return DB::table('contract_templates')
            ->whereIn('name', [
                'Contrato de prestación de servicios - infraestructura y soporte',
                'Contrato de prestación de servicios - Infraestructura, Soporte y ciberseguridad',
            ])
            ->first();
    }

    public function up()
    {
        $template = $this->serviceTemplate();
        if (!$template) {
            return;
        }

        $renderer = new class {
            use \App\traits\contracts_trait;

            public function rebuild($id)
            {
                $contract = contract::withTrashed()->find($id);
                if (!$contract) {
                    return;
                }

                $generationData = is_array($contract->generation_data) ? $contract->generation_data : [];
                $this->Contract_ApplyContractData($contract, [
                    'name' => $contract->name,
                    'subject' => $contract->subject,
                    'start_date' => $contract->start_date,
                    'end_date' => $contract->end_date,
                    'notes' => $contract->notes,
                    'status' => $contract->status,
                    'sync_license_recurrence' => $contract->sync_license_recurrence,
                    'custom_variables' => data_get($generationData, 'custom_variables', []),
                ], true);
                $contract->save();
            }
        };

        contract::withTrashed()
            ->where('contract_template_id', $template->id)
            ->whereNotNull('content')
            ->chunkById(100, function ($contracts) use ($renderer, $template) {
                foreach ($contracts as $contract) {
                    $generationData = is_array($contract->generation_data) ? $contract->generation_data : [];
                    $templateVersion = (int) data_get($generationData, 'template_version', 0);
                    $content = (string) $contract->content;
                    $needsRebuild = $templateVersion < (int) $template->version
                        || (str_contains($content, 'meses y ') && str_contains($content, 'días'));
                    if (!$needsRebuild) {
                        continue;
                    }

                    try {
                        $renderer->rebuild($contract->id);
                    } catch (\Throwable $e) {
                        info('RebuildServiceContractContentAfterLicensePeriodFix error: '.$e->getMessage(), [
                            'contract_id' => $contract->id,
                        ]);
                    }
                }
            });
    }

    public function down()
    {
    }
}
