<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
#use Illuminate\Support\Facades\Schema;
use Staudenmeir\LaravelMigrationViews\Facades\Schema;


class CreateViewDistProvDptoPeru extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $query = 'SELECT dist.id AS dist_id,
            dist.code AS dist_code,
            dist.name AS dist_name,
            dist.ubigeo AS dist_ubigeo,
            prov.id AS prov_id,
            prov.code AS prov_code,
            prov.name AS prov_name,
            prov.ubigeo AS prov_ubigeo,
            dpto.id AS dpto_id,
            dpto.code AS dpto_code,
            dpto.name AS dpto_name,
            dpto.ubigeo AS dpto_ubigeo
           FROM ubigeos dist
             JOIN ubigeos prov ON dist.parent_ubigeo_id = prov.id
             JOIN ubigeos dpto ON prov.parent_ubigeo_id = dpto.id
             JOIN ubigeos pais ON dpto.parent_ubigeo_id = pais.id
          WHERE pais.id = 163 AND dpto.ubigeo_type_id = 2 AND dpto.reg_status = 1 AND prov.reg_status = 1 AND dist.reg_status = 1;';

        Schema::createView('dist_prov_dpto_peru', $query);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropView('dist_prov_dpto_peru');
    }
}
