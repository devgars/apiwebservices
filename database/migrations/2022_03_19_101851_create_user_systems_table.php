<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserSystemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_systems', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->comment('user_id en la Tabla users que crea laravel');
            $table->foreign('user_id')->references('id')->on('users');
            $table->bigInteger('system_id')->comment('Sistema al que tendrá acceso en tabla gen_resource_details');
            $table->foreign('system_id')->references('id')->on('gen_resource_details');
            $table->bigInteger('company_id')->comment('company_id en la Tabla org_structures');
            $table->foreign('company_id')->references('id')->on('org_structures');
            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');
            $table->tinyInteger('reg_status')->comment('Estado de Registro');

            $table->unique(['user_id', 'system_id', 'company_id']);
            $table->index(['user_id', 'system_id', 'company_id']);
            $table->index('user_id');
            $table->index('system_id');
            $table->index('company_id');
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
        Schema::dropIfExists('user_systems');
    }
}
