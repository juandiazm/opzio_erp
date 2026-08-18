<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddContractSignatureWorkflow extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('contracts', 'signature_status')) {
            return;
        }

        Schema::table('contracts', function (Blueprint $table) {
            $table->string('signature_status', 20)->default('pending')->after('send_status');
            $table->text('signature_token')->nullable()->after('signature_status');
            $table->char('signature_token_hash', 64)->nullable()->after('signature_token');
            $table->string('signature_pdf_path', 255)->nullable()->after('signature_token_hash');
            $table->dateTime('signature_uploaded_at')->nullable()->after('signature_pdf_path');
            $table->dateTime('signature_accepted_at')->nullable()->after('signature_uploaded_at');
            $table->index('signature_status');
            $table->index('signature_token_hash');
        });
    }

    public function down()
    {
        if (!Schema::hasColumn('contracts', 'signature_status')) {
            return;
        }

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropIndex(['signature_status']);
            $table->dropIndex(['signature_token_hash']);
            $table->dropColumn([
                'signature_status',
                'signature_token',
                'signature_token_hash',
                'signature_pdf_path',
                'signature_uploaded_at',
                'signature_accepted_at',
            ]);
        });
    }
}
