<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToPoventaTrackingFileTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('poventa_tracking_file', function (Blueprint $table) {
            $table->smallInteger('status_type')->nullable()->comment('Campo para distinguir donde se guarda los archivos [1=>al momento de registrar las sol., 2=>al momento de hacer el seguimiento]');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('poventa_tracking_file', function (Blueprint $table) {
            $table->dropColumn('status_type');
        });
    }
}
