<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateServiceContractTemplateForLicense extends Migration
{
    public function up()
    {
        $template = DB::table('contract_templates')
            ->where('name', 'Contrato de prestación de servicios - infraestructura y soporte')
            ->first();

        if (!$template) {
            return;
        }

        $variables = json_decode((string) ($template->variables ?? '[]'), true);
        $variables = is_array($variables) ? $variables : [];
        $removedKeys = array_flip([
            'custom.contract_duration',
            'custom.start_date_text',
            'custom.end_date_text',
        ]);
        $variables = array_values(array_filter($variables, function ($variable) use ($removedKeys) {
            if (!is_array($variable)) {
                return false;
            }

            $key = strtolower(trim((string) ($variable['key'] ?? '')));
            return !isset($removedKeys[$key]);
        }));

        $content = (string) $template->content;
        $content = str_replace(
            ['{{custom.contract_duration}}', '{{custom.start_date_text}}', '{{custom.end_date_text}}'],
            ['{{license.recurrence_string}}', '{{contract.start_date}}', '{{contract.end_date}}'],
            $content
        );
        $content = str_replace(
            'para el año {{custom.payment_year}} el valor de la página es de <strong>SEIS MILLONES DE PESOS M/CTE ($6.000.000)</strong>',
            'para el año {{custom.payment_year}} el valor de la licencia es de <strong>$ {{license.value_string}} M/CTE</strong>',
            $content
        );

        $endDateRow = <<<'HTML'
            <tr>
                <td style="border: 1px solid #222; background-color: #d0cece; padding: 6px; font-weight: bold;">FECHA DE FINALIZACIÓN:</td>
                <td style="border: 1px solid #222; padding: 6px;">{{contract.end_date}}</td>
            </tr>
HTML;
        $licenseRows = <<<'HTML'
            <tr>
                <td style="border: 1px solid #222; background-color: #d0cece; padding: 6px; font-weight: bold;">VALOR:</td>
                <td style="border: 1px solid #222; padding: 6px;">$ {{license.value_string}} M/CTE</td>
            </tr>
            <tr>
                <td style="border: 1px solid #222; background-color: #d0cece; padding: 6px; font-weight: bold;">RECURRENCIA:</td>
                <td style="border: 1px solid #222; padding: 6px;">{{license.type_string}} - {{license.recurrence_string}}</td>
            </tr>
HTML;
        if (!str_contains($content, '{{license.type_string}} - {{license.recurrence_string}}')) {
            $content = str_replace($endDateRow, $endDateRow."\n".$licenseRows, $content);
        }

        DB::table('contract_templates')
            ->where('id', $template->id)
            ->update([
                'content' => $content,
                'variables' => json_encode($variables, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'version' => ((int) $template->version) + 1,
                'updated_at' => now(),
            ]);
    }

    public function down()
    {
        $template = DB::table('contract_templates')
            ->where('name', 'Contrato de prestación de servicios - infraestructura y soporte')
            ->first();

        if (!$template) {
            return;
        }

        $variables = json_decode((string) ($template->variables ?? '[]'), true);
        $variables = is_array($variables) ? $variables : [];
        $variables[] = ['key' => 'custom.contract_duration', 'label' => 'Duración visible', 'type' => 'text', 'default_value' => 'SEIS (6) MESES Y CUATRO (4) DÍAS', 'required' => true];
        $variables[] = ['key' => 'custom.start_date_text', 'label' => 'Fecha de inicio visible', 'type' => 'text', 'default_value' => '01 DE AGOSTO DE 2026', 'required' => true];
        $variables[] = ['key' => 'custom.end_date_text', 'label' => 'Fecha de finalización visible', 'type' => 'text', 'default_value' => '05 DE FEBRERO DE 2027', 'required' => true];

        $content = str_replace(
            ['{{license.recurrence_string}}', '{{contract.start_date}}', '{{contract.end_date}}'],
            ['{{custom.contract_duration}}', '{{custom.start_date_text}}', '{{custom.end_date_text}}'],
            (string) $template->content
        );
        $content = str_replace(
            'para el año {{custom.payment_year}} el valor de la licencia es de <strong>$ {{license.value_string}} M/CTE</strong>',
            'para el año {{custom.payment_year}} el valor de la página es de <strong>SEIS MILLONES DE PESOS M/CTE ($6.000.000)</strong>',
            $content
        );

        $licenseRows = <<<'HTML'
            <tr>
                <td style="border: 1px solid #222; background-color: #d0cece; padding: 6px; font-weight: bold;">VALOR:</td>
                <td style="border: 1px solid #222; padding: 6px;">$ {{license.value_string}} M/CTE</td>
            </tr>
            <tr>
                <td style="border: 1px solid #222; background-color: #d0cece; padding: 6px; font-weight: bold;">RECURRENCIA:</td>
                <td style="border: 1px solid #222; padding: 6px;">{{license.type_string}} - {{license.recurrence_string}}</td>
            </tr>
HTML;
        $content = str_replace("\n".$licenseRows, '', $content);

        DB::table('contract_templates')
            ->where('id', $template->id)
            ->update([
                'content' => $content,
                'variables' => json_encode($variables, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'version' => max(1, ((int) $template->version) - 1),
                'updated_at' => now(),
            ]);
    }
}