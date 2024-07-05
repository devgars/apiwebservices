<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVehModelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('veh_models', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('line_id')->comment('Línea');
            $table->foreign('line_id')->references('id')->on('gen_resource_details');
            $table->string('model_code', '30')->comment('Código del modelo');
            $table->string('model_description', '50')->comment('Descripción del modelo');
            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');
            $table->tinyInteger('reg_status')->comment('Estado de Registro');

            $table->unique(['line_id', 'model_code']);
            $table->index(['line_id', 'model_code']);
            $table->index('line_id');
            $table->index('model_code');
            $table->index('model_description');
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
        Schema::dropIfExists('veh_models');
    }
}
