<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePartDetailWarehousesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('part_detail_warehouses', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('part_detail_id')->commet('Id de detalle de parte en tabla part_details');
            $table->foreign('part_detail_id')->references('id')->on('part_part_details');
            $table->integer('warehouse_id')->commet('Id de almacén en tabla establishments');
            $table->foreign('warehouse_id')->references('id')->on('establishments');
            $table->decimal('init_qty', 15, 2)->default(0)->comment('Cantidad Inicial');
            $table->decimal('in_qty', 15, 2)->default(0)->comment('Cantidad Ingreso');
            $table->decimal('out_qty', 15, 2)->default(0)->comment('Cantidad Salida');
            $table->decimal('in_warehouse_stock', 15, 2)->default(0)->comment('Stock en almacén');
            $table->datetime('created_at')->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');
            $table->tinyInteger('reg_status')->default(1)->comment('Estado de Registro');

            $table->unique(['part_detail_id', 'warehouse_id']);
            $table->index('part_detail_id');
            $table->index('warehouse_id');
            $table->index('in_warehouse_stock');
            $table->index('created_at');
            $table->index('updated_at');
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
        Schema::dropIfExists('part_detail_warehouses');
    }
}
