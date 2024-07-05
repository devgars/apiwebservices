<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ord_orders', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id')->comment('Empresa');
            $table->foreign('company_id')->references('id')->on('establishments');
            $table->bigInteger('subsidiary_id')->comment('Sucursal');
            $table->foreign('subsidiary_id')->references('id')->on('establishments');
            $table->bigInteger('customer_id')->comment('Cliente');
            $table->foreign('customer_id')->references('id')->on('customers');
            $table->bigInteger('customer_address_id')->nullable()->comment('Dirección de entrega cliente');
            $table->foreign('customer_address_id')->references('id')->on('customer_addresses');
            $table->bigInteger('document_type_id')->comment('Tipo de Documento: Factura(Normal, Servicio, Transferencia Gratuita)|Boleta|Nota de Venta');
            $table->foreign('document_type_id')->references('id')->on('gen_resource_details');
            $table->integer('order_number')->comment('Número de Pedido');
            $table->integer('order_date')->comment('Fecha de Pedido');
            $table->integer('order_time')->default(1)->comment('Hora de Pedido');
            $table->bigInteger('origin_id')->nullable()->comment('Origen del Pedido: Mostrador, Web, Vendedor...');
            $table->foreign('origin_id')->references('id')->on('gen_resource_details');
            $table->bigInteger('quote_id')->nullable()->comment('Cotización');
            $table->foreign('quote_id')->references('id')->on('ord_quotes');
            $table->bigInteger('seller_id')->comment('Vendedor');
            $table->foreign('seller_id')->references('id')->on('user_users');
            $table->bigInteger('attended_by_user_id')->nullable()->comment('Atendido por');
            $table->foreign('attended_by_user_id')->references('id')->on('user_users');
            $table->bigInteger('currency_id')->comment('Tipo de Moneda');
            $table->foreign('currency_id')->references('id')->on('gen_resource_details');
            $table->bigInteger('payment_type_id')->nullable()->comment('Forma de pago');
            $table->foreign('payment_type_id')->references('id')->on('gen_resource_details');
            $table->bigInteger('payment_condition_id')->nullable()->comment('Condición de Pago');
            $table->foreign('payment_condition_id')->references('id')->on('gen_resource_details');
            $table->smallInteger('credit_days')->default(1)->comment('Días de crédito');
            $table->bigInteger('warehouse_id')->nullable()->comment('Almacén');
            $table->foreign('warehouse_id')->references('id')->on('establishments');
            $table->bigInteger('delivery_type_id')->comment('Tipo de entrega: Recojo Cliente, Entrega M&M, Empresa de Transporte (Provincias)');
            $table->foreign('delivery_type_id')->references('id')->on('gen_resource_details');
            $table->integer('carrier_id')->nullable()->comment('Empresa de envío');
            $table->foreign('carrier_id')->references('id')->on('gen_resource_details');
            $table->decimal('customer_class_discount_rate', 5, 2)->default(0)->comment('Porcentaje de descuento - Clase cliente');
            $table->decimal('customer_class_total_discount', 15, 2)->default(0)->comment('Total Descuento por clase de cliente');
            $table->decimal('payment_type_discount_rate', 5, 2)->default(0)->comment('Porcentaje de descuento - Clase cliente');
            $table->decimal('payment_type_total_discount', 15, 2)->default(0)->comment('Total Descuento por tipo de pago');
            $table->decimal('global_discount', 15, 2)->default(0)->comment('Total Descuento Global');
            $table->decimal('subtotal', 15, 2)->comment('Monto sin IGV');
            $table->decimal('igv_tax', 5, 2)->default(ENV('IGV'))->comment('Porcentaje IGV');
            $table->decimal('total_tax', 12, 2)->comment('Total IGV');
            $table->decimal('total', 15, 2)->comment('Monto total');
            $table->bigInteger('user_id')->nullable()->comment('Usuario');
            $table->foreign('user_id')->references('id')->on('user_users');
            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');
            $table->char('reg_doc_status', 1)->nullable()->comment('Estado de documento generado -> B, F, G');
            $table->char('reg_order_doc_status', 1)->comment('Estado de pedido documento -> A, C, I');
            $table->tinyInteger('reg_status')->comment('Estado de Registro -> A, I');

            $table->unique(['company_id', 'subsidiary_id', 'order_number']);
            $table->index(['company_id', 'subsidiary_id', 'order_number']);
            $table->index(['company_id', 'subsidiary_id']);
            $table->index(['company_id', 'order_number']);
            $table->index(['company_id', 'customer_id']);
            $table->index('company_id');
            $table->index('subsidiary_id');
            $table->index('customer_id');
            $table->index('document_type_id');
            $table->index('order_number');
            $table->index('order_date');
            $table->index('created_at');
            $table->index('reg_doc_status');
            $table->index('reg_order_doc_status');
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
        Schema::dropIfExists('ord_orders');
    }
}
