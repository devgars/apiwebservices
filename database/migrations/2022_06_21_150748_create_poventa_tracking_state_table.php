<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePoventaTrackingStateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('poventa_tracking_state', function (Blueprint $table) {
            $table->id();
            $table->integer('id_request')->nullable()->comment('id de la tabla poventa_request');
            $table->integer('id_user')->nullable()->comment('id de usario tabla users');
            $table->integer('id_state')->nullable()->comment('id de estado tabla ge_resources_detail');
            $table->string('comment')->nullable()->comment('comentario');
            $table->timestamp('date_reg')->nullable()->comment('fecha de registro'); 
            $table->timestamp('date_upd')->nullable()->comment('fecha de actualización');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('poventa_tracking_state');
    }
}
