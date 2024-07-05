<?php

namespace App\Http\Controllers\Sync;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use DB;
use App\Models\PartPartDetail;

class Utilidades extends Controller
{
    private $codBoletaDeposito = '06';
    const ECOMMERCEUSER = 'ECOMMERCE';
    const ECOMMERCEJOB = 'WSAPI';
    const ECOMMERCEDIASVENCIMIENTO = 30;

    public function busca_datos_vector($search, $resource_id, $array)
    {
        foreach ($array as $fila) {
            if ($fila->resource_id == $resource_id && $fila->code === $search) {
                return $fila->id;
            }
        }
        return null;
    }

    public function busca_datos_vector2($search, $array)
    {
        foreach ($array as $fila) {
            if ($fila->code === $search) {
                return $fila->id;
            }
        }
        return null;
    }

    public function selecciona_fila_from_tabla_db2($tabla_db2, $arrayWhere)
    {
        return DB::connection('ibmi')
            ->table($tabla_db2)
            ->where($arrayWhere)
            ->first();
    }

    public function selecciona_from_tabla_db2($tabla_db2, $arrayWhere)
    {
        return DB::connection('ibmi')
            ->table($tabla_db2)
            ->where($arrayWhere)
            ->get()
            ->toArray();
    }

    public function inserta_into_tabla($tabla, $arrayInsert)
    {
        return DB::table($tabla)
            ->insertGetId($arrayInsert);
    }

    public function inserta_into_tabla_as400($tabla, $arrayInsert)
    {
        return DB::connection('ibmi')->table($tabla)
            ->insert($arrayInsert);
    }

    public function selecciona_fila_from_tabla($tabla, $arrayWhere)
    {
        return DB::table($tabla)
            ->where($arrayWhere)
            ->first();
    }

    public function selecciona_max_from_tabla($tabla, $campo, $arrayWhere = '')
    {
        //if ($arrayWhere === '') $arrayWhere = array([$campo, '<>', null]);
        //die(print_r($arrayWhere));
        return DB::table($tabla)->where($arrayWhere)->max($campo);
    }

    public function selecciona_from_tabla($tabla, $arrayWhere, $arraySelect = '')
    {
        if (empty($arraySelect)) $arraySelect = ['*'];
        return DB::table($tabla)
            ->select($arraySelect)
            ->where($arrayWhere)
            ->get()
            ->toArray();
    }


    public function selecciona_from_tabla_where_in($tabla, $whereInField, $whereInArray, $arraySelect = '', $orderBy = '', $whereArray = '')
    {
        if (empty($arraySelect)) $arraySelect = ['*'];
        if (empty($orderBy)) $orderBy = $whereInField;
        if (empty($whereArray)) $whereArray = array(['reg_status', '>=', 0]);
        return DB::table($tabla)
            ->select($arraySelect)
            ->where($whereArray)
            ->whereIn($whereInField, $whereInArray)
            ->orderBy($orderBy)
            ->get()
            ->toArray();
    }

    public function retorna_datos_parte_as400_old($linea, $origen, $marca, $codigo)
    {
        $sql = "SELECT prode.ACDSCCOR, prode.ACDSCLAR, prode.ACCODMON, prode.ACUNIMED, subsis.ohclas01, subsis.ohclas02, proal.etimplis,
        proal.etimppmi, proal.etimpcrp, proal.etfacpmi, proal.etfacpma, proal.etprcumi, proal.etprcuma, proal.ETCODFAB
        FROM LIBPRDDAT.MMETREP AS proal 
        INNER JOIN LIBPRDDAT.MMACREP AS prode ON proal.etcodlin=prode.accodlin and proal.etcodart=prode.accodart 
        LEFT JOIN LIBPRDDAT.MMOHREP AS subsis ON proal.etcodart=subsis.ohcodart AND subsis.OHSTS ='A'
        WHERE proal.etcodlin='" . $linea . "'
        and proal.etcodori='" . $origen . "'
        and proal.etcodmar='" . $marca . "'
        and proal.etcodart='" . $codigo . "'
        AND prode.ACSTS ='A'        
        LIMIT 1";
        //echo $sql;
        $result =  DB::connection('ibmi')->select(DB::raw($sql));
        return ($result && is_array($result)) ? $result[0] : false;
    }

    public function retorna_datos_parte_as400($linea, $origen = '', $marca = '', $codigo)
    {

        $str_sql_codigo = (strpos($codigo, "'")) ? "and prode.accodart like '" . str_replace("'", "%", $codigo) . "'" : "and prode.accodart='" . $codigo . "'";
        //die("Cod: $str_sql_codigo");
        $str_sql_ori = (!empty($origen)) ? " and proal.etcodori='" . $origen . "'" : '';
        $str_sql_mar = (!empty($marca)) ? " and proal.etcodmar='" . $marca . "'" : '';
        $sql = "SELECT prode.ACDSCCOR, prode.ACDSCLAR, prode.ACCODMON, prode.ACUNIMED, subsis.ohclas01, subsis.ohclas02, subsis.ohdscart as caracteristicas, proal.etimplis,
        proal.etimppmi, proal.etimpcrp, proal.etfacpmi, proal.etfacpma, proal.etprcumi, proal.etprcuma, proal.ETCODFAB, obs.soobserv, proal.etcodori, proal.etcodmar
        FROM LIBPRDDAT.MMACREP AS prode
        left JOIN LIBPRDDAT.MMOHREP AS subsis ON prode.accodlin=subsis.ohcodlin and prode.accodart=subsis.ohcodart
        INNER JOIN LIBPRDDAT.MMETREP AS proal  ON proal.etcodlin=prode.accodlin and proal.etcodart=prode.accodart 
        left join LIBPRDDAT.MMSOREP AS obs on proal.etcodlin=obs.socodlin and proal.etcodori=obs.socodori and proal.etcodmar=obs.socodmar and proal.etcodart=obs.socodart
        WHERE prode.accodlin='" . $linea . "'
        " . $str_sql_ori . "
        " . $str_sql_mar . "
        " . $str_sql_codigo . "
        AND prode.ACSTS ='A'        
        LIMIT 1";
        //die($sql);
        $result =  DB::connection('ibmi')->select(DB::raw($sql));
        return ($result && is_array($result)) ? $result[0] : false;
    }

    public function retorna_datos_productos_almacen_as400($arrayWhere, $str_select, $offset = 0, $limit = 10000, $retorna_cantidad = false)
    {
        if ($retorna_cantidad) {
            return DB::connection('ibmi')->table('LIBPRDDAT.MMETREP')
                ->where($arrayWhere)
                ->select('ETCODART')
                ->count();
        } else {
            return  DB::connection('ibmi')
                ->table('LIBPRDDAT.MMETREP')
                //->distinct()
                ->select($str_select)
                ->where($arrayWhere)
                ->limit($limit)
                ->offset($offset)
                ->get()
                ->toArray();

            //->toSql();
            //die($sql);
        }
    }

    public function retorna_datos_parte($arrayWhere)
    {
        return DB::table('v_partes')
            ->where($arrayWhere)
            ->first();
    }

    public function retorna_datos_sistema_subsistema($sistema, $subsistema)
    {
        $sql = "select subsis.id, subsis.code, subsis.name
                from gen_resource_details sis 
                inner join gen_resource_details subsis on sis.id=subsis.parent_resource_detail_id
                where sis.resource_id=9 and sis.code = '" . $sistema . "' and subsis.code = '" . $subsistema . "' limit 1";
        $result =  DB::select(DB::raw($sql));
        return ($result && is_array($result)) ? $result[0] : false;
    }

    public function retorna_datos_establecimiento($id)
    {
        return DB::table('establishments AS e')
            ->join('establishment_types AS et', 'e.type_id', '=', 'et.id')
            ->select(['e.code', 'e.name', 'et.description'])
            ->where('e.id', '=', $id)
            ->first();
    }

    public function retorna_inventario_por_almacen($almacen_id)
    {
        $sql = "select *
                from v_partes_por_almacen  
                where warehouse_id = " . $almacen_id;
        return  DB::select(DB::raw($sql));
    }

    public function retorna_productos_oferta_grupo_polo()
    {
        $sql = "select *
                from v_partes_por_almacen_grupo_polo";
        return  DB::select(DB::raw($sql));
    }

    public function crear_producto_dado_linea_origen_marca_codigo($line_code, $origin_code, $trademark_code, $code)
    {
        //1.- VERIFICAR SI EXISTE EN TABLA PART_PARTS
        //2.- SI NO EXISTE, REGISTRARLO
        //3.- Y REGISTRAR EN TABLA PART_PART_DETAILS
        //4.- VERIFICAR SI EXISTE EN EN TABLA PART_PART_DETAILS
        //5.- SI NO EXISTE, REGISTRARLO

        if (!$datos_parte_as = $this->retorna_datos_parte_as400($line_code, $origin_code, $trademark_code, $code)) {
            echo ("<br>ERROR: EL CÓDIGO ($line_code - $origin_code - $trademark_code - $code) DE PRODUCTO NO EXISTE O ESTÁ INACTIVO EN AS400.");
            return 0;
        }
        $origin_code = (!empty($origin_code)) ? $origin_code : $datos_parte_as->etcodori;
        $trademark_code = (!empty($trademark_code)) ? $trademark_code : $datos_parte_as->etcodmar;

        $factory_code = strtoupper(trim(utf8_encode($datos_parte_as->etcodfab)));
        $list_price = floatval($datos_parte_as->etimplis);
        $min_price = floatval($datos_parte_as->etimppmi);
        $replacement_cost = floatval($datos_parte_as->etimpcrp);
        $min_price_factor = floatval($datos_parte_as->etfacpmi);
        $max_price_factor = floatval($datos_parte_as->etfacpma);
        $min_profit_rate = floatval($datos_parte_as->etprcumi);
        $max_profit_rate = floatval($datos_parte_as->etprcuma);

        $descripcion = strtoupper(trim(utf8_encode($datos_parte_as->acdsclar)));
        $desc_corta = strtoupper(trim(utf8_encode($datos_parte_as->acdsccor)));
        $unidad_medida = $datos_parte_as->acunimed;
        $currency_code = $datos_parte_as->accodmon;
        $sistema = $datos_parte_as->ohclas01;
        $subsistema = $datos_parte_as->ohclas02;
        $caracteristicas = strtoupper(trim(utf8_encode($datos_parte_as->caracteristicas)));
        $observaciones = strtoupper(trim(utf8_encode($datos_parte_as->soobserv)));


        $arrayWhereUnd = array(
            ['resource_id', '=', 27],
            ['code', '=', $unidad_medida]
        );
        $datos_und_medida = $this->selecciona_fila_from_tabla('gen_resource_details', $arrayWhereUnd);
        if (!$datos_sistema_subsistema = $this->retorna_datos_sistema_subsistema($sistema, $subsistema)) {
            echo "<br>ERROR: SISTEMA Y SUBSISTEMA NO ENCONTRADOS (Sis.: $sistema - Sub.: $subsistema)";
            $subsistema_id = 1486;
        } else {
            //die(print_r($datos_sistema_subsistema));
            $subsistema_id = $datos_sistema_subsistema->id;
        }

        if (!$datos_linea = $this->selecciona_fila_from_tabla('gen_resource_details', array(['resource_id', '=', 7], ['code', '=', $line_code]))) {
            echo "<br>Línea no encontrada: " . $line_code;
            exit;
        }

        $arrayWherePP = array(
            ['line_id', '=', $datos_linea->id],
            ['code', '=', $code]
        );
        if (!$datos_parte = $this->selecciona_fila_from_tabla('part_parts', $arrayWherePP)) {
            $arrayInsertPP = array(
                'line_id' => $datos_linea->id,
                'code' => $code,
                'name' => $descripcion,
                'short_name' => $desc_corta,
                'measure_unit_id' => $datos_und_medida->id,
                'subsystem_id' => $subsistema_id,
                'product_features' => $caracteristicas,
                'reg_status' => 1,
                'created_at' => date("Y-m-d H:i:s")
            );
            $this->inserta_into_tabla('part_parts', $arrayInsertPP);
            echo "<br>PRODUCTO CREADO EN TABLA PART_PARTS...";
            $datos_parte = $this->selecciona_fila_from_tabla('part_parts', $arrayWherePP);
        }


        if (!$datos_origen = $this->selecciona_fila_from_tabla('gen_resource_details', array(['resource_id', '=', 6], ['code', '=', $origin_code]))) {
            echo "<br>Origen no encontrado: " . $origin_code;
        }
        if (!$datos_marca = $this->selecciona_fila_from_tabla('part_trademarks', array(['code', '=', $trademark_code]))) {
            echo "<br>Registrar Marca: " . $trademark_code;
            if (!$datos_marca = $this->registra_nueva_marca_desde_db2($trademark_code)) {
                echo "<br>NO EXISTE Marca: " . $trademark_code;
                return false;
            }
        }
        echo "<br>Linea: $line_code - Origen: $origin_code - Marca: $trademark_code - Código: {$datos_parte->code} <br>";

        $nuevo_sku = $datos_linea->code . $datos_origen->code . $datos_marca->code .  $datos_parte->code;
        //ESCRIBIR REGISTRO EN TABLA part_part_details
        $arrayInsertPPD = array(
            'part_id' => $datos_parte->id,
            'line_id' => $datos_linea->id,
            'origin_id' => $datos_origen->id,
            'trademark_id' => $datos_marca->id,
            'sku' => $nuevo_sku,
            'reg_status' => 1,
            'factory_code' => $factory_code,
            'list_price' => $list_price,
            'currency_code' => $currency_code,
            'min_price' => $min_price,
            'replacement_cost' => $replacement_cost,
            'min_price_factor' => $min_price_factor,
            'max_price_factor' => $max_price_factor,
            'min_profit_rate' => $min_profit_rate,
            'max_profit_rate' => $max_profit_rate,
            'product_remarks' => $observaciones,
            'created_at' => date("Y-m-d H:i:s")
        );
        $arrayWherePPD = array(
            ['part_id', '=', $datos_parte->id],
            ['line_id', '=', $datos_linea->id],
            ['origin_id', '=', $datos_origen->id],
            ['trademark_id', '=', $datos_marca->id],
        );
        $x = PartPartDetail::updateOrCreate(
            $arrayWherePPD,
            $arrayInsertPPD
        );
        return $this->retorna_datos_parte(array(['sku', '=', $nuevo_sku]));
    }

    public function registra_nueva_marca_desde_db2($trademark_code)
    {
        if (!$datos_marca_as = $this->selecciona_fila_from_tabla_db2('LIBPRDDAT.MMEYREP', array(['EYCODMAR', '=', $trademark_code]))) {
            return false;
        }
        $nombre_corto_marca = trim(strtoupper(utf8_encode($datos_marca_as->eydsccor)));
        $nombre_largo_marca = trim(strtoupper(utf8_encode($datos_marca_as->eydsclar)));
        if ($marca_repetidas = $this->selecciona_from_tabla('part_trademarks', array(['name', 'like', $nombre_largo_marca . '%']))) {
            $cant = sizeof($marca_repetidas);
            $nombre_corto_marca .= ' ' . $cant;
            $nombre_largo_marca .= ' ' . $cant;
            //die($nombre_largo_marca);
        }
        $arrayInsert = array(
            'code' => trim($datos_marca_as->eycodmar),
            'abrv' => trim(strtoupper($datos_marca_as->eydscabr)),
            'short_name' => $nombre_corto_marca,
            'name' => $nombre_largo_marca,
            'reg_status' => ($datos_marca_as->eysts === 'A') ? 1 : 0,
        );
        if (!$this->inserta_into_tabla('part_trademarks', $arrayInsert)) return false;
        else return $this->selecciona_fila_from_tabla('part_trademarks', array(['code', '=', $trademark_code]));
    }

    public function retorna_limpia_cadena($cadena)
    {
        return strtoupper(trim(utf8_encode($cadena)));
    }

    public function retorna_tipo_cambio_dolar_mym($currencyCode)
    {
        $arrayWhere = array(
            ['currency_code', '=', $currencyCode],
            ['mym_selling_price', '>', 0],
            ['reg_status', '=', 1]
        );
        return DB::table('currency_exchange_rates')
            ->where($arrayWhere)
            ->orderBy('reg_date', 'DESC')
            ->first();
    }

    public function retorna_nuevo_numero_tabla_numeradores_mmfcrep($codCia, $codSuc, $docType, $arrayWhere = '')
    {
        if ($arrayWhere && is_array($arrayWhere)) {
            $max = DB::connection('ibmi')
                ->table('LIBPRDDAT.MMFCREL0')
                ->where($arrayWhere)
                ->max('FCCANACT') + 1;
        } else {
            $max = DB::connection('ibmi')
                ->table('LIBPRDDAT.MMFCREL0')
                ->where('FCCODCIA', '=', $codCia)
                ->where('FCCODSUC', '=', $codSuc)
                ->where('FCCODELE', '=', $docType)
                ->max('FCCANACT') + 1;
        }
        return $max;
    }

    public function actualiza_tabla_numeradores_mmfcrep($arrayWhere, $arrayUpdate)
    {
        return DB::connection('ibmi')
            ->table('LIBPRDDAT.MMFCREP')
            ->where($arrayWhere)
            ->update($arrayUpdate);
    }

    public function  get_max_deposito_db2($codCia)
    {
        return DB::connection('ibmi')
            ->table('MMYPREL3')
            ->where('YPCODCIA', '=', $codCia)
            ->max('YPDEPINT');
    }

    public function regitra_deposito_bancario($arrayDeposito)
    {
        $sucursal_deposito = ($arrayDeposito->subsidiary_code === '02') ? '01' : $arrayDeposito->subsidiary_code;
        $arrayWhere = array(
            ['YPCODCIA', '=', $arrayDeposito->company_code],
            ['YPCODSUC', '=', $sucursal_deposito],
            ['YPCODBCO', '=', $arrayDeposito->bank_code],
            ['YPNROCTA', '=', $arrayDeposito->bank_account],
            ['YPNROOPR', '=', $arrayDeposito->operation_number],
            ['YPSTS', '=', 'A'],
            ['YPFECDEP', '=', date("Ymd")]
        );
        if (!$datos_deposito = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMYPREP', $arrayWhere)) {
            //OBTENER CORRELATIVO DE BOLETA DE DEPOSITO EN TABLA NUMERADORES (MMFCREL0)
            //echo '<br>CORRELATIVO DE BOLETA DE DEPOSITO -> NUMERADORES (MMFCREL0): ';
            $correlativoBoletaDeposito = $this->getCorrelativeNumberByDocType($arrayDeposito->company_code, $sucursal_deposito, $this->codBoletaDeposito);

            //INSERTA DEPOSITO SI NO HA SIDO REGISTRADO 
            $deposito = $this->get_max_deposito_db2($arrayDeposito->company_code);
            $deposito = intval(substr($deposito, 3, 7));
            $deposito++;
            $deposito = 'D' . substr(date("Y"), 2, 2) . str_pad($deposito, 7, '0', STR_PAD_LEFT);

            //REGISTRA NUEVO DEPOSITO
            $arrayNewDep = array(
                'YPCODCIA' => $arrayDeposito->company_code,
                'YPCODSUC' => $sucursal_deposito,
                'YPDEPINT' => $deposito,
                'YPNRODEP' => $correlativoBoletaDeposito,
                'YPCODBCO' => $arrayDeposito->bank_code,
                'YPNROCTA' => $arrayDeposito->bank_account,
                'YPCODMON' => $arrayDeposito->currency_code,
                'YPNROOPR' => $arrayDeposito->operation_number,
                'YPFRMPAG' => 'C', //C -> CONTADO, R -> CRÉDITO
                'YPCODPAI' => '001',
                'YPCODCIU' => '001',
                'YPSTS' => 'A',
                'YPSTSAPL' => $arrayDeposito->deposit_status, //P -> PENDIENTE, N -> CONFIRMADO TESORERÍA, S -> DEPÓSITO APLICADO (Default)
                'YPFECDEP' => date("Ymd"),
                'YPIMPDEP' => $arrayDeposito->amount,
                'YPCLIREF' => $arrayDeposito->customer_code,
                'YPJDT' =>  date("Ymd"),
                'YPJTM' => date("His"),
                'YPCODVEN' => $arrayDeposito->seller_code,
                'YPUSR' => $arrayDeposito->user_code,
                'YPSUCDOC' => $arrayDeposito->subsidiary_code,
                'YPPGM' => $arrayDeposito->job
            );
            DB::connection('ibmi')->table('LIBPRDDAT.MMYPREP')->insert([
                $arrayNewDep
            ]);
            return $this->selecciona_from_tabla_db2('LIBPRDDAT.MMYPREP', $arrayWhere);
        } else return $datos_deposito;
    }

    public function getCorrelativeNumberByDocType($codCia, $codSuc, $docType)
    {
        DB::beginTransaction();
        $max = DB::connection('ibmi')
            ->table('LIBPRDDAT.MMFCREL0')
            ->where('FCCODCIA', '=', $codCia)
            ->where('FCCODSUC', '=', $codSuc)
            ->where('FCCODELE', '=', $docType)
            ->max('FCCANACT');

        $max = intval($max) + 1;
        DB::connection('ibmi')
            ->table('LIBPRDDAT.MMFCREL0')
            ->where('FCCODCIA', '=', $codCia)
            ->where('FCCODSUC', '=', $codSuc)
            ->where('FCCODELE', '=', $docType)
            ->update(['FCCANACT' => $max]);
        DB::commit();
        return $max;
    }

    public function retorna_fecha_formateada($formato_in = 'Y-m-d', $formato_out = 'Ymd', $fecha)
    {
        $fecha = Carbon::createFromFormat($formato_in, $fecha, 'America/Lima');
        return $fecha->format($formato_out);
    }

    public function sumar_restar_dias_fecha($fecha, $qty_dias, $accion = 'sumar')
    {
        $date = Carbon::createFromFormat("Ymd", $fecha, 'America/Lima');
        switch ($accion) {
            case 'sumar':
                return $date->addDay($qty_dias);
                break;

            default:
                return $date->subDay($qty_dias);
                break;
        }
    }

    public function actualiza_inventario_producto_almacen_as400($codCia, $codigo_cliente, $datos_producto)
    {
        $qty_salida_actual = $this->retorna_producto_almacen_as400($datos_producto, $codCia);
        $total_qty_salida = (float)$datos_producto->cantidadSolicitada + $qty_salida_actual;

        $arrayUpdate = array(
            'ETCANUVT' => $datos_producto->cantidadSolicitada,
            'ETFECUVT' => date("Ymd"),
            'ETCODCLI' => $codigo_cliente,
            'ETCANSLD' => $total_qty_salida
        );

        return DB::connection('ibmi')->table('LIBPRDDAT.MMETREP')
            ->where('ETSTS', 'A')
            ->where('ETCODCIA', $codCia)
            ->where('ETCODSUC', $datos_producto->codAlmacen)
            ->where('ETCODLIN', $datos_producto->codLinea)
            ->where('ETCODORI', $datos_producto->codOrigen)
            ->where('ETCODMAR', $datos_producto->codMarca)
            ->where('ETCODART', $datos_producto->codArticulo)
            ->update($arrayUpdate);
    }

    public function retorna_producto_almacen_as400($datos_producto, $codCia)
    {
        $datos_producto_almacen = DB::connection('ibmi')
            ->table('LIBPRDDAT.MMETREP')
            ->select('ETCANSLD')
            ->where('ETSTS', 'A')
            ->where('ETCODCIA', $codCia)
            ->where('ETCODSUC', $datos_producto->codAlmacen)
            ->where('ETCODLIN', $datos_producto->codLinea)
            ->where('ETCODORI', $datos_producto->codOrigen)
            ->where('ETCODMAR', $datos_producto->codMarca)
            ->where('ETCODART', $datos_producto->codArticulo)
            ->first();

        return ($datos_producto_almacen) ? (float)$datos_producto_almacen->etcansld : false;
    }

    public function rec_retorna_part_detail_id_reemplazo($part_detail_id_reemplazo)
    {
        ///ini_set('max_execution_time', '3000');
        /* $array_reemp = array_column($vector_registros, 'part_detail_replacement_id');
        $index_reemp = array_search($part_detail_id_reemplazo, $array_reemp);
        $part_detail_id_reemplazo = ($vector_registros[$index_reemp]->part_detail_id == false) ? $part_detail_id_reemplazo : $this->rec_retorna_part_detail_id_reemplazo($vector_registros[$index_reemp]->part_detail_replacement_id, $vector_registros); */
        $registro = DB::table('part_detail_replacements')
            ->where('part_detail_id', $part_detail_id_reemplazo)
            ->where('reg_status', 1)
            ->first();
        //die(print_r($registro));
        if ($registro) $part_detail_id_reemplazo = $this->rec_retorna_part_detail_id_reemplazo($registro->part_detail_replacement_id);
        /* foreach ($vector_registros as $registro) { 
            if ($registro->part_detail_id == $part_detail_id_reemplazo) {
                $part_detail_id_reemplazo = $this->rec_retorna_part_detail_id_reemplazo($registro->part_detail_replacement_id, $vector_registros);
            }
        }*/

        return $part_detail_id_reemplazo;
    }

    public function retorna_datos_proveedor_as($codigo)
    {
        $sql = "SELECT Distinct a.AHCODPRV,b.IPNVORUC,a.AHRAZSOC, a.AHTIPPRV, c.CGCODPAI,a.AHJDT, a.AHSTS 
        FROM MMAHREP a 
        Left join MMIPREP b on a.AHCODPRV=b.IPCODCLI 
        Left join MMCGREP c on a.AHCODPRV=c.CGCODPRV 
        where a.AHCODPRV='$codigo'";
        $result =  DB::connection('ibmi')->select(DB::raw($sql));
        return ($result && is_array($result)) ? $result[0] : false;
    }
}
