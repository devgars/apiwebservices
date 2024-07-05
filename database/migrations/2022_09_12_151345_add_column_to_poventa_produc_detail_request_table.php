<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToPoventaProducDetailRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('poventa_produc_detail_request', function (Blueprint $table) {
            $table->integer('recover_discard')->nullable()->comment('Campo para recuperar o se deshechar producto');
            $table->longText('detail_recover_discard')->nullable()->comment('Campo para recuperar o se deshechar producto');
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('poventa_produc_detail_request', function (Blueprint $table) {
            $table->dropColumn('recover_discard');
            $table->dropColumn('detail_recover_discard');
        });
    }
}
