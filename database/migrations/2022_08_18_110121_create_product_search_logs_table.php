<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductSearchLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_search_logs', function (Blueprint $table) {
            $table->id();
            $table->date('search_date')->comment('Fecha de búsqueda');
            $table->time('search_time')->comment('Hora de búsqueda');
            $table->string('searched_product', 100)->comment('Producto Buscado');
            $table->boolean('product_found')->default(true)->comment('Producto encontrado?');
            $table->string('customer_code', 6)->nullable()->comment('Código de cliente, si está logueado');
            $table->string('ip', 50)->comment('IP de donde se conectó');
            $table->datetime('created_at')->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');

            $table->index('search_date');
            $table->index('search_time');
            $table->index('searched_product');
            $table->index('product_found');
            $table->index('customer_code');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_search_logs');
    }
}
