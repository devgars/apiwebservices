<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_users', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable()->comment('ID USUARIO EN TABLA users');
            $table->foreign('user_id')->references('id')->on('users');
            $table->string('code', 10)->comment('Usuario AS400');
            $table->string('last_name', 20)->comment('Apellido Paterno');
            $table->string('mother_last_name', 20)->nullable()->comment('Apellido Materno');
            $table->string('first_name', 20)->comment('Primer Nombre');
            $table->string('second_name', 20)->nullable()->comment('Segundo Nombre');
            $table->date('birthdate')->default('2000-01-01')->comment('Fecha de nacimiento');
            $table->date('startdate')->default(DB::raw('CURRENT_TIMESTAMP'))->comment('Fecha de inicio');
            $table->bigInteger('country_id')->default(163)->comment('Nacionalidad');
            $table->foreign('country_id')->references('id')->on('ubigeos');
            $table->string('email', 70)->comment('Correo electrónico');
            $table->string('cellphone', 15)->comment('Celular');
            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');
            $table->tinyInteger('reg_status')->comment('Estado de Registro');

            $table->unique(['code']);
            $table->unique(['email']);
            $table->index('code');
            $table->index('country_id');
            $table->index('last_name');
            $table->index('first_name');
            $table->index('birthdate');
            $table->index('startdate');
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
        Schema::dropIfExists('user_users');
    }
}
