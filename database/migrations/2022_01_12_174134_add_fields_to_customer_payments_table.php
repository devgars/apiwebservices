<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToCustomerPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            $table->datetime('fecha_hora_actualizacion_db2')->nullable()->comment('Fecha y Hora de actualización de registro en AS400');
            $table->index('fecha_hora_actualizacion_db2');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            $table->dropIndex('fecha_hora_actualizacion_db2');
            $table->dropColumn('fecha_hora_actualizacion_db2');
        });
    }
}
