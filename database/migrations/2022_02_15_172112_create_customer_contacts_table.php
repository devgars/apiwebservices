<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_contacts', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('customer_id')->comment('Cliente Id');
            $table->foreign('customer_id')->references('id')->on('customers');

            $table->bigInteger('work_position_id')->comment('Cargo del contacto');
            $table->string('contact_name', 30)->nullable()->comment('Nombre de contacto');
            $table->string('contact_phone', 15)->nullable()->comment('Teléfono de contacto');
            $table->string('contact_email', 70)->nullable()->comment('Email de contacto');
            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');
            $table->tinyInteger('reg_status')->comment('Estado de Registro');
            $table->smallInteger('customer_contact_number')->default(1)->comment('ITEM01 en AS400');

            $table->unique(['customer_id', 'customer_contact_number']);

            $table->index('customer_id');
            $table->index('customer_contact_number');
            $table->index('contact_email');
            $table->index('work_position_id');
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
        Schema::dropIfExists('customer_contacts');
    }
}
