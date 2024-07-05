<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserUserCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_user_companies', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_user_id')->comment('Usuario tabla user_users');
            $table->foreign('user_user_id')->references('id')->on('user_users');
            $table->bigInteger('company_id')->comment('Compañia en tabla org_structures');
            $table->foreign('company_id')->references('id')->on('org_structures');
            $table->bigInteger('subsidiary_id')->comment('Sucursal en tabla org_structures');
            $table->foreign('subsidiary_id')->references('id')->on('org_structures');
            $table->string('user_code', 10)->comment('Código de usuario');
            $table->string('operator_code', 10)->comment('Código de trabajador');
            $table->bigInteger('staff_id')->comment('Tipo de Personal en tabla gen_resource_details');
            $table->foreign('staff_id')->references('id')->on('gen_resource_details');
            $table->bigInteger('control_center_id')->nullable()->comment('Centro de control en tabla gen_resource_details');
            $table->foreign('control_center_id')->references('id')->on('gen_resource_details');
            $table->bigInteger('cost_center_id')->nullable()->comment('Centro de Costos en tabla gen_resource_details');
            $table->foreign('cost_center_id')->references('id')->on('gen_resource_details');
            $table->bigInteger('job_type_id')->nullable()->comment('Tipo de cargo en tabla gen_resource_details');
            $table->foreign('job_type_id')->references('id')->on('gen_resource_details');

            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');
            $table->tinyInteger('reg_status')->comment('Estado de Registro');

            $table->unique(['user_user_id', 'company_id']);
            $table->index('user_user_id');
            $table->index('company_id');
            $table->index('subsidiary_id');
            $table->index('user_code');
            $table->index('staff_id');
            $table->index('job_type_id');
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
        Schema::dropIfExists('user_user_companies');
    }
}
