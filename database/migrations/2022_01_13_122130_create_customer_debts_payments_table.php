<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerDebtsPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_debts_payments', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('id_deuda')->comment('ID EN TABLA cliente_saldos');
            $table->foreign('id_deuda')->references('id')->on('cliente_saldos');

            $table->bigInteger('payment_id')->comment('ID PAGO EN TABLA customer_payments');
            $table->foreign('payment_id')->references('id')->on('customer_payments');

            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->datetime('updated_at')->nullable();

            $table->index('created_at');
            $table->index('updated_at');

            $table->unique(['id_deuda', 'payment_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customer_debts_payments');
    }
}
