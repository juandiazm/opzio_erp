<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWompyAsPaymentGateway extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $paymentGateway = new \App\Models\payment_gateway();
        $paymentGateway->position = 1;
        $paymentGateway->name = 'Wompi';
        $paymentGateway->img = 'logo-wompi.svg';
        $paymentGateway->save();
        $paymentGatewayKey = new \App\Models\payment_gateway_key();
        $paymentGatewayKey->payment_gateway_id = $paymentGateway->id;
        $paymentGatewayKey->name = 'public_key';
        $paymentGatewayKey->save();
        $paymentGatewayKey = new \App\Models\payment_gateway_key();
        $paymentGatewayKey->payment_gateway_id = $paymentGateway->id;
        $paymentGatewayKey->name = 'private_key';
        $paymentGatewayKey->save();
        $paymentGatewayKey = new \App\Models\payment_gateway_key();
        $paymentGatewayKey->payment_gateway_id = $paymentGateway->id;
        $paymentGatewayKey->name = 'events_key';
        $paymentGatewayKey->save();
        $paymentGatewayKey = new \App\Models\payment_gateway_key();
        $paymentGatewayKey->payment_gateway_id = $paymentGateway->id;
        $paymentGatewayKey->name = 'integrity_key';
        $paymentGatewayKey->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $paymentGateway = \App\Models\payment_gateway::where('name', 'Wompi')->first();
        \App\Models\payment_gateway_key::where('payment_gateway_id', $paymentGateway->id)->delete();
        $paymentGateway->delete();
    }
}
