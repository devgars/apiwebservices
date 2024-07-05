<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Http\Controllers\Sync\Utilidades;
use DB;

class QueueGenerateOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $objeto = '';
    private $codCia = '';
    private $nuevo_pedido = '';
    private $tipo_documento = '';
    private $modalidad_pago = '';
    private $numeroIdentidad = '';
    private $userId = '';
    private $codUser = '';
    private $job_as = '';

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($objeto, $codCia, $nuevo_pedido, $tipo_documento, $modalidad_pago, $numeroIdentidad, $userId, $codUser, $job_as)
    {
        $this->objeto = $objeto;
        $this->codCia = $codCia;
        $this->nuevo_pedido = $nuevo_pedido;
        $this->tipo_documento = $tipo_documento;
        $this->modalidad_pago = $modalidad_pago;
        $this->numeroIdentidad = $numeroIdentidad;
        $this->userId = $userId;
        $this->codUser = $codUser;
        $this->job_as = $job_as;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $util = new Utilidades();
        $objeto = $this->objeto;
        $codCia = $this->codCia;
        $nuevo_pedido = $this->nuevo_pedido;
        $tipo_documento = $this->tipo_documento;
        $modalidad_pago = $this->modalidad_pago;
        $numeroIdentidad = $this->numeroIdentidad;
        $userId = $this->userId;
        $codUser = $this->codUser;
        $job_as = $this->job_as;

        //SI ES A CREDITO, ESCRIBIR EN TABLA MMMLREP
        if ($objeto->formaPago === 'R') {
            $arrayInsert = array(
                'MLCODCIA' => $codCia,
                'MLCODSUC' => $objeto->codSucursal,
                'MLNROPDC' => $nuevo_pedido,
                'MLAPRPOR' => "",
                'MLFECAPR' => 0,
                'MLHORAPR' => 0,
                'MLSTSCRD' => "E",
                'MLSTSIMP' => "E",
                'MLSTS' => "A",
                'MLUSR' => $userId,
                'MLJOB' => $job_as,
                'MLJDT' => date("Ymd"),
                'MLJTM' => date("His")
            );
            if (!DB::connection('ibmi')->table('LIBPRDDAT.MMMLREP')->insert([
                $arrayInsert
            ])) {
                //echo 'Error insertando pago-credito PEDIDO';
                DB::rollBack();
                return false;
            }
        }
        //FIN - SI ES A CREDITO, ESCRIBIR EN TABLA MMMLREP

        //REGISTRAR DIRECCIONES
        $direcciones = array();
        $tipo_direcciones = array('01', '03', '05');

        for ($i = 0; $i < sizeof($tipo_direcciones); $i++) {
            if ($tipo_direcciones[$i] === '03') {
                if (!$direccion = DB::connection('ibmi')->table('LIBPRDDAT.MMALREP')
                    ->where('ALCODCLI', $objeto->idCliente)
                    ->where('ALITEM01', $objeto->idDirecionEntrega)
                    ->first()) {
                    $direccion = DB::connection('ibmi')->table('LIBPRDDAT.MMALREP')
                        ->where('ALCODCLI', $objeto->idCliente)
                        ->where('ALTIPDIR', $tipo_direcciones[$i])
                        ->orderBy('ALITEM01', 'DESC')
                        ->first();
                }
                $dir_entrega = $direccion;
            } else {
                $direccion = DB::connection('ibmi')->table('LIBPRDDAT.MMALREP')
                    ->where('ALCODCLI', $objeto->idCliente)
                    ->where('ALTIPDIR', $tipo_direcciones[$i])
                    ->orderBy('ALITEM01', 'DESC')
                    ->first();
            }
            if ($direccion) array_push($direcciones, $direccion);
        }
        $arrayIn = array(
            'tabla' => 'ord_orders',
            'mensaje' => 'Direcciones',
            'otro' => json_encode($direcciones),
            'created_at' => date("Y-m-d H:i:s")
        );
        $util->inserta_into_tabla('log_migraciones', $arrayIn);

        if ($direcciones && is_array($direcciones)) {
            //$dir_entrega = ($objeto->idDirecionEntrega !== false) ? $direcciones[$objeto->idDirecionEntrega] : 0;
            if ($dir_entrega) // && $dir_entrega->tp_dir_code==='03')
            {
                $direccion_completa = (strlen($dir_entrega->aldscdir) > 30) ? substr($dir_entrega->aldscdir, 0, 30) : $dir_entrega->aldscdir;
                $zone_name = (strlen($dir_entrega->aldsczdr) > 20) ? substr($dir_entrega->aldsczdr, 0, 20) : $dir_entrega->aldsczdr;

                $arrayInsertAs = array(
                    'CCCODCIA' => $codCia,
                    'CCCODSUC' => $objeto->codSucursal,
                    'CCNROPED' => $objeto->idCotizacion,
                    'CCNROPDC' => $nuevo_pedido,
                    'CCITEM01' => $objeto->idDirecionEntrega,
                    'CCTIPDIR' => $dir_entrega->altipdir,
                    'CCVIADIR' => $dir_entrega->alviadir,
                    'CCDSCDIR' => $direccion_completa,
                    'CCNRODIR' => $dir_entrega->alnrodir,
                    'CCNRODPT' => ($dir_entrega->alnrodpt) ? $dir_entrega->alnrodpt : "",
                    'CCNROPSO' => ($dir_entrega->alnropso) ? $dir_entrega->alnropso : "",
                    'CCNROMZA' => ($dir_entrega->alnromza) ? $dir_entrega->alnromza : "",
                    'CCNROLTE' => ($dir_entrega->alnrolte) ? $dir_entrega->alnrolte : "",
                    'CCZONDIR' => ($dir_entrega->alzondir) ? $dir_entrega->alzondir : "",
                    'CCDSCZDR' => $zone_name,
                    'CCDEPART' => $dir_entrega->aldepart,
                    'CCPROVIN' => $dir_entrega->alprovin,
                    'CCDISTRI' => $dir_entrega->aldistri,
                    'CCPLNGEO' => $dir_entrega->alplngeo,
                    'CCFILUBI' => $dir_entrega->alfilubi,
                    'CCCOLUBI' => $dir_entrega->alcolubi,
                    'CCCODPAI' => $dir_entrega->alcodpai,
                    'CCCODCIU' => "",
                    'CCSTSPDO' => 'A',
                    'CCSTS' => 'A',
                    'CCUSR' => $userId,
                    'CCJOB' => $job_as,
                    'CCJDT' => date("Ymd"),
                    'CCJTM' => date("His")
                );

                $arrayIn = array(
                    'tabla' => 'ord_orders',
                    'mensaje' => 'Dirección Entrega',
                    'otro' => json_encode($arrayInsertAs),
                    'created_at' => date("Y-m-d H:i:s")
                );
                $util->inserta_into_tabla('log_migraciones', $arrayIn);

                if ($util->inserta_into_tabla_as400('LIBPRDDAT.MMCCREP', $arrayInsertAs)) $i = 1;
                else $i = 0;
            } else $i = 0;

            foreach ($direcciones as $direccion) {
                $arrayWhereAs = array(
                    ['CCCODCIA', '=', $codCia],
                    ['CCCODSUC', '=', $objeto->codSucursal],
                    ['CCNROPDC', '=', $nuevo_pedido],
                    ['CCTIPDIR', '=', $direccion->altipdir],

                );
                if (!$util->selecciona_fila_from_tabla_db2('LIBPRDDAT.MMCCREP', $arrayWhereAs)) {
                    $i++;
                    $direccion_completa = (strlen($direccion->aldscdir) > 30) ? substr($direccion->aldscdir, 0, 30) : $direccion->aldscdir;
                    $zone_name = (strlen($direccion->aldsczdr) > 20) ? substr($direccion->aldsczdr, 0, 20) : $direccion->aldsczdr;
                    $arrayInsertAs = array(
                        'CCCODCIA' => $codCia,
                        'CCCODSUC' => $objeto->codSucursal,
                        'CCNROPED' => $objeto->idCotizacion,
                        'CCNROPDC' => $nuevo_pedido,
                        'CCITEM01' => $direccion->alitem01,
                        'CCTIPDIR' => $direccion->altipdir,
                        'CCVIADIR' => $direccion->alviadir,
                        'CCDSCDIR' => $direccion_completa,
                        'CCNRODIR' => $direccion->alnrodir,
                        'CCNRODPT' => ($direccion->alnrodpt) ? $direccion->alnrodpt : "",
                        'CCNROPSO' => ($direccion->alnropso) ? $direccion->alnropso : "",
                        'CCNROMZA' => ($direccion->alnromza) ? $direccion->alnromza : "",
                        'CCNROLTE' => ($direccion->alnrolte) ? $direccion->alnrolte : "",
                        'CCZONDIR' => ($direccion->alzondir) ? $direccion->alzondir : "",
                        'CCDSCZDR' => $zone_name,
                        'CCDEPART' => $direccion->aldepart,
                        'CCPROVIN' => $direccion->alprovin,
                        'CCDISTRI' => $direccion->aldistri,
                        'CCPLNGEO' => $direccion->alplngeo,
                        'CCFILUBI' => $direccion->alfilubi,
                        'CCCOLUBI' => $direccion->alcolubi,
                        'CCCODPAI' => $direccion->alcodpai,
                        'CCCODCIU' => "",
                        'CCSTSPDO' => 'A',
                        'CCSTS' => 'A',
                        'CCUSR' => $userId,
                        'CCJOB' => $job_as,
                        'CCJDT' => date("Ymd"),
                        'CCJTM' => date("His")
                    );
                    $arrayIn = array(
                        'tabla' => 'MMCCREP',
                        'mensaje' => 'Direcciones Pedido ' . $i,
                        'otro' => json_encode($arrayInsertAs),
                        'created_at' => date("Y-m-d H:i:s")
                    );
                    $util->inserta_into_tabla('log_migraciones', $arrayIn);

                    $util->inserta_into_tabla_as400('LIBPRDDAT.MMCCREP', $arrayInsertAs);
                }
            }
            //FIN - REGISTRAR DIRECCIONES



            //REGISTRAR EN TABLA DE SALDOS PRINCIPAL - MMEIREP
            if ($objeto->formaPago === 'R' && $objeto->modalidadPago === 'FA') {
                $fecha_vencimiento = $util->sumar_restar_dias_fecha(date("Ymd"), Utilidades::ECOMMERCEDIASVENCIMIENTO, 'sumar');
                $fecha_vencimiento = $fecha_vencimiento->format('Ymd');
            } else $fecha_vencimiento = date("Ymd");
            $arrayWhere = array(
                ['EICODCIA', '=', $codCia],
                ['EICODSUC', '=', $objeto->codSucursal],
                ['EICODCLI', '=', $objeto->idCliente],
                ['EITIPDOC', '=', $objeto->tipoDocumento],
                ['EINRODOC', '=', $nuevo_pedido],
            );
            if (!$util->selecciona_fila_from_tabla_db2('LIBPRDDAT.MMEIREP', $arrayWhere)) {
                $importe_total = round(((float)$objeto->impTotal + (float) $objeto->impImpuesto), 2);
                $arrayInsert = array(
                    'EICODCIA' => $codCia,
                    'EICODSUC' => $objeto->codSucursal,
                    'EICODCLI' => $objeto->idCliente,
                    'EITIPDOC' => $tipo_documento,
                    'EINRODOC' => $nuevo_pedido,
                    'EIFECTCM' => date("Ymd"),
                    'EIFECEMI' => date("Ymd"),
                    'EIFECVCT' => $fecha_vencimiento,
                    'EICODMON' => $objeto->codMoneda,
                    'EIIMPCCC' => $importe_total,
                    'EIIMPSLD' => $importe_total,
                    'EIFRMPAG' => $objeto->formaPago,
                    'EIMODPAG' => $modalidad_pago,
                    'EICNDPAG' => $objeto->condicionPago,
                    'EICODCBR' => $objeto->codSucursal,
                    'EICODVEN' => $codUser,
                    'EINROVIS' => 0,
                    'EINROREN' => 0,
                    'EICODMTV' => '',
                    'EISTSCLT' => '',
                    'EISTSABC' => '',
                    'EISTSEXT' => '',
                    'EISTSCOA' => 'C',
                    'EISTSDOC' => 'A',
                    'EISTSRCL' => '',
                    'EISTS' => 'A',
                    'EIUSR' => $userId,
                    'EIJOB' => $job_as,
                    'EIJDT' => date("Ymd"),
                    'EIJTM' => date("His"),
                    'EIMIGSAP' => ''
                );
                $arrayIn = array(
                    'tabla' => 'MMEIREP',
                    'mensaje' => 'Saldos Principal',
                    'otro' => json_encode($arrayInsert),
                    'created_at' => date("Y-m-d H:i:s")
                );
                $util->inserta_into_tabla('log_migraciones', $arrayIn);

                $util->inserta_into_tabla_as400('LIBPRDDAT.MMEIREP', $arrayInsert);
            }
            //FIN - REGISTRAR EN TABLA DE SALDOS PRINCIPAL - MMEIREP

            //AGREGAR PERSONA RECOJO
            $arrayWhere = array(
                ['PCCODCIA', '=', $codCia],
                ['PCCODSUC', '=', $objeto->codSucursal],
                ['PCNROPED', '=', $nuevo_pedido],
            );
            if (!$util->selecciona_fila_from_tabla_db2('LIBPRDDAT.MMPCREP', $arrayWhere)) {
                $persona_recojo = trim($objeto->personarecogepedido[0]->nombres . ' ' . $objeto->personarecogepedido[0]->nroDocumento);
                $arrayInsert = array(
                    'PCCODCIA' => $codCia,
                    'PCCODSUC' => $objeto->codSucursal,
                    'PCNROPED' => $nuevo_pedido,
                    'PCTXTADI' => $persona_recojo,
                    'PCSTS' => 'A',
                    'PCUSR' => $userId,
                    'PCJOB' => $job_as,
                    'PCJDT' => date("Ymd"),
                    'PCJTM' => date("His")
                );
                $arrayIn = array(
                    'tabla' => 'MMPCREP',
                    'mensaje' => 'Persona Recojo',
                    'otro' => json_encode($arrayInsert),
                    'created_at' => date("Y-m-d H:i:s")
                );
                $util->inserta_into_tabla('log_migraciones', $arrayIn);
                $util->inserta_into_tabla_as400('LIBPRDDAT.MMPCREP', $arrayInsert);
            }
            //FIN - AGREGAR PERSONA RECOJO

            //ENCABEZADO PARTE DE SALIDA
            $nueva_id_parte_salida = $util->retorna_nuevo_numero_tabla_numeradores_mmfcrep($codCia, $objeto->codSucursal, '13');
            if ($nueva_id_parte_salida > 0) {
                $arrayInsert = array(
                    'AICODCIA' => $codCia,
                    'AICODSUC' => $objeto->codSucursal,
                    'AINROPIS' => $nueva_id_parte_salida,
                    'AICODALM' => $objeto->codAlmacen,
                    'AITIPART' => 'AR',
                    'AIMTVDIS' => 'VL',
                    'AINROOCP' => 0,
                    'AICLIPRV' => $objeto->idCliente,
                    'AICODSOL' => $codUser,
                    'AIFECDIS' => date("Ymd"),
                    'AICODTRN' => $objeto->idTransporte,
                    'AINROPLC' => '',
                    'AIDSCREF' => 'Venta Local',
                    'AIDSCOBS' => 'Pedido',
                    'AITIPDOC' => '32',
                    'AINROREF' => $nuevo_pedido,
                    'AINROBLT' => 0,
                    'AISTSDIS' => 'S',
                    'AIATNPOR' => $userId,
                    'AISTS' => 'A',
                    'AIUSR' => $userId,
                    'AIJOB' => $job_as,
                    'AIJDT' => date("Ymd"),
                    'AIJTM' => date("His"),
                    'AISTSMIG' => '',
                    'AISUCVEN' => $objeto->codSucursal
                );

                $util->inserta_into_tabla_as400('LIBPRDDAT.MMAIREP', $arrayInsert);


                $arrayUpdate = array('FCCANACT' => $nueva_id_parte_salida);
                $arrayWhere = array(
                    ['FCCODCIA', '=', $codCia],
                    ['FCCODSUC', '=', $objeto->codSucursal],
                    ['FCCODELE', '=', '13'],
                );
                $util->actualiza_tabla_numeradores_mmfcrep($arrayWhere, $arrayUpdate);
            }
            //FIN - ENCABEZADO PARTE DE SALIDA

            //DETALLE PARTE DE SALIDA
            if ($nueva_id_parte_salida > 0) {
                foreach ($objeto->pedidodetalle as $detalle) {
                    $arrayWhere = array(
                        ['AJCODCIA', '=', $codCia],
                        ['AJCODSUC', '=', $detalle->codSucursal],
                        ['AJNROPIS', '=', $nueva_id_parte_salida],
                        ['AJCODLIN', '=', $detalle->codLinea],
                        ['AJCODART', '=', $detalle->codArticulo],
                        ['AJCODORI', '=', $detalle->codOrigen],
                        ['AJCODMAR', '=', $detalle->codMarca],
                        ['AJSTS', '=', 'A']
                    );
                    if (!$util->selecciona_fila_from_tabla_db2('LIBPRDDAT.MMAJREP', $arrayWhere)) {
                        $arrayInsert = array(
                            'AJCODCIA' => $codCia,
                            'AJCODSUC' => $detalle->codSucursal,
                            'AJNROPIS' => $nueva_id_parte_salida,
                            'AJCODLIN' => $detalle->codLinea,
                            'AJCODART' => $detalle->codArticulo,
                            'AJCODORI' => $detalle->codOrigen,
                            'AJCODMAR' => $detalle->codMarca,
                            'AJUMDUSO' => 'UN',
                            'AJCANNET' => $detalle->cantidadSolicitada,
                            'AJCANGOF' => $detalle->cantidadSolicitada,
                            'AJCODALM' => '',
                            'AJCODSEC' => '',
                            'AJCODEST' => '',
                            'AJSTSDIS' => 'S',
                            'AJPRICCA' => '',
                            'AJDSCPRE' => '',
                            'AJFECDIS' => date("Ymd"),
                            'AJSTS' => 'A',
                            'AJUSR' => $userId,
                            'AJJOB' => $job_as,
                            'AJJDT' => date("Ymd"),
                            'AJJTM' => date("His")
                        );
                        $util->inserta_into_tabla_as400('LIBPRDDAT.MMAJREP', $arrayInsert);

                        $util->actualiza_inventario_producto_almacen_as400($codCia, $objeto->idCliente, $detalle);
                    }
                }
            }
            //FIN - DETALLE PARTE DE SALIDA

            //TRACKING CABECERA MMQ1REP
            if ($nueva_id_parte_salida > 0) {
                $arrayWhere = array(
                    ['Q1STS', '=', 'A'],
                    ['Q1CODCIA', '=', $codCia],
                    ['Q1CODSUC', '=', $objeto->codSucursal],
                    ['Q1NROPED', '=', $objeto->idCotizacion],
                    ['Q1NROPDC', '=', $nuevo_pedido],

                );
                if (!$util->selecciona_fila_from_tabla_db2('LIBPRDDAT.MMQ1REP', $arrayWhere)) {
                    $arrayInsert = array(
                        'Q1CODCIA' => $codCia,
                        'Q1CODSUC' => $objeto->codSucursal,
                        'Q1NROPED' => $objeto->idCotizacion,
                        'Q1NROPDC' => $nuevo_pedido,
                        'Q1NROPTE' => $nueva_id_parte_salida,
                        'Q1CODCLI' => $objeto->idCliente,
                        'Q1RAZSOC' => $objeto->razonSocial,
                        'Q1NVORUC' => $numeroIdentidad,
                        'Q1DESTIN' => $dir_entrega->aldepart,
                        'Q1CODTRN' => $objeto->idTransporte,
                        'Q1ESTAMV' => '02',
                        'Q1STS' => 'A',
                        'Q1USR' => $userId,
                        'Q1JOB' => $dir_entrega->alprovin,
                        'Q1JDT' => date("Ymd"),
                        'Q1JTM' => date("His"),
                        'Q1PGM' => $job_as,
                        'Q1MUSR' => $userId,
                        'Q1MJOB' => $job_as,
                        'Q1MJDT' => date("Ymd"),
                        'Q1MJTM' => date("His"),
                        'Q1MPGM' => $job_as
                    );
                    $util->inserta_into_tabla_as400('LIBPRDDAT.MMQ1REP', $arrayInsert);
                }
            }
            //FIN - TRACKING CABECERA MMQ1REP


            //TRACKING DETALLE MMQ0REP
            $arrayInsert = array(
                'Q0CODCIA' => $codCia,
                'Q0CODSUC' => $objeto->codSucursal,
                'Q0NROPED' => $objeto->idCotizacion,
                'Q0NROPDC' => $nuevo_pedido,
                'Q0ESTADO' => '02',
                'Q0OBSERV' => 'Pedido Generado',
                'Q0STA' => 'A',
                'Q0PGM' => $job_as,
                'Q0USU' => $userId,
                'Q0JOB' => $job_as,
                'Q0DATE' => date("Ymd"),
                'Q0HORA' => date("His")
            );

            $util->inserta_into_tabla_as400('LIBPRDDAT.MMQ0REP', $arrayInsert);
            //return false;

            //FIN - TRACKING DETALLE MMQ0REP
        }
    }
}
