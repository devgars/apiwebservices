<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_payments', function (Blueprint $table) {
            $table->id();
            $table->string('requestId', 40);
            $table->string('bankCode', 3);
            $table->string('serviceId', 10)->comment('processId an BBVA');
            $table->string('operationId', 14)->nullable()->comment('BCP genera números únicos por dia');
            $table->string('channel', 4)->comment('Canal de pago');
            $table->string('paymentType', 4)->comment('Efectivo, Cheque, TDC, TDD, Etc.');
            $table->string('currencyCode', 3)->comment('PEN, USD');
            $table->string('transactionCurrencyCode', 3)->comment('PEN, USD');
            $table->decimal('currencyExchange', 10, 2)->default(0)->comment('Tipo de cambio');
            //$table->string('serviceId', 4)->nullable();
            $table->string('customerIdentificationCode', 20);
            $table->datetime('transactionDate');
            $table->decimal('totalAmount', 15, 2)->comment('Monto total pagado');
            $table->json('check')->nullable()->comment('BCP: si es pago con cheque, numero y banco');
            $table->json('paidDocuments')->comment('Objeto que guarda los comprobantes pagados, numero, monto, etc');
            $table->json('otherFields')->nullable();

            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->datetime('updated_at')->nullable();

            $table->unique(['bankCode', 'requestId']);
            $table->index('bankCode');
            $table->index('requestId');
            $table->index('serviceId');
            $table->index('channel');
            $table->index('customerIdentificationCode');
            $table->index('transactionDate');
            $table->index('currencyCode');

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
        Schema::dropIfExists('customer_payments');
    }
}
