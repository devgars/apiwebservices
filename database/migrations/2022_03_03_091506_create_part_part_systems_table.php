<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePartPartSystemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('part_part_systems', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('part_id')->comment('FK tabla part_parts');
            $table->foreign('part_id')->references('id')->on('part_parts');
            $table->bigInteger('subsystem_id')->comment('FK tabla vehicle_systems');
            $table->foreign('subsystem_id')->references('id')->on('vehicle_systems');
            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->comment('Fecha de creacion');
            $table->datetime('updated_at')->nullable()->comment('Fecha de modificacion');
            $table->tinyInteger('reg_status')->comment('Estado de Registro');

            $table->unique(['part_id', 'subsystem_id']);

            $table->index('part_id');
            $table->index('subsystem_id');
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
        Schema::dropIfExists('part_part_systems');
    }
}
