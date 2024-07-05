<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePoventaQuestionRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('poventa_question_request', function (Blueprint $table) {
            $table->id();
            $table->integer('id_request')->comment('id solicitud (product id)');
            $table->integer('id_question')->comment('id respuesta match con la tabla gen_resource_detail');
            $table->smallInteger('response')->nullable()->comment('Respuesta');
            $table->smallInteger('state')->nullable()->comment('estado');
            $table->string('description')->nullable()->comment('descripcion');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('poventa_question_request');
    }
}
