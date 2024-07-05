<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnFlagIterateClienteSaldos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cliente_saldos', function (Blueprint $table) {
            $table->integer('int_iterate')->nullable(true)->default(0);  
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cliente_saldos', function (Blueprint $table) {
            $table->integer('int_iterate')->nullable(true)->default(0);  
        });
    }
}
