<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
#use Illuminate\Support\Facades\Schema;
use Staudenmeir\LaravelMigrationViews\Facades\Schema;


class CreateVAniosXLineaModeloVeh extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $query = 'SELECT veh.id AS veh_id,
        line.code AS line_code,
        mod.model_code,
        veh.veh_year,
        veh.veh_engine,
        veh.veh_hp,
        veh.veh_traction
       FROM veh_vehicles veh
         JOIN veh_models mod ON mod.id = veh.model_id
         JOIN gen_resource_details line ON mod.line_id = line.id
      ORDER BY mod.model_code;';

        Schema::createView('v_anios_x_linea_modelo_veh', $query);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropView('v_anios_x_linea_modelo_veh');
    }
}
