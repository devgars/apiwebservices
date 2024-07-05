<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePoventaTrackingFileTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('poventa_tracking_file', function (Blueprint $table) {
            $table->id();
            $table->integer('id_product_detail_request')->nullable()->comment('id de la tabla poventa_product_detail_request ');
            $table->string('type_file', 5)->nullable()->comment('tipo de archivo ');
            $table->integer('id_request')->nullable()->comment('id de la tabla poventa_request');
            $table->integer('id_user')->nullable()->comment('id de usuario');
            $table->string('name_file', 100)->nullable()->comment('nombre de archivo');
            $table->text('name_file_encrypt')->nullable()->comment('nombre de archivo encriptado');
            $table->string('description')->nullable()->comment('descripcion del archivo');
            $table->string('name_icon_file')->nullable()->nullable()->comment('nombre del icono del archivo');
            $table->tinyInteger('status')->default(1)->comment('Estado de Registro');
            $table->string('Adicional')->nullable()->comment('campo adicional');
            $table->timestamp('date_reg')->nullable()->comment('fecha de reación');
            $table->timestamp('date_upd')->nullable()->comment('fecha de actualización');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('poventa_tracking_file');
    }
}
