<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;

class GenResourceSeeder extends Seeder
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
                'code' => 'BB',
                'abrv' => 'TIPOEMP',
                'name' => 'TIPOS DE EMPRESA',
                'description' => 'TIPOS DE EMPRESA',
                'reg_status' => 1
            ),
            array(
                'code' => '26',
                'abrv' => 'TIPODOC',
                'name' => 'TIPOS DE IDENTIFICACIÓN',
                'description' => 'TIPOS DE IDENTIFICACIÓN',
                'reg_status' => 1
            ),
            array(
                'code' => '15',
                'abrv' => 'TIPODIR',
                'name' => 'TIPOS DE DIRECCIONES - CLIENTES',
                'description' => 'TIPOS DE DIRECCIONES - CLIENTES',
                'reg_status' => 1
            ),
            array(
                'code' => '17',
                'abrv' => 'TIPOVIA',
                'name' => 'TIPOS DE VIA',
                'description' => 'TIPOS DE VIA',
                'reg_status' => 1
            ),
            array(
                'code' => '18',
                'abrv' => 'TIPOZONA',
                'name' => 'TIPOS DE ZONA',
                'description' => 'TIPOS DE ZONA',
                'reg_status' => 1
            ),
            array(
                'code' => '11',
                'abrv' => 'ORIGPROD',
                'name' => 'ORIGEN PRODUCTO',
                'description' => 'ORIGEN PRODUCTO',
                'reg_status' => 1
            ),
            array(
                'code' => '12',
                'abrv' => 'LINEA',
                'name' => 'LÍNEAS',
                'description' => 'LÍNEAS',
                'reg_status' => 1
            ),
            array(
                'code' => 'CG',
                'abrv' => 'CARGOS',
                'name' => 'CARGOS',
                'description' => 'CARGOS',
                'reg_status' => 1
            ),
            array(
                'code' => 'SIS',
                'abrv' => 'SIS',
                'name' => 'SISTEMAS',
                'description' => 'SISTEMAS',
                'reg_status' => 1
            ),
            array(
                'code' => 'SUBSIS',
                'abrv' => 'SUBSIS',
                'name' => 'SUBSISTEMAS',
                'description' => 'SUBSISTEMAS',
                'reg_status' => 1
            ),
            array(
                'code' => '04',
                'abrv' => 'BANCO',
                'name' => 'BANCOS',
                'description' => 'BANCOS',
                'reg_status' => 1
            ),
            array(
                'code' => '35',
                'abrv' => 'MONEDAS',
                'name' => 'MONEDAS',
                'description' => 'MONEDAS',
                'reg_status' => 1
            ),
            array(
                'code' => 'ZY',
                'abrv' => 'MOTDEV',
                'name' => 'MOTIVO DEVOLUCIÓN MERCADERÍA',
                'description' => 'MOTIVO DEVOLUCIÓN MERCADERÍA',
                'reg_status' => 1
            ),
            array(
                'code' => 'LM',
                'abrv' => 'LIMACRO',
                'name' => 'LÍNEAS MACRO',
                'description' => 'LÍNEAS MACRO',
                'reg_status' => 1
            ),
            array(
                'code' => '02',
                'abrv' => 'SUC',
                'name' => 'SUCURSALES',
                'description' => 'SUCURSALES',
                'reg_status' => 1
            ),
            array(
                'code' => '87',
                'abrv' => 'TIPVEH',
                'name' => 'TIPOS DE VEHÍCULOS',
                'description' => 'TIPOS DE VEHÍCULOS',
                'reg_status' => 1
            ),
        );
        DB::table('gen_resources')->insert($arrayInsert);
    }
}
