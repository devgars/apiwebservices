<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumndetailToPoventaProducDetailRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('poventa_produc_detail_request', function (Blueprint $table) {
            $table->longText('detail_init')->nullable()->comment('Comentario del cliente por el reclamo');
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
            $table->dropColumn('detail_init');
        });
    }
}
