<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSiigoInvoiceFieldsToIncomesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('incomes', function (Blueprint $table) {
            $table->string('siigo_invoice_id', 50)->nullable()->after('bill_final_value')->comment('ID de la factura en Siigo');
            $table->string('siigo_document_id', 50)->nullable()->after('siigo_invoice_id')->comment('Document ID de la factura en Siigo');
            $table->string('siigo_invoice_url', 255)->nullable()->after('siigo_document_id')->comment('URL de la factura electrónica en Siigo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('incomes', function (Blueprint $table) {
            $table->dropColumn('siigo_invoice_id');
            $table->dropColumn('siigo_document_id');
            $table->dropColumn('siigo_invoice_url');
        });
    }
} 