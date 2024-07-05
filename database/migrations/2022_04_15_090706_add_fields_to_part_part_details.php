<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToPartPartDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('part_part_details', function (Blueprint $table) {
            $table->decimal('list_price', 12, 2)->default(0)->comment('Precio normal/lista');
            $table->decimal('min_price', 12, 2)->default(0)->comment('Precio mínimo');
            $table->string('currency_code', 2)->default('02')->comment('Moneda: 01 -> PEN, 02 -> USD');
            $table->decimal('discount', 12, 2)->default(0)->comment('Monto descuento');
            $table->boolean('in_stock')->default(0)->comment('Hay Stock?: 0 -> NO, 1 -> SI');
            $table->integer('sales_qty')->default(0)->comment('Cantidad de veces vendida');
            $table->integer('last_sale_qty')->default(0)->comment('Cantidad última venta');
            $table->string('last_sale_date', 8)->default('')->comment('Fecha última venta');
            $table->string('last_sale_customer_code', 6)->default('')->comment('Código de Cliente -  última venta');
            $table->integer('last_purchase_qty')->default(0)->comment('Cantidad última compra');
            $table->string('last_purchase_date', 8)->default('')->comment('Fecha última compra');
            $table->string('last_purchase_provider_code', 6)->default('')->comment('Código de Proveedor -  última compra');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('part_part_details', function (Blueprint $table) {
            //
        });
    }
}
