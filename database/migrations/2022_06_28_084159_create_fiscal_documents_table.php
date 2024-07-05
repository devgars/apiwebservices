<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFiscalDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fiscal_documents', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id')->comment('Empresa');
            $table->foreign('company_id')->references('id')->on('establishments');
            $table->string('company_name', 100)->comment('Nombre de M&M');
            $table->string('company_address', 100)->comment('Dirección de M&M');
            $table->string('company_ubigeo', 8)->comment('UBIGEO sde M&M');
            $table->bigInteger('subsidiary_id')->comment('Sucursal');
            $table->foreign('subsidiary_id')->references('id')->on('establishments');
            $table->bigInteger('warehouse_id')->comment('Almacén');
            $table->foreign('warehouse_id')->references('id')->on('establishments');
            $table->bigInteger('customer_id')->comment('Cliente');
            $table->foreign('customer_id')->references('id')->on('customers');
            $table->string('customer_name', 100)->comment('Nombre de cliente');
            $table->string('customer_address', 100)->comment('Dirección de cliente');
            $table->bigInteger('fiscal_document_type_id')->comment('Tipo de Documento: Factura(Normal, Servicio, Transferencia Gratuita)|Boleta|Nota de Venta|NC|NB');
            $table->foreign('fiscal_document_type_id')->references('id')->on('gen_resource_details');
            $table->integer('internal_number')->comment('Número interno de: Pedido, NC, ND');
            $table->integer('reg_date')->comment('Fecha de Registro');
            $table->integer('year_month')->comment('Periodo: Año Mes');

            $table->bigInteger('serie_as_id')->comment('Serie AS400');
            $table->foreign('serie_as_id')->references('id')->on('gen_resource_details');
            $table->string('correlative_as_number', 8)->comment('Número Correlativo AS400');
            $table->bigInteger('serie_fiscal_id')->comment('Id Serie Fiscal');
            $table->foreign('serie_fiscal_id')->references('id')->on('gen_resource_details');
            $table->string('correlative_fiscal_number', 8)->comment('Número Correlativo Fiscal');

            $table->bigInteger('seller_id')->comment('Vendedor');
            $table->foreign('seller_id')->references('id')->on('user_user_companies');
            $table->string('auth_code_as', 30)->nullable()->comment('Codigo de autorización AS400');
            $table->string('support_as', 100)->nullable()->comment('Sustento AS400');
            $table->bigInteger('currency_id')->comment('Moneda');
            $table->foreign('currency_id')->references('id')->on('gen_resource_details');
            $table->decimal('net_amount', 15, 2)->comment('Importe neto');
            $table->decimal('net_amount_taxed', 15, 2)->comment('Importe neto gravado');
            $table->decimal('net_exonerated_amount', 15, 2)->default(0)->comment('Importe neto exonerado');
            $table->decimal('principal_tax_amount', 15, 2)->comment('Importe IGV');
            $table->decimal('total_amount', 15, 2)->comment('Importe total');
            $table->decimal('withholding_tax_amount', 15, 2)->default(0)->comment('Importe retención de impuesto');
            $table->decimal('withholding_tax_base_amount', 15, 2)->default(0)->comment('Monto base de retención');

            $table->string('additional_tax_code', 4)->comment('Código de impuesto adicional');
            $table->decimal('additional_tax_amount', 15, 2)->default(0)->comment('Monto impuesto adicional');
            $table->decimal('additional_tax_rate', 5, 2)->default(0)->comment('Tasa impuesto adicional');

            $table->bigInteger('ord_order_id')->nullable()->comment('Id de pedido, si es Factura o Boleta');
            $table->foreign('ord_order_id')->references('id')->on('ord_orders');
            $table->bigInteger('parent_fiscal_document_id')->nullable()->comment('Documento Padre, si es NC o ND');
            $table->foreign('parent_fiscal_document_id')->references('id')->on('fiscal_documents');

            $table->char('additional_status', 1)->comment('Estado Adicional registro');
            $table->datetime('additional_status_date')->nullable()->comment('Fecha-hora Estado adicional de registro');
            $table->char('down_status_reg', 1)->comment('Estado de baja registro');
            $table->datetime('down_status_date')->nullable()->comment('Fecha-hora Estado de baja de registro');
            $table->tinyInteger('reg_status')->comment('Estado del registro');
            $table->datetime('anullment_date')->nullable()->comment('Fecha-hora Anulación');

            $table->bigInteger('user_id')->comment('Usuario creador del registro');
            $table->foreign('user_id')->references('id')->on('user_user_companies');
            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');


            $table->unique(['company_id', 'subsidiary_id', 'fiscal_document_type_id', 'internal_number']);
            $table->index('company_id');
            $table->index('subsidiary_id');
            $table->index('fiscal_document_type_id');
            $table->index('internal_number');
            $table->index('customer_id');
            $table->index('warehouse_id');
            $table->index('reg_date');
            $table->index('year_month');
            $table->index(['company_id', 'customer_id', 'serie_fiscal_id', 'correlative_fiscal_number']);
            $table->index('serie_fiscal_id');
            $table->index('correlative_fiscal_number');
            $table->index('currency_id');
            $table->index('seller_id');
            $table->index('ord_order_id');
            $table->index('parent_fiscal_document_id');

            $table->index('created_at');
            $table->index('additional_status');
            $table->index('down_status_reg');
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
        Schema::dropIfExists('fiscal_documents');
    }
}
