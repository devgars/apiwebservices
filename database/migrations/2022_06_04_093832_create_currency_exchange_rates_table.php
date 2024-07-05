<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCurrencyExchangeRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('currency_exchange_rates', function (Blueprint $table) {
            $table->increments('id');
            $table->string('currency_code', 2)->comment('Código de tipo de moneda AS400: 01 -> SOLES, 02 -> DOLARES');
            $table->date('reg_date')->comment('Fecha');
            $table->float('official_buying_price')->default(0)->comment('Precio oficial (SBS/SUNAT) de compra');
            $table->float('official_selling_price')->default(0)->comment('Precio oficial (SBS/SUNAT) de venta');
            $table->float('official_average_price')->default(0)->comment('Precio oficial (SBS/SUNAT) promedio');
            $table->float('mym_buying_price')->comment('Precio de compra M&M');
            $table->float('mym_selling_price')->comment('Precio de venta M&M');
            $table->float('mym_average_price')->comment('Precio promedio M&M');
            $table->datetime('created_at')->comment('Fecha de creación');
            $table->datetime('updated_at')->nullable()->comment('Fecha de Actualización');
            $table->tinyInteger('reg_status')->default(1)->comment('Estado de Registro');

            $table->unique(['currency_code', 'reg_date']);
            $table->index('currency_code');
            $table->index('reg_date');
            $table->index('created_at');
            $table->index('reg_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('currency_exchange_rates');
    }
}
