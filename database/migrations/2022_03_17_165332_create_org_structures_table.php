<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrgStructuresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('org_structures', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->comment('Código en AS400 ');
            $table->string('abrv', 5)->nullable()->comment('Abreviación');
            $table->string('name', 100)->comment('Nombre de item de estructura organizacional');

            $table->integer('org_structure_type_id')->comment('Tipo de Estructura Empresa, Sucursal, Almacen, etc.');
            $table->foreign('org_structure_type_id')->references('id')->on('org_structure_types');

            $table->bigInteger('parent_org_structure_id')->nullable()->comment('Estructura padre');
            $table->foreign('parent_org_structure_id')->references('id')->on('org_structures');

            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');
            $table->tinyInteger('reg_status')->comment('Estado de Registro');

            $table->unique(['code']);
            $table->unique(['name']);

            $table->index('code');
            $table->index('name');
            $table->index('org_structure_type_id');
            $table->index('parent_org_structure_id');
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
        Schema::dropIfExists('org_structures');
    }
}
