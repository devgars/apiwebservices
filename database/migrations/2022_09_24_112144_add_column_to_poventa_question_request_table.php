<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToPoventaQuestionRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('poventa_question_request', function (Blueprint $table) {
            $table->integer('id_product_detail_request')->nullable()->comment('id de la tabla poventa_product_detail_request ');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('poventa_question_request', function (Blueprint $table) {
            $table->dropColumn('id_product_detail_request');
        });
    }
}
