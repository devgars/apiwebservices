<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePartFamilyAttributesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('part_family_attributes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('family_id')->comment('Id Familia');
            $table->foreign('family_id')->references('id')->on('gen_resource_details');
            $table->bigInteger('attribute_id')->comment('Id Familia');
            $table->foreign('attribute_id')->references('id')->on('gen_resource_details');
            $table->tinyInteger('reg_status')->default(1)->comment('Estado de Registro');
            $table->datetime('created_at')->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');

            $table->unique(['family_id', 'attribute_id']);
            $table->index('family_id');
            $table->index('attribute_id');
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
        Schema::dropIfExists('part_family_attributes');
    }
}
