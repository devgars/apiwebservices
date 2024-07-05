<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGenResourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gen_resources', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code', 10)->comment('Código Interno AS400');
            $table->string('abrv', 10)->nullable()->comment('Abreviación de Recurso');
            $table->string('name', 50)->comment('Nombre de Recurso');
            $table->string('description', 100)->nullable()->comment('Descripción del Recurso');
            $table->tinyInteger('reg_status')->default(1)->comment('Estatus de registro: 1 -> ACTIVO, 0 -> INACTIVO');

            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->datetime('updated_at')->nullable();

            $table->unique('code');
            $table->unique('name');

            $table->index('reg_status');
            $table->index('created_at');
            $table->index('updated_at');
        });
        DB::statement("COMMENT ON TABLE gen_resources IS 'Encabezado de recursos utilitarios como: Tipos de identificación, Tipos de direcciones, clases de clientes, etc'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gen_resources');
    }
}
