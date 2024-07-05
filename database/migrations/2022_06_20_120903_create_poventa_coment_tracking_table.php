<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePoventaComentTrackingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('poventa_coment_tracking', function (Blueprint $table) {
            $table->id();
            $table->integer('id_request')->comment('id de la tabla poventa_request');
            $table->integer('id_user')->comment('id de usuario');
            $table->string('coment')->nullable()->comment('comentario');
            $table->string('type_coment', 10)->nullable()->comment('tipo de comentario [B = mensaje ingresado por el usuario, A = mensaje automatico cuando se genera la sol, C=mensaje automatico cuando cambia de estado] ');
            $table->smallInteger('state')->nullable()->comment('estado');
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
        Schema::dropIfExists('poventa_coment_tracking');
    }
}
