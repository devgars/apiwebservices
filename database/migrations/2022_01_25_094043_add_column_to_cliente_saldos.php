<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToClienteSaldos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cliente_saldos', function (Blueprint $table) {
            $table->integer('fecha_extorno')->default(0)->comment('Fecha Extorno');
            $table->integer('hora_extorno')->default(0)->comment('Hora Extorno');
            $table->index('fecha_extorno');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cliente_saldos', function (Blueprint $table) {
            //
        });
    }
}
