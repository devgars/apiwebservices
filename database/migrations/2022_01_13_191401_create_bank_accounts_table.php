<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBankAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('bank_id');
            $table->foreign('bank_id')->references('id')->on('banks');

            $table->string('currency_code', 2)->comment('Código Moneda ERP: 01 -> PEN, 02 -> USD');
            $table->string('account_type', 15)->comment('Tipo de Cuenta');
            $table->string('account_code', 30)->comment('Código/Número de Cuenta');
            $table->boolean('active')->default(true)->comment('Activa/Inactiva');

            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->datetime('updated_at')->nullable();

            $table->index('created_at');
            $table->index('updated_at');
            $table->unique(['bank_id', 'account_code']);
            $table->index('bank_id');
            $table->index('account_code');
            $table->index('currency_code');
            $table->index('active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bank_accounts');
    }
}
