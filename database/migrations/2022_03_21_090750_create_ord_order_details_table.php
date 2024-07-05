<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdOrderDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ord_order_details', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('order_id')->comment('Id de Pedido');
            $table->foreign('order_id')->references('id')->on('ord_orders');
            $table->bigInteger('sku_id')->comment('Línea-Origen-Marca-Producto');
            $table->foreign('sku_id')->references('id')->on('part_part_details');
            $table->integer('item_number')->default(1)->comment('Número de item');
            $table->string('item_description', 50)->comment('Descripción de producto');
            $table->decimal('item_qty', 11, 2)->default(1)->comment('Cantidad');
            $table->decimal('item_qty_return', 11, 2)->default(0)->comment('Cantidad Devuelta');
            $table->decimal('item_price', 10, 2)->default(1)->comment('Precio');
            $table->decimal('item_line_discount', 10, 2)->default(0)->comment('Descuento de linea');
            $table->decimal('item_discount', 10, 2)->default(0)->comment('Otro descuento');
            $table->decimal('item_tax', 5, 2)->default(ENV('IGV'))->comment('Porcentaje IGV');

            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');
            $table->tinyInteger('reg_status')->comment('Estado de Registro');

            $table->unique(['order_id', 'item_number']);
            $table->index(['order_id', 'sku_id']);
            $table->index('order_id');
            $table->index('sku_id');
            $table->index('item_number');
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
        Schema::dropIfExists('ord_order_details');
    }
}
