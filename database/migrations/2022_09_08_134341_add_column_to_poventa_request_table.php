<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToPoventaRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('poventa_request', function (Blueprint $table) {
            $table->string('filename_request', 150)->nullable()->comment('Arvhicos subidos');
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
            $table->dropColumn('filename_request');
        });
    }
}
