<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnCustomerPayments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            $table->bigInteger('return_request_id')->nullable()->comment('Numero de Extorno');
            $table->foreign('return_request_id')->references('id')->on('bank_return_requests');

            $table->datetime('return_request_date')->nullable()->comment('Fecha-Hora Extorno');

            $table->index('return_request_id');
            $table->index('return_request_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
