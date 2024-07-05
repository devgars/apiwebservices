<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EcommerceOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ecommerce_orders', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('ecommerce_cart_id')->comment('Cart_Id de Cotización BD Ecommerce');
            $table->json('json_quote')->comment('Datos de la cotización: Cabecera y Detalle');
            $table->string('tmp_quote_number', 10)->comment('Número TEMPORAL Generado por BD Intermedia de la cotización');
            $table->string('as400_quote_number', 10)->nullable()->comment('Número GENERADO POR AS400 a la cotización');

            $table->bigInteger('ecommerce_order_number')->comment('Número de pedido BD Ecommerce');
            $table->json('json_order')->comment('Datos del pedido: Cabecera y Detalle');
            $table->string('tmp_order_number', 10)->comment('Número TEMPORAL Generado por BD Intermedia del pedido');
            $table->string('as400_order_number', 10)->nullable()->comment('Número GENERADO POR AS400 al pedido');

            $table->datetime('created_at')->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de Actualizacion');
            $table->datetime('as400_sync')->nullable()->comment('Fecha de descarga en AS400');

            $table->unique('ecommerce_quote_number');
            $table->unique('ecommerce_order_number');
            $table->index('as400_quote_number');
            $table->index('as400_order_number');
            $table->index('tmp_quote_number');
            $table->index('tmp_order_number');
            $table->index('created_at');
            $table->index('as400_sync');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
