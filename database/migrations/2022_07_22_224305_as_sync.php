<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AsSync extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('as_sync', function (Blueprint $table) {
            $table->id();
            $table->string('sytabla', 50)->comment('tabla');
            $table->string('sql', 300)->comment('Cadena SQL');
            $table->string('usuario', 15)->comment('Usuario');
            $table->integer('fecha_generado')->comment('Fecha generado');
            $table->integer('hora_generado')->comment('Hora generado');
            $table->string('tipo_operacion', 15)->comment('Tipo operación: Insert|Update|Delete');
            $table->datetime('created_at')->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');

            $table->index('sytabla');
            $table->index('fecha_generado');
            $table->index('tipo_operacion');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
