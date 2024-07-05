<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUbigeoTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ubigeo_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code', 10)->comment('Código Interno AS400');
            $table->string('ubigeo_type', 50)->comment('Tipo de Ubigeo: País|Departamento|Provincia|Distrito');
            $table->tinyInteger('level')->comment('1 -> Pais, 2->Dpto, 3->Prov., 4->Dist.');
            $table->tinyInteger('reg_status')->default(1)->comment('Estatus de registro: 1 -> ACTIVO, 0 -> INACTIVO');

            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->datetime('updated_at')->nullable();

            $table->unique('code');
            $table->unique('ubigeo_type');

            $table->index('reg_status');
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
        Schema::dropIfExists('ubigeo_types');
    }
}
