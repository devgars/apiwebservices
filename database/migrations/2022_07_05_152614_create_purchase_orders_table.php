<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id')->comment('Empresa');
            $table->foreign('company_id')->references('id')->on('establishments');
            $table->bigInteger('subsidiary_id')->comment('Sucursal');
            $table->foreign('subsidiary_id')->references('id')->on('establishments');
            $table->bigInteger('warehouse_id')->comment('Almacen');
            $table->foreign('warehouse_id')->references('id')->on('establishments');
            $table->bigInteger('provider_id')->comment('Proveedor');
            $table->foreign('provider_id')->references('id')->on('providers');
            $table->bigInteger('currency_id')->comment('Moneda');
            $table->foreign('currency_id')->references('id')->on('gen_resource_details');
            $table->integer('purchase_number')->comment('Número de orden de compra');
            $table->string('purchase_description', 150)->nullable()->comment('Descripción OC');
            $table->date('reg_date')->comment('Fecha de emisión');
            $table->date('estimated_delivery_date')->comment('Fecha de entrega estimada');
            $table->decimal('discount_rate_1', 5, 2)->default(0)->comment('Porcentaje Descuento 1');
            $table->decimal('discount_rate_2', 5, 2)->default(0)->comment('Porcentaje Descuento 2');
            $table->decimal('tax_rate', 5, 2)->comment('Porcentaje IGV');

            $table->decimal('total_amount', 15, 2)->comment('Monto total');
            $table->decimal('discount_amount_1', 15, 2)->default(0)->comment('Monto descuento 1');
            $table->decimal('discount_amount_2', 15, 2)->default(0)->comment('Monto descuento 2');
            $table->decimal('freight_amount', 15, 2)->default(0)->comment('Monto Flete');
            $table->decimal('outlay_amount', 15, 2)->default(0)->comment('Monto gastos');
            $table->decimal('tax_amount', 15, 2)->default(0)->comment('Monto de impuestos');
            $table->decimal('net_amount', 15, 2)->default(0)->comment('Monto neto');

            $table->datetime('created_at')->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');
            $table->tinyInteger('reg_status')->default(1)->comment('Estado de Registro');

            $table->unique(['subsidiary_id', 'purchase_number']);
            $table->index('subsidiary_id');
            $table->index('purchase_number');
            $table->index('provider_id');
            $table->index('currency_id');
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
        Schema::dropIfExists('purchase_orders');
    }
}
