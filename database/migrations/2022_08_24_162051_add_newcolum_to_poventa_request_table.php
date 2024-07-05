<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewcolumToPoventaRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('poventa_request', function (Blueprint $table) {
            $table->integer('devolucion')->default(0)->nullable()->comment('Devolucion de cliente');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('poventa_request', function (Blueprint $table) {
            $table->dropColumn('devolucion');
        });
    }
}
