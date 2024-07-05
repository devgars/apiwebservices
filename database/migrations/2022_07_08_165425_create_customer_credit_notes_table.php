<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerCreditNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_credit_notes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id')->comment('Empresa');
            $table->foreign('company_id')->references('id')->on('establishments');
            $table->bigInteger('subsidiary_id')->comment('Sucursal');
            $table->foreign('subsidiary_id')->references('id')->on('establishments');
            $table->bigInteger('warehouse_id')->comment('Almacén');
            $table->foreign('warehouse_id')->references('id')->on('establishments');
            $table->string('credit_note_number', 10)->comment('Número de nota de crédito');
            $table->string('return_type_code', 2)->comment('Código de Tipo de Devolución');
            $table->string('reason_type_code', 2)->comment('Código de Motivo de Devolución');
            $table->date('credit_note_date')->comment('Fecha de NC');
            $table->bigInteger('seller_id')->comment('Vendedor');
            $table->decimal('condition_payment_discount_rate', 5, 2)->default(0)->comment('% Descuento por condición de pago');
            $table->decimal('customer_class_discount_rate', 5, 2)->default(0)->comment('% Descuento por clase de cliente');
            $table->decimal('total_amount', 15, 2)->default(0)->comment('Total');
            $table->decimal('condition_payment_discount_amount', 15, 2)->default(0)->comment('Monto descuento por condición de pago');
            $table->decimal('customer_class_discount_amount', 15, 2)->default(0)->comment('Monto descuento por clase de cliente');
            $table->decimal('tax_amount', 15, 2)->default(0)->comment('Monto Impuestos');
            $table->string('document_type_code', 2)->comment('Tipo de documento');

            $table->datetime('created_at')->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');
            $table->tinyInteger('reg_status')->default(1)->comment('Estado de Registro');

            $table->unique(['company_id', 'subsidiary_id', 'credit_note_number']);
            $table->index('subsidiary_id');
            $table->index('credit_note_number');
            $table->index('warehouse_id');
            $table->index('credit_note_date');
            $table->index('document_type_code');
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
        Schema::dropIfExists('customer_credit_notes');
    }
}
