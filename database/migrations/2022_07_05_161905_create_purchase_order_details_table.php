<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseOrderDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_order_details', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('purchase_order_id')->comment('Orden de compra');
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders');
            $table->bigInteger('sku_id')->comment('SKU id');
            $table->foreign('sku_id')->references('id')->on('part_part_details');
            $table->bigInteger('measurement_unit_id')->comment('Unidad de medida Compra');
            $table->foreign('measurement_unit_id')->references('id')->on('gen_resource_details');
            $table->decimal('discount_rate', 5, 2)->default(0)->comment('Porcentaje Descuento');
            $table->decimal('ordered_quantity', 11, 2)->default(0)->comment('Cantidad Pedida');
            $table->decimal('returned_quantity', 11, 2)->default(0)->comment('Cantidad devuelta');
            $table->decimal('price', 11, 2)->default(0)->comment('Precio Unitario');

            $table->datetime('created_at')->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');
            $table->tinyInteger('reg_status')->default(1)->comment('Estado de Registro');

            $table->unique(['purchase_order_id', 'sku_id']);
            $table->index('purchase_order_id');
            $table->index('sku_id');
            $table->index('measurement_unit_id');
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
        Schema::dropIfExists('purchase_order_details');
    }
}
