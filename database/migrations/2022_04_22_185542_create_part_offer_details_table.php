<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePartOfferDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('part_offer_details', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('offer_id')->comment('Id de Promoción/Oferta');
            $table->foreign('offer_id')->references('id')->on('part_offers');
            $table->bigInteger('part_detail_id')->comment('Detalle de parte');
            $table->foreign('part_detail_id')->references('id')->on('part_part_details');
            $table->decimal('list_price', 12, 2)->comment('Precio lista');
            $table->decimal('min_price', 12, 2)->comment('Precio Mínimo');
            $table->decimal('cost_price', 12, 2)->comment('Precio-Costo');
            $table->decimal('discount_rate', 8, 2)->default(0)->comment('Porcentaje de descuento');
            $table->decimal('profit_rate', 8, 2)->default(0)->comment('Porcentaje de utilidad');
            $table->decimal('new_profit_rate', 8, 2)->default(0)->comment('Nuevo Porcentaje de utilidad');
            $table->decimal('base_factor', 8, 2)->default(0)->comment('Factor base');
            $table->datetime('created_at')->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');
            $table->tinyInteger('reg_status')->default(1)->comment('Estado de Registro');

            $table->unique(['offer_id', 'part_detail_id']);
            $table->index('offer_id');
            $table->index('part_detail_id');
            $table->index('list_price');
            $table->index('min_price');
            $table->index('cost_price');
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
        Schema::dropIfExists('part_offer_details');
    }
}
