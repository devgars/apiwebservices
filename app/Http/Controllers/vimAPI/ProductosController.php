<?php

namespace App\Http\Controllers\vimAPI;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\JoinClause;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

use stdClass;

class ProductosController extends Controller
{
    public function getProducto()
    {
        $rs = DB::connection('ibmi')->table('LIBPRDDAT.B2BPROD')->get()->toArray();

        if (is_array($rs) && count($rs) > 0) {
            $arrayResult = array();
            foreach ($rs as  $value) {
                $value->b2codcia = trim($value->b2codcia);
                $value->b2codsuc = trim($value->b2codsuc);
                $value->b2codcli = trim($value->b2codcli);
                $value->b2codlin = trim($value->b2codlin);
                $value->b2codart = trim($value->b2codart);
                $value->b2codori = trim($value->b2codori);
                $value->b2codmar = trim($value->b2codmar);
                $value->b2codfab = trim($value->b2codfab);
                $value->b2stkdic = trim($value->b2stkdic);
                $value->b2stkdit = trim($value->b2stkdit);
                $value->b2implis = trim($value->b2implis);
                $value->b2dsclar = utf8_encode(trim($value->b2dsclar));
                $value->b2ctgcca = trim($value->b2ctgcca);
                $value->b2umduso = trim($value->b2umduso);
                $value->b2sts = trim($value->b2sts);
                $value->b2usr = trim($value->b2usr);
                $value->b2job = trim($value->b2job);
                $value->b2jdt = trim($value->b2jdt);
                $value->b2jtm = trim($value->b2jtm);

                array_push($arrayResult, $value);
            }
        }
        $object = new stdClass();
        $object->success = true;
        $object->msg = "Products data";
        $object->items = $arrayResult;

        return response()->json($object, 200, array('Content-Type' => 'application/json; charset=utf-8'));
    }

    public function getRepuesto($sku)
    {
        $asku=$this->f_transformarSku($sku);
        $etcodlin=$asku[0];
        $etcodori=$asku[1];
        $etcodmar=$asku[2];
        $etcodart=$asku[3];
        $etsts='A';
        $repuesto_db2 = array();
        $repuesto_db2 = DB::connection('ibmi')->table('LIBPRDDAT.MMETREL0')
        ->select('ETCODLIN','ETCODORI','ETCODMAR','ETCODART','ACDSCLAR')
        ->leftJoin("LIBPRDDAT.MMACREP", function (JoinClause $join) {
            $join->on('ACCODLIN', '=', 'ETCODLIN')
                ->on('ACCODART', '=', 'ETCODART');
        })
        ->where('ETCODLIN', '=', $etcodlin)
        ->where('ETCODORI', '=', $etcodori)
        ->where('ETCODMAR', '=', $etcodmar)
        ->where('ETCODART', '=', $etcodart)
        ->where('ETSTS', '=', $etsts)
        ->limit(1)
        ->get();

        $repuesto = array();
        foreach ($repuesto_db2 as $row) {
            $row->etcodlin = utf8_encode(trim($row->etcodlin));
            $row->etcodori = utf8_encode(trim($row->etcodori));
            $row->etcodmar = utf8_encode(trim($row->etcodmar));
            $row->etcodart = utf8_encode(trim($row->etcodart));
            $row->acdsclar = utf8_encode(trim($row->acdsclar));
            array_push($repuesto, $row);
        }

        return response()->json($repuesto);
    }

    private function f_transformarSku($sku)
    {
        $fsku=array();
        $asku=explode('.',$sku);
        $search_str='-ld-';
        $es_carpeta=strpos($asku[0],$search_str);
        if($es_carpeta){
            $nsku=substr($asku[0],0,strlen($asku[0])-8);
        } else {
            $nsku=substr($asku[0],0,strlen($asku[0])-5);
        }
        $fsku=explode(',',$nsku);
        return $fsku;
    }

    public function getMarca()
    {
        $eysts = 'A';

        $marca_db2 = DB::connection('ibmi')->table('LIBPRDDAT.MMEYREL0')
        ->select('EYCODMAR AS CODIGO', 'EYDSCLAR AS DESCRIPCION')
        ->where('EYSTS', '=', $eysts)
        ->orderBy('EYCODMAR', 'ASC')
        ->get();

        $marca = array();
        foreach ($marca_db2 as $row) {
            $row->codigo = utf8_encode(trim($row->codigo));
            $row->descripcion = utf8_encode(trim($row->descripcion));
            array_push($marca, $row);
        }

        return response()->json($marca);
    }

    public function getLinea()
    {
        $eusts = 'A';
        $eucodtbl = '12';

        $linea_db2 = DB::connection('ibmi')->table('LIBPRDDAT.MMEUREL0')
        ->select('EUCODELE AS CODIGO', 'EUDSCLAR AS DESCRIPCION')
        ->where('EUSTS', '=', $eusts)
        ->where('EUCODTBL', '=', $eucodtbl)
        ->orderBy('EUCODELE', 'ASC')
        ->get();

        $linea = array();
        foreach ($linea_db2 as $row) {
            $row->codigo = utf8_encode(trim($row->codigo));
            $row->descripcion = utf8_encode(trim($row->descripcion));
            array_push($linea, $row);
        }

        return response()->json($linea);
    }

    public function getOrigen()
    {
        $eusts = 'A';
        $eucodtbl = '11';

        $origen_db2 = DB::connection('ibmi')->table('LIBPRDDAT.MMEUREL0')
        ->select('EUCODELE AS CODIGO', 'EUDSCLAR AS DESCRIPCION')
        ->where('EUSTS', '=', $eusts)
        ->where('EUCODTBL', '=', $eucodtbl)
        ->orderBy('EUCODELE', 'ASC')
        ->get();

        $origen = array();
        foreach ($origen_db2 as $row) {
            $row->codigo = utf8_encode(trim($row->codigo));
            $row->descripcion = utf8_encode(trim($row->descripcion));
            array_push($origen, $row);
        }

        return response()->json($origen);
    }

    public function  getConsultaRepuesto($texto)
    {
        $eucodtbl_linea = '12';
        $eucodtbl_origen = '11';
        $etsts = 'A';
        $texto = trim(strtoupper($texto));

        $LINEA = DB::connection('ibmi')->table('LIBPRDDAT.MMEUREL0 B')
        ->selectRaw('B.EUCODELE, B.EUDSCLAR')
        ->where('B.EUCODTBL', '=', $eucodtbl_linea)
        ->where('B.EUSTS', '=', $etsts);

        $ORIGEN = DB::connection('ibmi')->table('LIBPRDDAT.MMEUREL0 C')
        ->selectRaw('C.EUCODELE, C.EUDSCLAR')
        ->where('C.EUCODTBL', '=', $eucodtbl_origen)
        ->where('C.EUSTS', '=', $etsts);

        $MARCA = DB::connection('ibmi')->table('LIBPRDDAT.MMEYREL0 D')
        ->selectRaw('D.EYCODMAR, D.EYDSCLAR')
        ->where('D.EYSTS', '=', $etsts);

        $REPUESTO = DB::connection('ibmi')->table('LIBPRDDAT.MMACREP E')
        ->selectRaw('E.ACCODLIN, E.ACCODART, E.ACDSCLAR')
        ->where('E.ACSTS', '=', $etsts);

        $consulta_db2 = DB::connection('ibmi')->table('LIBPRDDAT.MMETREL0 A')
        ->selectRaw("DISTINCT A.ETCODLIN AS COD_LINEA, LINEA.EUDSCLAR AS LDESC, A.ETCODORI AS COD_ORIGEN, ORIGEN.EUDSCLAR AS ODESC, A.ETCODMAR AS COD_MARCA, MARCA.EYDSCLAR AS MDESC, A.ETCODART AS COD_ARTICULO, A.ETCODFAB AS COD_FABRICACION, REPUESTO.ACDSCLAR AS DESC_ARTICULO, A.ETCODLIN CONCAT A.ETCODORI CONCAT A.ETCODMAR CONCAT A.ETCODART AS SKU_REPUESTO")
        ->joinSub($LINEA, 'LINEA', function (JoinClause $join) {
            $join->on('LINEA.EUCODELE', '=', 'A.ETCODLIN');
        })
        ->joinSub($ORIGEN, 'ORIGEN', function (JoinClause $join) {
            $join->on('ORIGEN.EUCODELE', '=', 'A.ETCODORI');
        })
        ->joinSub($MARCA, 'MARCA', function (JoinClause $join) {
            $join->on('MARCA.EYCODMAR', '=', 'A.ETCODMAR');
        })
        ->joinSub($REPUESTO, 'REPUESTO', function (JoinClause $join) {
            $join->on('REPUESTO.ACCODLIN', '=', 'A.ETCODLIN')
                ->on('REPUESTO.ACCODART', '=', 'A.ETCODART');
        })
        ->where('A.ETSTS','=',$etsts)
        ->where('A.ETCODLIN CONCAT A.ETCODORI CONCAT A.ETCODMAR CONCAT A.ETCODART', 'LIKE', '%'.$texto.'%')
        ->orWhere('REPUESTO.ACDSCLAR', 'LIKE', '%'.$texto.'%')
        ->orWhere('A.ETCODFAB', 'LIKE', '%'.$texto.'%')
        ->orderBy('A.ETCODLIN,A.ETCODORI,A.ETCODMAR,A.ETCODART')
        ->limit(45)
        ->get();

        $consulta = array();
        $sku="";
        foreach ($consulta_db2 as $row) {
            $row->cod_linea = utf8_encode(trim($row->cod_linea));
            $row->ldesc = utf8_encode(trim($row->ldesc));
            $row->cod_origen = utf8_encode(trim($row->cod_origen));
            $row->odesc = utf8_encode(trim($row->odesc));
            $row->cod_marca = utf8_encode(trim($row->cod_marca));
            $row->mdesc = utf8_encode(trim($row->mdesc));
            $row->cod_articulo = utf8_encode(trim($row->cod_articulo));
            $row->cod_fabricacion = utf8_encode(trim($row->cod_fabricacion));
            $row->desc_articulo = utf8_encode(trim($row->desc_articulo));
            $row->sku_repuesto = utf8_encode(trim($row->sku_repuesto));
            if($sku!==$row->sku_repuesto){
                array_push($consulta, $row);
                $sku=$row->sku_repuesto;
            }
        }

        return response()->json($consulta);
    }

}
