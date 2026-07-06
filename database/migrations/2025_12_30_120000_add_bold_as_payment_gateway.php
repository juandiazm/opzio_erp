<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBoldAsPaymentGateway extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Crear pasarela de pago BOLD
        $paymentGateway = new \App\Models\payment_gateway();
        $paymentGateway->position = 2;
        $paymentGateway->name = 'Bold';
        $paymentGateway->img = 'logo-bold.svg';
        $paymentGateway->save();

        // Llave de identidad (API Key) - Pública
        $paymentGatewayKey = new \App\Models\payment_gateway_key();
        $paymentGatewayKey->payment_gateway_id = $paymentGateway->id;
        $paymentGatewayKey->name = 'api_key';
        $paymentGatewayKey->save();

        // Llave secreta (Secret Key) - Privada para firma de integridad y webhooks
        $paymentGatewayKey = new \App\Models\payment_gateway_key();
        $paymentGatewayKey->payment_gateway_id = $paymentGateway->id;
        $paymentGatewayKey->name = 'secret_key';
        $paymentGatewayKey->save();

        // Modo de entorno (test/production)
        $paymentGatewayKey = new \App\Models\payment_gateway_key();
        $paymentGatewayKey->payment_gateway_id = $paymentGateway->id;
        $paymentGatewayKey->name = 'environment';
        $paymentGatewayKey->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $paymentGateway = \App\Models\payment_gateway::where('name', 'Bold')->first();
        if ($paymentGateway) {
            \App\Models\payment_gateway_key::where('payment_gateway_id', $paymentGateway->id)->delete();
            $paymentGateway->delete();
        }
    }
}
