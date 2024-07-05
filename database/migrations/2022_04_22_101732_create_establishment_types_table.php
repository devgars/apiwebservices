<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEstablishmentTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('establishment_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('description', 50)->commet('EMPRESA, SUCURSAL, ALMACÉN, ETC.');
            $table->datetime('created_at')->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');
            $table->smallInteger('order')->comment('Orden');
            $table->tinyInteger('reg_status')->comment('Estado de Registro');

            $table->unique(['description']);
            $table->index('description');
            $table->index('order');
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
        Schema::dropIfExists('establishment_types');
    }
}
