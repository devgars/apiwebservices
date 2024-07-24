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

        $repuesto = DB::connection('ibmi')->table('LIBPRDDAT.MMETREL0')
        ->where('ETCODLIN', '=', $etcodlin)
        ->where('ETCODORI', '=', $etcodori)
        ->where('ETCODMAR', '=', $etcodmar)
        ->where('ETCODART', '=', $etcodart)
        ->where('ETSTS', '=', $etsts)
        ->limit(1)
        ->get();

        return response()->json($repuesto);
    }

    private function f_transformarSku($sku)
    {
        $fsku=array();
        $asku=explode('.',$sku);
        $nsku=substr($asku[0],0,strlen($asku[0])-5);
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

}
