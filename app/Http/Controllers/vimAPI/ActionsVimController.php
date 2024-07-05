<?php

namespace App\Http\Controllers\vimAPI;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vim\SyncVimCustomer;
use App\Models\Vim\SyncVimCountry;
use App\Models\Vim\SyncVimProvider;
use App\Models\Vim\SyncVimOrigin;
use App\Models\Vim\SyncVimType;
use App\Models\Vim\SyncVimArticle;
use App\Models\Vim\SyncVimArticleDetail;
use App\Models\Vim\SyncVimLine;
use App\Models\Vim\SyncVimBrand;
use App\Models\Vim\SyncVimModel;
use App\Models\Vim\SyncVimModelDetail;

// use DB;
// use Illuminate\Support\Facades\DB;

class ActionsVimController extends Controller
{
    //FUNCIONES PARA PROCESOS INICIALES
    public function mmakrep_maestro_clientes_vim_migracion($fila)
    {
        $cliente = $fila;
        
        $code = strtoupper(utf8_encode(trim($cliente->akcodcli)));

        //Formateando la fecha de registro antes de insertar
        $fecha_registro = utf8_encode(trim($cliente->akfecins));

        $dia = substr("$fecha_registro", -2);
        $mes = substr("$fecha_registro", -4, 2);
        $anio = substr("$fecha_registro", -8, 4);

        $new_fecha_registro = $dia.'/'.$mes.'/'.$anio;
        //Fin de formateo de fecha de registro

        //VALIDANDO LA INSERCION POR TIPO DE EMPRESA
        $tipo_empresa = utf8_encode(trim($cliente->eudscabr));

        if ($tipo_empresa == 'PNA') {
            $new_nrodocidentida = utf8_encode(trim($cliente->ifnvoruc));
            $new_nrodocidentida1 = utf8_encode(trim($cliente->ifnroruc));
            $new_nrodocidentida2 = utf8_encode(trim($cliente->aknroruc));
            $new_nrodocidentida3 = utf8_encode(trim($cliente->aknroide));

            if ($new_nrodocidentida == '' && $new_nrodocidentida1 == '' && $new_nrodocidentida2 == '' && $new_nrodocidentida3 == '') {
                $new_nrodocidentida = ''; //SIN DATOS
            }elseif($new_nrodocidentida == '' && $new_nrodocidentida1 == '' && $new_nrodocidentida2 == ''){
                $new_nrodocidentida = utf8_encode(trim($cliente->aknroide));
            }elseif($new_nrodocidentida == '' && $new_nrodocidentida1 == ''){
                $new_nrodocidentida = utf8_encode(trim($cliente->aknroruc));
            }elseif($new_nrodocidentida == ''){
                $new_nrodocidentida = utf8_encode(trim($cliente->ifnroruc));
            }else{
                #code...
            }

        }elseif($tipo_empresa == 'PJU'){
            $new_nrodocidentida = utf8_encode(trim($cliente->ifnvoruc));
            $new_nrodocidentida1 = utf8_encode(trim($cliente->ifnroruc));
            $new_nrodocidentida2 = utf8_encode(trim($cliente->aknroruc));
            $new_nrodocidentida3 = utf8_encode(trim($cliente->aknroide));

            if ($new_nrodocidentida == '' && $new_nrodocidentida1 == '' && $new_nrodocidentida2 == '' && $new_nrodocidentida3 == '') {
                $new_nrodocidentida = ''; //SIN DATOS
            }elseif($new_nrodocidentida == '' && $new_nrodocidentida1 == '' && $new_nrodocidentida2 == ''){
                $new_nrodocidentida = utf8_encode(trim($cliente->aknroide));
            }elseif($new_nrodocidentida == '' && $new_nrodocidentida1 == ''){
                $new_nrodocidentida = utf8_encode(trim($cliente->aknroruc));
            }elseif($new_nrodocidentida == ''){
                $new_nrodocidentida = utf8_encode(trim($cliente->ifnroruc));
            }else{
                #code...
            }

        }elseif($tipo_empresa == 'PNN'){
            $new_nrodocidentida = utf8_encode(trim($cliente->ifnvoruc));
            $new_nrodocidentida1 = utf8_encode(trim($cliente->ifnroruc));
            $new_nrodocidentida2 = utf8_encode(trim($cliente->aknroruc));
            $new_nrodocidentida3 = utf8_encode(trim($cliente->aknroide));

            if ($new_nrodocidentida == '' && $new_nrodocidentida1 == '' && $new_nrodocidentida2 == '' && $new_nrodocidentida3 == '') {
                $new_nrodocidentida = ''; //SIN DATOS
            }elseif($new_nrodocidentida == '' && $new_nrodocidentida1 == '' && $new_nrodocidentida2 == ''){
                $new_nrodocidentida = utf8_encode(trim($cliente->aknroide));
            }elseif($new_nrodocidentida == '' && $new_nrodocidentida1 == ''){
                $new_nrodocidentida = utf8_encode(trim($cliente->aknroruc));
            }elseif($new_nrodocidentida == ''){
                $new_nrodocidentida = utf8_encode(trim($cliente->ifnroruc));
            }else{
                #code...
            }
            
        }elseif($tipo_empresa == 'NDO'){
            $new_nrodocidentida = utf8_encode(trim($cliente->ifnvoruc));
            $new_nrodocidentida1 = utf8_encode(trim($cliente->ifnroruc));
            $new_nrodocidentida2 = utf8_encode(trim($cliente->aknroruc));
            $new_nrodocidentida3 = utf8_encode(trim($cliente->aknroide));

            if ($new_nrodocidentida == '' && $new_nrodocidentida1 == '' && $new_nrodocidentida2 == '' && $new_nrodocidentida3 == '') {
                $new_nrodocidentida = ''; //SIN DATOS
            }elseif($new_nrodocidentida == '' && $new_nrodocidentida1 == '' && $new_nrodocidentida2 == ''){
                $new_nrodocidentida = utf8_encode(trim($cliente->aknroide));
            }elseif($new_nrodocidentida == '' && $new_nrodocidentida1 == ''){
                $new_nrodocidentida = utf8_encode(trim($cliente->aknroruc));
            }elseif($new_nrodocidentida == ''){
                $new_nrodocidentida = utf8_encode(trim($cliente->ifnroruc));
            }else{
                #code...
            }
            
        }else{
            #code...
        }
        //FIN VALIDACION TIPO DE EMPRESA
        
        $arrayWhere = array(['idcliente', '=', $code]);
        $arrayInsert = array(
            // 'nrodocidentida' => utf8_encode(trim($cliente->aknroruc)),
            'nrodocidentida' => $new_nrodocidentida,
            'idcliente' => utf8_encode(trim($cliente->akcodcli)),
            // 'fecharegistro' => trim($cliente->akfecins),
            'fecharegistro' => $new_fecha_registro,
            'razonsocial' => utf8_encode(trim($cliente->akrazsoc)),
            'nombrecomercial' => utf8_encode(trim($cliente->aknomcom)),
            'created_at' => date("Y-m-d H:i:s")
        );
        SyncVimCustomer::updateOrCreate($arrayWhere,$arrayInsert);
    }

    public function mmferel0_maestro_paises_vim_migracion($fila)
    {
        //$pais = $fila->datos_consulta;
        $pais = $fila;

        $code = strtoupper(utf8_encode(trim($pais->fecodpai)));

        $arrayWhere = array(['code', '=', $code]);
        $arrayInsert = array(
            // 'name' => trim($pais->fedsccor),
            'name' => utf8_encode(trim($pais->fedsccor)),
            'iso_code' => utf8_encode(trim($pais->fests)),
            // 'user_id' => trim($pais->feusr),
            'user_id' => 1,
            'created_at' => date("Y-m-d H:i:s"),
            'code' => utf8_encode(trim($pais->fecodpai))
        );
        SyncVimCountry::updateOrCreate($arrayWhere,$arrayInsert);
    }

    public function mmahrep_maestro_proveedores_vim_migracion($fila)
    {
        //$proveedor = $fila->datos_consulta;
        $proveedor = $fila;

        $code = strtoupper(utf8_encode(trim($proveedor->ahcodprv)));

        //Formateando la fecha de registro antes de insertar
        $fecha_registro = utf8_encode(trim($proveedor->ahjdt));

        $dia = substr("$fecha_registro", -2);
        $mes = substr("$fecha_registro", -4, 2);
        $anio = substr("$fecha_registro", -8, 4);

        $new_fecha_registro = $dia.'/'.$mes.'/'.$anio;
        //Fin de formateo de fecha de registro

        //VALIDANDO LA INSERCION, DANDOLE PRIORIDAD AL NUEVO RUC
        $new_nroidentificacion = utf8_encode(trim($proveedor->ipnvoruc));
        $new_nroidentificacion1 = utf8_encode(trim($proveedor->ipnroruc));
        $new_nroidentificacion2 = utf8_encode(trim($proveedor->ahnroruc));
        $new_nroidentificacion3 = utf8_encode(trim($proveedor->ahnroide));

        if ($new_nroidentificacion == '' && $new_nroidentificacion1 == '' && $new_nroidentificacion2 == '' && $new_nroidentificacion3 == '') {
            $new_nroidentificacion = ''; //SIN DATOS
        }elseif($new_nroidentificacion == '' && $new_nroidentificacion1 == '' && $new_nroidentificacion2 == ''){
            $new_nroidentificacion = utf8_encode(trim($proveedor->ahnroide));
        }elseif($new_nroidentificacion == '' && $new_nroidentificacion1 == ''){
            $new_nroidentificacion = utf8_encode(trim($proveedor->ahnroruc));
        }elseif($new_nroidentificacion == ''){
            $new_nroidentificacion = utf8_encode(trim($proveedor->ipnroruc));
        }else{
            #code...
        }
        //FIN VALIDACION

        $arrayWhere = array(['idproveedor', '=', $code]);
        $arrayInsert = array(
            'idproveedor' => utf8_encode(trim($proveedor->ahcodprv)),
            // 'nroidentificacion' => utf8_encode(trim($proveedor->ahnroruc)),
            'nroidentificacion' => $new_nroidentificacion,
            'razonsocial' => utf8_encode(trim($proveedor->ahrazsoc)),
            'idpais' => utf8_encode(trim($proveedor->ahcodpai)),
            // 'fecharegistro' => trim($proveedor->ahjdt),
            'fecharegistro' => $new_fecha_registro,
            'created_at' => date("Y-m-d H:i:s")
        );
        SyncVimProvider::updateOrCreate($arrayWhere,$arrayInsert);
    }

    public function mmeurel0_maestro_origenes_vim_migracion($fila)
    {
        //$origen = $fila->datos_consulta;
        $origen = $fila;

        $code = strtoupper(utf8_encode(trim($origen->eucodele)));

        $arrayWhere = array(['code', '=', $code]);
        $arrayInsert = array(
            'code' => utf8_encode(trim($origen->eucodele)),
            'descripcion' => utf8_encode(trim($origen->eudsclar)),
            'created_at' => date("Y-m-d H:i:s"),
            'user_id' => 1
        );
        SyncVimOrigin::updateOrCreate($arrayWhere,$arrayInsert);
    }

    public function comodvel0_maestro_tipos_vim_migracion($fila)
    {
        //$tipo = $fila->datos_consulta;
        $tipo = $fila;

        $code = strtoupper(utf8_encode(trim($tipo->mvcodele)));

        $arrayWhere = array(['code', '=', $code]);
        $arrayInsert = array(
            'code' => utf8_encode(trim($tipo->mvcodele)),
            'descripcion' => utf8_encode(trim($tipo->mvdesele)),
            'created_at' => date("Y-m-d H:i:s")
        );
        SyncVimType::updateOrCreate($arrayWhere,$arrayInsert);
    }

    public function mmacrep_maestro_articulos_vim_migracion($fila)
    {
        //$articulo = $fila->datos_consulta;
        $articulo = $fila;

        $vala1 = strtoupper(utf8_encode(trim($articulo->accodart)));
        $vala2 = strtoupper(utf8_encode(trim($articulo->accodlin)));

        $arrayWhere = array(['code', '=', $vala1],['linea_code', '=', $vala2]);
        $arrayInsert = array(
            'code' => utf8_encode(trim($articulo->accodart)),
            'descripcion' => utf8_encode(trim($articulo->acdsclar)),
            'unidad_medida' => utf8_encode(trim($articulo->acunimed)),
            'created_at' => date("Y-m-d H:i:s"),
            'linea_code' => utf8_encode(trim($articulo->accodlin))
        );
        SyncVimArticle::updateOrCreate($arrayWhere,$arrayInsert);
    }

    public function mmetrep_maestro_articulo_detalles_vim_migracion($fila)
    {
        $articulo_detalle = $fila;

        $valad1 = strtoupper(utf8_encode(trim($articulo_detalle->etcodfab)));
        $valad2 = strtoupper(utf8_encode(trim($articulo_detalle->etcodlin)));
        $valad3 = strtoupper(utf8_encode(trim($articulo_detalle->etcodart)));
        $valad4 = strtoupper(utf8_encode(trim($articulo_detalle->etcodori)));
        $valad5 = strtoupper(utf8_encode(trim($articulo_detalle->etcodmar)));

        $arrayWhere = array(['cod_fabricante', '=', $valad1],['linea_id', '=', $valad2],['codarticulo', '=', $valad3],
                            ['origen_id', '=', $valad4],['marca_id', '=', $valad5]);
        $arrayInsert = array(
            'cod_fabricante' => utf8_encode(trim($articulo_detalle->etcodfab)),
            'created_at' => date("Y-m-d H:i:s"),
            'linea_id' => utf8_encode(trim($articulo_detalle->etcodlin)),
            'codarticulo' => utf8_encode(trim($articulo_detalle->etcodart)),
            'origen_id' => utf8_encode(trim($articulo_detalle->etcodori)),
            'marca_id' => utf8_encode(trim($articulo_detalle->etcodmar))
        );
        SyncVimArticleDetail::updateOrCreate($arrayWhere,$arrayInsert);
    }

    public function mmeurel0_maestro_lineas_vim_migracion($fila)
    {
        //$linea = $fila->datos_consulta;
        $linea = $fila;

        $code = strtoupper(utf8_encode(trim($linea->eucodele)));

        $arrayWhere = array(['code', '=', $code]);
        $arrayInsert = array(
            'code' => utf8_encode(trim($linea->eucodele)),
            'descripcion' => utf8_encode(trim($linea->eudsclar)),
            'created_at' => date("Y-m-d H:i:s")
        );
        SyncVimLine::updateOrCreate($arrayWhere,$arrayInsert);
    }

    public function mmeyrel0_maestro_marcas_vim_migracion($fila)
    {
        //$marca = $fila->datos_consulta;
        $marca = $fila;

        $code = strtoupper(utf8_encode(trim($marca->eycodmar)));

        $arrayWhere = array(['code', '=', $code]);
        $arrayInsert = array(
            'code' => utf8_encode(trim($marca->eycodmar)),
            // 'descripcion' => utf8_encode(trim($marca->eydsclar)),
            'descripcon' => utf8_encode(trim($marca->eydsclar)),
            'created_at' => date("Y-m-d H:i:s"),
            'user_id' => 1
        );
        SyncVimBrand::updateOrCreate($arrayWhere,$arrayInsert);
    }

    public function mmocrel0_maestro_modelos_vim_migracion($fila)
    {
        $modelo = $fila;

        $valm1 = strtoupper(utf8_encode(trim($modelo->occodmod)));
        $valm2 = strtoupper(utf8_encode(trim($modelo->oclinart)));
        $valm3 = strtoupper(utf8_encode(trim($modelo->occodart)));
        $valm4 = strtoupper(utf8_encode(trim($modelo->oclinmod)));

        //Formateando la fecha de registro antes de insertar
        $fecha_registro = utf8_encode(trim($modelo->ocjdt));

        $dia = substr("$fecha_registro", -2);
        $mes = substr("$fecha_registro", -4, 2);
        $anio = substr("$fecha_registro", -8, 4);

        $new_fecha_registro = $dia.'/'.$mes.'/'.$anio;
        //Fin de formateo de fecha de registro

        // $arrayWhere = array(['idmodelo', '=', $code]);
        $arrayWhere = array(['idmodelo', '=', $valm1],['codlinea', '=', $valm2],['codarticulo', '=', $valm3],['codlineamodelo', '=', $valm4]);
        $arrayInsert = array(
            'idmodelo' => utf8_encode(trim($modelo->occodmod)),
            // 'fecharegistro' => trim($modelo->ocjdt),
            'fecharegistro' => $new_fecha_registro,
            'codlinea' => utf8_encode(trim($modelo->oclinart)),
            'codarticulo' => utf8_encode(trim($modelo->occodart)),
            'codlineamodelo' => utf8_encode(trim($modelo->oclinmod)),
            'created_at' => date("Y-m-d H:i:s")
        );
        SyncVimModel::updateOrCreate($arrayWhere,$arrayInsert);
        //SyncVimModel::create($arrayInsert);
    }

    public function mmobrel0_maestro_modelo_detalles_vim_migracion($fila)
    {
        $modelo_detalle = $fila;

        $valmd1 = strtoupper(trim($modelo_detalle->obanomod));
        $valmd2 = strtoupper(trim($modelo_detalle->obhp));
        $valmd3 = strtoupper(trim($modelo_detalle->obtracc));
        $valmd4 = strtoupper(trim($modelo_detalle->obmotor));
        $valmd5 = strtoupper(trim($modelo_detalle->obcajac));
        $valmd6 = strtoupper(trim($modelo_detalle->obcoron));
        $valmd7 = strtoupper(trim($modelo_detalle->obejdel));
        $valmd8 = strtoupper(trim($modelo_detalle->obejpst));
        $valmd9 = strtoupper(trim($modelo_detalle->obcodmod));
        $valmd10 = strtoupper(trim($modelo_detalle->obcodlin));

        //Formateando la fecha de registro antes de insertar
        $fecha_registro = utf8_encode(trim($modelo_detalle->objdt));

        $dia = substr("$fecha_registro", -2);
        $mes = substr("$fecha_registro", -4, 2);
        $anio = substr("$fecha_registro", -8, 4);

        $new_fecha_registro = $dia.'/'.$mes.'/'.$anio;
        //Fin de formateo de fecha de registro

        $arrayWhere = array(['anio', '=', $valmd1],['hp', '=', $valmd2],['traccion', '=', $valmd3],['motor', '=', $valmd4],
                            ['caja_cambio', '=', $valmd5],['corona', '=', $valmd6],['eje_delantero', '=', $valmd7],['eje_trasero', '=', $valmd8],
                            ['idmodelo', '=', $valmd9],['idlinea', '=', $valmd10]);
        $arrayInsert = array(
            'anio' => utf8_encode(trim($modelo_detalle->obanomod)),
            'hp' => utf8_encode(trim($modelo_detalle->obhp)),
            'traccion' => utf8_encode(trim($modelo_detalle->obtracc)),
            'motor' => utf8_encode(trim($modelo_detalle->obmotor)),
            'caja_cambio' => utf8_encode(trim($modelo_detalle->obcajac)),
            'corona' => utf8_encode(trim($modelo_detalle->obcoron)),
            'eje_delantero' => utf8_encode(trim($modelo_detalle->obejdel)),
            'eje_trasero' => utf8_encode(trim($modelo_detalle->obejpst)),
            // 'fecharegistro' => trim($modelo->objdt),
            'fecharegistro' => $new_fecha_registro,
            'created_at' => date("Y-m-d H:i:s"),
            'idmodelo' => utf8_encode(trim($modelo_detalle->obcodmod)),
            'idlinea' => utf8_encode(trim($modelo_detalle->obcodlin)),
            // 'idtipovehiculo' => utf8_encode(trim($modelo_detalle->obsts)) //Preguntar
            'idtipovehiculo' => 'NF' //Preguntar
        );
        SyncVimModelDetail::updateOrCreate($arrayWhere,$arrayInsert);
        // SyncVimModelDetail::create($arrayInsert);
    }

    //FUNCIONES PARA PROCESOS AUTOMATICOS
    public function mmakrep_maestro_clientes_vim_automatico($fila)
    {
        $cliente = $fila->datos_consulta;
        
        $code = strtoupper(utf8_encode(trim($cliente->akcodcli)));

        //Formateando la fecha de registro antes de insertar
        $fecha_registro = utf8_encode(trim($cliente->akfecins));

        $dia = substr("$fecha_registro", -2);
        $mes = substr("$fecha_registro", -4, 2);
        $anio = substr("$fecha_registro", -8, 4);

        $new_fecha_registro = $dia.'/'.$mes.'/'.$anio;
        //Fin de formateo de fecha de registro

        //VALIDANDO LA INSERCION POR TIPO DE EMPRESA
        $tipo_empresa = utf8_encode(trim($cliente->eudscabr));

        if ($tipo_empresa == 'PNA') {
            $new_nrodocidentida = utf8_encode(trim($cliente->ifnvoruc));
            $new_nrodocidentida1 = utf8_encode(trim($cliente->ifnroruc));
            $new_nrodocidentida2 = utf8_encode(trim($cliente->aknroruc));
            $new_nrodocidentida3 = utf8_encode(trim($cliente->aknroide));

            if ($new_nrodocidentida == '' && $new_nrodocidentida1 == '' && $new_nrodocidentida2 == '' && $new_nrodocidentida3 == '') {
                $new_nrodocidentida = ''; //SIN DATOS
            }elseif($new_nrodocidentida == '' && $new_nrodocidentida1 == '' && $new_nrodocidentida2 == ''){
                $new_nrodocidentida = utf8_encode(trim($cliente->aknroide));
            }elseif($new_nrodocidentida == '' && $new_nrodocidentida1 == ''){
                $new_nrodocidentida = utf8_encode(trim($cliente->aknroruc));
            }elseif($new_nrodocidentida == ''){
                $new_nrodocidentida = utf8_encode(trim($cliente->ifnroruc));
            }else{
                #code...
            }

        }elseif($tipo_empresa == 'PJU'){
            $new_nrodocidentida = utf8_encode(trim($cliente->ifnvoruc));
            $new_nrodocidentida1 = utf8_encode(trim($cliente->ifnroruc));
            $new_nrodocidentida2 = utf8_encode(trim($cliente->aknroruc));
            $new_nrodocidentida3 = utf8_encode(trim($cliente->aknroide));

            if ($new_nrodocidentida == '' && $new_nrodocidentida1 == '' && $new_nrodocidentida2 == '' && $new_nrodocidentida3 == '') {
                $new_nrodocidentida = ''; //SIN DATOS
            }elseif($new_nrodocidentida == '' && $new_nrodocidentida1 == '' && $new_nrodocidentida2 == ''){
                $new_nrodocidentida = utf8_encode(trim($cliente->aknroide));
            }elseif($new_nrodocidentida == '' && $new_nrodocidentida1 == ''){
                $new_nrodocidentida = utf8_encode(trim($cliente->aknroruc));
            }elseif($new_nrodocidentida == ''){
                $new_nrodocidentida = utf8_encode(trim($cliente->ifnroruc));
            }else{
                #code...
            }

        }elseif($tipo_empresa == 'PNN'){
            $new_nrodocidentida = utf8_encode(trim($cliente->ifnvoruc));
            $new_nrodocidentida1 = utf8_encode(trim($cliente->ifnroruc));
            $new_nrodocidentida2 = utf8_encode(trim($cliente->aknroruc));
            $new_nrodocidentida3 = utf8_encode(trim($cliente->aknroide));

            if ($new_nrodocidentida == '' && $new_nrodocidentida1 == '' && $new_nrodocidentida2 == '' && $new_nrodocidentida3 == '') {
                $new_nrodocidentida = ''; //SIN DATOS
            }elseif($new_nrodocidentida == '' && $new_nrodocidentida1 == '' && $new_nrodocidentida2 == ''){
                $new_nrodocidentida = utf8_encode(trim($cliente->aknroide));
            }elseif($new_nrodocidentida == '' && $new_nrodocidentida1 == ''){
                $new_nrodocidentida = utf8_encode(trim($cliente->aknroruc));
            }elseif($new_nrodocidentida == ''){
                $new_nrodocidentida = utf8_encode(trim($cliente->ifnroruc));
            }else{
                #code...
            }
            
        }elseif($tipo_empresa == 'NDO'){
            $new_nrodocidentida = utf8_encode(trim($cliente->ifnvoruc));
            $new_nrodocidentida1 = utf8_encode(trim($cliente->ifnroruc));
            $new_nrodocidentida2 = utf8_encode(trim($cliente->aknroruc));
            $new_nrodocidentida3 = utf8_encode(trim($cliente->aknroide));

            if ($new_nrodocidentida == '' && $new_nrodocidentida1 == '' && $new_nrodocidentida2 == '' && $new_nrodocidentida3 == '') {
                $new_nrodocidentida = ''; //SIN DATOS
            }elseif($new_nrodocidentida == '' && $new_nrodocidentida1 == '' && $new_nrodocidentida2 == ''){
                $new_nrodocidentida = utf8_encode(trim($cliente->aknroide));
            }elseif($new_nrodocidentida == '' && $new_nrodocidentida1 == ''){
                $new_nrodocidentida = utf8_encode(trim($cliente->aknroruc));
            }elseif($new_nrodocidentida == ''){
                $new_nrodocidentida = utf8_encode(trim($cliente->ifnroruc));
            }else{
                #code...
            }
            
        }else{
            #code...
        }
        //FIN VALIDACION TIPO DE EMPRESA
        
        $arrayWhere = array(['idcliente', '=', $code]);
        $arrayInsert = array(
            // 'nrodocidentida' => utf8_encode(trim($cliente->aknroruc)),
            'nrodocidentida' => $new_nrodocidentida,
            'idcliente' => utf8_encode(trim($cliente->akcodcli)),
            // 'fecharegistro' => trim($cliente->akfecins),
            'fecharegistro' => $new_fecha_registro,
            'razonsocial' => utf8_encode(trim($cliente->akrazsoc)),
            'nombrecomercial' => utf8_encode(trim($cliente->aknomcom)),
            'created_at' => date("Y-m-d H:i:s")
        );
        SyncVimCustomer::updateOrCreate($arrayWhere,$arrayInsert);
    }

    public function mmferel0_maestro_paises_vim_automatico($fila)
    {
        //$pais = $fila->datos_consulta;
        $pais = $fila;

        $code = strtoupper(utf8_encode(trim($pais->fecodpai)));

        $arrayWhere = array(['code', '=', $code]);
        $arrayInsert = array(
            // 'name' => trim($pais->fedsccor),
            'name' => utf8_encode(trim($pais->fedsccor)),
            'iso_code' => utf8_encode(trim($pais->fests)),
            // 'user_id' => trim($pais->feusr),
            'user_id' => 1,
            'created_at' => date("Y-m-d H:i:s"),
            'code' => utf8_encode(trim($pais->fecodpai))
        );
        SyncVimCountry::updateOrCreate($arrayWhere,$arrayInsert);
    }

    public function mmahrep_maestro_proveedores_vim_automatico($fila)
    {
        $proveedor = $fila->datos_consulta;
        //$proveedor = $fila;

        $code = strtoupper(utf8_encode(trim($proveedor->ahcodprv)));

        //Formateando la fecha de registro antes de insertar
        $fecha_registro = utf8_encode(trim($proveedor->ahjdt));

        $dia = substr("$fecha_registro", -2);
        $mes = substr("$fecha_registro", -4, 2);
        $anio = substr("$fecha_registro", -8, 4);

        $new_fecha_registro = $dia.'/'.$mes.'/'.$anio;
        //Fin de formateo de fecha de registro

        //VALIDANDO LA INSERCION, DANDOLE PRIORIDAD AL NUEVO RUC
        $new_nroidentificacion = utf8_encode(trim($proveedor->ipnvoruc));
        $new_nroidentificacion1 = utf8_encode(trim($proveedor->ipnroruc));
        $new_nroidentificacion2 = utf8_encode(trim($proveedor->ahnroruc));
        $new_nroidentificacion3 = utf8_encode(trim($proveedor->ahnroide));

        if ($new_nroidentificacion == '' && $new_nroidentificacion1 == '' && $new_nroidentificacion2 == '' && $new_nroidentificacion3 == '') {
            $new_nroidentificacion = ''; //SIN DATOS
        }elseif($new_nroidentificacion == '' && $new_nroidentificacion1 == '' && $new_nroidentificacion2 == ''){
            $new_nroidentificacion = utf8_encode(trim($proveedor->ahnroide));
        }elseif($new_nroidentificacion == '' && $new_nroidentificacion1 == ''){
            $new_nroidentificacion = utf8_encode(trim($proveedor->ahnroruc));
        }elseif($new_nroidentificacion == ''){
            $new_nroidentificacion = utf8_encode(trim($proveedor->ipnroruc));
        }else{
            #code...
        }
        //FIN VALIDACION

        $arrayWhere = array(['idproveedor', '=', $code]);
        $arrayInsert = array(
            'idproveedor' => utf8_encode(trim($proveedor->ahcodprv)),
            // 'nroidentificacion' => utf8_encode(trim($proveedor->ahnroruc)),
            'nroidentificacion' => $new_nroidentificacion,
            'razonsocial' => utf8_encode(trim($proveedor->ahrazsoc)),
            'idpais' => utf8_encode(trim($proveedor->ahcodpai)),
            // 'fecharegistro' => trim($proveedor->ahjdt),
            'fecharegistro' => $new_fecha_registro,
            'created_at' => date("Y-m-d H:i:s")
        );
        SyncVimProvider::updateOrCreate($arrayWhere,$arrayInsert);
    }

    public function mmeurel0_maestro_origenes_vim_automatico($fila)
    {
        //$origen = $fila->datos_consulta;
        $origen = $fila;

        $code = strtoupper(utf8_encode(trim($origen->eucodele)));

        $arrayWhere = array(['code', '=', $code]);
        $arrayInsert = array(
            'code' => utf8_encode(trim($origen->eucodele)),
            'descripcion' => utf8_encode(trim($origen->eudsclar)),
            'created_at' => date("Y-m-d H:i:s"),
            'user_id' => 1
        );
        SyncVimOrigin::updateOrCreate($arrayWhere,$arrayInsert);
    }

    public function comodvel0_maestro_tipos_vim_automatico($fila)
    {
        //$tipo = $fila->datos_consulta;
        $tipo = $fila;

        $code = strtoupper(utf8_encode(trim($tipo->mvcodele)));

        $arrayWhere = array(['code', '=', $code]);
        $arrayInsert = array(
            'code' => utf8_encode(trim($tipo->mvcodele)),
            'descripcion' => utf8_encode(trim($tipo->mvdesele)),
            'created_at' => date("Y-m-d H:i:s")
        );
        SyncVimType::updateOrCreate($arrayWhere,$arrayInsert);
    }

    public function mmacrep_maestro_articulos_vim_automatico($fila)
    {
        $articulo = $fila->datos_consulta;
        //$articulo = $fila;

        $vala1 = strtoupper(utf8_encode(trim($articulo->accodart)));
        $vala2 = strtoupper(utf8_encode(trim($articulo->accodlin)));

        $arrayWhere = array(['code', '=', $vala1],['linea_code', '=', $vala2]);
        $arrayInsert = array(
            'code' => utf8_encode(trim($articulo->accodart)),
            'descripcion' => utf8_encode(trim($articulo->acdsclar)),
            'unidad_medida' => utf8_encode(trim($articulo->acunimed)),
            'created_at' => date("Y-m-d H:i:s"),
            'linea_code' => utf8_encode(trim($articulo->accodlin))
        );
        SyncVimArticle::updateOrCreate($arrayWhere,$arrayInsert);
    }

    public function mmetrep_maestro_articulo_detalles_vim_automatico($fila)
    {
        $articulo_detalle = $fila->datos_consulta;

        $valad1 = strtoupper(utf8_encode(trim($articulo_detalle->etcodfab)));
        $valad2 = strtoupper(utf8_encode(trim($articulo_detalle->etcodlin)));
        $valad3 = strtoupper(utf8_encode(trim($articulo_detalle->etcodart)));
        $valad4 = strtoupper(utf8_encode(trim($articulo_detalle->etcodori)));
        $valad5 = strtoupper(utf8_encode(trim($articulo_detalle->etcodmar)));

        $arrayWhere = array(['cod_fabricante', '=', $valad1],['linea_id', '=', $valad2],['codarticulo', '=', $valad3],
                            ['origen_id', '=', $valad4],['marca_id', '=', $valad5]);
        $arrayInsert = array(
            'cod_fabricante' => utf8_encode(trim($articulo_detalle->etcodfab)),
            'created_at' => date("Y-m-d H:i:s"),
            'linea_id' => utf8_encode(trim($articulo_detalle->etcodlin)),
            'codarticulo' => utf8_encode(trim($articulo_detalle->etcodart)),
            'origen_id' => utf8_encode(trim($articulo_detalle->etcodori)),
            'marca_id' => utf8_encode(trim($articulo_detalle->etcodmar))
        );
        SyncVimArticleDetail::updateOrCreate($arrayWhere,$arrayInsert);
    }

    public function mmeurel0_maestro_lineas_vim_automatico($fila)
    {
        //$linea = $fila->datos_consulta;
        $linea = $fila;

        $code = strtoupper(utf8_encode(trim($linea->eucodele)));

        $arrayWhere = array(['code', '=', $code]);
        $arrayInsert = array(
            'code' => utf8_encode(trim($linea->eucodele)),
            'descripcion' => utf8_encode(trim($linea->eudsclar)),
            'created_at' => date("Y-m-d H:i:s")
        );
        SyncVimLine::updateOrCreate($arrayWhere,$arrayInsert);
    }

    public function mmeyrel0_maestro_marcas_vim_automatico($fila)
    {
        //$marca = $fila->datos_consulta;
        $marca = $fila;

        $code = strtoupper(utf8_encode(trim($marca->eycodmar)));

        $arrayWhere = array(['code', '=', $code]);
        $arrayInsert = array(
            'code' => utf8_encode(trim($marca->eycodmar)),
            // 'descripcion' => utf8_encode(trim($marca->eydsclar)),
            'descripcon' => utf8_encode(trim($marca->eydsclar)),
            'created_at' => date("Y-m-d H:i:s"),
            'user_id' => 1
        );
        SyncVimBrand::updateOrCreate($arrayWhere,$arrayInsert);
    }

    public function mmocrel0_maestro_modelos_vim_automatico($fila)
    {
        $modelo = $fila;

        $valm1 = strtoupper(utf8_encode(trim($modelo->occodmod)));
        $valm2 = strtoupper(utf8_encode(trim($modelo->oclinart)));
        $valm3 = strtoupper(utf8_encode(trim($modelo->occodart)));
        $valm4 = strtoupper(utf8_encode(trim($modelo->oclinmod)));

        //Formateando la fecha de registro antes de insertar
        $fecha_registro = utf8_encode(trim($modelo->ocjdt));

        $dia = substr("$fecha_registro", -2);
        $mes = substr("$fecha_registro", -4, 2);
        $anio = substr("$fecha_registro", -8, 4);

        $new_fecha_registro = $dia.'/'.$mes.'/'.$anio;
        //Fin de formateo de fecha de registro

        // $arrayWhere = array(['idmodelo', '=', $code]);
        $arrayWhere = array(['idmodelo', '=', $valm1],['codlinea', '=', $valm2],['codarticulo', '=', $valm3],['codlineamodelo', '=', $valm4]);
        $arrayInsert = array(
            'idmodelo' => utf8_encode(trim($modelo->occodmod)),
            // 'fecharegistro' => trim($modelo->ocjdt),
            'fecharegistro' => $new_fecha_registro,
            'codlinea' => utf8_encode(trim($modelo->oclinart)),
            'codarticulo' => utf8_encode(trim($modelo->occodart)),
            'codlineamodelo' => utf8_encode(trim($modelo->oclinmod)),
            'created_at' => date("Y-m-d H:i:s")
        );
        SyncVimModel::updateOrCreate($arrayWhere,$arrayInsert);
        //SyncVimModel::create($arrayInsert);
    }

    public function mmobrel0_maestro_modelo_detalles_vim_automatico($fila)
    {
        $modelo_detalle = $fila;

        $valmd1 = strtoupper(trim($modelo_detalle->obanomod));
        $valmd2 = strtoupper(trim($modelo_detalle->obhp));
        $valmd3 = strtoupper(trim($modelo_detalle->obtracc));
        $valmd4 = strtoupper(trim($modelo_detalle->obmotor));
        $valmd5 = strtoupper(trim($modelo_detalle->obcajac));
        $valmd6 = strtoupper(trim($modelo_detalle->obcoron));
        $valmd7 = strtoupper(trim($modelo_detalle->obejdel));
        $valmd8 = strtoupper(trim($modelo_detalle->obejpst));
        $valmd9 = strtoupper(trim($modelo_detalle->obcodmod));
        $valmd10 = strtoupper(trim($modelo_detalle->obcodlin));

        //Formateando la fecha de registro antes de insertar
        $fecha_registro = utf8_encode(trim($modelo_detalle->objdt));

        $dia = substr("$fecha_registro", -2);
        $mes = substr("$fecha_registro", -4, 2);
        $anio = substr("$fecha_registro", -8, 4);

        $new_fecha_registro = $dia.'/'.$mes.'/'.$anio;
        //Fin de formateo de fecha de registro

        $arrayWhere = array(['anio', '=', $valmd1],['hp', '=', $valmd2],['traccion', '=', $valmd3],['motor', '=', $valmd4],
                            ['caja_cambio', '=', $valmd5],['corona', '=', $valmd6],['eje_delantero', '=', $valmd7],['eje_trasero', '=', $valmd8],
                            ['idmodelo', '=', $valmd9],['idlinea', '=', $valmd10]);
        $arrayInsert = array(
            'anio' => utf8_encode(trim($modelo_detalle->obanomod)),
            'hp' => utf8_encode(trim($modelo_detalle->obhp)),
            'traccion' => utf8_encode(trim($modelo_detalle->obtracc)),
            'motor' => utf8_encode(trim($modelo_detalle->obmotor)),
            'caja_cambio' => utf8_encode(trim($modelo_detalle->obcajac)),
            'corona' => utf8_encode(trim($modelo_detalle->obcoron)),
            'eje_delantero' => utf8_encode(trim($modelo_detalle->obejdel)),
            'eje_trasero' => utf8_encode(trim($modelo_detalle->obejpst)),
            // 'fecharegistro' => trim($modelo->objdt),
            'fecharegistro' => $new_fecha_registro,
            'created_at' => date("Y-m-d H:i:s"),
            'idmodelo' => utf8_encode(trim($modelo_detalle->obcodmod)),
            'idlinea' => utf8_encode(trim($modelo_detalle->obcodlin)),
            // 'idtipovehiculo' => utf8_encode(trim($modelo_detalle->obsts)) //Preguntar
            'idtipovehiculo' => 'NF' //Preguntar
        );
        SyncVimModelDetail::updateOrCreate($arrayWhere,$arrayInsert);
        // SyncVimModelDetail::create($arrayInsert);
    }

}
