<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerPaymentMethodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('customer_id')->comment('Cliente');
            $table->foreign('customer_id')->references('id')->on('customers');
            $table->bigInteger('payment_method_id')->comment('Método de Pago');
            $table->foreign('payment_method_id')->references('id')->on('gen_resource_details');
            $table->bigInteger('payment_modality_id')->comment('Modalidad de Pago');
            $table->foreign('payment_modality_id')->references('id')->on('gen_resource_details');
            $table->bigInteger('payment_condition_id')->comment('Condición de pago');
            $table->foreign('payment_condition_id')->references('id')->on('gen_resource_details');
            $table->smallInteger('days_to_pay')->default(0)->comment('Días para pagar');

            $table->datetime('created_at')->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');
            $table->tinyInteger('reg_status')->default(1)->comment('Estado de Registro');

            $table->unique(['customer_id', 'payment_method_id', 'payment_modality_id', 'payment_condition_id']);
            $table->index('customer_id');
            $table->index('payment_method_id');
            $table->index('payment_modality_id');
            $table->index('payment_condition_id');
            $table->index('created_at');
            $table->index('reg_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customer_payment_methods');
    }
}
