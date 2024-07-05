<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBanksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('banks', function (Blueprint $table) {
            $table->increments('id');
            $table->string('cod_cia', 2)->comment('Código Compañia AS400');
            $table->string('erp_code', 2)->comment('Código AS400');
            $table->string('sbs_code', 3)->comment('Código SBS');
            $table->string('description', 30)->comment('Descripción');
            $table->boolean('active')->default(true)->comment('Activa/Inactiva');

            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->datetime('updated_at')->nullable();

            $table->index('created_at');
            $table->index('updated_at');
            $table->unique(['erp_code']);
            $table->index('sbs_code');
            $table->index('cod_cia');
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
        Schema::dropIfExists('banks');
    }
}
