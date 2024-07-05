<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewcolumToPoventaProducDetailRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('poventa_produc_detail_request', function (Blueprint $table) {
            $table->longText('cause_failure', 150)->nullable()->comment('Causas de falla del repuesto')->after('type_money_cli');
            $table->longText('recommendations', 150)->nullable()->comment('Recomendaciones')->after('type_money_cli');
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
            $table->dropColumn('cause_failure', 150);
            $table->dropColumn('recommendations', 150);
        });
    }
}
