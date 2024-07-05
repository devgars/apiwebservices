<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumn2ToPoventaProducDetailRequestTable extends Migration
{
    public function up()
    {
        Schema::table('poventa_produc_detail_request', function (Blueprint $table) {
            $table->longText('conclusion_detail')->nullable()->comment('conclusion detalle');
            $table->smallInteger('devolution')->nullable()->comment('estado devolucion [1,0]');
            $table->longText('evidence')->nullable()->comment('evidencia de imagen');
        });
    }
    public function down()
    {
        Schema::table('poventa_produc_detail_request', function (Blueprint $table) {
            $table->dropColumn('conclusion_detail');
            $table->dropColumn('devolution');
            $table->dropColumn('evidence');
        });
    }
}
