<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSyncProcessStepsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sync_process_steps', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('process_id')->comment('Id de proceso');
            $table->foreign('process_id')->references('id')->on('sync_processes');
            $table->integer('step_id')->comment('Id de paso');
            $table->foreign('step_id')->references('id')->on('sync_steps');
            $table->smallInteger('order')->default(0)->comment('Orden ');
            $table->tinyInteger('reg_status')->comment('Estado de Registro');
            $table->datetime('created_at')->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');

            $table->unique(['process_id', 'step_id']);
            $table->index('process_id');
            $table->index('step_id');
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
        Schema::dropIfExists('sync_process_steps');
    }
}
