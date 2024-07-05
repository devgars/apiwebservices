<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumn1ToPoventaRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('poventa_request', function (Blueprint $table) {
            $table->string('serie_name',5)->nullable()->comment('cadena serie');    
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
            $table->dropColumn('serie_name');
        });
    }
}
