<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdQuoteDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ord_quote_details', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('quote_id')->comment('Id de Cotización');
            $table->foreign('quote_id')->references('id')->on('ord_quotes');
            $table->bigInteger('sku_id')->comment('Línea-Origen-Marca-Producto');
            $table->foreign('sku_id')->references('id')->on('part_part_details');
            $table->string('item_description', 50)->comment('Descripción de producto');
            $table->string('product_brand', 30)->comment('Marca de producto');
            $table->decimal('item_price', 10, 2)->default(1)->comment('Precio');
            $table->decimal('item_discount', 10, 2)->default(0)->comment('Descuento');
            $table->decimal('item_tax', 5, 2)->default(ENV('IGV'))->comment('Porcentaje IGV');
            $table->decimal('item_qty', 10, 2)->default(1)->comment('Cantidad');
            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');
            $table->tinyInteger('reg_status')->comment('Estado de Registro');

            $table->unique(['quote_id', 'sku_id']);
            $table->index(['quote_id', 'sku_id']);
            $table->index('quote_id');
            $table->index('sku_id');
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
        Schema::dropIfExists('ord_quote_details');
    }
}
