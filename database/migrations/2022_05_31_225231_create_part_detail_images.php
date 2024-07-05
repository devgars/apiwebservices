<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePartDetailImages extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('part_detail_images', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('part_detail_id')->comment('Part_detail_id');
            $table->foreign('part_detail_id')->references('id')->on('part_part_details');
            $table->string('image')->comment('Imagen');
            $table->datetime('created_at')->comment('Fecha de creación');
            $table->datetime('updated_at')->nullable()->comment('Fecha de Actualización');

            $table->unique(['part_detail_id', 'image']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('part_detail_images');
    }
}
