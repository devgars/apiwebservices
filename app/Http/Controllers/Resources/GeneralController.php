<?php

namespace App\Http\Controllers\Resources;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

use Illuminate\Support\Facades\DB;

class GeneralController extends Controller
{
    public function get_providers_as400(Request $request)
    {
        $fromDate = null;
        $provCode = null;
        $provName = (strlen($request->provName) > 0) ? $request->provName : '';

        $select = [
            'prov.AHCODPRV as idproveedor',
            'ruc.IPNVORUC as nroidentificacion',
            'prov.AHRAZSOC as razonsocial',
            'dire.CGCODPAI as idpais',
            'prov.AHJDT as fecharegistro'
        ];
        if ($fromDate) {
            $fromDate = Carbon::createFromFormat('Ymd', $fromDate, 'America/Lima');
            $providers = DB::connection('ibmi')
                ->table('LIBPRDDAT.MMAHREP AS prov')
                ->leftJoin('LIBPRDDAT.MMIPREP AS ruc', 'prov.AHCODPRV', '=', 'ruc.IPCODCLI')
                ->leftJoin('LIBPRDDAT.MMCGREP AS dire', 'prov.AHCODPRV', '=', 'dire.CGCODPRV')
                ->where('prov.AHSTS', 'A')
                ->where('prov.AHJDT', $fromDate)
                ->whereIn('prov.AHTIPPRV', ['IE', 'MP', 'PT', 'RP', 'CO'])
                ->orderBy('prov.AHRAZSOC', 'ASC')
                ->select($select)
                ->distinct()
                //->get()->toArray();
                ->toSql();
            die($providers);
        }
        if ($provName) {
            $provName = '%' . strtoupper(trim($provName)) . '%';
            $providers = DB::connection('ibmi')
                ->table('LIBPRDDAT.MMAHREP AS prov')
                ->leftJoin('LIBPRDDAT.MMIPREP AS ruc', 'prov.AHCODPRV', '=', 'ruc.IPCODCLI')
                ->leftJoin('LIBPRDDAT.MMCGREP AS dire', 'prov.AHCODPRV', '=', 'dire.CGCODPRV')
                ->where('prov.AHSTS', 'A')
                //->whereIn('prov.AHTIPPRV', ['IE', 'MP', 'PT', 'RP', 'CO'])
                ->where('prov.AHRAZSOC', 'like', $provName)
                ->orderBy('prov.AHRAZSOC', 'ASC')
                ->select($select)
                //->distinct()
                ->get()->toArray();
            //->toSql();
            //die(print_r($providers));
        }
        if (!$provName && !$fromDate) {
            $providers = DB::connection('ibmi')
                ->table('LIBPRDDAT.MMAHREP AS prov')
                ->leftJoin('LIBPRDDAT.MMIPREP AS ruc', 'prov.AHCODPRV', '=', 'ruc.IPCODCLI')
                ->leftJoin('LIBPRDDAT.MMCGREP AS dire', 'prov.AHCODPRV', '=', 'dire.CGCODPRV')
                ->where('prov.AHSTS', 'A')
                ->whereIn('prov.AHTIPPRV', ['IE', 'MP', 'PT', 'RP', 'CO'])
                ->orderBy('prov.AHRAZSOC', 'ASC')
                ->select($select)
                ->distinct()
                ->get()->toArray();
            //->toSql();
            //die($providers);
        }


        $response = new \stdClass();
        $rs = (is_array($providers) && sizeof($providers) > 0) ? $providers : array();
        foreach ($rs as $prov) {
            $prov->nroidentificacion = trim($prov->nroidentificacion);
            $prov->idpais = trim($prov->idpais);
            $prov->razonsocial = utf8_encode(trim($prov->razonsocial));
        }

        $response->total_registros = sizeof($rs);
        $response->lista = $rs;
        return response()->json($response, 200, array('Content-Type' => 'application/json; charset=utf-8'));
    }

    public function get_countries()
    {
        $select = ['code AS codigo', 'abrv', 'name AS pais'];

        $rs = DB::table('ubigeos')
            ->select($select)
            ->where('ubigeo_type_id', '=', 1)
            ->where('reg_status', '=', 1)
            ->get()->toArray();

        return response()->json($rs, 200, array('Content-Type' => 'application/json; charset=utf-8'));
    }
}
