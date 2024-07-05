<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerDebtsBankConsultationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_debts_bank_consultations', function (Blueprint $table) {
            $table->id();
            $table->string('bankCode', 3);
            $table->string('processId', 10);
            $table->string('requestId', 40);
            $table->string('channel', 4);
            $table->string('currencyCode', 3);
            $table->string('serviceId', 4);
            $table->string('customerIdentificationCode', 20);
            $table->datetime('transactionDate');
            $table->json('customerDebts');

            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->datetime('updated_at')->nullable();

            $table->unique(['bankCode', 'requestId']);
            $table->index('bankCode');
            $table->index('requestId');
            $table->index('channel');
            $table->index('customerIdentificationCode');

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
        Schema::dropIfExists('customer_debts_bank_consultations');
    }
}
