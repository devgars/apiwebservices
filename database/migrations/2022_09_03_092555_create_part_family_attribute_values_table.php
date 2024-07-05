<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePartFamilyAttributeValuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('part_family_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('part_detail_id')->comment('Id en tabla parte-detalle');
            $table->foreign('part_detail_id')->references('id')->on('part_part_details');
            $table->bigInteger('part_family_attribute_id')->comment('Id en tabla part_family_attribute');
            $table->foreign('part_family_attribute_id')->references('id')->on('part_family_attributes');
            $table->string('attribute_value', 50)->comment('Valor del atributo');

            $table->tinyInteger('reg_status')->default(1)->comment('Estado de Registro');
            $table->datetime('created_at')->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');

            $table->unique(['part_detail_id', 'part_family_attribute_id']);
            $table->index('part_detail_id');
            $table->index('part_family_attribute_id');
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
        Schema::dropIfExists('part_family_attribute_values');
    }
}
