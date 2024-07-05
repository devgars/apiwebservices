<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ord_quotes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id')->comment('EMPRESA');
            $table->foreign('company_id')->references('id')->on('org_structures');
            $table->bigInteger('subsidiary_id')->comment('SUCURSAL');
            $table->foreign('subsidiary_id')->references('id')->on('org_structures');
            $table->bigInteger('customer_id')->comment('CLIENTE');
            $table->foreign('customer_id')->references('id')->on('customers');
            $table->string('quote_number', 11)->comment('Número de Cotización');
            $table->bigInteger('seller_id')->comment('Vendedor');
            $table->foreign('seller_id')->references('id')->on('user_users');
            $table->bigInteger('currency_id')->comment('Tipo de Moneda');
            $table->foreign('currency_id')->references('id')->on('gen_resource_details');

            $table->bigInteger('payment_type_id')->comment('Forma de pago');
            $table->foreign('payment_type_id')->references('id')->on('gen_resource_details');
            $table->bigInteger('payment_condition_id')->comment('Condición de Pago');
            $table->foreign('payment_condition_id')->references('id')->on('gen_resource_details');
            $table->smallInteger('credit_days')->default(1)->comment('Días de crédito');
            $table->bigInteger('warehouse_id')->comment('Almacén');
            $table->foreign('warehouse_id')->references('id')->on('gen_resource_details');
            $table->bigInteger('delivery_type_id')->comment('Tipo de entrega: Recojo Cliente, Entrega M&M, Empresa de Transporte (Provincias)');
            $table->foreign('delivery_type_id')->references('id')->on('gen_resource_details');
            $table->integer('carrier_id')->nullable()->comment('Empresa de envío');
            $table->foreign('carrier_id')->references('id')->on('gen_resource_details');

            $table->integer('quote_date')->comment('Fecha de Cotización');
            $table->decimal('discount', 15, 2)->default(0)->comment('Descuento Global');
            $table->decimal('subtotal', 15, 2)->comment('Monto sin IGV');
            $table->decimal('igv_tax', 5, 2)->default(ENV('IGV'))->comment('Porcentaje IGV');
            $table->decimal('total_tax', 12, 2)->comment('Total IGV');
            $table->decimal('total', 15, 2)->comment('Monto total');
            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');
            $table->tinyInteger('reg_status')->comment('Estado de Registro');

            $table->unique(['company_id', 'quote_number']);
            $table->index(['company_id', 'quote_number']);
            $table->index(['company_id', 'subsidiary_id']);
            $table->index(['company_id', 'customer_id']);
            $table->index('company_id');
            $table->index('subsidiary_id');
            $table->index('customer_id');
            $table->index('quote_number');
            $table->index('quote_date');
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
        Schema::dropIfExists('ord_quotes');
    }
}
