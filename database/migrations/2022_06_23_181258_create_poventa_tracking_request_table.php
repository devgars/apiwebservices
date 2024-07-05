<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePoventaTrackingRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('poventa_tracking_request', function (Blueprint $table) {
            $table->id();
            $table->integer('id_product_detail_request')->nullable()->comment('id de la tabla poventa_product_detail_request ');
            $table->integer('id_request')->nullable()->comment('id de la tabla poventa_request');
            $table->integer('id_motivo')->nullable()->comment('motivo del proceso (ESTADO) match con la tabla gen_resource_detail');
            $table->string('detail')->nullable()->comment('detalle de revisión');
            $table->string('subjet')->nullable()->comment('asunto de revisión');
            $table->string('filename_cli', 100)->nullable()->comment('nombre de archivo del cliente');
            $table->integer('id_user')->nullable()->comment('id usuario');
            $table->decimal('costo_eva', 10,2)->nullable()->comment('cosoto de evaluación'); 
            $table->string('type_money_cli', 10)->nullable()->comment('tipo de moneda al cliente');
            $table->string('num_nc_cli', 30)->nullable()->comment('número de nota de crédito al cliente');
            $table->integer('id_accion_correctiva')->nullable()->comment('acción correcativa match con la tabla gen_resource_deatil');
            $table->string('filename_nc_prov')->nullable()->comment('archivo de nota de credito de archivo del proveedor');
            $table->string('num_nc_prov', 30)->nullable()->comment('nota de credito al proveedor');
            $table->date('fecha_nc_prov')->nullable()->comment('fecha de la nota de credito del proveedor');
            $table->string('type_money_cn_prov',10)->nullable()->comment('tipo de moneda del proveedor');
            $table->decimal('importe_nc_prov', 10,1)->nullable()->comment('importe de la nota de credito');
            $table->string('status_product',10)->nullable()->comment('Estado de del producto [PROCEDE, NO PROCEDE]');
            $table->tinyInteger('status')->default(1)->comment('Estado de Registro');
            $table->timestamp('date_reg')->nullable()->comment('fecha de reación');
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
        Schema::dropIfExists('poventa_tracking_request');
    }
}
