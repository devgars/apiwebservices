<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Sync\Utilidades;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Stmt\Echo_;
use PhpParser\Node\Stmt\Else_;
use App\Jobs\QueueSyncBank;
use stdClass;

class SyncBankController extends Controller
{
    private $vista_registros_nuevos_db2 = 'LIBPRDDAT.CCAPLBCOL01';
    private $tabla_saldos_aux_db2 = 'LIBPRDDAT.CCAPLBCO';
    private $tablas = array(
        'vista_registros_nuevos_db2' => 'LIBPRDDAT.CCAPLBCOL01',
        'tabla_saldos_principal' => 'LIBPRDDAT.MMEIREP',
        'tabla_saldos_aux_db2' => 'LIBPRDDAT.CCAPLBCO',
        'tabla_doc_comp_cab' => 'LIBPRDDAT.CCDOCAGC',
        'tabla_doc_comp_det' => 'LIBPRDDAT.CCDOCAGD',
        'tabla_mmdmrep' => 'LIBPRDDAT.MMDMREP',
        'tabla_mmdnrep' => 'LIBPRDDAT.MMDNREP',
        'tabla_historicos_mmejrep' => 'LIBPRDDAT.MMEJREP',
        'tabla_aplicaciones_mmelrep' => 'LIBPRDDAT.MMELREP',
        'tabla_mmcdreca' => 'LIBPRDDAT.MMCDRECA',
        'tabla_mmdorep' => 'LIBPRDDAT.MMDOREP',
        'tabla_mmyprep' => 'LIBPRDDAT.MMYPREP',
        'tabla_mmcjfdt' => 'LIBPRDDAT.MMCJFDT',
    );

    private $codCia = '10';
    private $codBoletaDeposito = '06';
    private $codCobrador = '0T1299';
    private $tipoPlanillaCredito = '02';
    private $app = 'APPBANCOS';
    private $user = 'SISTEMAS';
    private $min_descarga_pagos_contado;
    private $cax_documents = 'DP';
    private $codcli_mym = '010077';
    private $count_temp = 0;

    public function __construct()
    {
        $this->min_descarga_pagos_contado = env('min_descarga_pagos_contado', 10);
    }

    public function execute_job_sync_bank(){
        // Excel::queueImport(new ExtractionImport($idHeader,$responsable), request()->file('excelin')->store('temp'));
        QueueSyncBank::dispatch();
        $response = [
            'status' => 200,
            'data' => $this->user
        ];
        return json_encode($response);
    }

    public function empezar_sincronizacion(){
        echo '<br>:::: empezar_sincronizacion - ' . date("d-m-Y H:i:s").' ::::';
        $this->count_temp = $this->count_temp + 1;
        //try {
            /*:: PROCEDEMOS A SINCRONIZAR LAS TABLAS DE CCAPLBCO Y CLIENTES_SALDOS*/
            $this->sincronizar_documentos();
            /*:::: FINALIZAMOS EJECUTANDO LA RECURSIVIDAD ::::::*/
            sleep('10');
            $this->empezar_sincronizacion();
            if ($this->count_temp > 3) {
                die('Finalizamos recursividad');
            }
        /*} catch (\Exception $e) {
            echo '<br>:::: error: finalizo_sincronizacion - ' . date("d-m-Y H:i:s").' ::::';
            return $e->getMessage();
        }*/
        
    }

    public function sincronizar_documentos(){
        if ($registros = $this->leer_nuevos_registros_db2()) {
            echo '<br>Registros a procesar: ' . sizeof($registros);
            $i = 0;
            foreach ($registros as $fila) {
                $fecha = date('Ymd');
                $hora = date('His');
                $fila_interface = new \stdClass();
                $fila_interface->accion = null;
                //Registra en tabla interface
                if ($this->insertar_actualizar_registro_tabla_interface($fila)) {
                    //Actualizar campos (FECMMWEB, HMSMMWEB) en registro en DB2
                    if (!$this->actualiza_estatus_tabla_aux_saldos_db2($fila)) // luego cambiar a $fila_interface
                    {
                        echo "<br>Registro no actualizado en AS400";
                        //die('REGISTRO NO ACTUALIZADO EN AS 400');
                    }
                }
                echo '<br>NroDoc: ' . $fila->abnrodoc . ' - F: ' . $fecha . ' - H: ' . $hora;
            }
        }
    }

    public function insertar_actualizar_registro_tabla_interface($registro)
    {
        if ($this->retorna_registro_interface($registro)) {
            //ACTUALIZAR REGISTRO EN INTERFACE
            return $this->actualiza_cliente_saldos_interface($registro);
        } else {
            //ESCRIBIR REGISTRO EN INTERFACE
            return $this->inserta_cliente_saldos_interface($registro);
        }
    }

    public function leer_nuevos_registros_db2()
    {
        echo "<br>procedemos a traer los registros";
        $registros = DB::connection('ibmi')
            ->table($this->vista_registros_nuevos_db2)
            ->get()->toArray();

        $clientIP = request()->ip();
        $arrayIn = array(
            'tabla' => 'LIBPRDDAT.MMCJFGT',
            'mensaje' => 'registro nuevos '.$clientIP.' fecha'. date("d-m-Y H:i:s"),
            'otro' => json_encode($registros)
        );
        DB::table('log_migraciones')->insert($arrayIn);
        return $registros;
    }

    public function retorna_registro_interface($registro)
    {
        if ($registro->abtipdoc === 'DA' || $registro->abtipdoc === $this->cax_documents) {
            $rs = DB::table('cliente_saldos')
                ->where('ABCODCIA', '=', $registro->abcodcia)
                ->where('ABCODCLI', '=', $registro->abcodcli)
                ->where('ABTIPDOC', '=', $registro->abtipdoc)
                ->where('ABNRODOC', '=', $registro->abnrodoc)
                ->get();
        } else {
            $rs = DB::table('cliente_saldos')
                ->where('ABCODCIA', '=', $registro->abcodcia)
                ->where('ABCODSUC', '=', $registro->abcodsuc)
                ->where('ABCODCLI', '=', $registro->abcodcli)
                ->where('ABTIPDOC', '=', $registro->abtipdoc)
                ->where('ABNRODOC', '=', $registro->abnrodoc)
                ->get();
        }

        if ($rs && sizeof($rs) > 0) return $rs;
        else return false;
    }

    public function actualiza_cliente_saldos_interface($objeto)
    {
        $absts = ($objeto->rupdate == 3) ? 'I' : $objeto->absts;

        if ($objeto->abtipdoc === 'DA' || $objeto->abtipdoc === $this->cax_documents) {
            $arrayWhere = array(
                ['ABCODCIA', '=', trim($objeto->abcodcia)],
                ['ABCODCLI', '=', trim($objeto->abcodcli)],
                ['ABTIPDOC', '=', trim($objeto->abtipdoc)],
                ['ABNRODOC', '=', trim($objeto->abnrodoc)]
            );
        } else {
            $arrayWhere = array(
                ['ABCODCIA', '=', trim($objeto->abcodcia)],
                ['ABCODSUC', '=', trim($objeto->abcodsuc)],
                ['ABCODCLI', '=', trim($objeto->abcodcli)],
                ['ABTIPDOC', '=', trim($objeto->abtipdoc)],
                ['ABNRODOC', '=', trim($objeto->abnrodoc)]
            );
        }

        $arrayUpdate = array(
            'ABSTS' => $absts,
            'ABIMPSLD' => trim($objeto->abimpsld),
            'ABFRMPAG' => trim($objeto->abfrmpag),
            'CBNROSER' => trim($objeto->cbnroser),
            'CBNROCOR' => trim($objeto->cbnrocor),
            'ABFECVCT' => trim($objeto->abfecvct)
        );

        $this->actualiza_tabla_postgres('cliente_saldos', $arrayWhere, $arrayUpdate);
        return true;
    }

    public function inserta_cliente_saldos_interface($registro)
    {
        $ident_natural = trim($registro->aknroide);
        $ident_juridica = trim($registro->ifnvoruc);
        $numero_identificacion = ($registro->aktipide === '01' && strlen($ident_natural) > 0) ? $ident_natural : $ident_juridica;
        return DB::table('cliente_saldos')->insertGetId([
            'ABCODCIA' => trim($registro->abcodcia),
            'ABCODSUC' => trim($registro->abcodsuc),
            'ABCODCLI' => trim($registro->abcodcli),
            'AKTIPIDE' => trim($registro->aktipide),
            'NUMERO_IDENTIFICACION' => $numero_identificacion,
            'AKRAZSOC' => utf8_encode(trim($registro->akrazsoc)),
            'ABTIPDOC' => trim($registro->abtipdoc),
            'ABNRODOC' => trim($registro->abnrodoc),
            'ABFECEMI' => trim($registro->abfecemi),
            'ABCODMON' => trim($registro->abcodmon),
            'ABIMPSLD' => trim($registro->abimpsld),
            'ABIMPCCC' => trim($registro->abimpccc),
            'ABFRMPAG' => trim($registro->abfrmpag),
            'ABMODPAG' => trim($registro->abmodpag),
            'ABCNDPAG' => trim($registro->abcndpag),
            'ABFECVCT' => trim($registro->abfecvct),
            'ABFECTCM' => trim($registro->abfectcm),
            'CBNROSER' => trim($registro->cbnroser),
            'CBNROCOR' => trim($registro->cbnrocor),
            'ABSTS' => trim($registro->absts),
            'ABCODVEN' => trim($registro->abcodven),
            'created_at' => date("Y-m-d H:i:s")
        ]);
    }

    public function actualiza_estatus_tabla_aux_saldos_db2($registro)
    {
        $fecha = date('Ymd');
        $hora = date('His');
        $absts = ($registro->rupdate == 3) ? 'I' : $registro->absts;
        return DB::connection('ibmi')
            ->table($this->tabla_saldos_aux_db2)
            ->where('ABCODCIA', '=', $registro->abcodcia)
            ->where('ABCODSUC', '=', $registro->abcodsuc)
            ->where('ABCODCLI', '=', $registro->abcodcli)
            ->where('ABTIPDOC', '=', $registro->abtipdoc)
            ->where('ABNRODOC', '=', $registro->abnrodoc)
            ->where('ABFECEMI', '=', $registro->abfecemi)
            ->where('ABCODMON', '=', $registro->abcodmon)
            ->where('ABIMPSLD', '=', $registro->abimpsld)
            ->where('ABSTS', '=', $registro->absts)
            ->update(['FECMMWEB' => $fecha, 'HMSMMWEB' => $hora, 'ABJOB' => $this->app, 'RUPDATE' => '0', 'ABSTS' => $absts]);
    }

    /* :::: funciones genericas ::::*/

    public function actualiza_tabla_postgres($tabla, $arrayWhere, $arrayUpdate)
    {
        return DB::table($tabla)
            ->where($arrayWhere)
            ->update($arrayUpdate);
    }
}
