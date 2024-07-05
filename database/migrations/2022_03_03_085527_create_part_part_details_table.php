<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePartPartDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('part_part_details', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('part_id')->comment('FK tabla part_parts');
            $table->foreign('part_id')->references('id')->on('part_parts');
            $table->bigInteger('line_id')->comment('Linea: Volvo, Scania, Mercedez, etc.');
            $table->bigInteger('origin_id')->comment('Origen: Genuino, Original, Nacional');
            $table->bigInteger('trademark_id')->comment('Marca');
            $table->string('sku', 30)->comment('SKU del producto');
            $table->string('factory_code', 50)->nullable()->comment('Código de Fabricante');
            $table->char('rotation', 1)->default('-')->comment('Rotación del producto');
            $table->string('principal_image', 200)->nullable()->comment('Imagen Principal');
            $table->smallInteger('weight')->default(0)->comment('Orden de aparición del producto');
            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');
            $table->tinyInteger('reg_status')->comment('Estado de Registro');

            $table->unique(['part_id', 'line_id', 'origin_id', 'trademark_id']);

            $table->index('part_id');
            $table->index('line_id');
            $table->index('origin_id');
            $table->index('trademark_id');
            $table->index('created_at');
            $table->index('reg_status');

            $table->index('sku');
            $table->index('factory_code');
            $table->index('rotation');
            $table->index('principal_image');
            $table->index('weight');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('part_part_details');
    }
}
