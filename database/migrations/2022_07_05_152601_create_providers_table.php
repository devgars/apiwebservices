<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProvidersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 6)->comment('Código AS400');
            $table->string('identification_number', 20)->nullable()->comment('Nro de Identificación');
            $table->string('name', 100)->comment('Nombre de Proveedor');
            $table->string('country_code', 4)->nullable()->comment('Código de País');
            $table->string('provider_type_code', 4)->nullable()->comment('Tipo de proveedor');

            $table->datetime('created_at')->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');
            $table->tinyInteger('reg_status')->default(1)->comment('Estado de Registro');

            $table->unique(['code']);
            $table->index('identification_number');
            $table->index('name');
            $table->index('country_code');
            $table->index('provider_type_code');
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
        Schema::dropIfExists('providers');
    }
}
