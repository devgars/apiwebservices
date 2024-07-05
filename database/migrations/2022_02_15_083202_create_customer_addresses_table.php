<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('customer_id')->comment('Cliente Id');
            $table->foreign('customer_id')->references('id')->on('customers');

            $table->tinyInteger('address_order')->default(1)->comment('Orden de Direcciones');

            $table->bigInteger('address_type_id')->comment('Tipo de Dirección');
            $table->bigInteger('road_type_id')->comment('Tipo de vía');
            $table->string('road_name', 100)->comment('Nombre de  Vía');
            $table->string('number')->nullable()->comment('Nro. de Dirección');
            $table->string('apartment')->nullable()->comment('Nro. de Departamento');
            $table->string('floor')->nullable()->comment('Nro. de Piso');
            $table->string('block')->nullable()->comment('Nro. de Manzana');
            $table->string('allotment')->nullable()->comment('Nro. de Lote');
            $table->bigInteger('zone_type_id')->nullable()->comment('Tipo de zona');
            $table->string('zone_name', 100)->nullable()->comment('Nombre de  Zona');
            $table->bigInteger('country_id')->comment('País');
            $table->foreign('country_id')->references('id')->on('ubigeos');
            $table->bigInteger('region_id')->nullable()->comment('Distrito');
            $table->foreign('region_id')->references('id')->on('ubigeos');

            $table->string('contact_name', 30)->nullable()->comment('Nombre de contacto');
            $table->string('contact_phone', 15)->nullable()->comment('Teléfono de contacto');
            $table->string('contact_email', 70)->nullable()->comment('Email de contacto');
            $table->jsonb('geo_coordinates');

            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');
            $table->tinyInteger('reg_status')->comment('Estado de Registro');

            $table->unique(['address_order', 'customer_id']);

            $table->index('customer_id');
            $table->index('address_order');
            $table->index('country_id');
            $table->index('region_id');
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
        Schema::dropIfExists('customer_addresses');
    }
}
