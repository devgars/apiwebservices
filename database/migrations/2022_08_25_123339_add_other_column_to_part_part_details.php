<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOtherColumnToPartPartDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('part_part_details', function (Blueprint $table) {
            $table->string('product_remarks', 200)->nullable()->comment('Observaciones/Comentarios del producto');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('part_part_details', function (Blueprint $table) {
            //
        });
    }
}
