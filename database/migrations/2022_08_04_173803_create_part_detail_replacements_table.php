<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePartDetailReplacementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('part_detail_replacements', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('part_detail_id')->comment('id en detalle de partes SKU');
            $table->foreign('part_detail_id')->references('id')->on('part_part_details');
            $table->bigInteger('part_detail_replacement_id')->comment('Reemplazo de detalle de parte SKU');
            $table->foreign('part_detail_replacement_id')->references('id')->on('part_part_details');
            $table->bigInteger('part_detail_last_replace_id')->nullable()->comment('Último reemplazo de detalle de parte SKU');
            $table->foreign('part_detail_last_replace_id')->references('id')->on('part_part_details');
            $table->tinyInteger('reg_status')->default(1)->comment('Estado de Registro');

            $table->datetime('created_at')->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');

            $table->index('part_detail_id');
            $table->index('part_detail_replacement_id');
            $table->index('part_detail_last_replace_id');
            $table->index('reg_status');
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
        Schema::dropIfExists('part_detail_replacements');
    }
}
