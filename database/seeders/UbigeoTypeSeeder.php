<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;


class UbigeoTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $arrayInsert = array(
            array(
                'code' => 'PAIS',
                'ubigeo_type' => 'PAÍS',
                'level' => 1
            ),
            array(
                'code' => 'DPTO',
                'ubigeo_type' => 'DEPARTAMENTO',
                'level' => 2
            ),
            array(
                'code' => 'PROV',
                'ubigeo_type' => 'PROVINCIA',
                'level' => 3
            ),
            array(
                'code' => 'DIST',
                'ubigeo_type' => 'DISTRITO',
                'level' => 4
            ),

        );
        DB::table('ubigeo_types')->insert($arrayInsert);
    }
}
