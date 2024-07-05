<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApiLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->string('system', 50)->comment('Sistema');
            $table->string('method', 150)->comment('Método');
            $table->integer('user')->default(0)->comment('Usuario');
            $table->json('json_request_values')->comment('parámetros recibidos en formato JSON');
            $table->json('json_response_values')->nullable()->comment('response en formato JSON');
            $table->datetime('created_at')->comment('Fecha de creación');
            $table->datetime('updated_at')->nullable()->comment('Fecha de Actualización');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('api_logs');
    }
}
