<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class testController extends Controller
{
    public function retorna_campos_tablas()
    {
        $sql = 'SELECT 
        TABLE_CAT, 
        TABLE_SCHEM, 
        TABLE_NAME, 
        COLUMN_NAME, 
        DATA_TYPE, 
        TYPE_NAME, 
        COLUMN_SIZE, 
        COLUMN_TEXT
        FROM "SYSIBM"."COLUMNS"
        WHERE TABLE_SCHEM = "LIBPRDDAT" 
        AND TABLE_NAME IN ("MMEIREP");';
    }
    public function test_db()
    {
        $rs = DB::connection('ibmi')->table('LIBPRDDAT.MMEIREP')
        ->where('EIFECEMI','>=',20211129)
        ->get();
        echo '<pre>';print_r($rs);
    }


    public function administrarRoles()
    {
        echo '<pre>';
        $permiso_nuevo = 'mostrar-pedidos-vehiculos';
        //Permission::create(['name' => $permiso_nuevo ]);

        $role = Role::where('name','Administrador')->with('permissions')->first();
        
        //asignar permiso al rol
        //$role->givePermissionTo($permiso_nuevo);
        
        //mostrar-pedidos-vehiculos
        return response()->json($role);
         
    }

    public function buscarContacto()
    {
        $buscarContacto = DB::connection('ibmi')->select("SELECT * FROM LIBPRDDAT.CPCNREP WHERE CPCODCIA='10' AND CPCODSUC='03' AND CPNROPDC=439457 AND CPITEM01=0;");
        return response()->json($buscarContacto);
    
    }
}
