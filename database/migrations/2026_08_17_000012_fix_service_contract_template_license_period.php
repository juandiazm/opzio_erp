<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class FixServiceContractTemplateLicensePeriod extends Migration
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

        $content = str_replace(
            [
                '{{license.last_billing_date}}',
                '{{license.next_billing_date}}',
                '{{license.type_string}} - {{license.recurrence_months}} meses y {{license.days_to_expire}} días',
                '{{license.type_string}} - {{license.recurrence_months}} meses',
                '{{license.recurrence_months}} meses y {{license.days_to_expire}} días',
            ],
            [
                '{{contract.start_date}}',
                '{{contract.end_date}}',
                '{{license.type_string}} - {{license.recurrence_string}}',
                '{{license.type_string}} - {{license.recurrence_string}}',
                '{{license.recurrence_string}}',
            ],
            (string) $template->content
        );

        if ($content === (string) $template->content) {
            return;
        }

        DB::table('contract_templates')
            ->where('id', $template->id)
            ->update([
                'content' => $content,
                'version' => ((int) $template->version) + 1,
                'updated_at' => now(),
            ]);
    }

    public function down()
    {
        $template = $this->serviceTemplate();

        if (!$template) {
            return;
        }

        $content = str_replace(
            [
                '{{contract.start_date}}',
                '{{contract.end_date}}',
                '{{license.type_string}} - {{license.recurrence_string}}',
                '{{license.recurrence_string}}',
            ],
            [
                '{{license.last_billing_date}}',
                '{{license.next_billing_date}}',
                '{{license.type_string}} - {{license.recurrence_months}} meses y {{license.days_to_expire}} días',
                '{{license.recurrence_months}} meses y {{license.days_to_expire}} días',
            ],
            (string) $template->content
        );

        if ($content === (string) $template->content) {
            return;
        }

        DB::table('contract_templates')
            ->where('id', $template->id)
            ->update([
                'content' => $content,
                'version' => max(1, ((int) $template->version) - 1),
                'updated_at' => now(),
            ]);
    }
}
