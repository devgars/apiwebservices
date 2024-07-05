<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePartOffersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('part_offers', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('company_id')->comment('Empresa');
            $table->foreign('company_id')->references('id')->on('establishments');
            $table->string('offer_code', 15)->comment('Código de la promoción/oferta');
            $table->string('offer_description', 80)->comment('Descripción de la promoción/oferta');
            $table->string('discount_state', 2)->nullable()->comment('Estado descuento');
            $table->bigInteger('type_offer_id')->comment('Tipo de promoción/oferta');
            $table->foreign('type_offer_id')->references('id')->on('gen_resource_details');
            $table->smallInteger('year_offer')->comment('Año de la promoción/oferta');
            $table->dateTime('init_date')->comment('Fecha de inicio promoción/oferta');
            $table->dateTime('end_date')->comment('Fecha de finalización promoción/oferta');
            $table->datetime('created_at')->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');
            $table->tinyInteger('reg_status')->default(1)->comment('Estado de Registro');

            $table->unique(['company_id', 'offer_code']);
            $table->index('company_id');
            $table->index('offer_code');
            $table->index('type_offer_id');
            $table->index('year_offer');
            $table->index('init_date');
            $table->index('end_date');
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
        Schema::dropIfExists('part_offers');
    }
}
