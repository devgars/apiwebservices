<?php

namespace App\Http\Controllers\Cajas;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sync\Utilidades;
use Illuminate\Http\Request;

use DB;
use stdClass;
use Carbon\Carbon;
use Validator;

use Barryvdh\DomPDF\PDF;
use Illuminate\Support\Facades\Storage;


class CajasController extends Controller
{
    public function generar_pdf_caja_cerrada(Request $request)
    {
        $objeto = new stdClass();

        $objeto->codCia = $request->cia;
        $objeto->suc = $request->suc;
        $objeto->fecha = $request->fecha;


        //$objeto->tipo_cambio = Utilidades::retorna_tipo_cambio_dolar_mym('02');

        //ENCABEZADO DE PLANILLA
        $arrayWhere = [
            ['CJAPCCIA', '=', $objeto->codCia],
            ['CJAPCSUC', '=', $objeto->suc],
            ['cjapfepl', '=', $objeto->fecha],
        ];
        $objeto->enc_rep = DB::connection('ibmi')->table('LIBPRDDAT.MMCJFAP')->where($arrayWhere)->first();
        //FIN - ENCABEZADO DE PLANILLA
        if (!$objeto->enc_rep) {
            echo "<br>HAY UN ERROR EN LOS PARAMETROS";
            exit;
        }

        $tipos_doc = [
            'Facturas' => '01',
            'Boletas' => '03'
        ];

        $objeto->total_dolares = 0.0;
        $objeto->total_soles = 0.0;

        foreach ($tipos_doc as $tipo_doc => $valor) {
            //REGISTROS DE PLANILLA
            $arrayWhere = [
                ['CJCBCCIA', '=', $objeto->codCia],
                ['CJCBCSUC', '=', $objeto->suc],
                ['CJCBNPLL', '=', $objeto->enc_rep->cjapnpll],
                ['CJCBTIPD', '=', $valor],
                //['CJCBNPDC', '=', 828893]
            ];

            $select = ['cli.akrazsoc', 'cb.*'];
            $registros_planilla = DB::connection('ibmi')->table('LIBPRDDAT.MMCJFCBL5 as cb')
                ->join('LIBPRDDAT.MMAKREP as cli', 'cb.cjcbccli', '=', 'cli.akcodcli')
                ->select($select)
                ->where($arrayWhere)->get()->toArray();

            $docs['documentos'] = $registros_planilla;

            $total_dolares_td = 0.0;
            $total_soles_td = 0.0;
            $cant_pagos_online = 0;
            $total_pagado_dolares = 0.0;
            $total_pagado_soles = 0.0;
            foreach ($registros_planilla as $registro) {
                $registro->akrazsoc = trim(utf8_encode($registro->akrazsoc));
                $registro->cjcbfdoc = Carbon::createFromFormat('Ymd', $registro->cjcbfdoc)->format('d-m-Y');
                //ACUMULAR SOLES Y DOLARES
                $total_dolares_td += ($registro->cjcbmond === '02') ? round((float)$registro->cjcbimdo, 2) : 0;
                $total_soles_td += ($registro->cjcbmond === '01') ? round((float)$registro->cjcbimdo, 2) : 0;

                //VALIDAR SI ES PAGO ONLINE
                $cant_pagos_online += (trim($registro->cjcbpgm) === 'APPBANCOS') ? 1 : 0;

                $registro->cjcbimdo = round($registro->cjcbimdo, 2);

                $registro->total_pagos_dolares = round($this->retorna_total_pagos_documento_moneda($objeto->codCia, $registro->cjcbsudo, $registro->cjcbnpll, $registro->cjcbnpdc, '02'), 2);
                $registro->total_pagos_soles = round($this->retorna_total_pagos_documento_moneda($objeto->codCia, $registro->cjcbsudo, $registro->cjcbnpll, $registro->cjcbnpdc, '01'), 2);

                $total_pagado_dolares += round((float)$registro->total_pagos_dolares, 2);
                $total_pagado_soles += round((float)$registro->total_pagos_soles, 2);
            }

            $objeto->tipos_doc[$tipo_doc] = $docs['documentos'];
            $objeto->total_dolares_td[$tipo_doc] = $total_dolares_td;
            $objeto->total_soles_td[$tipo_doc] = $total_soles_td;
            $objeto->cant_pagos_online[$tipo_doc] = $cant_pagos_online;

            //$objeto->total_pagado_dolares_td[$tipo_doc] = $total_pagado_dolares;
            //$objeto->total_pagado_soles_td[$tipo_doc] = $total_pagado_soles;

            //echo "<br>Tipo Documento: $tipo_doc - Total USD: $total_dolares_td - Total PEN: $total_soles_td - Qty. pagos online: $cant_pagos_online";
            $objeto->total_dolares += $total_dolares_td;
            $objeto->total_soles += $total_soles_td;
        }

        //NOTAS DE CRÉDITO
        $select = ['cjdtccia', 'cjdtcsuc', 'cjdtnrop', 'cjcbccli', 'akrazsoc', 'cjdtnrdo', 'cjdtfecd', 'fxnroser', 'fxnrocor', 'cjdtmone', 'cjdtimpd', 'fximptot', 'fximpimp'];
        $objeto->notas_credito = DB::connection('ibmi')
            ->table('LIBPRDDAT.MMCJFDT as dt')
            ->join('LIBPRDDAT.MMCJFCB as cb', function ($join) {
                $join->on('dt.cjdtccia', '=', 'cb.cjcbccia');
                $join->on('dt.cjdtnpll', '=', 'cb.cjcbnpll');
                $join->on('dt.cjdtnpdc', '=', 'cb.cjcbnpdc');
            })
            ->join('LIBPRDDAT.MMIAREL0 AS ia', function ($join) {
                $join->on('dt.cjdtccia', '=', 'ia.iacodcia');
                $join->on('dt.cjdtcsuc', '=', 'ia.iacodsuc');
                $join->on('cb.cjcbccli', '=', 'ia.iacodcli');
                $join->on('dt.cjdtnrdo', '=', 'ia.ianroref');
                $join->on('dt.cjdtsecu', '=', 'ia.iaitem01');
            })
            ->join('LIBPRDDAT.MMFXREL0 AS fx', function ($join) {
                $join->on('dt.cjdtccia', '=', 'fx.fxcodcia');
                $join->on('dt.cjdtcsuc', '=', 'fx.fxcodsuc');
                $join->on('ia.ianropdc', '=', 'fx.fxnropdc');
                $join->on('dt.cjdtsecu', '=', 'fx.fxitem01');
            })
            ->join('LIBPRDDAT.MMAKREP cli', 'cb.cjcbccli', '=', 'cli.akcodcli')
            //->select($select)
            ->where('CJDTCPAG', 'NC')
            ->where('cjdtnpll', $objeto->enc_rep->cjapnpll)
            ->where('cjdtccia', $objeto->codCia)
            ->where('cjdtest', 'A')
            ->get()->toArray();
        /*
        echo '<pre>';
        print_r($objeto->notas_credito);
        exit;
        */


        foreach ($objeto->notas_credito as $registro) {
            $registro->akrazsoc = trim(utf8_encode($registro->akrazsoc));
            $registro->cjdtfecd = Carbon::createFromFormat('Ymd', $registro->cjdtfecd)->format('d-m-Y');
            //$registro->importe_nc = round(((float)$registro->fximptot + (float)$registro->fximpimp), 2);
            $registro->importe_nc = round($registro->cjdtimpd, 2);
        }


        //DEPÓSITOS BANCARIOS
        //$objeto->depositos_bancarios = $this->retorna_documentos_pagados_planilla($objeto->enc_rep->cjapnpll, ['BD']);
        $select = ['cjdtccia', 'cjdtcsuc', 'cjdtnrop', 'cjcbccli', 'akrazsoc', 'cjdtbnco', 'eudsclar AS nombre_banco', 'cjdtncta as nro_cta', 'cjdtnrdo nro_operacion', 'cjdtfecd as fecha_deposito', 'cjdtmone', 'cjdtimpd'];

        $objeto->depositos_bancarios =  DB::connection('ibmi')
            ->table('LIBPRDDAT.MMCJFDT as dt')
            ->select($select)
            ->join('LIBPRDDAT.MMCJFCB as cb', function ($join) {
                $join->on('dt.cjdtccia', '=', 'cb.cjcbccia');
                $join->on('dt.cjdtnpll', '=', 'cb.cjcbnpll');
                $join->on('dt.cjdtnpdc', '=', 'cb.cjcbnpdc');
            })
            ->join('LIBPRDDAT.MMEUREL0 as tbl', function ($join) {
                $join->on('tbl.EUCODTBL', '=', "'04'");
                $join->on('tbl.eucodele', '=', 'dt.cjdtbnco');
            })
            ->join('LIBPRDDAT.MMAKREP cli', 'cb.cjcbccli', '=', 'cli.akcodcli')
            ->where('dt.cjdtccia', $objeto->codCia)
            ->where('dt.cjdtnpll', $objeto->enc_rep->cjapnpll)
            ->where('CJDTCPAG', 'BD')
            ->get()->toArray();
        //echo '<pre>';
        //print_r($objeto->depositos_bancarios);
        //exit;

        foreach ($objeto->depositos_bancarios as $registro) {
            $registro->akrazsoc = trim(utf8_encode($registro->akrazsoc));
            $registro->nombre_banco = trim(utf8_encode($registro->nombre_banco));
            $registro->nro_cta = trim(($registro->nro_cta));
            $registro->nro_operacion = trim(($registro->nro_operacion));
            $registro->fecha_deposito = Carbon::createFromFormat('Ymd', $registro->fecha_deposito)->format('d-m-Y');
            $registro->gastos_deposito = 0.0;
        }

        $select = ['cjgtcia as cjdtccia', "'02' as cjdtcsuc", 'cjgtsec as cjdtnrop', "'' as cjcbccli", "'DEPOSITO DE CAJA' as akrazsoc", 'eucodele as cjdtbnco', 'eudsclar as nombre_banco', 'cjgtcta as nro_cta', 'cjgtnop as nro_operacion', 'cjgtfeg as fecha_deposito', 'cjgtmon as cjdtmone', 'cjgtimg as cjdtimpd', 'cjgtgtb as gastos_deposito'];
        $depositos_cax = DB::connection('ibmi')->table('LIBPRDDAT.MMCJFGT as cax')
            ->join('LIBPRDDAT.MMEUREL0 as tbl', function ($join) {
                $join->on('tbl.EUCODTBL', '=', "'04'");
                $join->on('tbl.eucodele', '=', 'cax.cjgtbco');
            })
            ->select($select)
            ->where('cax.cjgtcia', $objeto->codCia)
            ->where('cax.cjgtnpl', $objeto->enc_rep->cjapnpll)

            ->get()->toArray();

        foreach ($depositos_cax as $deposito) {
            $deposito->fecha_deposito = Carbon::createFromFormat('Ymd', $deposito->fecha_deposito)->format('d-m-Y');
            array_push($objeto->depositos_bancarios, $deposito);
        }

        /*
        echo '<pre>';
        print_r($depositos_cax);
        print_r($objeto->depositos_bancarios);
        exit;
        */

        //BANCO TARJETAS
        $objeto->pagos_tdc = $this->retorna_documentos_pagados_planilla($objeto->enc_rep->cjapnpll, ['TC']);
        foreach ($objeto->pagos_tdc as $registro) {
            $registro->akrazsoc = trim(utf8_encode($registro->akrazsoc));
            $registro->cjdtfecp = Carbon::createFromFormat('Ymd', $registro->cjdtfecp)->format('d-m-Y');
        }


        //ANTICIPOS APLICADOS
        $objeto->anticipos = $this->retorna_documentos_pagados_planilla($objeto->enc_rep->cjapnpll, ['PA']);
        foreach ($objeto->anticipos as $registro) {
            $registro->akrazsoc = trim(utf8_encode($registro->akrazsoc));
            $registro->cjdtfecp = Carbon::createFromFormat('Ymd', $registro->cjdtfecp)->format('d-m-Y');
        }

        /*
        echo '<pre>';
        print_r($objeto->notas_credito);
        print_r($objeto->depositos_bancarios);
        print_r($objeto->pagos_tdc);
        print_r($objeto->anticipos);
        exit;
        */


        $filename = 'caja/'.date("YmdHis") . '_prueba.pdf';
        $objeto->nombre_archivo =  Storage::url($filename);
        $pdf = \PDF::loadview('cajas.reporte_cierre_caja', ['data' => $objeto]);
        $pdf->setPaper('letter', 'portrait');
        $pdf->render();
        Storage::disk('public')->put($filename, $pdf->output());



        return view('cajas.reporte_cierre_caja', ['data' => $objeto]);
    }


    public function retorna_total_pagos_documento_moneda($codCia, $codSuc, $nro_planilla, $nro_doc, $cod_moneda)
    {
        /*
            EF,VL,AJ,CH,NC,BD,TC,PA,CR
            EF -> EFECTIVO
            VL -> VUELTO
            AJ -> AJUSTE
            CH -> CHEQUE
            NC -> NOTA DE CREDITO
            BD -> DEPOSITO BANCARIO
            TC -> TARJETA DE CREDITO
            PA -> PAGO ADELANTADO
            CR -> COMPROBANTE RETENCION
        */

        $vec_monto_x_tp = [];

        $arrayWhere = [
            ['CJDTCCIA', '=', $codCia],
            ['CJDTCSUC', '=', $codSuc],
            ['CJDTNPLL', '=', $nro_planilla],
            ['CJDTNPDC', '=', $nro_doc],
            ['CJDTMONE', '=', $cod_moneda],
            ['CJDTEST', '=', 'A'],
        ];

        $tipos_pago_suma = ['EF', 'TC', 'BD','CH', 'PA'];
        $sumar = DB::connection('ibmi')->table('LIBPRDDAT.MMCJFDT')->where($arrayWhere)->whereIn('CJDTCPAG', $tipos_pago_suma)->sum('CJDTIMPD');
        
        $arrayWhereNC = [
            ['CJDTCCIA', '=', $codCia],
            ['CJDTSERC', '=', $codSuc],
            ['CJDTNPLL', '=', $nro_planilla],
            ['CJDTNPDC', '=', $nro_doc],
            ['CJDTMONE', '=', $cod_moneda],
            ['CJDTEST', '=', 'A'],
        ];
        $tipos_pago_suma_nc = [ 'NC'];
        $sumar_nc = DB::connection('ibmi')->table('LIBPRDDAT.MMCJFDT')->where($arrayWhereNC)->whereIn('CJDTCPAG', $tipos_pago_suma_nc)->sum('CJDTIMPD');



        $tipos_pago_resta = ['VL']; //, 'AJ'
        $restar = DB::connection('ibmi')->table('LIBPRDDAT.MMCJFDT')->where($arrayWhere)->whereIn('CJDTCPAG', $tipos_pago_resta)->sum('CJDTIMPD');
        /*
        if ($nro_doc == 828979) {
            die("<br> Sumar: $sumar | Restar: $restar");
        }
        */
        /*
        if ($cod_moneda === '02') {
            echo '<pre>';
            print_r($arrayWhere);
            echo "<br>Suma: " . $suma;
            exit;
        }
        */

        return round(((float)($sumar)+ (float)($sumar_nc) - (float)($restar)), 2);
        /*
        foreach ($tipos_pago as $tp) {
            $arrayWhere = [
                ['CJDTCCIA', '=', $codCia],
                ['CJDTCSUC', '=', $codSuc],
                ['CJDTNPLL', '=', $nro_planilla],
                ['CJDTNPDC', '=', $nro_doc],
                ['CJDTMONE', '=', $cod_moneda],
                ['CJDTEST', '=', 'A'],
                ['CJDTCPAG', '=', $tp],
            ];
            $vec_monto_x_tp[] = DB::connection('ibmi')->table('LIBPRDDAT.MMCJFDT')->where($arrayWhere)->sum('CJDTIMPD');
        }

        
        echo '<pre>';
        echo "<br>CIA: $codCia - SUC: $codSuc - Nro Planilla: $nro_planilla - Nro doc.: $nro_doc - Mon: $cod_moneda";
        print_r($tipos_pago);
        print_r($vec_monto_x_tp);
        echo "<br>Total: " . round((float)($vec_monto_x_tp[0] + $vec_monto_x_tp[1] - $vec_monto_x_tp[2] - $vec_monto_x_tp[3] + $vec_monto_x_tp[4]), 2);
        exit;

        return round((float)($vec_monto_x_tp[0] + $vec_monto_x_tp[1] + $vec_monto_x_tp[2] + $vec_monto_x_tp[3] + $vec_monto_x_tp[4] + $vec_monto_x_tp[5] + $vec_monto_x_tp[6] - $vec_monto_x_tp[7]), 2);
        */
        //return round((float)($vec_monto_x_tp[0] + $vec_monto_x_tp[1] - $vec_monto_x_tp[2] - $vec_monto_x_tp[3] + $vec_monto_x_tp[4]), 2);
    }

    public function retorna_documentos_pagados_planilla($nro_planilla, $array_tipo_documentos)
    {
        $select = ['cli.akrazsoc', 'cb.*', 'dt.*'];
        $registros = DB::connection('ibmi')->table('LIBPRDDAT.MMCJFCBL5 as cb')
            ->join('LIBPRDDAT.MMCJFDT as dt', function ($join) {
                $join->on('cb.cjcbccia', '=', 'dt.cjdtccia');
                $join->on('cb.cjcbsudo', '=', 'dt.cjdtcsuc');
                $join->on('cb.cjcbnpll', '=', 'dt.cjdtnpll');
                $join->on('cb.cjcbnpdc', '=', 'dt.cjdtnpdc');
            })
            ->join('LIBPRDDAT.MMAKREP as cli', 'cb.cjcbccli', '=', 'cli.akcodcli')
            ->select($select)
            ->where('cb.cjcbnpll', $nro_planilla)
            ->whereIn('dt.cjdtcpag', $array_tipo_documentos) //CJDTCPAG
            ->where('dt.cjdtest', 'A')
            ->get()->toArray();
        //->toSql();
        //die($registros);
        //echo '<pre>';
        //print_r($registros);
        //exit;
        return $registros;
    }

    public function retorna_depositos_bancarios_planilla($nro_planilla)
    {
        echo $nro_planilla;
        $select = ['cli.akrazsoc', 'dt.*'];
        $registros = DB::connection('ibmi')->table('LIBPRDDAT.MMYPREP AS dp')
            ->join('LIBPRDDAT.MMAKREP as cli', 'dp.ypcodcli', '=', 'cli.akcodcli')
            ->select($select)
            ->where('YP', $nro_planilla)
            ->get()->toArray();

        print_r($registros);
        exit;
    }
}
