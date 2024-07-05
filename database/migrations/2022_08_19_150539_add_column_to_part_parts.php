<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToPartParts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('part_parts', function (Blueprint $table) {
            $table->bigInteger('line_id')->nullable()->comment('Linea: Volvo, Scania, Mercedez, etc.');
            $table->foreign('line_id')->references('id')->on('gen_resource_details');

            $table->string('product_features', 200)->nullable()->comment('Características del producto');
            $table->bigInteger('principal_family_id')->nullable()->comment('Familia principal');

            $table->index('line_id');
            $table->index('principal_family_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('part_parts', function (Blueprint $table) {
            //
        });
    }
}
