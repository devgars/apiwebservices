<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBankReturnRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bank_return_requests', function (Blueprint $table) {
            $table->id();
            $table->string('requestId', 40);
            $table->string('operationNumberAnnulment', 40);
            $table->string('bankCode', 3);
            $table->string('customerIdentificationCode', 20);
            $table->string('channel', 4)->comment('Canal de pago');
            $table->string('returnType', 1)->default('M')->comment('A -> AUTOMÁTICO, M -> MANUAL');
            $table->datetime('transactionDate');

            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->datetime('updated_at')->nullable();

            $table->unique(['bankCode', 'requestId']);
            $table->index('bankCode');
            $table->index('requestId');
            $table->index('channel');
            $table->index('returnType');
            $table->index('customerIdentificationCode');
            $table->index('transactionDate');
            $table->index('created_at');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bank_return_requests');
    }
}
