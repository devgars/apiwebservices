<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVehPartVehiclesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('veh_part_vehicles', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('part_id')->comment('Id Parte');
            $table->foreign('part_id')->references('id')->on('part_parts');
            $table->bigInteger('vehicle_id')->comment('Vehículo');
            $table->foreign('vehicle_id')->references('id')->on('veh_vehicles');
            $table->smallInteger('veh_order')->default(1)->comment('Secuencia en AS400');
            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');
            $table->tinyInteger('reg_status')->comment('Estado de Registro');

            $table->unique(['part_id', 'vehicle_id']);
            $table->index('part_id');
            $table->index('vehicle_id');
            $table->index('veh_order');
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
        Schema::dropIfExists('veh_part_vehicles');
    }
}
