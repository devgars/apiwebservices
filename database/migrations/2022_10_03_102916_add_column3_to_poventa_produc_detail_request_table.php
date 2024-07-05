<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumn3ToPoventaProducDetailRequestTable extends Migration
{
    public function up()
    {
        Schema::table('poventa_produc_detail_request', function (Blueprint $table) {
            $table->integer('line_id_veh')->nullable()->comment('id de la linea');
            $table->string('line_code_veh', 30)->nullable()->comment('codigo de la linea');
            $table->string('brand_veh', 100)->nullable()->comment('descripcion de la linea');
            $table->integer('model_id_veh')->nullable()->comment('id de modelo de vehiculo');
            $table->string('model_code_veh', 30)->nullable()->comment('code de modelo de vehiculo');
            $table->string('model_Veh', 50)->nullable()->comment('modelo de vehiculo');
            $table->string('year_veh', 10)->nullable()->comment('año de vehiculo');
            $table->string('plate_veh', 20)->nullable()->comment('placa de vehiculo');
            $table->string('engine_veh', 100)->nullable()->comment('motor de vehiculo');
            $table->integer('type_use_machinery')->nullable()->comment('tipo de uso de maquinaria (ge_resources_detail id)');
        });
    }
    public function down()
    {
        Schema::table('poventa_produc_detail_request', function (Blueprint $table) {
            $table->dropColumn('line_id_veh');
            $table->dropColumn('line_code_veh');
            $table->dropColumn('brand_veh');
            $table->dropColumn('model_id_veh');
            $table->dropColumn('model_code_veh');
            $table->dropColumn('model_Veh');
            $table->dropColumn('year_veh');
            $table->dropColumn('plate_veh');
            $table->dropColumn('engine_veh');
            $table->dropColumn('type_use_machinery');
        });
    }
}
