<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSyncLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('log_table', 30)->commet('Tabla ');
            $table->bigInteger('log_table_id')->commet('Id  de la Tabla ');
            $table->integer('process_id')->comment('Id de proceso');
            $table->integer('step_id')->comment('Id de paso');
            //$table->tinyInteger('process_end')->default(0)->comment('Proceso Finalizado 0 -> NO, 1 -> SI');
            $table->tinyInteger('reg_status')->comment('Estado de Registro');
            $table->datetime('created_at')->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');

            $table->index('log_table');
            $table->index('log_table_id');
            $table->index('process_id');
            $table->index('step_id');
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
        Schema::dropIfExists('sync_logs');
    }
}
