<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSyncProcessesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sync_processes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('process_name')->comment('Nombre del proceso');
            $table->string('process_description', 50)->nullable()->comment('Descripción del proceso de sincronización');
            $table->bigInteger('process_type_id')->comment('Tipo de proceso de sincronización: AS400 -> DB Intermedia, DB Intermedia -> AS400');
            $table->foreign('process_type_id')->references('id')->on('gen_resource_details');
            $table->tinyInteger('reg_status')->comment('Estado de Registro');
            $table->datetime('created_at')->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');

            $table->unique(['process_name']);
            $table->index('process_type_id');
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
        Schema::dropIfExists('sync_processes');
    }
}
