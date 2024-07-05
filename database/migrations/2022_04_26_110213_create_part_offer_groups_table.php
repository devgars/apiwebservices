<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePartOfferGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('part_offer_groups', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('company_id')->comment('Empresa');
            $table->foreign('company_id')->references('id')->on('establishments');
            $table->bigInteger('offer_id')->comment('Nro. Oferta AS400');
            $table->string('offer_description', 100)->comment('Descripción de lista de precios');
            $table->bigInteger('offer_type_id')->comment('ID Tipo oferta');
            $table->foreign('offer_type_id')->references('id')->on('gen_resource_details');
            $table->string('origin_code', 2)->comment('Origen');
            $table->bigInteger('company_group_id')->comment('Id grupo de empresas');
            $table->foreign('company_group_id')->references('id')->on('gen_resource_details');
            $table->string('currency_code', 2)->comment('Moneda');
            $table->date('init_date')->comment('Fecha de Inicio');
            $table->date('end_date')->comment('Fecha Fin');

            $table->datetime('created_at')->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');
            $table->tinyInteger('reg_status')->default(1)->comment('Estado de Registro');

            $table->unique(['company_id', 'offer_id']);
            $table->index('company_id');
            $table->index('offer_id');
            $table->index('offer_description');
            $table->index('offer_type_id');
            $table->index('company_group_id');
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
        Schema::dropIfExists('part_offer_groups');
    }
}
