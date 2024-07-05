<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePoventaProveedorRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('poventa_proveedor_request', function (Blueprint $table) {
            $table->id();
            $table->integer('id_request')->nullable()->comment('id de la tabla poventa_request');
            $table->integer('id_product_detail_request')->nullable()->comment('id de la tabla poventa_product_detail_request');
            $table->string('part_detail_id', 30)->nullable()->comment('id de producto part_detail_id');
            $table->string('code', 30)->nullable()->comment('codigo de producto part_code');
            $table->string('brand', 50)->nullable()->comment('marca');
            $table->string('description', 150)->nullable()->comment('descripción');
            $table->string('linea', 5)->nullable()->comment('linea');
            $table->string('origin', 5)->nullable()->comment('origen');
            $table->integer('unidad')->nullable()->comment('unidad');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('poventa_proveedor_request');
    }
}
