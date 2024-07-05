<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToPartPartDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('part_part_details', function (Blueprint $table) {
            $table->bigInteger('part_detail_replacement_id')->nullable()->comment('Id detalle parte por la que ha sido reemplazada la parte actual');
            $table->foreign('part_detail_replacement_id')->references('id')->on('part_part_details');

            $table->index('part_detail_replacement_id');
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
