<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePoventaProducDetailRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    { 
        Schema::create('poventa_produc_detail_request', function (Blueprint $table) {
            $table->id();
            $table->integer('id_request')->comment('id de solicitud (posventa_request)');
            $table->string('num_fac', 50)->nullable()->comment('numero de factura');
            $table->string('factory_code', 50)->nullable()->comment('codigo de fabricante sku');
            $table->string('sku', 50)->nullable()->comment('id de producto sku');
            $table->integer('part_detail_id')->nullable()->comment('id de producto part_detail_id');
            $table->string('code', 30)->nullable()->comment('codigo de producto part_code');
            $table->string('brand', 50)->nullable()->comment('marca');
            $table->string('description')->nullable()->comment('Descripción');
            $table->integer('unit_ven')->nullable()->comment('unidad de venta');
            $table->integer('unit_rec')->nullable()->comment('unidad de reclamo');
            $table->smallInteger('state')->nullable()->comment('estado');
            $table->timestamp('date_reg')->nullable()->comment('fecha de registro');
            $table->timestamp('date_upd')->nullable()->comment('fecha de actualización'); 
            $table->integer('id_motivo')->nullable()->comment('motivo del proceso (ESTADO) match con la tabla gen_resource_detail');
            $table->longText('detail')->nullable()->comment('detalle de revisión');
            $table->string('subjet')->nullable()->comment('asunto de revisión');
            $table->string('unit_proc')->nullable()->comment('unidad procedente');
            $table->string('filename_cli', 100)->nullable()->comment('nombre de archivo del cliente');
            $table->integer('id_user_reg')->nullable()->comment('id usuario registro');
            $table->integer('id_user_up')->nullable()->comment('id usuario actualizado');
            $table->decimal('costo_eva', 10,2)->nullable()->comment('cosoto de evaluación'); 
            $table->string('type_money_cli', 10)->nullable()->comment('tipo de moneda al cliente');
            $table->integer('id_accion_correctiva')->nullable()->comment('acción correcativa match con la tabla gen_resource_detail');
            $table->integer('status_product')->nullable()->comment('Estado de del producto [PROCEDE, NO PROCEDE] (tabla gen_resource_detail )');

            $table->decimal('item_price', 10,2)->nullable()->comment('precio de producto');
            $table->decimal('total_ven', 10,2)->nullable()->comment('Total venta de producto');
            $table->string('origin_code', 5)->nullable()->comment('codigo de origen');
            $table->string('line_code', 5)->nullable()->comment('codigo de linea');
            $table->integer('order_id')->nullable()->comment('id de orden');
            $table->integer('oc_id')->nullable()->comment('orden de compra id');
            $table->integer('oc_purchase_num')->nullable()->comment('número de ordec de compra');
            $table->decimal('costo_proveedor', 10,2)->nullable()->comment('orden de compra id');
            $table->integer('id_proveedor')->nullable()->comment('id de proveedor');
            $table->string('name_proveedor', 50)->nullable()->comment('nombre de proveedor');
            $table->integer('state_proveedor')->nullable()->comment('estado cuando este en la seccion proveedor');
            $table->string('prov_solution_proveedor', 10)->nullable()->comment('Tipo/Solucion de proveedor [NC, FAC, DES]');
            $table->string('prov_file_name_nc', 100)->nullable()->comment('nombre de archivo');
            $table->string('prov_file_description_nc')->nullable()->comment('descripcion de archivo de nota de credito');
            $table->string('prov_num_nc', 50)->nullable()->comment('numero de nota de credito');
            $table->string('prov_type_money_nc', 10)->nullable()->comment('tipo de moneda');
            $table->date('prov_date_nc')->nullable()->comment('fecha de nota de crédito');
            $table->decimal('prov_importe_nc', 10, 2)->nullable()->comment('Importe de nota de credito');
            $table->string('prov_num_fac', 50)->nullable()->comment('numero de factura ');
            $table->date('prov_date_fac')->nullable()->comment('numero de factura ');
            $table->string('prov_tipo_desc', 10)->nullable()->comment('Tipo Descuento');
            $table->decimal('prov_monto_desc', 10, 2)->nullable()->comment('monto a descontar ');
            $table->decimal('prov_porcentaje_desc', 10, 2)->nullable()->comment('porcentaje a descontar ');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('poventa_produc_detail_request');
    }
}
