<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePoventaRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('poventa_request', function (Blueprint $table) {
            $table->id();
            $table->string('num_request', 50)->comment('numero de solicitud');
            $table->integer('type_request')->comment('tipo de solicitud (ge_resources_detail id)');
            $table->integer('category')->comment('categoria (ge_resources_detail id)');
            $table->integer('id_client')->comment('id cliente (customers id)');
            $table->integer('type_document')->comment('tipo de documento (ge_resources_detail id)');
            $table->integer('serie')->comment('serie (ge_resources_detail id)');
            $table->integer('id_contact')->comment('id contacto (customer_contacts id)');
            $table->integer('fac_fiscal_document_id')->nullable()->comment('id de documento');
            $table->string('num_fact', 50)->nullable()->comment('numero de facuta');
            $table->integer('id_responsable')->comment('id responsabe  (users id)');
            $table->integer('id_user_responsable')->nullable()->comment('id responsable tecnico  (users id)');
            $table->integer('id_state')->nullable()->comment('El estado de la solictud  (ge_resources_detail id)');
            $table->integer('line_id')->nullable()->comment('id de la linea ');
            $table->string('line_code', 30)->nullable()->comment('codigo de la linea');
            $table->string('brand_veh', 100)->nullable()->comment('descripcion de la linea');
            $table->integer('model_id')->nullable()->comment('id de modelo de vehiculo');
            $table->string('model_code', 30)->nullable()->comment('code de modelo de vehiculo');
            $table->string('model_Veh', 50)->nullable()->comment('modelo de vehiculo');
            $table->string('year_veh', 10)->nullable()->comment('año de vehiculo');
            $table->string('plate_veh', 20)->nullable()->comment('placa de vehiculo');
            $table->string('engine_veh', 100)->nullable()->comment('motor de vehiculo');
            $table->integer('type_use_machinery')->nullable()->comment('tipo de uso de maquinaria (ge_resources_detail id)');
            $table->integer('type_flaw')->nullable()->comment('tipo defecto (ge_resources_detail id)');
            $table->string('detail_request')->nullable()->comment('detalle de la solicitud');
            $table->integer('id_product')->nullable()->comment('id procto (product id)');
            $table->smallInteger('state')->nullable()->comment('estado');
            $table->integer('id_user_upd')->nullable()->comment('id de usuario modificado (tabla users) ');
            $table->integer('id_user_reg')->nullable()->comment('id de usuario inertado (tabla users)');
            $table->timestamp('date_reg')->nullable()->comment('fecha de registro');
            $table->timeTz('hour_reg', 0)->nullable()->comment('hora de registro');
            $table->timestamp('date_upd')->nullable()->comment('fecha de actualizacion');
            $table->timeTz('hour_upd', 0)->nullable()->comment('hora de actualizacion');
            $table->timeTz('hours_use', 0)->nullable()->comment('horas de uso');
            $table->string('km_route', 30)->nullable()->comment('kilometros recorrido');
            $table->string('adicional', 100)->nullable()->comment('adicional');
            $table->string('filenamepdf', 100)->nullable()->comment('nombre de pdf');
            $table->integer('accion_correctiva_cli')->nullable()->comment('accion correctiva match con la table gen_resources_detail');
            //$table->integer('id_nc_cli')->nullable()->comment('id de nota de credito de cliente');
            $table->integer('nc_fiscal_document_id')->nullable()->comment('id de documento nc');
            $table->string('num_nc_cli', 30)->nullable()->comment('nota de credito de cliente');
            $table->integer('fac_order_id')->nullable()->comment('id de orden de factura');
            $table->string('fac_nom_vendedor', 50)->nullable()->comment('nombre de vendedor de factura');
            $table->date('fac_date_emision')->nullable()->comment('fecha de emision de factura');
            $table->string('fac_guia_remision', 50)->nullable()->comment('guia de remision de factura ');
            $table->string('fac_suc', 50)->nullable()->comment('sucursal de factura ');
            $table->string('fac_alm', 50)->nullable()->comment('almacen de factura ');
            $table->string('fac_dir_cli', 150)->nullable()->comment('direccion de cliente de factura');
            $table->tinyInteger('alert_state')->default(0)->nullable()->comment('estado de alerta');
            $table->integer('alert_id_user')->nullable()->comment('usuario de la alerta');
            $table->date('alert_date')->nullable()->comment('fecha de la alerta');
            $table->string('alert_description')->nullable()->comment('descripción de la alerta');
            $table->timestamp('alert_date_reg')->nullable()->comment('fecha de registros de la alerta');
            $table->timestamp('alert_date_upd')->nullable()->comment('fecha de actualizacion de la alerta');
            $table->integer('id_motivo')->nullable()->comment('motivo del proceso (ESTADO) match con la tabla gen_resource_detail');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('poventa_request');
    }
}
