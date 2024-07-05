<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUbigeosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ubigeos', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->comment('Código Interno AS400');

            $table->integer('ubigeo_type_id')->comment('Tipo de Ubigeo: País|Departamento|Provincia|Distrito');
            $table->foreign('ubigeo_type_id')->references('id')->on('ubigeo_types');

            $table->string('abrv', 10)->comment('Abreviación del País o Región');
            $table->string('name', 100)->comment('Nombre del País o Región');
            $table->string('ubigeo', 20)->nullable()->comment('Ubigeo Perú');
            $table->string('flag_image', 150)->nullable()->comment('Imagen de Bandera');
            $table->string('phone_code', 5)->nullable()->comment('Código Telefónico del País o Región');

            $table->bigInteger('parent_ubigeo_id')->nullable()->comment('Reigión Padre');
            $table->foreign('parent_ubigeo_id')->references('id')->on('ubigeos');

            $table->tinyInteger('order')->default(1)->comment('Orden');
            $table->tinyInteger('reg_status')->default(1)->comment('Estatus de registro: 1 -> ACTIVO, 0 -> INACTIVO');

            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->datetime('updated_at')->nullable();

            $table->unique(['ubigeo_type_id', 'code', 'parent_ubigeo_id']);

            $table->index('reg_status');
            $table->index('order');
            $table->index('code');
            $table->index('ubigeo_type_id');
            $table->index('name');
            $table->index('parent_ubigeo_id');

            $table->index('created_at');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ubigeos');
    }
}
