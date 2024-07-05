<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePoventaUsersPermissionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('poventa_users_permission', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->comment('user_id en la Tabla users que crea laravel');
            $table->integer('system_id')->comment('Sistema al que tendrá acceso en tabla gen_resource_details');
            $table->smallInteger('status')->comment('Estado del registro');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('poventa_users_permission');
    }
}
