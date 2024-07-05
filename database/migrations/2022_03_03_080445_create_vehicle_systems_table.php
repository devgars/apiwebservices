<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVehicleSystemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vehicle_systems', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code', 10)->comment('CÓDIGO AS400');
            $table->string('abrv', 10)->nullable()->comment('Abreviación');
            $table->bigInteger('system_type_id')->comment('Sistema o Subsistema (resource_id -> 20)');
            $table->string('name', 70)->comment('Nombre de Sistema/Subsistema');
            $table->string('description', 100)->nullable()->comment('Descripción de Sistema/Subsistema');
            $table->integer('parent_system_id')->nullable()->comment('Sistema Padre');
            $table->foreign('parent_system_id')->references('id')->on('vehicle_systems');
            $table->smallInteger('order')->default(1)->comment('Orden');
            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');
            $table->tinyInteger('reg_status')->comment('Estado de Registro');

            $table->unique(['code', 'system_type_id']);
            $table->unique(['name', 'system_type_id']);

            $table->index('code');
            $table->index('system_type_id');
            $table->index('name');
            $table->index('parent_system_id');
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
        Schema::dropIfExists('vehicle_systems');
    }
}
