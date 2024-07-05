<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVehVehiclesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('veh_vehicles', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('model_id')->comment('Modelo');
            $table->foreign('model_id')->references('id')->on('veh_models');
            $table->string('vin', '20')->nullable()->comment('VIN');
            $table->smallInteger('veh_year')->comment('Año del vehículo');
            $table->string('veh_hp', '30')->nullable()->comment('Caballos de fuerza');
            $table->string('veh_traction', '30')->nullable()->comment('Tracción');
            $table->string('veh_engine', '30')->nullable()->comment('Motor del vehículo');
            $table->string('veh_gearbox', '30')->nullable()->comment('Caja de cambios');
            $table->string('veh_front_axle', '30')->nullable()->comment('Eje Delantero');
            $table->string('veh_rear_axle', '30')->nullable()->comment('Eje Posterior');
            $table->string('veh_category_code', '2')->comment('Código de Categoría');
            $table->smallInteger('veh_order')->default(1)->comment('Secuencia en AS400');

            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');
            $table->tinyInteger('reg_status')->comment('Estado de Registro');

            $table->unique(['model_id', 'veh_year', 'veh_hp', 'veh_traction']);
            $table->index(['model_id', 'veh_year', 'veh_hp', 'veh_traction']);
            $table->index('model_id');
            $table->index('vin');
            $table->index('veh_year');
            $table->index('veh_hp');
            $table->index('veh_traction');
            $table->index('veh_category_code');
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
        Schema::dropIfExists('veh_vehicles');
    }
}
