<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGenResourceDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gen_resource_details', function (Blueprint $table) {
            $table->id();
            $table->integer('resource_id')->comment('Clave foranea a tabla resources');
            $table->foreign('resource_id')->references('id')->on('gen_resources');
            $table->string('code', 10)->comment('Código Interno AS400');
            $table->string('abrv', 10)->nullable()->comment('Abreviación de Recurso');
            $table->string('name', 70)->comment('Nombre');
            $table->string('description', 100)->nullable()->comment('Descripción');
            $table->smallInteger('order')->default(1)->comment('Orden');
            $table->tinyInteger('reg_status')->default(1)->comment('Estatus de registro: 1 -> ACTIVO, 0 -> INACTIVO');

            $table->bigInteger('parent_resource_detail_id')->nullable()->comment('Recurso padre');
            $table->foreign('parent_resource_detail_id')->references('id')->on('gen_resource_details');

            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->datetime('updated_at')->nullable();
            $table->jsonb('other_fields')->comment('Otros campos en formato JSONB');

            $table->unique(['resource_id', 'code', 'parent_resource_detail_id']);
            $table->unique(['resource_id', 'name', 'parent_resource_detail_id']);

            $table->index('resource_id');
            $table->index('code');
            $table->index('name');
            $table->index('reg_status');
            $table->index('order');
            $table->index('created_at');
            $table->index('updated_at');
        });
        DB::statement("COMMENT ON TABLE gen_resource_details IS 'Detalle de recursos utilitarios como: Tipos de identificación, Tipos de direcciones, clases de clientes, etc'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gen_resource_details');
    }
}
