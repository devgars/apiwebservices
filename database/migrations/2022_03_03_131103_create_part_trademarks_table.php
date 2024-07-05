<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePartTrademarksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('part_trademarks', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->comment('Código de marca de producto en AS400 ');
            $table->string('abrv', 5)->nullable()->comment('Abreviación');
            $table->string('short_name', 20)->nullable()->comment('Nombre corto de marca');
            $table->string('name', 50)->comment('Nombre de marca');
            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');
            $table->tinyInteger('reg_status')->comment('Estado de Registro');

            $table->unique(['code']);
            $table->unique(['name']);

            $table->index('code');
            $table->index('name');
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
        Schema::dropIfExists('part_trademarks');
    }
}
