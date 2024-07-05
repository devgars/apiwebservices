<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
#use Illuminate\Support\Facades\Schema;
use Staudenmeir\LaravelMigrationViews\Facades\Schema;

class CreateVAlmacenesConsignacion extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $query = 'SELECT e.id,
        e.code,
        e.name,
        et.description,
        e.address,
        e."order",
        e.reg_status
       FROM establishments e
         JOIN establishment_types et ON e.type_id = et.id
      WHERE et.id = 4;';

        Schema::createView('v_almacenes_consignacion', $query);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropView('v_almacenes_consignacion');
    }
}
