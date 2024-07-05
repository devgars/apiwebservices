<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEstablishmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('establishments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code', 5)->comment('Código AS400');
            $table->string('name')->comment('Nombre de establecimiento');
            $table->string('description')->nullable()->comment('Descripción de establecimiento');
            $table->integer('type_id')->comment('Tipo de establecimiento: EMPRESA, SUCURSAL, ALMACEN, ETC.');
            $table->foreign('type_id')->references('id')->on('establishment_types');
            $table->integer('parent_establishment_id')->nullable()->comment('Establecimiento padre');
            $table->foreign('parent_establishment_id')->references('id')->on('establishments');
            $table->bigInteger('region_id')->comment('Distrito');
            $table->foreign('region_id')->references('id')->on('ubigeos');
            $table->string('address', 100)->commet('Dirección');
            $table->string('contact_name', 50)->nullable()->commet('Persona Contacto');
            $table->string('phone_number', 20)->nullable()->commet('Número Telefónico');
            $table->string('email', 40)->nullable()->commet('Correo Electrónico');
            $table->datetime('created_at')->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');
            $table->smallInteger('order')->comment('Orden');
            $table->tinyInteger('reg_status')->comment('Estado de Registro');

            $table->unique(['code', 'type_id', 'parent_establishment_id']);
            $table->unique(['name']);
            $table->index('code');
            $table->index('name');
            $table->index('type_id');
            $table->index('parent_establishment_id');
            $table->index('region_id');
            $table->index('order');
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
        Schema::dropIfExists('establishments');
    }
}
