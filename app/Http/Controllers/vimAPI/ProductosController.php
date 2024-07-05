<?php

namespace App\Http\Controllers\vimAPI;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use DB;
// use Maatwebsite\Excel\Concerns\ToArray;
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
        //dd($object);
        
        return response()->json($object, 200, array('Content-Type' => 'application/json; charset=utf-8'));
    }
}
