<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSyncStepsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sync_steps', function (Blueprint $table) {
            $table->increments('id');
            $table->string('step_name', 40)->comment('Nombre del Paso');
            $table->string('step_table', 40)->comment('Tabla que afecta');
            $table->tinyInteger('reg_status')->comment('Estado de Registro');
            $table->datetime('created_at')->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');

            $table->unique(['step_name']);
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
        Schema::dropIfExists('sync_steps');
    }
}
