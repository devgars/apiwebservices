<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerCreditNoteDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_credit_note_details', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('credit_note_id')->comment('Nota de crédito');
            $table->foreign('credit_note_id')->references('id')->on('customer_credit_notes');
            $table->bigInteger('part_detail_id')->comment('SKU id');
            $table->foreign('part_detail_id')->references('id')->on('part_part_details');
            $table->integer('item1')->default(0)->comment('Item 1');
            $table->integer('item2')->default(0)->comment('Item 2');
            $table->decimal('returned_quantity', 11, 2)->default(0)->comment('Cantidad devuelta');
            $table->decimal('price', 11, 2)->default(0)->comment('Precio Unitario');
            $table->decimal('line_discount', 5, 2)->default(0)->comment('Descuento de linea');
            $table->decimal('additional_discount', 5, 2)->default(0)->comment('Descuento Adicional');
            $table->decimal('tax_rate', 5, 2)->default(18)->comment('Porcentaje IGV');

            $table->datetime('created_at')->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');
            $table->tinyInteger('reg_status')->default(1)->comment('Estado de Registro');

            $table->unique(['credit_note_id', 'part_detail_id']);
            $table->index('credit_note_id');
            $table->index('part_detail_id');
            $table->index('item1');
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
        Schema::dropIfExists('customer_credit_note_details');
    }
}
