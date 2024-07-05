<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->comment('Código de Cliente AS400');

            $table->bigInteger('company_type_id')->comment('Tipo de Empresa: Persona Natural|Con negocio|Jurídica');
            $table->bigInteger('document_type_id')->comment('Tipo de documento de identidad');
            $table->string('document_number', 20)->comment('Número de documento');
            $table->string('name_social_reason', 100)->comment('Nommbres o Razón Social');
            $table->string('tradename', 50)->nullable()->comment('Nombre Comercial');
            $table->string('ruc_code_old', 8)->nullable()->comment('Número de RUC 8 digitos');
            $table->integer('economic_group_id')->nullable()->comment('Grupo Económico');
            $table->bigInteger('business_turn')->comment('Giro del Negocio');

            $table->bigInteger('country_id')->default(163)->comment('País');
            $table->foreign('country_id')->references('id')->on('ubigeos');

            $table->bigInteger('region_id')->default(163)->comment('Distrito');
            $table->foreign('region_id')->references('id')->on('ubigeos');

            $table->string('client_class', 2)->comment('clase de cliente');
            $table->integer('reg_date')->comment('Fecha de Inscripcion');
            $table->integer('capital_amount')->nullable()->comment('Capital de la empresa');

            $table->string('tax_condition', 2)->comment('Condicion Tributaria');
            $table->string('currency_code', 2)->comment('Codigo de Moneda');
            $table->double('max_credit_limit', 2, 8)->comment('Importe Limite Credito');
            $table->integer('consumption_amount')->nullable()->comment('Importe de Consumo');
            $table->string('sales_block', 1)->nullable()->comment('Bloque de Ventas');
            $table->string('credit_block', 1)->nullable()->comment('Bloque de Creditos');

            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');
            $table->tinyInteger('reg_status')->comment('Estado de Registro');

            $table->unique('code');

            $table->index('company_type_id');
            $table->index('document_type_id');
            $table->index('document_number');
            $table->index('business_turn');
            $table->index('client_class');
            $table->index('max_credit_limit');
            $table->index('sales_block');
            $table->index('credit_block');

            $table->index('created_at');
            $table->index('updated_at');
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
        Schema::dropIfExists('customers');
    }
}
