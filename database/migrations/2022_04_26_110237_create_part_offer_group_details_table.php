<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePartOfferGroupDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('part_offer_group_details', function (Blueprint $table) {
            $table->id();
            $table->integer('part_offer_group_id')->comment('Id de oferta');
            $table->foreign('part_offer_group_id')->references('id')->on('part_offer_groups');
            $table->bigInteger('part_detail_id')->comment('id Part Detail');
            $table->foreign('part_detail_id')->references('id')->on('part_part_details');
            $table->decimal('offer_price', 12, 2)->comment('Precio de oferta');
            $table->decimal('discount_rate', 12, 2)->comment('Porcentaje de descuento');

            $table->datetime('created_at')->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');
            $table->tinyInteger('reg_status')->default(1)->comment('Estado de Registro');

            $table->unique(['part_offer_group_id', 'part_detail_id']);
            $table->index('part_offer_group_id');
            $table->index('part_detail_id');
            $table->index('offer_price');
            $table->index('discount_rate');
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
        Schema::dropIfExists('part_offer_group_details');
    }
}
