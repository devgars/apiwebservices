<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Sync\Utilidades;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Stmt\Echo_;
use PhpParser\Node\Stmt\Else_;
use stdClass;

class SyncController extends Controller
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

    public function __construct()
    {
        $this->min_descarga_pagos_contado = env('min_descarga_pagos_contado', 10);
    }

    public function sincronizar_db2_con_interface()
    {
        echo '<br>sincronizar_db2_con_interface - ' . date("d-m-Y H:i:s");
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
                        die('REGISTRO NO ACTUALIZADO EN AS 400');
                    }
                }
                echo '<br>NroDoc: ' . $fila->abnrodoc . ' - F: ' . $fecha . ' - H: ' . $hora;
            }
        }
        $this->sincroniza_middleware_con_db2();
        $this->redirecciona('/sync', 27);
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

    public function registra_deposito_bancario_mmyprep($codCia, $sucursal_deposito, $codigo_banco_as, $numero_deposito, $regSaldo, $bankAccount, $pago)
    {
        $sucursal_deposito = ($sucursal_deposito === '02') ? '01' : $sucursal_deposito;
        $arrayWhere = array(
            ['YPCODCIA', '=', $codCia],
            ['YPCODSUC', '=', $sucursal_deposito],
            ['YPCODBCO', '=', $codigo_banco_as],
            ['YPNROOPR', '=', $numero_deposito],
            ['YPSTS', '=', 'A']
        );
        if (!$datos_deposito = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMYPREP', $arrayWhere)) {
            //OBTENER CORRELATIVO DE BOLETA DE DEPOSITO EN TABLA NUMERADORES (MMFCREL0)
            echo '<br>CORRELATIVO DE BOLETA DE DEPOSITO -> NUMERADORES (MMFCREL0): ';
            $correlativoBoletaDeposito = $this->getCorrelativeNumberByDocType($codCia, $sucursal_deposito, $this->codBoletaDeposito);

            //INSERTA DEPOSITO SI NO HA SIDO REGISTRADO 
            $deposito = $this->get_max_deposito_db2($codCia);
            $deposito = intval(substr($deposito, 3, 7));
            $deposito++;
            $deposito = 'D' . substr(date("Y"), 2, 2) . str_pad($deposito, 7, '0', STR_PAD_LEFT);

            //REGISTRA NUEVO DEPOSITO
            $arrayNewDep = array(
                'YPCODCIA' => $codCia,
                'YPCODSUC' => $sucursal_deposito,
                'YPDEPINT' => $deposito,
                'YPNRODEP' => $correlativoBoletaDeposito,
                'YPNROPDC' => $regSaldo->ABNRODOC,
                'YPCODBCO' => $codigo_banco_as,
                'YPNROCTA' => $bankAccount[0]->nro_cuenta,
                'YPCODMON' => $bankAccount[0]->moneda,
                'YPNROOPR' => $numero_deposito,
                'YPFRMPAG' => $regSaldo->ABFRMPAG, //C -> CONTADO, R -> CRÉDITO
                'YPTIPDOC' => $regSaldo->ABTIPDOC, //TIPO DE DOCUMENTO (PEDIDO/FACTURA/BOLETA)
                'YPCODPAI' => '001',
                'YPCODCIU' => '001',
                'YPSTS' => 'A',
                'YPSTSAPL' => 'S', //P -> PENDIENTE, N -> CONFIRMADO TESORERÍA, S -> DEPÓSITO APLICADO (Default)
                'YPFECDEP' => date("Ymd"),
                'YPIMPDEP' => $pago->totalAmount,
                'YPIMPAPL' => $pago->totalAmount, //$documentAmountPaid, //*** MONTO APLICADO = MONTO DEL DOCUMENTO PAGADO ***
                'YPCLIREF' => $regSaldo->ABCODCLI,
                'YPCODCLI' => $regSaldo->ABCODCLI,
                'YPPGM' => $this->app,
                'YPUSR' => $this->user,
                'YPJDT' =>  date("Ymd"),
                'YPJTM' => date("His"),
                'YPCODVEN' => $regSaldo->ABCODVEN,
                'YPSUCDOC' => $regSaldo->ABCODSUC
            );

            DB::connection('ibmi')->table('LIBPRDDAT.MMYPREP')->insert([$arrayNewDep]);

            return $this->selecciona_from_tabla_db2('LIBPRDDAT.MMYPREP', $arrayWhere);
        } else {
            echo '<br>REGISTRO EXISTENTE EN LA MMYPREP';
            return $datos_deposito;
        }
    }

    public function valida_tiempo_pagos_de_contado($pago, $bankCode)
    {
        $pagos_relacionados = $pago->related_debts;
        if (is_array($pagos_relacionados)) {
            foreach ($pagos_relacionados as $pago_rel) {
                $fechaTransaccion = new \Carbon\Carbon($pago->transactionDate);
                $fechaActual = new \Carbon\Carbon("now");
                $minutos_transcurridos = $fechaTransaccion->diffInMinutes($fechaActual);
                echo "<br>$pago_rel->ABFRMPAG --- $pago_rel->id --- $pago_rel->NUMERO_IDENTIFICACION --- $pago_rel->ABCODCLI ---  $fechaTransaccion --- Tiempo Transcurrido: $minutos_transcurridos";

                if ($pago_rel->ABFRMPAG === 'C' && $fechaActual >= $fechaTransaccion && $minutos_transcurridos < $this->min_descarga_pagos_contado) {
                    if ($pago->bankCode === '009' && $pago->channel !== '90') return true;
                    if ($pago->bankCode === '011' && $pago->channel !== 'TF') return true;
                    if ($pago->bankCode === '002' && $pago->channel !== 'FI') return true;

                    return false;
                } else return true;
            }
        } else {
            echo "<br>NO TIENE PAGOS RELACIONADOS";
            return false;
        }
    }

    public function sincroniza_middleware_con_db2() //PAGOS
    {
        $codCia = $this->codCia;

        echo '<br>Inicio sincroniza_middleware_con_db2  - ' . date("d-m-Y H:i:s");
        if (!$registros = $this->get_bank_payments_for_update_db2()) {
            echo '<BR>NO HAY PAGOS';
            $this->sincroniza_extornos_con_db2();
            $this->redirecciona('/sync', 35);
            exit;
        }
        echo '<br>Registros a Actualizar en AS400: ' . sizeof($registros);
        echo '<pre>';

        if ($registros && sizeof($registros) > 0) {
            $i = 0;
            foreach ($registros as $pago) {
                echo "<br>PROCESAMOS EL PAGO ID: ".$pago->id;
                /* ::: actualizamos el campo is_sync::: */
                echo "<br>PROCEDEMOS A INCREMENTAR EL CAMPO IS_SYNC";
                $this->incrementar_flag_sync('customer_payments',$pago->id);
                /* ::: Por incidencias de ejecucion doble, volvemos a validar si el pago no ha sido procesado ::*/
                if(!$this->validate_pago_sincronizado($pago->id)){
                    echo "<br>ESTE PAGO YA FUE SINCRONIZADO PAGO:".$pago->id." OPERATIONID:".$pago->operationNumber;
                    continue;
                }
                $pago->related_debts = $this->get_customer_debts_paid_by_payment_id($pago->id, $pago->customerIdentificationCode);
                $paidDocuments = json_decode($pago->paidDocuments);
                $bankCode = $pago->bankCode;
                $i += 1;
                $sincronizar = false;
                $sincronizar = $this->valida_tiempo_pagos_de_contado($pago, $bankCode);
                if ($sincronizar) {
                    echo "<BR>SINCRONIZAR PAGO - Tiempo: " . $this->min_descarga_pagos_contado;
                } else {
                    echo "<br>PAGO AÚN NO SERÁ SINCRONIZADO CON AS400... Tiempo: " . $this->min_descarga_pagos_contado;
                    continue;
                }
                if (($bankCode === '011' || $bankCode === '009') && $pago->operationNumber) $operationId = $pago->operationNumber;
                else $operationId = ($pago->operationId) ? $pago->operationId : $pago->requestId;

                $customerIdentificationCode = $pago->customerIdentificationCode;
                $channel = $pago->channel;
                $paymentType = $pago->paymentType;
                $currencyCode = ($pago->currencyCode === 'USD') ? '02' : '01';
                $bankAccount = $this->get_bank_accounts($codCia, $bankCode, $currencyCode, '01');
                /* ::: registramos el operationID en la tabla log de migraciones*/
                $arrayIn = array(
                    'tabla' => 'LIBPRDDAT.MMCJFGT',
                    'mensaje' =>'operationid = '.$operationId.' pago: '.$pago->id.' fecha:'.date("d-m-Y H:i:s"),
                    'otro' => json_encode($pago)
                );
                DB::table('log_migraciones')->insert($arrayIn);

                if ($pago->bankCode === '002' && $paidDocuments && is_array($paidDocuments) && sizeof($paidDocuments) > 1) //BCP
                {
                    echo "<br>NO ESTÁ PERMITIDO EL PAGO MULTIPLE - BCP";
                    continue;
                    $response = $this->sincronizar_pago_bcp($codCia, $pago, $bankAccount, $operationId, $i);
                    echo ('<br>FIN SINCRONIZACIÓN PAGO BCP<br>');
                } else {

                    $sumPaidDocs = 0.0;
                    if ($currencyCode === '01') {
                        if ($tipo_cambio_dolar = $this->retorna_tipo_cambio_dolar_mym('02')) {
                            $precio_dolar_mym_venta = (float) round($tipo_cambio_dolar->mym_selling_price, 2);
                        }
                    }
                    if ($paidDocuments && is_array($paidDocuments)) {
                        $registrar_deposito = 0;
                        //VALIDA TIPO DE DOCUMENTO PAGADO: CONTADO/CRÉDITO
                        $arrayNroDocumento = explode('-', $paidDocuments[0]->documentId);
                        switch (sizeof($arrayNroDocumento)) {
                            case '2': //CRÉDITO
                                $PROCESO = 3;
                                $ID_PAGO = $pago->id;
                                $registrar_deposito = 1;

                                $docNumber = $arrayNroDocumento[1];
                                $serieNumber = $arrayNroDocumento[0];
                                if (!$regSaldo = $this->retorna_doc_cliente_saldo_interface_fac($serieNumber, $docNumber)) {
                                    echo "<br>Deuda de documento no encontrada: ";
                                    die(" $serieNumber - $docNumber");
                                }
                                $docType = $regSaldo->ABTIPDOC;
                                $codSucDeposito = ($regSaldo->ABCODSUC === '01') ? '02' : $regSaldo->ABCODSUC;
                                break;

                            case '3': //CONTADO
                                $PROCESO = 4;
                                $ID_PAGO = $pago->id;
                                $registrar_deposito = 1;

                                $docNumber = $arrayNroDocumento[2]; //NRO PEDIDO EN SALDOS
                                $docType = $arrayNroDocumento[1];
                                $codSuc = $pago->related_debts[0]->ABCODSUC;
                                //OBTENER DOC DESDE TABLA DE SALDOS
                                $regSaldo = $this->retorna_doc_cliente_saldo_interface($codCia, $codSuc, $customerIdentificationCode, $docType, $docNumber);
                                $codSucDeposito = ($regSaldo->ABCODSUC === '01') ? '02' : $regSaldo->ABCODSUC;
                                break;
                        }

                        //SI DOCUMENTO ES CONTADO O CRÉDITO, REGISTRAR DEPÓSITO
                        if ($registrar_deposito == 1) {
                            $datosDeposito = $this->registra_deposito_bancario_mmyprep($codCia, $regSaldo->ABCODSUC, $bankAccount[0]->erp_code, $operationId, $regSaldo, $bankAccount, $pago);
                            $correlativoBoletaDeposito = $datosDeposito->ypnrodep;
                            echo '<br>Depósito ' . $correlativoBoletaDeposito . '  --- Fecha: ' . date("Y-m-d H:i:s");
                            $this->registra_paso_proceso(14, $PROCESO, $this->tablas['tabla_mmyprep'], $ID_PAGO);
                        }
                        //FIN - REGISTRAR DEPOSITO BANCARIO
                        foreach ($paidDocuments as $paidDoc) {
                            echo '<br>RECORREMOS EL DOCUMENTID: '.$paidDoc->documentId;
                            $documentAmountPaid = (float) round($paidDoc->amounts[0]->amount, 2);
                            $sumPaidDocs += $documentAmountPaid;
                            $arrayNroDoc = explode('-', $paidDoc->documentId);
                            if (sizeof($arrayNroDoc) == 2) //DOCUMENTO A CRÉDITO
                            {
                                echo '<br>PROCESAMOS EL DOCUMENTO:'.$arrayNroDoc[1].' - CREDITO';
                                $docNumber = $arrayNroDoc[1];
                                $serieNumber = $arrayNroDoc[0];
                                if (!$regSaldo = $this->retorna_doc_cliente_saldo_interface_fac($serieNumber, $docNumber)) {
                                    echo "<br>Deuda de documento no encontrada: ";
                                    echo "<br> DETENEMOS RECORRIDO";
                                    die(" $serieNumber - $docNumber");
                                }
                                $docType = $regSaldo->ABTIPDOC;

                                $arrayWhere = array(
                                    ['ABCODCIA', '=', $codCia],
                                    ['ABCODSUC', '=', $regSaldo->ABCODSUC],
                                    ['ABCODClI', '=', $regSaldo->ABCODCLI],
                                    ['ABTIPDOC', '=', $regSaldo->ABTIPDOC],
                                    ['ABNRODOC', '=', $regSaldo->ABNRODOC]
                                );
                                $datos_doc_db2 = $this->selecciona_from_tabla_db2($this->tabla_saldos_aux_db2, $arrayWhere);

                                $original_paid_doc_curr_code = $datos_doc_db2->abcodmon; //MONEDA ORIGINAL DE DOCUMENTO PAGADO

                                if ($original_paid_doc_curr_code === '02' && $currencyCode === '01') {
                                    $saldo_actual_USD = (float)round($datos_doc_db2->abimpsld, 2);
                                    $saldo_actual_documento_db2 = (float)round(($saldo_actual_USD * $precio_dolar_mym_venta), 2);
                                    $monto_usd_documento_pagado = (float)round(($documentAmountPaid / $precio_dolar_mym_venta), 2);
                                    $nuevo_saldo = (float) round(($saldo_actual_documento_db2 - $documentAmountPaid), 2);
                                } else {
                                    $saldo_actual_USD = (float)round($datos_doc_db2->abimpsld, 2);
                                    $saldo_actual_documento_db2 = $saldo_actual_USD;
                                    $monto_usd_documento_pagado = (float)round($documentAmountPaid, 2);
                                    $nuevo_saldo = (float) round(($saldo_actual_documento_db2 - $documentAmountPaid), 2);
                                }

                                $codSuc = ($regSaldo->ABCODSUC === '02') ? '01' : $regSaldo->ABCODSUC;
                                $codSucDeposito = '01';
                                /*
                                //PROCESA DEPÓSITO
                                $datosDeposito = $this->registra_deposito_bancario_mmyprep($codCia, $codSucDeposito, $bankAccount[0]->erp_code, $operationId, $regSaldo, $bankAccount, $pago);
                                $correlativoBoletaDeposito = $datosDeposito->ypnrodep;
                                echo '<br>Depósito ' . $correlativoBoletaDeposito;
                                //FIN PROCESA DEPÓSITO
                                */

                                //VERIFICAR/REGISTRAR PLANILLA DE COBRANZAS (CREDITO) TABLA (MMDLREP)
                                $arrayWhere = array(
                                    ['DLCODCIA', '=', $codCia],
                                    ['DLCODSUC', '=', $codSucDeposito],
                                    ['DLFECPLL', '=', date("Ymd")],
                                    ['DLSTS', '=', 'A'],
                                    ['DLSTSPLL', '=', 'A'],
                                    ['DLCODCBR', '=', $this->codCobrador]
                                );
                                if (!$regPlanillaCobranzas = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMDLREP', $arrayWhere)) {
                                    $regPlanillaCobranzas = $this->registra_planilla_cobranzas_credito($codCia, $currencyCode, $documentAmountPaid);
                                }
                                $correlativoPlanillaCobranzas = $regPlanillaCobranzas->dlnropll;
                                //FIN VERIFICAR/REGISTRAR PLANILLA DE COBRANZAS (CREDITO) TABLA (MMDLREP)

                                //VERIFICAR/REGISTRAR SI EXISTE PLANILLA DE COBRANZAS DEL DÍA
                                $arrayWhere = array(
                                    ['IECODCIA', '=', $codCia],
                                    ['IECODSUC', '=', $codSucDeposito],
                                    ['IENROPLL', '=', $correlativoPlanillaCobranzas],
                                );
                                if (!$regTipoPlanilla = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMIEREP', $arrayWhere)) {
                                    //OBTENER CORRELATIVO DE PLANILLA DE COBRANZAS EN TABLA NUMERADORES (MMFCREL0)
                                    $regTipoPlanilla = $this->registra_planilla_cobranzas_dia($codCia, $correlativoPlanillaCobranzas, $this->codCobrador, $this->tipoPlanillaCredito, $this->user, $this->app);
                                }
                                echo ('<BR>PLANILLA DE COBRANZAS DEL DÍA: ' . $correlativoPlanillaCobranzas);
                                //FIN GENERACION PLANILLA DE COBRANZAS


                                //<br> BUSQUEDA / REGISTRO DE DOCUMENTO EN TABLA MMDMREP
                                if (!$this->verifica_paso_proceso(17, $PROCESO, $this->tablas['tabla_mmdmrep'], $ID_PAGO)) {
                                    if ($this->procesa_registro_tabla_mmdmrep($codCia, $correlativoPlanillaCobranzas, $datos_doc_db2, $monto_usd_documento_pagado, $datos_doc_db2->abcodmon)) {
                                        $this->registra_paso_proceso(17, $PROCESO, $this->tablas['tabla_mmdmrep'], $ID_PAGO);
                                        echo '<br>FIN BUSQUEDA / REGISTRO DE DOCUMENTO EN TABLA MMDMREP';
                                    } else echo "<br>ATENCIÓN::: PROCESO DE BÚSQUEDA/REGISTRO DE DOCUMENTO EN TABLA MMDMREP NO REALIZADO";
                                }

                                echo '<br> BÚSQUEDA/REGISTRO DE DOCUMENTO EN TABLA MMDNREP - 18';
                                if (!$this->verifica_paso_proceso(18, $PROCESO, $this->tablas['tabla_mmdnrep'], $ID_PAGO)) {

                                    if ($this->procesa_registro_tabla_mmdnrep($codCia, $correlativoPlanillaCobranzas, $datos_doc_db2->abcodcli, $correlativoBoletaDeposito, $documentAmountPaid, $operationId, $bankAccount, $currencyCode)) {
                                        $this->registra_paso_proceso(18, $PROCESO, $this->tablas['tabla_mmdnrep'], $ID_PAGO);
                                        echo '<br>FIN BUSQUEDA / REGISTRO DE DOCUMENTO EN TABLA MMDNREP - 18';
                                    } else echo "<br>ATENCIÓN::: PROCESO DE BÚSQUEDA/REGISTRO DE DOCUMENTO EN TABLA MMDNREP NO REALIZADO";
                                }

                                echo '<br> BUSQUEDA / REGISTRO DE DOCUMENTO EN TABLA MMDOREP - 19';
                                if (!$this->verifica_paso_proceso(19, $PROCESO, $this->tablas['tabla_mmdorep'], $ID_PAGO)) {
                                    if ($this->procesa_registro_tabla_mmdorep($codCia, $correlativoPlanillaCobranzas, $datos_doc_db2, $correlativoBoletaDeposito, $monto_usd_documento_pagado, $datos_doc_db2->abcodmon)) {
                                        $this->registra_paso_proceso(19, $PROCESO, $this->tablas['tabla_mmdorep'], $ID_PAGO);
                                        echo '<br>FIN BUSQUEDA / REGISTRO DE DOCUMENTO EN TABLA MMDOREP - 19';
                                    } else echo "<br>ATENCIÓN::: PROCESO DE BÚSQUEDA/REGISTRO DE DOCUMENTO EN TABLA MMDOREP NO REALIZADO";
                                }

                                echo '<br> ACTUALIZAR TABLA DE SALDOS AUXILIAR (CCAPLBCO) - 20';
                                if (!$this->verifica_paso_proceso(20, $PROCESO, $this->tablas['tabla_saldos_aux_db2'], $ID_PAGO)) {
                                    if ($this->actualizar_tabla_saldos_auxiliar($codCia, $datos_doc_db2, $documentAmountPaid, $operationId, $bankAccount, $regSaldo->ABIMPSLD, 0)) {
                                        $this->registra_paso_proceso(20, $PROCESO, $this->tablas['tabla_saldos_aux_db2'], $ID_PAGO);
                                        echo '<br>FIN ACTUALIZAR TABLA DE SALDOS AUXILIAR (CCAPLBCO) - 20';
                                    } else {
                                        die('<br> ERROR ACTUALIZANDO TABLA DE SALDOS AUXILIAR (CCAPLBCO) - 20');
                                    }
                                }

                                echo '<br> ACTUALIZAR TABLA DE SALDOS PRINCIPAL (MMEIREP) - 21';
                                if (!$this->verifica_paso_proceso(21, $PROCESO, $this->tablas['tabla_saldos_principal'], $ID_PAGO)) {
                                    if ($this->actualizar_tabla_saldos_principal($codCia, $datos_doc_db2, $regSaldo->ABIMPSLD)) {
                                        $this->registra_paso_proceso(21, $PROCESO, $this->tablas['tabla_saldos_principal'], $ID_PAGO);
                                        echo '<br>FIN ACTUALIZAR TABLA DE SALDOS PRINCIPAL (MMEIREP) - 21';
                                    } else {
                                        die('<br> ERROR ACTUALIZANDO TABLA DE SALDOS PRINCIPAL (MMEIREP) - 21');
                                    }
                                }

                                echo '<br> REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) PADRE - 22';
                                if (!$this->verifica_paso_proceso(22, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_PAGO)) {
                                    if ($this->registra_historicos_padre($codCia, $datos_doc_db2, $regSaldo->ABIMPSLD)) {
                                        $this->registra_paso_proceso(22, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_PAGO);
                                        echo '<br>FIN REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) PADRE - 22';
                                    } else {
                                        die("<br>ATENCIÓN::: PROCESO DE REGISTRO EN TABLA HISTÓRICO DE SALDOS (MMEJREP) PADRE NO REALIZADO");
                                    }
                                }

                                echo '<br> REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) HIJO - 23';
                                if (!$this->verifica_paso_proceso(23, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_PAGO)) {
                                    if ($this->registra_tabla_historicos_hijo($codCia, $correlativoBoletaDeposito, $datos_doc_db2, $nuevo_saldo, $documentAmountPaid, $currencyCode)) {
                                        $this->registra_paso_proceso(23, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_PAGO);
                                        echo '<br>FIN REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) HIJO - 23';
                                    } else {
                                        echo "<br>ATENCIÓN::: PROCESO DE REGISTRO EN TABLA HISTÓRICO DE SALDOS (MMEJREP) HIJO NO REALIZADO";
                                    }
                                }

                                echo '<br> REGISTRAR EN TABLA DE APLICACIONES (MMELREP) - 24';
                                if ($currencyCode === '01' && $datos_doc_db2->abcodmon === '02') {
                                    $monto_pagado_padre = $monto_usd_documento_pagado;
                                    $monto_pagado_hijo = $documentAmountPaid;
                                } else {
                                    $monto_pagado_padre = $documentAmountPaid;
                                    $monto_pagado_hijo = $documentAmountPaid;
                                }

                                if (!$this->verifica_paso_proceso(24, $PROCESO, $this->tablas['tabla_aplicaciones_mmelrep'], $ID_PAGO)) {
                                    if ($this->registrar_tabla_aplicaciones($codCia, $datos_doc_db2, $correlativoBoletaDeposito, $monto_pagado_padre, $monto_pagado_hijo)) {
                                        $this->registra_paso_proceso(24, $PROCESO, $this->tablas['tabla_aplicaciones_mmelrep'], $ID_PAGO);
                                        echo '<br>FIN REGISTRAR EN TABLA DE APLICACIONES (MMELREP) - 24';
                                    } else {
                                        echo "<br>ATENCIÓN::: PROCESO DE REGISTRO EN TABLA DE APLICACIONES (MMELREP) NO REALIZADO";
                                    }
                                }

                                echo '<br> REGISTRAR EN TABLA MMCDRECA - 26';
                                if (!$this->verifica_paso_proceso(26, $PROCESO, $this->tablas['tabla_mmcdreca'], $ID_PAGO)) {
                                    if ($this->registra_tabla_mmcdreca($codCia, $bankAccount, $datos_doc_db2, $correlativoBoletaDeposito, $operationId, $correlativoPlanillaCobranzas, $pago)) {
                                        $this->registra_paso_proceso(26, $PROCESO, $this->tablas['tabla_mmcdreca'], $ID_PAGO);
                                        echo '<br>FIN REGISTRAR EN TABLA MMCDRECA - 26';
                                    } else {
                                        echo "<br>ATENCIÓN::: PROCESO DE REGISTRO EN TABLA (MMCDRECA) NO REALIZADO";
                                    }
                                }

                                echo '<br> REGISTRAR EN TABLA MMCCRECA';
                                $this->escribe_actualiza_tabla_mmccreca($codCia, $bankAccount, $pago, $correlativoPlanillaCobranzas);
                                echo '<br> FIN REGISTRAR EN TABLA MMCCRECA';
                                echo '<br>FIN DOCUMENTO:'.$arrayNroDoc[1].' - CREDITO';
                            } elseif (sizeof($arrayNroDoc) == 3) //DOCUMENTO DE CONTADO
                            {
                                echo '<br>PROCESAMOS EL DOCUMENTO:'.$arrayNroDoc[2].' - CONTADO';
                                $docNumber = $arrayNroDoc[2]; //NRO PEDIDO EN SALDOS
                                $docType = $arrayNroDoc[1];

                                $codSuc = $pago->related_debts[0]->ABCODSUC;

                                //OBTENER DOC DESDE TABLA DE SALDOS
                                $regSaldo = $this->retorna_doc_cliente_saldo_interface($codCia, $codSuc, $customerIdentificationCode, $docType, $docNumber);

                                $arrayWhere = array(
                                    ['ABCODCIA', '=', $codCia],
                                    ['ABCODSUC', '=', $regSaldo->ABCODSUC],
                                    ['ABCODClI', '=', $regSaldo->ABCODCLI],
                                    ['ABTIPDOC', '=', $regSaldo->ABTIPDOC],
                                    ['ABNRODOC', '=', $regSaldo->ABNRODOC]
                                );
                                $datos_doc_db2 = $this->selecciona_from_tabla_db2($this->tabla_saldos_aux_db2, $arrayWhere);

                                $original_paid_doc_curr_code = $datos_doc_db2->abcodmon; //MONEDA ORIGINAL DE DOCUMENTO PAGADO
                                if ($original_paid_doc_curr_code === '02') {
                                    $saldo_actual_USD = (float)round($datos_doc_db2->abimpsld, 2);
                                    if ($currencyCode === '01') {
                                        $saldo_actual_documento_db2 = (float)round(($saldo_actual_USD * $precio_dolar_mym_venta), 2);
                                        $monto_usd_documento_pagado = (float)round(($documentAmountPaid / $precio_dolar_mym_venta), 2);
                                    } else {
                                        $saldo_actual_documento_db2 = $saldo_actual_USD;
                                        $monto_usd_documento_pagado = (float)round($documentAmountPaid, 2);
                                    }
                                }


                                $codSucDeposito = ($regSaldo->ABCODSUC === '01') ? '02' : $regSaldo->ABCODSUC;

                                //OBTENER PLANILLA DEL DÍA
                                if (!$regPlanilla = $this->retorna_planilla_contado_actual_db2($codCia, $codSucDeposito, date("Ymd"))) {
                                    echo '<br>1.- NO HAY PLANILLA CREADA PARA LA FECHA ' . date("Ymd") . ' - Cia: ' . $codCia . ' - Suc: ' . $codSucDeposito;
                                    echo "<br>DETENEMOS RECORRIDO CONTADO";
                                    $this->redirecciona('/sync', 15);
                                    exit;
                                }
                                $correlativoPlanillaCobranzas = $regPlanilla->cjapnpll;
                                echo (" - PLANILLA COBRANZAS: $correlativoPlanillaCobranzas");

                                $documentAmountPaid = (float)round($documentAmountPaid, 2);
                                $nuevo_saldo = 0;

                                echo '<br> ACTUALIZAR TABLA DE SALDOS AUXILIAR (CCAPLBCO) - 20';
                                if (!$this->verifica_paso_proceso(20, $PROCESO, $this->tablas['tabla_saldos_aux_db2'], $ID_PAGO)) {
                                    if ($this->actualizar_tabla_saldos_auxiliar($codCia, $datos_doc_db2, $documentAmountPaid, $operationId, $bankAccount, $nuevo_saldo, 2)) {
                                        $this->registra_paso_proceso(20, $PROCESO, $this->tablas['tabla_saldos_aux_db2'], $ID_PAGO);
                                    } else {
                                        die('<br> ERROR ACTUALIZANDO TABLA DE SALDOS AUXILIAR (CCAPLBCO) - 20');
                                    }
                                }
                                echo '<br>FIN ACTUALIZAR TABLA DE SALDOS AUXILIAR (CCAPLBCO) - 20';

                                echo '<br> ACTUALIZAR TABLA DE SALDOS PRINCIPAL (MMEIREP) - 21';
                                if (!$this->verifica_paso_proceso(21, $PROCESO, $this->tablas['tabla_saldos_principal'], $ID_PAGO)) {
                                    $this->actualizar_tabla_saldos_principal($codCia, $datos_doc_db2, $nuevo_saldo);
                                    $this->registra_paso_proceso(21, $PROCESO, $this->tablas['tabla_saldos_principal'], $ID_PAGO);
                                }
                                echo '<br>FIN ACTUALIZAR TABLA DE SALDOS PRINCIPAL (MMEIREP) - 21';

                                echo '<br> REGISTRA/ACTUALIZA ENCABEZADO DE PAGOS - MMCJFCB - 27';
                                if (!$this->verifica_paso_proceso(27, $PROCESO, $this->tablas['tabla_mmdorep'], $ID_PAGO)) {
                                    if ($this->registra_actualiza_encabezado_pagos_contado_mmcjfcb($codCia, $codSuc, $docNumber, $datos_doc_db2, $regPlanilla)) {
                                        $this->registra_paso_proceso(27, $PROCESO, $this->tablas['tabla_mmdorep'], $ID_PAGO);
                                        echo '<br>FIN REGISTRA/ACTUALIZA ENCABEZADO DE PAGOS - MMCJFCB - 27';
                                    } else {
                                        die('<br>ERROR REGISTRANDO/ACTUALIZANDO ENCABEZADO DE PAGOS - MMCJFCB - 27');
                                    }
                                }

                                echo '<br> REGISTRA DETALLE DE PAGOS - MMCJFDT - 28';
                                if (!$this->verifica_paso_proceso(28, $PROCESO, $this->tablas['tabla_mmdorep'], $ID_PAGO)) {
                                    if ($this->registra_detalle_pagos_contado_mmcjfdt($codCia, $codSuc, $docNumber, $regPlanilla, $correlativoBoletaDeposito, $operationId, $bankAccount, $currencyCode, $datos_doc_db2, $documentAmountPaid)) {
                                        $this->registra_paso_proceso(28, $PROCESO, $this->tablas['tabla_mmdorep'], $ID_PAGO);
                                        echo '<br>FIN REGISTRA DETALLE DE PAGOS - MMCJFDT - 28';
                                    } else {
                                        die('<br>ERROR REGISTRANDO/ACTUALIZANDO DETALLE DE PAGOS - MMCJFDT - 28');
                                    }
                                }

                                echo '<br> REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) PADRE - 22';
                                if (!$this->verifica_paso_proceso(22, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_PAGO)) {
                                    if ($this->registra_historicos_padre($codCia, $datos_doc_db2, $regSaldo->ABIMPSLD)) {
                                        $this->registra_paso_proceso(22, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_PAGO);
                                        echo '<br>FIN REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) PADRE - 22';
                                    } else {
                                        echo "<br>ATENCIÓN::: PROCESO DE REGISTRO EN TABLA HISTÓRICO DE SALDOS (MMEJREP) PADRE NO REALIZADO";
                                        exit;
                                    }
                                }

                                echo '<br> REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) HIJO - 23';
                                if (!$this->verifica_paso_proceso(23, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_PAGO)) {
                                    if ($this->registra_tabla_historicos_hijo($codCia, $correlativoBoletaDeposito, $datos_doc_db2, $nuevo_saldo, $documentAmountPaid, $currencyCode)) {
                                        $this->registra_paso_proceso(23, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_PAGO);
                                        echo '<br>FIN REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) HIJO - 23';
                                    } else {
                                        echo "<br>ATENCIÓN::: PROCESO DE REGISTRO EN TABLA HISTÓRICO DE SALDOS (MMEJREP) HIJO NO REALIZADO";
                                        exit;
                                    }
                                }

                                echo '<br> REGISTRAR EN TABLA DE APLICACIONES (MMELREP) - 24';
                                if ($currencyCode === '01' && $datos_doc_db2->abcodmon === '02') {
                                    $monto_pagado_padre = $monto_usd_documento_pagado;
                                    $monto_pagado_hijo = $documentAmountPaid;
                                } else {
                                    $monto_pagado_padre = $documentAmountPaid;
                                    $monto_pagado_hijo = $documentAmountPaid;
                                }

                                if (!$this->verifica_paso_proceso(24, $PROCESO, $this->tablas['tabla_aplicaciones_mmelrep'], $ID_PAGO)) {
                                    if ($this->registrar_tabla_aplicaciones($codCia, $datos_doc_db2, $correlativoBoletaDeposito, $monto_pagado_padre, $monto_pagado_hijo)) {
                                        $this->registra_paso_proceso(24, $PROCESO, $this->tablas['tabla_aplicaciones_mmelrep'], $ID_PAGO);
                                        echo '<br>FIN REGISTRAR EN TABLA DE APLICACIONES (MMELREP) - 24';
                                    } else {
                                        die("<br>ATENCIÓN::: PROCESO DE REGISTRO EN TABLA DE APLICACIONES (MMELREP) NO REALIZADO");
                                    }
                                }

                                //REGISTRAR PAGOS PARCIALES EN TABLAS MMEJREP Y MMELREP
                                $arrayWhere = array(
                                    ['CJDTCCIA', '=', $codCia],
                                    ['CJDTSERC', '=', $regSaldo->ABCODSUC],
                                    ['CJDTNPDC', '=', $regSaldo->ABNRODOC],
                                    ['CJDTNPLL', '=', $regPlanilla->cjapnpll],
                                );
                                $pagos_parciales = $this->selecciona_all_from_tabla_db2('LIBPRDDAT.MMCJFDT', $arrayWhere);
                                if ($pagos_parciales && is_array($pagos_parciales)) {
                                    if (sizeof($pagos_parciales) > 1) {
                                        if (!$this->verifica_paso_proceso(29, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_PAGO)) {
                                            if ($this->registrar_pagos_parciales_en_mmejrep($codCia, $datos_doc_db2, $pagos_parciales)) {
                                                $this->registra_paso_proceso(29, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_PAGO);
                                                echo '<br>FIN REGISTRAR PAGOS PARCIALES EN TABLA DE HISTÓRICO DE SALDOS (MMEJREP) HIJO - 29';
                                            } else {
                                                die("<br>ATENCIÓN::: PROCESO DE PAGOS PARCIALES EN TABLA DE HISTÓRICO DE SALDOS (MMEJREP) HIJO NO REALIZADO");
                                            }
                                        }

                                        if (!$this->verifica_paso_proceso(30, $PROCESO, $this->tablas['tabla_aplicaciones_mmelrep'], $ID_PAGO)) {
                                            if ($this->registrar_pagos_parciales_en_mmelrep($codCia, $datos_doc_db2, $pagos_parciales)) {
                                                $this->registra_paso_proceso(30, $PROCESO, $this->tablas['tabla_aplicaciones_mmelrep'], $ID_PAGO);
                                                echo '<br>FIN REGISTRAR PAGOS PARCIALES EN TABLA DE APLICACIONES (MMELREP) - 30';
                                            } else {
                                                die("<br>ATENCIÓN::: PROCESO DE PAGOS PARCIALES EN TABLA DE APLICACIONES (MMELREP) NO REALIZADO");
                                            }
                                        }
                                    }
                                }
                                //FIN - REGISTRAR PAGOS PARCIALES EN TABLAS MMEJREP Y MMELREP


                                echo '<br> REGISTRAR EN TABLA MMCDRECA - 26';
                                if (!$this->verifica_paso_proceso(26, $PROCESO, $this->tablas['tabla_mmcdreca'], $ID_PAGO)) {
                                    if ($this->registra_tabla_mmcdreca($codCia, $bankAccount, $datos_doc_db2, $correlativoBoletaDeposito, $operationId, $correlativoPlanillaCobranzas, $pago)) {
                                        $this->registra_paso_proceso(26, $PROCESO, $this->tablas['tabla_mmcdreca'], $ID_PAGO);
                                        echo '<br>FIN REGISTRAR EN TABLA MMCDRECA - 26';
                                    } else {
                                        die("<br>ATENCIÓN::: PROCESO DE REGISTRO EN TABLA (MMCDRECA) NO REALIZADO");
                                    }
                                }

                                echo '<br> REGISTRAR EN TABLA MMCCRECA';
                                $this->escribe_actualiza_tabla_mmccreca($codCia, $bankAccount, $pago, $correlativoPlanillaCobranzas);
                                echo '<br> FIN REGISTRAR EN TABLA MMCCRECA';
                                echo '<br>FIN DOCUMENTO: - CONTADO';
                            } else {
                                $totalAmountPaid = (float) round($pago->totalAmount, 2);
                                $vector = explode('_', $paidDocuments[0]->documentId);
                                switch ($vector[0]) {
                                    case 'DA':
                                        echo "<br>DOCUMENTO COMPUESTO: " . $paidDocuments[0]->documentId;
                                        $numero_grupo = $vector[1];
                                        $docType = 'DA';
                                        //OBTENER DOC DESDE TABLA DE SALDOS
                                        $regSaldoCompuesto = $this->retorna_doc_cliente_saldo_interface($codCia, '', $customerIdentificationCode, $docType, $numero_grupo);
                                        $docType = $vector[0];
                                        //PROCESA DEPÓSITO
                                        $datosDeposito = $this->registra_deposito_bancario_mmyprep($codCia, '01', $bankAccount[0]->erp_code, $operationId, $regSaldoCompuesto, $bankAccount, $pago);
                                        $correlativoBoletaDeposito = $datosDeposito->ypnrodep;
                                        echo '<br>Depósito ' . $correlativoBoletaDeposito;
                                        //FIN PROCESA DEPÓSITO
                                        //LEER TABLA DE DOCUMENTOS COMPUESTOS - CABECERA
                                        $arrayWhereGC = array(
                                            ['DACCIA', '=', $codCia],
                                            ['DACTIP', '=', 'DA'],
                                            ['DACGRU', '=', $numero_grupo]
                                        );
                                        $datos_grupo = $this->selecciona_from_tabla_db2($this->tablas['tabla_doc_comp_cab'], $arrayWhereGC);

                                        //LEER TABLA DE DOCUMENTOS COMPUESTOS - DETALLE
                                        $arrayWhereGD = array(
                                            ['DADCIA', '=', $codCia],
                                            ['DADTIP', '=', 'DA'],
                                            ['DADGRU', '=', $numero_grupo]
                                        );
                                        $datos_documentos = $this->selecciona_all_from_tabla_db2($this->tablas['tabla_doc_comp_det'], $arrayWhereGD);

                                        $original_paid_doc_curr_code = $datos_grupo->dacmon;

                                        if ($original_paid_doc_curr_code === '02' && $currencyCode === '01') {
                                            $total_monto_pagado_USD = (float)round(($totalAmountPaid / $precio_dolar_mym_venta), 2);
                                            $total_saldo_USD = (float)round(($datos_grupo->dacimp), 2);
                                        } else {
                                            $total_monto_pagado_USD = (float)round(($totalAmountPaid), 2);
                                            $total_saldo_USD = (float)round(($datos_grupo->dacimp), 2);
                                        }

                                        echo "<br>DEPOSITO: $totalAmountPaid | SA USD: $total_saldo_USD - DEPOSITO USD: $total_monto_pagado_USD";
                                        //VALIDAR QUE EL MONTO DEL DEPÓSITO COINCIDA CON LA SUMA DEL SALDO DE TODOS LOS DOCUMENTOS
                                        if (!$this->validar_monto_deposito_documento_compuesto($datos_documentos, $total_monto_pagado_USD)) {
                                            echo "<br>DETENEMOS PROCESO DA";
                                            die("<br>MONTO DE DEPÓSITO NO COINCIDE CON SUMA TOTAL DE SALDOS DE DOCUMENTOS");
                                        }
                                        //FIN VALIDAR QUE EL MONTO DEL DEPÓSITO COINCIDA CON LA SUMA DEL SALDO DE TODOS LOS DOCUMENTOS

                                        $codigo_cliente = $datos_grupo->daccli;
                                        //$currencyCode = $datos_grupo->dacmon;
                                        echo "<br>RECORREMOS LA TABLA : LIBPRDDAT.CCDOCAGD";
                                        foreach ($datos_documentos as $documento) {
                                            echo "<br>CCDOCAGD DOCUMENTO : ". $documento->dadndo;
                                            $arrayWhere = array(
                                                ['ABCODCIA', '=', $codCia],
                                                ['ABCODSUC', '=', $documento->dadsuc],
                                                ['ABCODCli', '=', $codigo_cliente],
                                                ['ABTIPDOC', '=', $documento->dadtdo],
                                                ['ABNRODOC', '=', $documento->dadndo]
                                            );
                                            if ($datos_doc_db2 = $this->selecciona_from_tabla_db2($this->tabla_saldos_aux_db2, $arrayWhere)) {
                                                $tipo_moneda_documento = trim($datos_doc_db2->abcodmon);
                                                if ($currencyCode === '01' && $tipo_moneda_documento === '02') {
                                                    $saldo_documento_USD = (float)round($datos_doc_db2->abimpsld, 2);
                                                    $saldo_actual_documento = (float)round(($saldo_documento_USD * $precio_dolar_mym_venta), 2);
                                                } else {
                                                    $saldo_documento_USD = (float)round($datos_doc_db2->abimpsld, 2);
                                                    $saldo_actual_documento = $saldo_documento_USD;
                                                }

                                                if ($datos_doc_db2->abfrmpag == 'R') {
                                                    //DOCUMENTO A CRÉDITO
                                                    echo "<br>DOCUMENTO AGRUPADO A CRÉDITO";
                                                    $PROCESO = 3;

                                                    if (!$datos_deuda = $this->retorna_datos_deuda_cliente_saldos($datos_doc_db2)) {
                                                        echo "<br>Deuda de documento no encontrada";
                                                        die(print_r($datos_doc_db2));
                                                    } else $ID_DEUDA = $datos_deuda->id;

                                                    $codSuc = ($documento->dadsuc === '02') ? '01' : $documento->dadsuc;
                                                    $codSucDeposito = '01';
                                                    //VERIFICAR/REGISTRAR PLANILLA DE COBRANZAS (CREDITO) TABLA (MMDLREP)
                                                    $arrayWhere = array(
                                                        ['DLCODCIA', '=', $codCia],
                                                        ['DLCODSUC', '=', $codSucDeposito],
                                                        ['DLFECPLL', '=', date("Ymd")],
                                                        ['DLSTS', '=', 'A'],
                                                        ['DLSTSPLL', '=', 'A'],
                                                        ['DLCODCBR', '=', $this->codCobrador]
                                                    );
                                                    if (!$regPlanillaCobranzas = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMDLREP', $arrayWhere)) {
                                                        $regPlanillaCobranzas = $this->registra_planilla_cobranzas_credito($codCia, $currencyCode, $totalAmountPaid);
                                                    }
                                                    $correlativoPlanillaCobranzas = $regPlanillaCobranzas->dlnropll;
                                                    echo ("<br>OK -> Correlativo: $correlativoPlanillaCobranzas");
                                                    //FIN VERIFICAR/REGISTRAR PLANILLA DE COBRANZAS (CREDITO) TABLA (MMDLREP)

                                                    //VERIFICAR/REGISTRAR SI EXISTE PLANILLA DE COBRANZAS DEL DÍA
                                                    $arrayWhere = array(
                                                        ['IECODCIA', '=', $codCia],
                                                        ['IECODSUC', '=', $codSucDeposito],
                                                        ['IENROPLL', '=', $correlativoPlanillaCobranzas],
                                                    );
                                                    if (!$regTipoPlanilla = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMIEREP', $arrayWhere)) {
                                                        //OBTENER CORRELATIVO DE PLANILLA DE COBRANZAS EN TABLA NUMERADORES (MMFCREL0)
                                                        $regTipoPlanilla = $this->registra_planilla_cobranzas_dia($codCia, $correlativoPlanillaCobranzas, $this->codCobrador, $this->tipoPlanillaCredito, $this->user, $this->app);
                                                    }
                                                    echo ('<BR>PLANILLA DE COBRANZAS DEL DÍA: ' . $correlativoPlanillaCobranzas);
                                                    //FIN GENERACION PLANILLA DE COBRANZAS

                                                    //$documentAmountPaid = (float) round($datos_doc_db2->abimpsld, 2);

                                                    //<br> BUSQUEDA / REGISTRO DE DOCUMENTO EN TABLA MMDMREP
                                                    if (!$this->verifica_paso_proceso(17, $PROCESO, $this->tablas['tabla_mmdmrep'], $ID_DEUDA)) {
                                                        if ($this->procesa_registro_tabla_mmdmrep($codCia, $correlativoPlanillaCobranzas, $datos_doc_db2, $datos_doc_db2->abimpsld, $datos_doc_db2->abcodmon)) {
                                                            $this->registra_paso_proceso(17, $PROCESO, $this->tablas['tabla_mmdmrep'], $ID_DEUDA);
                                                            echo '<br>FIN BUSQUEDA / REGISTRO DE DOCUMENTO EN TABLA MMDMREP';
                                                        }
                                                    }

                                                    echo '<br> BUSQUEDA / REGISTRO DE DOCUMENTO EN TABLA MMDNREP - 18';
                                                    if (!$this->verifica_paso_proceso(18, $PROCESO, $this->tablas['tabla_mmdnrep'], $ID_DEUDA)) {
                                                        if ($this->procesa_registro_tabla_mmdnrep($codCia, $correlativoPlanillaCobranzas, $datos_doc_db2->abcodcli, $correlativoBoletaDeposito, $totalAmountPaid, $operationId, $bankAccount, $currencyCode)) {
                                                            $this->registra_paso_proceso(18, $PROCESO, $this->tablas['tabla_mmdnrep'], $ID_DEUDA);
                                                            echo '<br>FIN BUSQUEDA / REGISTRO DE DOCUMENTO EN TABLA MMDNREP - 18';
                                                        }
                                                    }

                                                    echo '<br> BUSQUEDA / REGISTRO DE DOCUMENTO EN TABLA MMDOREP - 19';
                                                    if (!$this->verifica_paso_proceso(19, $PROCESO, $this->tablas['tabla_mmdorep'], $ID_DEUDA)) {
                                                        if ($this->procesa_registro_tabla_mmdorep($codCia, $correlativoPlanillaCobranzas, $datos_doc_db2, $correlativoBoletaDeposito, $datos_doc_db2->abimpsld, $datos_doc_db2->abcodmon)) { //IMPORTE ORIGINAL
                                                            $this->registra_paso_proceso(19, $PROCESO, $this->tablas['tabla_mmdorep'], $ID_DEUDA);
                                                            echo '<br>FIN BUSQUEDA / REGISTRO DE DOCUMENTO EN TABLA MMDOREP - 19';
                                                        }
                                                    }

                                                    //$documentAmountPaid = (float)round($documentAmountPaid, 2);
                                                    //$saldo_actual = (float)round($datos_doc_db2->abimpsld, 2);
                                                    //$nuevo_saldo = (float)round(($saldo_actual - $documentAmountPaid), 2);
                                                    $nuevo_saldo = 0;
                                                    //die("<br>Pago: $documentAmountPaid - SA: $saldo_actual - NS: $nuevo_saldo");

                                                    echo '<br> ACTUALIZAR TABLA DE SALDOS AUXILIAR (CCAPLBCO) - 20';
                                                    if (!$this->verifica_paso_proceso(20, $PROCESO, $this->tablas['tabla_saldos_aux_db2'], $ID_DEUDA)) {
                                                        if ($this->actualizar_tabla_saldos_auxiliar($codCia, $datos_doc_db2, $totalAmountPaid, $operationId, $bankAccount, $nuevo_saldo, 0)) {
                                                            $this->registra_paso_proceso(20, $PROCESO, $this->tablas['tabla_saldos_aux_db2'], $ID_DEUDA);
                                                            echo '<br>FIN ACTUALIZAR TABLA DE SALDOS AUXILIAR (CCAPLBCO) - 20';
                                                        } else {
                                                            die('<br> ERROR ACTUALIZANDO TABLA DE SALDOS AUXILIAR (CCAPLBCO) - 20');
                                                        }
                                                    }

                                                    echo '<br> ACTUALIZAR TABLA DE SALDOS PRINCIPAL (MMEIREP) - 21';
                                                    if (!$this->verifica_paso_proceso(21, $PROCESO, $this->tablas['tabla_saldos_principal'], $ID_DEUDA)) {
                                                        if ($this->actualizar_tabla_saldos_principal($codCia, $datos_doc_db2, $nuevo_saldo)) {
                                                            $this->registra_paso_proceso(21, $PROCESO, $this->tablas['tabla_saldos_principal'], $ID_DEUDA);
                                                            echo '<br>FIN ACTUALIZAR TABLA DE SALDOS PRINCIPAL (MMEIREP) - 21';
                                                        } else {
                                                            die('<br> ERROR ACTUALIZANDO TABLA DE SALDOS PRINCIPAL (MMEIREP) - 21');
                                                        }
                                                    }

                                                    echo '<br> REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) PADRE - 22';
                                                    if (!$this->verifica_paso_proceso(22, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_DEUDA)) {
                                                        if ($this->registra_historicos_padre($codCia, $datos_doc_db2, $nuevo_saldo)) {
                                                            $this->registra_paso_proceso(22, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_DEUDA);
                                                            echo '<br>FIN REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) PADRE - 22';
                                                        } else {
                                                            die("<br>ATENCIÓN::: PROCESO DE REGISTRO EN TABLA HISTÓRICO DE SALDOS (MMEJREP) PADRE NO REALIZADO");
                                                        }
                                                    }

                                                    echo '<br> REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) HIJO - 23';
                                                    if (!$this->verifica_paso_proceso(23, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_DEUDA)) {
                                                        if ($this->registra_tabla_historicos_hijo($codCia, $correlativoBoletaDeposito, $datos_doc_db2, $nuevo_saldo, $saldo_actual_documento, $currencyCode)) {
                                                            $this->registra_paso_proceso(23, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_DEUDA);
                                                            echo '<br>FIN REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) HIJO - 23';
                                                        } else {
                                                            die("<br>ATENCIÓN::: PROCESO DE REGISTRO EN TABLA HISTÓRICO DE SALDOS (MMEJREP) HIJO NO REALIZADO");
                                                        }
                                                    }

                                                    echo '<br> REGISTRAR EN TABLA DE APLICACIONES (MMELREP) - 24';
                                                    if (!$this->verifica_paso_proceso(24, $PROCESO, $this->tablas['tabla_aplicaciones_mmelrep'], $ID_DEUDA)) {
                                                        if ($this->registrar_tabla_aplicaciones($codCia, $datos_doc_db2, $correlativoBoletaDeposito, $datos_doc_db2->abimpsld, $saldo_actual_documento)) {
                                                            $this->registra_paso_proceso(24, $PROCESO, $this->tablas['tabla_aplicaciones_mmelrep'], $ID_DEUDA);
                                                            echo '<br>FIN REGISTRAR EN TABLA DE APLICACIONES (MMELREP) - 24';
                                                        } else {
                                                            echo "<br>ATENCIÓN::: PROCESO DE REGISTRO EN TABLA DE APLICACIONES (MMELREP) NO REALIZADO";
                                                        }
                                                    }

                                                    echo '<br> REGISTRAR EN TABLA MMCDRECA - 26';
                                                    if (!$this->verifica_paso_proceso(26, $PROCESO, $this->tablas['tabla_mmcdreca'], $ID_DEUDA)) {
                                                        if ($this->registra_tabla_mmcdreca($codCia, $bankAccount, $datos_doc_db2, $correlativoBoletaDeposito, $operationId, $correlativoPlanillaCobranzas, $pago)) {
                                                            $this->registra_paso_proceso(26, $PROCESO, $this->tablas['tabla_mmcdreca'], $ID_DEUDA);
                                                            echo '<br>FIN REGISTRAR EN TABLA MMCDRECA - 26';
                                                        } else {
                                                            echo "<br>ATENCIÓN::: PROCESO DE REGISTRO EN TABLA (MMCDRECA) NO REALIZADO";
                                                        }
                                                    }

                                                    echo '<br> REGISTRAR EN TABLA MMCCRECA';
                                                    $this->escribe_actualiza_tabla_mmccreca($codCia, $bankAccount, $pago, $correlativoPlanillaCobranzas);
                                                    //$this->actualiza_saldos_mmccreca($codCia, $bankAccount[0]->nro_cuenta, date("Ymd"));
                                                    echo '<br> FIN REGISTRAR EN TABLA MMCCRECA';
                                                    echo "<br>FIN DOCUMENTO AGRUPADO A CRÉDITO";

                                                    // *** FIN PAGO DOCUMENTO GRUPAL A CRÉDITO *** //
                                                } else {
                                                    // *** DOCUMENTO DE CONTADO ***  //
                                                    echo "<br>DOCUMENTO AGRUPADO CONTADO";
                                                    $PROCESO = 4;

                                                    if (!$datos_deuda = $this->retorna_datos_deuda_cliente_saldos($datos_doc_db2)) {
                                                        echo "<br>Deuda de documento no encontrada";
                                                        echo '<pre>';
                                                        die(print_r($datos_doc_db2));
                                                    } else $ID_DEUDA = $datos_deuda->id;

                                                    $codSuc = $datos_doc_db2->abcodsuc;
                                                    $docNumber = $datos_doc_db2->abnrodoc; //NRO PEDIDO EN SALDOS
                                                    $docType = $datos_doc_db2->abtipdoc;

                                                    //OBTENER PLANILLA DEL DÍA
                                                    if (!$regPlanilla = $this->retorna_planilla_contado_actual_db2($codCia, $codSuc, date("Ymd"))) {
                                                        echo '<br>2.- NO HAY PLANILLA CREADA PARA LA FECHA ' . date("Ymd") . ' - Cia: ' . $codCia . ' - Suc: ' . $codSuc;
                                                        $this->redirecciona('/sync', 15);
                                                        exit;
                                                    }

                                                    $nuevo_saldo = 0;

                                                    echo '<br> ACTUALIZAR TABLA DE SALDOS AUXILIAR (CCAPLBCO) - 20';
                                                    if (!$this->verifica_paso_proceso(20, $PROCESO, $this->tablas['tabla_saldos_aux_db2'], $ID_DEUDA)) {
                                                        if ($this->actualizar_tabla_saldos_auxiliar($codCia, $datos_doc_db2, $totalAmountPaid, $operationId, $bankAccount, $nuevo_saldo, 2)) {
                                                            $this->registra_paso_proceso(20, $PROCESO, $this->tablas['tabla_saldos_aux_db2'], $ID_DEUDA);
                                                        } else {
                                                            die('<br> ERROR ACTUALIZANDO TABLA DE SALDOS AUXILIAR (CCAPLBCO) - 20');
                                                        }
                                                    }
                                                    echo '<br>FIN ACTUALIZAR TABLA DE SALDOS AUXILIAR (CCAPLBCO) - 20';

                                                    echo '<br> ACTUALIZAR TABLA DE SALDOS PRINCIPAL (MMEIREP) - 21';
                                                    if (!$this->verifica_paso_proceso(21, $PROCESO, $this->tablas['tabla_saldos_principal'], $ID_DEUDA)) {
                                                        $this->actualizar_tabla_saldos_principal($codCia, $datos_doc_db2, $nuevo_saldo);
                                                        $this->registra_paso_proceso(21, $PROCESO, $this->tablas['tabla_saldos_principal'], $ID_DEUDA);
                                                    }
                                                    echo '<br>FIN ACTUALIZAR TABLA DE SALDOS PRINCIPAL (MMEIREP) - 21';

                                                    echo '<br> REGISTRA/ACTUALIZA ENCABEZADO DE PAGOS - MMCJFCB - 27';
                                                    if (!$this->verifica_paso_proceso(27, $PROCESO, $this->tablas['tabla_mmdorep'], $ID_DEUDA)) {
                                                        if ($this->registra_actualiza_encabezado_pagos_contado_mmcjfcb($codCia, $codSuc, $docNumber, $datos_doc_db2, $regPlanilla)) {
                                                            $this->registra_paso_proceso(27, $PROCESO, $this->tablas['tabla_mmdorep'], $ID_DEUDA);
                                                        } else {
                                                            die('<br>ERROR REGISTRANDO/ACTUALIZANDO ENCABEZADO DE PAGOS - MMCJFCB - 27');
                                                        }
                                                    }
                                                    echo '<br>FIN REGISTRA/ACTUALIZA ENCABEZADO DE PAGOS - MMCJFCB - 27';

                                                    echo '<br> REGISTRA DETALLE DE PAGOS - MMCJFDT - 28';
                                                    if (!$this->verifica_paso_proceso(28, $PROCESO, $this->tablas['tabla_mmdorep'], $ID_DEUDA)) {
                                                        $this->registra_detalle_pagos_contado_mmcjfdt($codCia, $codSuc, $docNumber, $regPlanilla, $correlativoBoletaDeposito, $operationId, $bankAccount, $currencyCode, $datos_doc_db2, $saldo_actual_documento);
                                                        $this->registra_paso_proceso(28, $PROCESO, $this->tablas['tabla_mmdorep'], $ID_DEUDA);
                                                    }
                                                    echo '<br>FIN REGISTRA DETALLE ENCABEZADO DE PAGOS - MMCJFDT - 28';

                                                    echo '<br> REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) PADRE - 22';
                                                    if (!$this->verifica_paso_proceso(22, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_DEUDA)) {
                                                        $this->registra_historicos_padre($codCia, $datos_doc_db2, $nuevo_saldo);
                                                        $this->registra_paso_proceso(22, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_DEUDA);
                                                    }
                                                    echo '<br>FIN REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) PADRE - 22';


                                                    echo '<br> REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) HIJO - 23';
                                                    if (!$this->verifica_paso_proceso(23, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_DEUDA)) {
                                                        $this->registra_tabla_historicos_hijo($codCia, $correlativoBoletaDeposito, $datos_doc_db2, $nuevo_saldo, $saldo_actual_documento, $currencyCode);
                                                        $this->registra_paso_proceso(23, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_DEUDA);
                                                    }
                                                    echo '<br>FIN REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) HIJO - 23';


                                                    echo '<br> REGISTRAR EN TABLA DE APLICACIONES (MMELREP) - 24';
                                                    if (!$this->verifica_paso_proceso(24, $PROCESO, $this->tablas['tabla_aplicaciones_mmelrep'], $ID_DEUDA)) {
                                                        $this->registrar_tabla_aplicaciones($codCia, $datos_doc_db2, $correlativoBoletaDeposito, $datos_doc_db2->abimpsld, $saldo_actual_documento);
                                                        $this->registra_paso_proceso(24, $PROCESO, $this->tablas['tabla_aplicaciones_mmelrep'], $ID_DEUDA);
                                                    }
                                                    echo '<br>FIN REGISTRAR EN TABLA DE APLICACIONES (MMELREP) - 24';


                                                    //REGISTRAR PAGOS PARCIALES EN TABLAS MMEJREP Y MMELREP
                                                    $arrayWhere = array(
                                                        ['CJDTCCIA', '=', $codCia],
                                                        ['CJDTSERC', '=', $datos_doc_db2->abcodsuc],
                                                        ['CJDTNPDC', '=', $datos_doc_db2->abnrodoc],
                                                        ['CJDTNPLL', '=', $regPlanilla->cjapnpll],
                                                    );
                                                    $pagos_parciales = $this->selecciona_all_from_tabla_db2('LIBPRDDAT.MMCJFDT', $arrayWhere);
                                                    if ($pagos_parciales && is_array($pagos_parciales)) {
                                                        if (sizeof($pagos_parciales) > 1) {

                                                            if (!$this->verifica_paso_proceso(29, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_DEUDA)) {
                                                                if ($this->registrar_pagos_parciales_en_mmejrep($codCia, $datos_doc_db2, $pagos_parciales)) {
                                                                    $this->registra_paso_proceso(29, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_DEUDA);
                                                                    echo '<br>FIN REGISTRAR PAGOS PARCIALES EN TABLA DE HISTÓRICO DE SALDOS (MMEJREP) HIJO - 29';
                                                                } else {
                                                                    die("<br>ATENCIÓN::: PROCESO DE PAGOS PARCIALES EN TABLA DE HISTÓRICO DE SALDOS (MMEJREP) HIJO NO REALIZADO");
                                                                }
                                                            }

                                                            if (!$this->verifica_paso_proceso(30, $PROCESO, $this->tablas['tabla_aplicaciones_mmelrep'], $ID_DEUDA)) {
                                                                if ($this->registrar_pagos_parciales_en_mmelrep($codCia, $datos_doc_db2, $pagos_parciales)) {
                                                                    $this->registra_paso_proceso(30, $PROCESO, $this->tablas['tabla_aplicaciones_mmelrep'], $ID_DEUDA);
                                                                    echo '<br>FIN REGISTRAR PAGOS PARCIALES EN TABLA DE APLICACIONES (MMELREP) - 30';
                                                                } else {
                                                                    die("<br>ATENCIÓN::: PROCESO DE PAGOS PARCIALES EN TABLA DE APLICACIONES (MMELREP) NO REALIZADO");
                                                                }
                                                            }
                                                        }
                                                    }
                                                    //FIN - REGISTRAR PAGOS PARCIALES EN TABLAS MMEJREP Y MMELREP


                                                    echo '<br> REGISTRAR EN TABLA MMCDRECA - 26';
                                                    if (!$this->verifica_paso_proceso(26, $PROCESO, $this->tablas['tabla_mmcdreca'], $ID_DEUDA)) {
                                                        $this->registra_tabla_mmcdreca($codCia, $bankAccount, $datos_doc_db2, $correlativoBoletaDeposito, $operationId, $regPlanilla->cjapnpll, $pago);
                                                        $this->registra_paso_proceso(26, $PROCESO, $this->tablas['tabla_mmcdreca'], $ID_DEUDA);
                                                    }
                                                    echo '<br>FIN REGISTRAR EN TABLA MMCDRECA - 26';

                                                    echo '<br> REGISTRAR EN TABLA MMCCRECA';
                                                    $this->escribe_actualiza_tabla_mmccreca($codCia, $bankAccount, $pago, $regPlanilla->cjapnpll);
                                                    echo '<br>FIN REGISTRAR EN TABLA MMCCRECA';
                                                    // *** FIN PAGO DOCUMENTO DE CONTADO *** //
                                                    echo "<br>FIN DOCUMENTO AGRUPADO - CONTADO";
                                                }
                                            }
                                        }
                                        //ACTUALIZA ESTATUS CANCELANDO EN TABLA DE GRUPOS
                                        echo "<br>ACTUALIZAMOS DOCUMENTO AGRUPADO";
                                        $this->actualizar_estado_documento_grupo($datos_grupo);
                                        echo "<br>FIN DOCUMENTOS AGRUPADOS";
                                        break;
                                    case $this->cax_documents: //DP
                                        echo "<br>INICIO DEPOSITO CAJAS (DP)";
                                        $PROCESO = 5;
                                        $ID_PAGO = $pago->id;
                                        $numero_documento = $vector[1];
                                        $codSuc = $vector[3];
                                        $motivo = '02';
                                        $moneda = ($pago->currencyCode === 'USD') ? '02' : '01';
                                        $tipo_cambio_dolar = $this->retorna_tipo_cambio_dolar_mym('02');
                                        

                                        $fecha = date("Ymd");
                                        if ($vector[4] === 'A') //A -> AYER, H -> HOY
                                        {
                                            //BUSCAR LA PLANILLA DEL DÍA ANTERIOR, SINO EL ANTERIOR
                                            $numero_planilla = 0;
                                            $i = 0;
                                            while ($numero_planilla == 0) {
                                                $i++;
                                                $fecha = Utilidades::retorna_fecha_formateada('Y-m-d H:i:s', 'Ymd', Utilidades::sumar_restar_dias_fecha($fecha, 1, 'restar'));
                                                echo "<br>Fecha ($i): $fecha";
                                                //OBTENER PLANILLA DEL DÍA
                                                if (!$regPlanilla = $this->retorna_planilla_contado_actual_db2($codCia, $codSuc, $fecha)) {
                                                    echo '<br>3.- NO HAY PLANILLA CREADA PARA LA FECHA ' . $fecha . ' - Cia: ' . $codCia . ' - Suc: ' . $codSuc;
                                                } else {
                                                    $numero_planilla = $regPlanilla->cjapnpll;
                                                    echo "<br>PLANILLA DIA ANTERIOR: $numero_planilla";
                                                }
                                            }
                                        } else {
                                            //OBTENER PLANILLA DEL DÍA
                                            if (!$regPlanilla = $this->retorna_planilla_contado_actual_db2($codCia, $codSuc, $fecha)) {
                                                echo '<br>4.- NO HAY PLANILLA CREADA PARA LA FECHA ' . $fecha . ' - Cia: ' . $codCia . ' - Suc: ' . $codSuc;
                                                exit;
                                            } else {
                                                $numero_planilla = $regPlanilla->cjapnpll;
                                                echo "<br>PLANILLA DIA ACTUAL: $numero_planilla";
                                            }
                                        }
                                        echo '<pre>';
                                        echo "<br>Nro Planilla: $numero_planilla";
                                        $secuencia = DB::connection('ibmi')->table('LIBPRDDAT.MMCJFGT')
                                            ->where('CJGTNPL', $numero_planilla)->max('CJGTSEC') + 1;
                                        echo " - Secuencia: $secuencia";

                                        //print_r($regPlanilla);
                                        echo "<br>DOCUMENTO TIPO CAX: " . $paidDocuments[0]->documentId;
                                        $docType = $this->cax_documents;

                                        //(print_r($bankAccount));
                                        $arrayWhere = [
                                            ['CJGTCIA', '=', $codCia],
                                            ['CJGTNOP', '=', $pago->operationNumber],
                                            ['CJGTEST', '=', 'A'],
                                            ['CJGTFEG', '=', date("Ymd")],
                                            ['CJGTBCO', '=', $bankAccount[0]->erp_code],
                                        ];
                                        // $importe =
                                        $importeDeposito = $pago->totalAmount;
                                        $comision = 0;
                                        if ($moneda == '01') {
                                            if ($bankAccount[0]->erp_code == '02') { //soles
                                                $comision = 2.50;
                                            }else{
                                                $comision = 0;
                                            }
                                        }else{
                                            if ($bankAccount[0]->erp_code == '02') { //soles
                                                $comision = 0.90;
                                            }else{
                                                $comision = 0;
                                            }
                                        }

                                        $importe = $importeDeposito + $comision;
                                        if ($moneda === '02') {
                                            $monto_dolares = round($importe, 2);
                                            $monto_soles = round(0, 2);
                                        } else {
                                            $monto_soles = round($importe, 2);
                                            $monto_dolares = round(0, 2);
                                        }
                                        $rs = DB::connection('ibmi')->table('LIBPRDDAT.MMCJFGT')->where($arrayWhere)->first();
                                        //die(print_r($rs));

                                        if (!is_object($rs)) {
                                            $arrayInsert = array(
                                                'CJGTCIA' => $codCia,
                                                'CJGTSUC' => $codSuc,
                                                'CJGTNPL' => $numero_planilla,
                                                'CJGTSEC' => $secuencia,
                                                'CJGTMGA' => $motivo,
                                                'CJGTFEG' => date("Ymd"),
                                                'CJGTCLI' => $this->codcli_mym,
                                                'CJGTRSP' => '',
                                                'CJGTAUT' => '',
                                                'CJGTMON' => $moneda,
                                                'CJGTTCA' => round($tipo_cambio_dolar->mym_selling_price, 2),
                                                'CJGTIDO' => $monto_dolares,
                                                'CJGTISO' => $monto_soles,
                                                'CJGTAJS' => 0,
                                                'CJGTIMG' => round($importe, 2), //importe
                                                'CJGTTOT' => round($importeDeposito,2), //importe deposito
                                                'CJGTGTB' => round($comision,2), // comision
                                                'CJGTOBS' => '',
                                                'CJGTNRG' => 0,
                                                'CJGTNOP' => $pago->operationNumber,
                                                'CJGTFCE' => date('Ymd'),
                                                'CJGTBCO' => $bankAccount[0]->erp_code,
                                                'CJGTCTA' => $bankAccount[0]->nro_cuenta,
                                                'CJGTPAI' => '001',
                                                'CJGTCIU' => '001',
                                                'CJGTLI1' => $codSuc,
                                                'CJGTLI2' => 0,
                                                'CJGTEST' => 'A',
                                                'CJGTUSR' => $this->user,
                                                'CJGTJDT' => date("Ymd"),
                                                'CJGTJTM' => date("His"),
                                                'CJGTJOB' => $this->app,
                                                'CJGTPGM' => $this->app,
                                                'CJGTTPMO' => '',
                                                'CJGTMTAN' => '',
                                                'CJGTOBSA' => '',
                                                'CJGTUSRA' => '',
                                                'CJGTJDTA' => 0,
                                                'CJGTJTMA' => 0,
                                                'CJGTPGMA' => $pago->id
                                            );
                                            //if (!DB::connection('ibmi')->table('LIBPRDDAT.MMCJFGT')->where('CJGTNOP',trim($pago->operationNumber))->first()) {
                                            $variable =  DB::connection('ibmi')->table('LIBPRDDAT.MMCJFGT')->insert([$arrayInsert]);
                                            // print_r(json_encode(DB::getQueryLog()));
                                            if (!$variable) {
                                                echo "<br>No se pudo insertar en la MMCJFGT";
                                            }else{
                                                echo "<br>Se inserto correctamente en la MMCJFGT";
                                            }
                                            //}
                                        }else {
                                            echo "<br>DEPÓSITO YA EXISTE ($pago->operationNumber)";
                                        }
                                        $regSaldo = $this->retorna_doc_cliente_saldo_interface($codCia, $codSuc, $customerIdentificationCode, $docType, $numero_documento);
                                        $regSaldo->ABCODCLI = $this->codcli_mym; //ASIGNAR EL CLIENTE MYM AL USUARIO QUE HIZO EL DEPÓSITO
                                        $datosDeposito = $this->registra_deposito_bancario_mmyprep($codCia, $codSuc, $bankAccount[0]->erp_code, $pago->operationNumber, $regSaldo, $bankAccount, $pago);
                                        //print_r($datosDeposito);
                                        echo "<br>FIN DEPOSITO CAJAS (DP)";
                                        break;
                                }
                            }
                            echo '<br>FIN DOCUMENTID: '.$paidDoc->documentId;
                            $this->actualiza_saldos_mmccreca($codCia, $bankAccount[0]->nro_cuenta, date("Ymd"));
                        }
                    }
                }

                $arrayWhere = array(
                    'id' =>  $pago->id,
                    'fecha_hora_actualizacion_db2' => null
                );
                $arrayUpdate = array(
                    'fecha_hora_actualizacion_db2' => date("Y-m-d H:i:s")
                );
                $this->actualiza_tabla_postgres('customer_payments', $arrayWhere, $arrayUpdate);
                echo "<br>FINALIZAMOS EL PAGO ID: ".$pago->id;
            }
        }
        echo '<br>FIN sincroniza_middleware_con_db2  - ' . date("d-m-Y H:i:s");
    }

    public function verifica_documentos_pagados($vector_documentos_pagados, $id_pago)
    {
        $response = new stdClass();
        $suma = 0.0;
        $response->cantidad_doc_pagados = sizeof($vector_documentos_pagados);

        foreach ($vector_documentos_pagados as $doc_pagado) {
            $doc_pagado->detalle_pago = DB::table('customer_debts_payments')->where('payment_id', $id_pago)->where('id_deuda', $doc_pagado->id)->first();
            $response->tipo_cambio = $doc_pagado->detalle_pago->exchange_rate;
            $response->moneda = $doc_pagado->detalle_pago->currency_code;
            $response->codSuc = $doc_pagado->ABCODSUC;
            $suma = (float)$suma + (float)round($doc_pagado->detalle_pago->payment_amount, 2);
        }

        $response->suma = $suma;
        return $response;
    }

    public function sincronizar_pago_bcp($codCia, $pago, $bankAccount, $operationId, $i)
    {
        $paidDocuments = json_decode($pago->paidDocuments);
        $customerIdentificationCode = $pago->customerIdentificationCode;
        $channel = $pago->channel;
        $paymentType = $pago->paymentType;
        $currencyCode = ($pago->currencyCode === 'USD') ? '02' : '01';

        echo '<pre>';

        $vector_documentos_pagados = $pago->related_debts;
        if (is_array($vector_documentos_pagados)) {

            $objeto_docs = $this->verifica_documentos_pagados($vector_documentos_pagados, $pago->id);
            $precio_dolar_mym_venta = $objeto_docs->tipo_cambio;
            $suma_total_saldo_documentos_pagados = $objeto_docs->suma;
            $codSuc = $objeto_docs->codSuc;
            $monto_total_pagado = $pago->totalAmount;
            $total_pagado_restante = $monto_total_pagado;
            $regSaldo = $vector_documentos_pagados[0];

            echo "<br>Cant. Doc. Pagados: $objeto_docs->cantidad_doc_pagados - Suma Doc. Pagados: $objeto_docs->suma --- Monto depósito: $pago->totalAmount --- Tipo de cambio: $precio_dolar_mym_venta";
            if ($currencyCode === '01') {
                $total = round(($objeto_docs->suma * $objeto_docs->tipo_cambio), 2);
                if ($pago->totalAmount <> $total) {
                    echo "<br>Monto depósitado ($pago->totalAmount) no va con el monto de los documentos ($total)";
                    exit;
                }
            } elseif ($pago->totalAmount <> $objeto_docs->suma) {
                echo "<br>Monto depósitado ($pago->totalAmount) es diferente a la sumatoria de los documentos pagados ($objeto_docs->suma)";
                exit;
            }

            //REGISTRAR DEPÓSITO
            $datosDeposito = $this->registra_deposito_bancario_mmyprep($codCia, $codSuc, $bankAccount[0]->erp_code, $operationId, $regSaldo, $bankAccount, $pago);
            $correlativoBoletaDeposito = $datosDeposito->ypnrodep;
            echo "<br>Datos Depósito -> Nro. Correlatido : $correlativoBoletaDeposito - Nro. Depósito: $datosDeposito->ypnroopr";
            //FIN - REGISTRAR DEPOSITO BANCARIO

            foreach ($vector_documentos_pagados as $doc_pagado) {

                $regSaldo = $doc_pagado;
                $arrayWhere = array(
                    ['ABCODCIA', '=', $codCia],
                    ['ABCODSUC', '=', $regSaldo->ABCODSUC],
                    ['ABCODClI', '=', $regSaldo->ABCODCLI],
                    ['ABTIPDOC', '=', $regSaldo->ABTIPDOC],
                    ['ABNRODOC', '=', $regSaldo->ABNRODOC]
                );
                $datos_doc_db2 = $this->selecciona_from_tabla_db2($this->tabla_saldos_aux_db2, $arrayWhere);

                echo "<br>Datos Doc. Pagado -> CLI: $regSaldo->ABCODCLI - SUC: $regSaldo->ABCODSUC -  Tip. Doc.: $regSaldo->ABTIPDOC - Nro. Doc.: $regSaldo->ABNRODOC ";

                $saldo_actual_documento = ($currencyCode === '01') ? (float)round(($datos_doc_db2->abimpsld * $precio_dolar_mym_venta), 2) : (float)round(($datos_doc_db2->abimpsld), 2);

                $nuevo_saldo = ($total_pagado_restante >= $saldo_actual_documento) ? 0 : (float)round(($saldo_actual_documento - $total_pagado_restante), 2);
                $monto_pagado_documento = (float)round(($saldo_actual_documento - $nuevo_saldo), 2);
                $total_pagado_restante = (float)round(($total_pagado_restante - $monto_pagado_documento), 2);

                echo "<br>Monto Total Pagado: $monto_total_pagado - Monto Restante: $total_pagado_restante - Saldo Act. Doc.: $saldo_actual_documento - Nuevo Saldo: $nuevo_saldo - Monto Pagado Doc.: $monto_pagado_documento";

                switch ($doc_pagado->ABTIPDOC) {
                    case 'DP': //DEPÓSITOS CAJA (CAX)
                        # code...
                        break;

                    case 'DA': //DOCUMENTOS AGRUPADOS
                        # code...
                        break;

                    default: //DOCUMENTOS A CRÉDITO Y CONTADO

                        if ($doc_pagado->ABFRMPAG === 'C') {
                            echo '<pre>';
                            print_r($regSaldo);
                            exit;
                            ###DOCUMENTO DE CONTADO
                            echo "<br>PAGO DE CONTADO";
                            $this->procesa_pago_deuda_contado($regSaldo->id, $codCia, $codSuc, $operationId, $datos_doc_db2, $bankAccount, $pago, $saldo_actual_documento, $nuevo_saldo, $monto_pagado_documento, $precio_dolar_mym_venta, $correlativoBoletaDeposito);
                        } else {
                            ###DOCUMENTO A CRÉDITO
                            echo "<br>PAGO A CRÉDITO";
                            $docType = $regSaldo->ABTIPDOC;

                            //VERIFICAR/REGISTRAR PLANILLA DE COBRANZAS (CREDITO) TABLA (MMDLREP)
                            $regPlanillaCobranzas = $this->registra_planilla_cobranzas_credito($codCia, $currencyCode, $suma_total_saldo_documentos_pagados);
                            $correlativoPlanillaCobranzas = $regPlanillaCobranzas->dlnropll;
                            //FIN VERIFICAR/REGISTRAR PLANILLA DE COBRANZAS (CREDITO) TABLA (MMDLREP)

                            //VERIFICAR/REGISTRAR SI EXISTE PLANILLA DE COBRANZAS DEL DÍA
                            $regTipoPlanilla = $this->registra_planilla_cobranzas_dia($codCia, $correlativoPlanillaCobranzas, $this->codCobrador, $this->tipoPlanillaCredito, $this->user, $this->app);
                            echo ('<BR>PLANILLA DE COBRANZAS DEL DÍA: ' . $correlativoPlanillaCobranzas);
                            //FIN GENERACION PLANILLA DE COBRANZAS

                            $this->procesa_pago_deuda_credito($codCia, $codSuc, $currencyCode, $suma_total_saldo_documentos_pagados, $correlativoBoletaDeposito, $bankAccount, $operationId, $datos_doc_db2, $pago, $saldo_actual_documento, $nuevo_saldo, $monto_pagado_documento, $precio_dolar_mym_venta, $correlativoPlanillaCobranzas);

                            echo "<br>Saldo Actual: $saldo_actual_documento - Nuevo saldo: $nuevo_saldo - Monto actual depósito: $monto_actual_deposito";
                        }
                        break;
                }
            }
        }

        /*
        exit;

        $arrayNroDocumento = explode('-', $paidDocuments[0]->documentId);
        switch (sizeof($arrayNroDocumento)) {
            case '2': //CRÉDITO
                $docNumber = $arrayNroDocumento[1];
                $serieNumber = $arrayNroDocumento[0];
                if (!$regSaldo = $this->retorna_doc_cliente_saldo_interface_fac($serieNumber, $docNumber)) {
                    echo "<br>Deuda de documento no encontrada: ";
                    die(" $serieNumber - $docNumber");
                }
                $docType = $regSaldo->ABTIPDOC;
                $codSucDeposito = ($regSaldo->ABCODSUC === '01') ? '02' : $regSaldo->ABCODSUC;
                break;

            case '3': //CONTADO
                $docNumber = $arrayNroDocumento[2]; //NRO PEDIDO EN SALDOS
                $docType = $arrayNroDocumento[1];
                $codSuc = $pago->related_debts[0]->ABCODSUC;
                $codSucDeposito = ($codSuc === '01') ? '02' : $codSuc;

                echo "<br>Cia: $codCia - Doc: $docNumber - Type: $docType - Suc: $codSuc - Cli: $customerIdentificationCode<br>";
                //OBTENER DOC DESDE TABLA DE SALDOS
                $regSaldo = $this->retorna_doc_cliente_saldo_interface($codCia, $codSuc, $customerIdentificationCode, $docType, $docNumber);
                break;
        }

        echo '<pre>';
        echo "<br>RegSaldo: ";
        print_r($regSaldo);
        echo "<br>Pago: ";
        print_r($pago);
        exit;

        //REGISTRAR DEPÓSITO
        $datosDeposito = $this->registra_deposito_bancario_mmyprep($codCia, $codSucDeposito, $bankAccount[0]->erp_code, $operationId, $regSaldo, $bankAccount, $pago);
        $correlativoBoletaDeposito = $datosDeposito->ypnrodep;
        echo "<br>Depósito " . $correlativoBoletaDeposito;
        //FIN - REGISTRAR DEPOSITO BANCARIO



        if ($paidDocuments && is_array($paidDocuments)) {
            //VALIDAR QUE EL MONTO DEL DEPÓSITO SEA IGUAL A LA SUMA DE SALDOS DE LOS DOCUMENTOS 
            //TOMAR EN CUENTA SI EL PAGO ES EN SOLES O EN DÓLARES
            $suma_total_saldo_documentos_pagados = 0.0;
            foreach ($paidDocuments as $paidDoc) {
                $suma_total_saldo_documentos_pagados += (float)round($paidDoc->amounts[0]->amount, 2);
            }

            if ($suma_total_saldo_documentos_pagados <> (float)$pago->totalAmount) {
                echo "<br>Sumatoria de montos pagados por documento no coincide con monto de depósito";
                exit;
            } else {
                echo "<br>Sumatoria de montos pagados por documento: $suma_total_saldo_documentos_pagados - Monto Depósito: " . $pago->totalAmount . " - Moneda: " . $currencyCode;
            }
        }

        $monto_actual_deposito = (float)round($pago->totalAmount, 2);

        //RETORNA TIPO DE CAMBIO DEL DIA
        $tipo_cambio_dolar = $this->retorna_tipo_cambio_dolar_mym('02');
        $precio_dolar_mym_venta = (float) round($tipo_cambio_dolar->mym_selling_price, 2);

        //VALIDAR: SI EL DEPÓSITO ES EN SOLES, DEBE SER IGUAL AL MONTO X EL TIPO DE CAMBIO MYM ACTUAL
        ###

        if ($paidDocuments && is_array($paidDocuments)) {
            echo "<br>Cantidad de documentos pagados: " . sizeof($paidDocuments);
            foreach ($paidDocuments as $paidDoc) {
                $arrayNroDoc = explode('-', $paidDoc->documentId);
                switch (sizeof($arrayNroDoc)) {
                    case '2': //DOCUMENTO A CRÉDITO
                        $ID_PAGO = $pago->id;
                        $PROCESO = 3;

                        $docNumber = $arrayNroDoc[1];
                        $serieNumber = $arrayNroDoc[0];
                        if (!$regSaldo = $this->retorna_doc_cliente_saldo_interface_fac($serieNumber, $docNumber)) {
                            echo "<br>Deuda de documento no encontrada: ";
                            die(" $serieNumber - $docNumber");
                        }
                        $docType = $regSaldo->ABTIPDOC;

                        $arrayWhere = array(
                            ['ABCODCIA', '=', $codCia],
                            ['ABCODSUC', '=', $regSaldo->ABCODSUC],
                            ['ABCODClI', '=', $regSaldo->ABCODCLI],
                            ['ABTIPDOC', '=', $regSaldo->ABTIPDOC],
                            ['ABNRODOC', '=', $regSaldo->ABNRODOC]
                        );
                        $datos_doc_db2 = $this->selecciona_from_tabla_db2($this->tabla_saldos_aux_db2, $arrayWhere);

                        if ($currencyCode === '01') {
                            $saldo_actual_documento = (float)round(($datos_doc_db2->abimpsld * $precio_dolar_mym_venta), 2);
                            $nuevo_saldo = ($monto_actual_deposito >= $saldo_actual_documento) ? 0 : ($saldo_actual_documento - $monto_actual_deposito);
                            $monto_pagado_documento = (float)round(($saldo_actual_documento - $nuevo_saldo), 2);
                            $monto_actual_deposito -= $saldo_actual_documento;
                        } else {
                            $saldo_actual_documento = (float)round(($datos_doc_db2->abimpsld), 2);
                            $nuevo_saldo = (float)round(($monto_actual_deposito >= $saldo_actual_documento) ? 0 : ($saldo_actual_documento - $monto_actual_deposito), 2);
                            $monto_pagado_documento = (float)round(($saldo_actual_documento - $nuevo_saldo), 2);
                            $monto_actual_deposito = round(((float)$monto_actual_deposito - (float)$saldo_actual_documento), 2);
                        }

                        //VERIFICAR/REGISTRAR PLANILLA DE COBRANZAS (CREDITO) TABLA (MMDLREP)
                        $regPlanillaCobranzas = $this->registra_planilla_cobranzas_credito($codCia, $currencyCode, $suma_total_saldo_documentos_pagados);
                        $correlativoPlanillaCobranzas = $regPlanillaCobranzas->dlnropll;
                        //FIN VERIFICAR/REGISTRAR PLANILLA DE COBRANZAS (CREDITO) TABLA (MMDLREP)

                        //VERIFICAR/REGISTRAR SI EXISTE PLANILLA DE COBRANZAS DEL DÍA
                        $regTipoPlanilla = $this->registra_planilla_cobranzas_dia($codCia, $correlativoPlanillaCobranzas, $this->codCobrador, $this->tipoPlanillaCredito, $this->user, $this->app);
                        echo ('<BR>PLANILLA DE COBRANZAS DEL DÍA: ' . $correlativoPlanillaCobranzas);
                        //FIN GENERACION PLANILLA DE COBRANZAS

                        $this->procesa_pago_deuda_credito($PROCESO, $ID_PAGO, $codCia, $codSucDeposito, $currencyCode, $suma_total_saldo_documentos_pagados, $correlativoBoletaDeposito, $bankAccount, $operationId, $datos_doc_db2, $pago, $saldo_actual_documento, $nuevo_saldo, $monto_pagado_documento, $tipo_cambio_dolar, $correlativoPlanillaCobranzas);

                        echo "<br>Saldo Actual: $saldo_actual_documento - Nuevo saldo: $nuevo_saldo - Monto actual depósito: $monto_actual_deposito";
                        break;

                    case '2': //DOCUMENTO DE CONTADO
                        # code...
                        break;

                    default: //DOCUMENTO COMPUESTO, DEPÓSITO O COTIZACIÓN
                        # code...
                        break;
                }
            }
        }


        print_r($pago);
        */
    }


    public function procesa_pago_deuda_contado($ID_DEUDA, $codCia, $codSuc, $operationId, $datos_doc_db2, $bankAccount, $pago, $saldo_actual_documento, $nuevo_saldo, $monto_pagado_documento, $precio_dolar_mym_venta, $correlativoBoletaDeposito)
    {
        $PROCESO = 4;
        $ID_PAGO = $pago->id;

        $docNumber = $datos_doc_db2->abnrodoc;
        $currencyCode = $pago->currencyCode;
        $MON = ($currencyCode === 'USD') ? '02' : '01';

        //OBTENER PLANILLA DEL DÍA
        if (!$regPlanilla = $this->retorna_planilla_contado_actual_db2($codCia, $codSuc, date("Ymd"))) {
            echo '<br>5.- NO HAY PLANILLA CREADA PARA LA FECHA ' . date("Ymd") . ' - Cia: ' . $codCia . ' - Suc: ' . $codSuc;
            $this->redirecciona('/sync', 15);
            exit;
        }
        $correlativoPlanillaCobranzas = $regPlanilla->cjapnpll;
        echo (" - PLANILLA COBRANZAS: $correlativoPlanillaCobranzas");

        $documentAmountPaid = (float)round($monto_pagado_documento, 2);

        echo '<br> ACTUALIZAR TABLA DE SALDOS AUXILIAR (CCAPLBCO) - 20';
        if (!$this->verifica_paso_proceso(20, $PROCESO, $this->tablas['tabla_saldos_aux_db2'], $ID_DEUDA)) {
            if ($this->actualizar_tabla_saldos_auxiliar($codCia, $datos_doc_db2, $documentAmountPaid, $operationId, $bankAccount, $nuevo_saldo, 2)) {
                $this->registra_paso_proceso(20, $PROCESO, $this->tablas['tabla_saldos_aux_db2'], $ID_DEUDA);
            } else {
                die('<br> ERROR ACTUALIZANDO TABLA DE SALDOS AUXILIAR (CCAPLBCO) - 20');
            }
        }
        echo '<br>FIN ACTUALIZAR TABLA DE SALDOS AUXILIAR (CCAPLBCO) - 20';

        echo '<br> ACTUALIZAR TABLA DE SALDOS PRINCIPAL (MMEIREP) - 21';
        if (!$this->verifica_paso_proceso(21, $PROCESO, $this->tablas['tabla_saldos_principal'], $ID_DEUDA)) {
            $this->actualizar_tabla_saldos_principal($codCia, $datos_doc_db2, $nuevo_saldo);
            $this->registra_paso_proceso(21, $PROCESO, $this->tablas['tabla_saldos_principal'], $ID_DEUDA);
        }
        echo '<br>FIN ACTUALIZAR TABLA DE SALDOS PRINCIPAL (MMEIREP) - 21';

        echo '<br> REGISTRA/ACTUALIZA ENCABEZADO DE PAGOS - MMCJFCB - 27';
        if (!$this->verifica_paso_proceso(27, $PROCESO, $this->tablas['tabla_mmdorep'], $ID_DEUDA)) {
            if ($this->registra_actualiza_encabezado_pagos_contado_mmcjfcb($codCia, $datos_doc_db2->abcodsuc, $docNumber, $datos_doc_db2, $regPlanilla)) {
                $this->registra_paso_proceso(27, $PROCESO, $this->tablas['tabla_mmdorep'], $ID_DEUDA);
                echo '<br>FIN REGISTRA/ACTUALIZA ENCABEZADO DE PAGOS - MMCJFCB - 27';
            } else {
                die('<br>ERROR REGISTRANDO/ACTUALIZANDO ENCABEZADO DE PAGOS - MMCJFCB - 27');
            }
        }

        echo '<br> REGISTRA DETALLE DE PAGOS - MMCJFDT - 28';
        if (!$this->verifica_paso_proceso(28, $PROCESO, $this->tablas['tabla_mmcjfdt'], $ID_PAGO)) {
            if ($this->registra_detalle_pagos_contado_mmcjfdt($codCia, $codSuc, $docNumber, $regPlanilla, $correlativoBoletaDeposito, $operationId, $bankAccount, $MON, $datos_doc_db2, $documentAmountPaid)) {
                $this->registra_paso_proceso(28, $PROCESO, $this->tablas['tabla_mmcjfdt'], $ID_PAGO);
                echo '<br>FIN REGISTRA DETALLE DE PAGOS - MMCJFDT - 28';
            } else {
                die('<br>ERROR REGISTRANDO/ACTUALIZANDO DETALLE DE PAGOS - MMCJFDT - 28');
            }
        }

        echo '<br> REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) PADRE - 22';
        if (!$this->verifica_paso_proceso(22, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_PAGO)) {
            if ($this->registra_historicos_padre($codCia, $datos_doc_db2, $nuevo_saldo)) {
                $this->registra_paso_proceso(22, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_PAGO);
                echo '<br>FIN REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) PADRE - 22';
            } else {
                echo "<br>ATENCIÓN::: PROCESO DE REGISTRO EN TABLA HISTÓRICO DE SALDOS (MMEJREP) PADRE NO REALIZADO";
                exit;
            }
        }

        echo '<br> REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) HIJO - 23';
        if (!$this->verifica_paso_proceso(23, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_PAGO)) {
            if ($this->registra_tabla_historicos_hijo($codCia, $correlativoBoletaDeposito, $datos_doc_db2, $nuevo_saldo, $documentAmountPaid, $MON)) {
                $this->registra_paso_proceso(23, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_PAGO);
                echo '<br>FIN REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) HIJO - 23';
            } else {
                echo "<br>ATENCIÓN::: PROCESO DE REGISTRO EN TABLA HISTÓRICO DE SALDOS (MMEJREP) HIJO NO REALIZADO";
                exit;
            }
        }

        echo '<br> REGISTRAR EN TABLA DE APLICACIONES (MMELREP) - 24';
        if ($MON === '01' && $datos_doc_db2->abcodmon === '02') {
            $monto_pagado_padre = (float)round(($datos_doc_db2->abimpsld * $precio_dolar_mym_venta), 2);
            $monto_pagado_hijo = $documentAmountPaid;
        } else {
            $monto_pagado_padre = $datos_doc_db2->abimpsld;
            $monto_pagado_hijo = $documentAmountPaid;
        }

        if (!$this->verifica_paso_proceso(24, $PROCESO, $this->tablas['tabla_aplicaciones_mmelrep'], $ID_PAGO)) {
            if ($this->registrar_tabla_aplicaciones($codCia, $datos_doc_db2, $correlativoBoletaDeposito, $monto_pagado_padre, $monto_pagado_hijo)) {
                $this->registra_paso_proceso(24, $PROCESO, $this->tablas['tabla_aplicaciones_mmelrep'], $ID_PAGO);
                echo '<br>FIN REGISTRAR EN TABLA DE APLICACIONES (MMELREP) - 24';
            } else {
                die("<br>ATENCIÓN::: PROCESO DE REGISTRO EN TABLA DE APLICACIONES (MMELREP) NO REALIZADO");
            }
        }

        //REGISTRAR PAGOS PARCIALES EN TABLAS MMEJREP Y MMELREP
        $arrayWhere = array(
            ['CJDTCCIA', '=', $codCia],
            ['CJDTSERC', '=', $datos_doc_db2->abcodsuc],
            ['CJDTNPDC', '=', $datos_doc_db2->abnrodoc],
            ['CJDTNPLL', '=', $regPlanilla->cjapnpll],
        );
        $pagos_parciales = $this->selecciona_all_from_tabla_db2('LIBPRDDAT.MMCJFDT', $arrayWhere);
        if ($pagos_parciales && is_array($pagos_parciales)) {
            if (sizeof($pagos_parciales) > 1) {
                if (!$this->verifica_paso_proceso(29, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_PAGO)) {
                    if ($this->registrar_pagos_parciales_en_mmejrep($codCia, $datos_doc_db2, $pagos_parciales)) {
                        $this->registra_paso_proceso(29, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_PAGO);
                        echo '<br>FIN REGISTRAR PAGOS PARCIALES EN TABLA DE HISTÓRICO DE SALDOS (MMEJREP) HIJO - 29';
                    } else {
                        die("<br>ATENCIÓN::: PROCESO DE PAGOS PARCIALES EN TABLA DE HISTÓRICO DE SALDOS (MMEJREP) HIJO NO REALIZADO");
                    }
                }

                if (!$this->verifica_paso_proceso(30, $PROCESO, $this->tablas['tabla_aplicaciones_mmelrep'], $ID_PAGO)) {
                    if ($this->registrar_pagos_parciales_en_mmelrep($codCia, $datos_doc_db2, $pagos_parciales)) {
                        $this->registra_paso_proceso(30, $PROCESO, $this->tablas['tabla_aplicaciones_mmelrep'], $ID_PAGO);
                        echo '<br>FIN REGISTRAR PAGOS PARCIALES EN TABLA DE APLICACIONES (MMELREP) - 30';
                    } else {
                        die("<br>ATENCIÓN::: PROCESO DE PAGOS PARCIALES EN TABLA DE APLICACIONES (MMELREP) NO REALIZADO");
                    }
                }
            }
        }
        //FIN - REGISTRAR PAGOS PARCIALES EN TABLAS MMEJREP Y MMELREP


        echo '<br> REGISTRAR EN TABLA MMCDRECA - 26';
        if (!$this->verifica_paso_proceso(26, $PROCESO, $this->tablas['tabla_mmcdreca'], $ID_PAGO)) {
            if ($this->registra_tabla_mmcdreca($codCia, $bankAccount, $datos_doc_db2, $correlativoBoletaDeposito, $operationId, $correlativoPlanillaCobranzas, $pago)) {
                $this->registra_paso_proceso(26, $PROCESO, $this->tablas['tabla_mmcdreca'], $ID_PAGO);
                echo '<br>FIN REGISTRAR EN TABLA MMCDRECA - 26';
            } else {
                die("<br>ATENCIÓN::: PROCESO DE REGISTRO EN TABLA (MMCDRECA) NO REALIZADO");
            }
        }

        echo '<br> REGISTRAR EN TABLA MMCCRECA';
        $this->escribe_actualiza_tabla_mmccreca($codCia, $bankAccount, $pago, $correlativoPlanillaCobranzas);
        echo '<br> FIN REGISTRAR EN TABLA MMCCRECA';
    }

    public function procesa_pago_deuda_credito($PROCESO, $ID_DEUDA, $codCia, $codSucDeposito, $currencyCode, $totalAmountPaid, $correlativoBoletaDeposito, $bankAccount, $operationId, $datos_doc_db2, $pago, $saldo_actual_documento, $nuevo_saldo, $monto_pagado_documento, $tipo_cambio_dolar, $correlativoPlanillaCobranzas)
    {
        $PROCESO = 3;
        $ID_DEUDA = $pago->id;
        echo "<br>DOCUMENTO A CRÉDITO: CIA: $codCia - SUC: {$datos_doc_db2->abcodsuc} - CLI: {$datos_doc_db2->abcodcli} - TD: {$datos_doc_db2->abtipdoc} - NroDOC: {$datos_doc_db2->abnrodoc} ";

        //<br> BUSQUEDA / REGISTRO DE DOCUMENTO EN TABLA MMDMREP
        if (!$this->verifica_paso_proceso(17, $PROCESO, $this->tablas['tabla_mmdmrep'], $ID_DEUDA)) {
            if ($this->procesa_registro_tabla_mmdmrep($codCia, $correlativoPlanillaCobranzas, $datos_doc_db2, $monto_pagado_documento, $currencyCode)) {
                $this->registra_paso_proceso(17, $PROCESO, $this->tablas['tabla_mmdmrep'], $ID_DEUDA);
                echo '<br>FIN BUSQUEDA / REGISTRO DE DOCUMENTO EN TABLA MMDMREP';
            }
        }

        if (!$this->verifica_paso_proceso(18, $PROCESO, $this->tablas['tabla_mmdnrep'], $ID_DEUDA)) {
            echo '<br> BUSQUEDA / REGISTRO DE DOCUMENTO EN TABLA MMDNREP - 18';
            if ($this->procesa_registro_tabla_mmdnrep($codCia, $correlativoPlanillaCobranzas, $datos_doc_db2->abcodcli, $correlativoBoletaDeposito, $totalAmountPaid, $operationId, $bankAccount, $currencyCode)) {
                $this->registra_paso_proceso(18, $PROCESO, $this->tablas['tabla_mmdnrep'], $ID_DEUDA);
                echo '<br>FIN BUSQUEDA / REGISTRO DE DOCUMENTO EN TABLA MMDNREP - 18';
            }
        }

        if (!$this->verifica_paso_proceso(19, $PROCESO, $this->tablas['tabla_mmdorep'], $ID_DEUDA)) {
            echo '<br> BUSQUEDA / REGISTRO DE DOCUMENTO EN TABLA MMDOREP - 19';
            if ($this->procesa_registro_tabla_mmdorep($codCia, $correlativoPlanillaCobranzas, $datos_doc_db2, $correlativoBoletaDeposito, $datos_doc_db2->abimpsld, $datos_doc_db2->abcodmon)) { //IMPORTE ORIGINAL
                $this->registra_paso_proceso(19, $PROCESO, $this->tablas['tabla_mmdorep'], $ID_DEUDA);
                echo '<br>FIN BUSQUEDA / REGISTRO DE DOCUMENTO EN TABLA MMDOREP - 19';
            }
        }

        if (!$this->verifica_paso_proceso(20, $PROCESO, $this->tablas['tabla_saldos_aux_db2'], $ID_DEUDA)) {
            echo '<br> ACTUALIZAR TABLA DE SALDOS AUXILIAR (CCAPLBCO) - 20';
            if ($this->actualizar_tabla_saldos_auxiliar($codCia, $datos_doc_db2, $totalAmountPaid, $operationId, $bankAccount, $nuevo_saldo, 0)) {
                $this->registra_paso_proceso(20, $PROCESO, $this->tablas['tabla_saldos_aux_db2'], $ID_DEUDA);
                echo '<br>FIN ACTUALIZAR TABLA DE SALDOS AUXILIAR (CCAPLBCO) - 20';
            } else {
                die('<br> ERROR ACTUALIZANDO TABLA DE SALDOS AUXILIAR (CCAPLBCO) - 20');
            }
        }

        if (!$this->verifica_paso_proceso(21, $PROCESO, $this->tablas['tabla_saldos_principal'], $ID_DEUDA)) {
            echo '<br> ACTUALIZAR TABLA DE SALDOS PRINCIPAL (MMEIREP) - 21';
            if ($this->actualizar_tabla_saldos_principal($codCia, $datos_doc_db2, $nuevo_saldo)) {
                $this->registra_paso_proceso(21, $PROCESO, $this->tablas['tabla_saldos_principal'], $ID_DEUDA);
                echo '<br>FIN ACTUALIZAR TABLA DE SALDOS PRINCIPAL (MMEIREP) - 21';
            } else {
                die('<br> ERROR ACTUALIZANDO TABLA DE SALDOS PRINCIPAL (MMEIREP) - 21');
            }
        }

        if (!$this->verifica_paso_proceso(22, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_DEUDA)) {
            echo '<br> REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) PADRE - 22';
            if ($this->registra_historicos_padre($codCia, $datos_doc_db2, $nuevo_saldo)) {
                $this->registra_paso_proceso(22, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_DEUDA);
                echo '<br>FIN REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) PADRE - 22';
            } else {
                die("<br>ATENCIÓN::: PROCESO DE REGISTRO EN TABLA HISTÓRICO DE SALDOS (MMEJREP) PADRE NO REALIZADO");
            }
        }

        if (!$this->verifica_paso_proceso(23, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_DEUDA)) {
            echo '<br> REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) HIJO - 23';
            if ($this->registra_tabla_historicos_hijo($codCia, $correlativoBoletaDeposito, $datos_doc_db2, $nuevo_saldo, $saldo_actual_documento, $currencyCode)) {
                $this->registra_paso_proceso(23, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_DEUDA);
                echo '<br>FIN REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) HIJO - 23';
            } else {
                die("<br>ATENCIÓN::: PROCESO DE REGISTRO EN TABLA HISTÓRICO DE SALDOS (MMEJREP) HIJO NO REALIZADO");
            }
        }

        if (!$this->verifica_paso_proceso(24, $PROCESO, $this->tablas['tabla_aplicaciones_mmelrep'], $ID_DEUDA)) {
            echo '<br> REGISTRAR EN TABLA DE APLICACIONES (MMELREP) - 24';
            if ($this->registrar_tabla_aplicaciones($codCia, $datos_doc_db2, $correlativoBoletaDeposito, $datos_doc_db2->abimpsld, $saldo_actual_documento)) {
                $this->registra_paso_proceso(24, $PROCESO, $this->tablas['tabla_aplicaciones_mmelrep'], $ID_DEUDA);
                echo '<br>FIN REGISTRAR EN TABLA DE APLICACIONES (MMELREP) - 24';
            } else {
                echo "<br>ATENCIÓN::: PROCESO DE REGISTRO EN TABLA DE APLICACIONES (MMELREP) NO REALIZADO";
            }
        }

        if (!$this->verifica_paso_proceso(26, $PROCESO, $this->tablas['tabla_mmcdreca'], $ID_DEUDA)) {
            echo '<br> REGISTRAR EN TABLA MMCDRECA - 26';
            if ($this->registra_tabla_mmcdreca($codCia, $bankAccount, $datos_doc_db2, $correlativoBoletaDeposito, $operationId, $correlativoPlanillaCobranzas, $pago)) {
                $this->registra_paso_proceso(26, $PROCESO, $this->tablas['tabla_mmcdreca'], $ID_DEUDA);
                echo '<br>FIN REGISTRAR EN TABLA MMCDRECA - 26';
            } else {
                echo "<br>ATENCIÓN::: PROCESO DE REGISTRO EN TABLA (MMCDRECA) NO REALIZADO";
            }
        }

        echo '<br> REGISTRAR EN TABLA MMCCRECA';
        $this->escribe_actualiza_tabla_mmccreca($codCia, $bankAccount, $pago, $correlativoPlanillaCobranzas);
        echo '<br> FIN REGISTRAR EN TABLA MMCCRECA';
    }


    //FUNCIÓN QUE REGISTRA/ACTUALIZA TABLA MMDMREP (RELACION DOCUMENTOS PLANILLA)
    public function procesa_registro_tabla_mmdmrep($codCia, $correlativoPlanillaCobranzas, $regSaldo, $documentAmountPaid, $currencyCode)
    {

        $arrayWhere = array(
            ['DMSTS', '=', 'A'],
            ['DMCODCIA', '=', $codCia],
            ['DMCODSUC', '=', '01'],
            ['DMNROPLL', '=', $correlativoPlanillaCobranzas],
            ['DMCODCLI', '=', $regSaldo->abcodcli],
            ['DMTIPDOC', '=', $regSaldo->abtipdoc],
            ['DMNRODOC', '=', $regSaldo->abnrodoc],
        );
        if (!$datosDoc = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMDMREP', $arrayWhere)) {
            $currencyCode = (strlen($currencyCode) == 2) ? $currencyCode : $regSaldo->abcodmon;
            $arrayInsert = array(
                'DMSTS' => 'A',
                'DMCODCIA' => $codCia,
                'DMCODSUC' => '01',
                'DMSUCDOC' => $regSaldo->abcodsuc,
                'DMNROPLL' => $correlativoPlanillaCobranzas,
                'DMCODCLI' => $regSaldo->abcodcli,
                'DMTIPDOC' => $regSaldo->abtipdoc,
                'DMNRODOC' => $regSaldo->abnrodoc,
                'DMCODMON' => $currencyCode,
                'DMFECEMI' => $regSaldo->abfecemi,
                'DMIMPCCC' => $documentAmountPaid,
                'DMIMPINF' => $documentAmountPaid,
                'DMIMPPIN' => 0,
                'DMSTSPED' => 'A',
                'DMUSR' => $this->user,
                'DMJOB' => $this->app,
                'DMJDT' => date("Ymd"),
                'DMJTM' => date("His")
            );
            if (!$this->inserta_into_tabla_db2('LIBPRDDAT.MMDMREP', $arrayInsert)) {
                die('<br>ERROR AL REGISTRAR REGISTRO EN TABLA (MMDMREP)');
            }
            $datosDoc = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMDMREP', $arrayWhere);
        } else {
            $importePagado = (float) round($datosDoc->dmimpccc, 2) + $documentAmountPaid;
            $arrayUpdate = array(
                'DMIMPCCC' => $importePagado,
                'DMIMPINF' => $importePagado
            );
            if (!$this->actualiza_tabla_db2('LIBPRDDAT.MMDMREP', $arrayWhere, $arrayUpdate)) {
                die('<BR>ERROR ACTUALIZANDO MMDMREP');
            }
        }

        return $datosDoc;
        //FIN BUSQUEDA / REGISTRO DE DOCUMENTO EN TABLA MMDMREP
    }

    //FUNCIÓN QUE REGISTRA/ACTUALIZA TABLA MMDNREP (RELACION DOCUMENTOS PLANILLA)
    public function procesa_registro_tabla_mmdnrep($codCia, $correlativoPlanillaCobranzas, $codigo_cliente, $correlativoBoletaDeposito, $monto_pagado, $operationId, $bankAccount, $currencyCode) //$pago,
    {
        echo '<br>REGISTRO EN TABLA (MMDNREP)';
        $arrayWhere = array(
            ['DNSTS', '=', 'A'],
            ['DNCODCIA', '=', $codCia],
            ['DNCODSUC', '=', '01'],
            ['DNNROPLL', '=', $correlativoPlanillaCobranzas],
            ['DNNROBOL', '=', $correlativoBoletaDeposito],
        );
        if (!$this->selecciona_from_tabla_db2('LIBPRDDAT.MMDNREP', $arrayWhere)) {
            //REGISTRA
            $arrayInsert = array(
                'DNCODCIA' => $codCia,
                'DNCODSUC' => '01', //sucursal de la planilla
                'DNNROPLL' => $correlativoPlanillaCobranzas,
                'DNNROBOL' => $correlativoBoletaDeposito,
                'DNCODMON' => $currencyCode,
                'DNIMPBOL' => $monto_pagado, //$pago->totalAmount,
                'DNIMPPIN' => $monto_pagado, //$pago->totalAmount,
                'DNNROOPR' => $operationId,
                'DNCODPAI' => '001',
                'DNCODCIU' => '001',
                'DNCODBCO' => $bankAccount[0]->erp_code,
                'DNCODCLI' => $codigo_cliente,
                'DNNROCTA' => $bankAccount[0]->nro_cuenta,
                'DNFRMBDP' => 'BD',
                'DNFECDOC' => date("Ymd"),
                'DNSTS' => 'A',
                'DNUSR' => $this->user,
                'DNJOB' => $this->app,
                'DNJDT' => date("Ymd"),
                'DNJTM' => date("Him"),
            );
            if (!$this->inserta_into_tabla_db2('LIBPRDDAT.MMDNREP', $arrayInsert))
                die('<br>ERROR AL INSERTAR REGISTRO EN TABLA MMDNREP');
            else {
                echo '<br>REGISTRO  AGREGADO EN TABLA MMDNREP';
                return true;
            }
        } else {
            echo '<br>REGISTRO YA EXISTE EN TABLA (MMDNREP)';
            return true;
        }
    }


    //FUNCION QUE VALIDA SI MONTO DE DEPOSITO COINCIDE CON SUMA DEL MONTO DE TODOS LOS DOCUMENTOS COMPUESTOS PAGADOS
    public function validar_monto_deposito_documento_compuesto($documentos, $monto_deposito)
    {
        $monto_deposito = (float)round($monto_deposito, 2);
        $total_saldo_documentos = 0.0;
        foreach ($documentos as $documento) {
            $total_saldo_documentos += (float)round($documento->dadsld, 2);
            //echo "<br>$documento->dadsld";
        }
        //echo "<br>DEP: $monto_deposito --- SUMA: $total_saldo_documentos";
        return ((float)round($monto_deposito, 2) == (float)round($total_saldo_documentos, 2)) ? true : false;
    }


    public function sincroniza_extornos_con_db2()
    {
        $codCia = $this->codCia;

        if (!$extornos = $this->get_bank_return_requests_for_update_db2()) {
            echo ('<BR>NO HAY EXTORNOS');
            $this->redirecciona('/sync', 10);
        }
        //$procesos_credito = $this->retorna_procesos_sync_extornos();
        //echo '<pre>';
        //die(print_r($extornos));

        echo '<br>Extornos: ' . sizeof($extornos);
        echo '<br>Sincronizar Extornos ' . date("Y-m-d H:i:s");
        foreach ($extornos as $extorno) {
            /*:: incrementamos el flag is_sync ::*/
            echo '<br>Extornamos requestId:'.$extorno->requestId.' - operationAnullment:'.$extorno->operationNumberAnnulment;
            $this->incrementar_flag_sync('bank_return_requests',$extorno->id);
            /* ::: Por incidencias de ejecucion doble, volvemos a validar si el extorno no ha sido procesado ::*/
            if(!$this->validate_extorno_sincronizado($extorno->id)){
                echo "<br>ESTE EXTORNO YA FUE SINCRONIZADO PAGO:".$extorno->id." OPERATIONANULLMENT:".$extorno->operationNumberAnnulment;
                continue;
            }
            $ID_EXTORNO = $extorno->id;
            $TABLA = 'bank_return_requests';
            //$pasos_faltantes = $this->retorna_pasos_faltantes_extornos(1, $TABLA, $extorno->id);
            //echo '<pre>';
            //die(print_r($pasos_faltantes));
            $operationId = null;
            $documentsToReturn = json_decode($extorno->paidDocuments);
            $bankCode = $extorno->bankCode;
            $currencyCode = ($extorno->currencyCode === 'PEN') ? '01' : '02';
            $bankAccount = $this->get_bank_accounts($codCia, $bankCode, $currencyCode, '01');
            $customerIdentificationCode = $extorno->customerIdentificationCode;
            echo "<br>Ident: $customerIdentificationCode --- Id_Extorno: $extorno->id";
            switch ($bankCode) {
                case '011': //BBVA
                    $operationId = $extorno->operationNumber;
                    break;
                case '009': //SCOTIABANK
                    $operationId = $extorno->operationNumber;
                    break;
                case '002': //BCP
                    $operationId  = ($extorno->operationNumber) ? $extorno->operationNumber : $extorno->requestId;

                default:
                    $operationId  = ($extorno->operationNumber) ? $extorno->operationNumber : $extorno->requestId;
                    break;
            }
            $channel = $extorno->channel;
            $paymentType = $extorno->paymentType;

            foreach ($documentsToReturn as $documento) {
                $arrayNroDoc = explode('-', $documento->documentId);
                if (sizeof($arrayNroDoc) == 2) //DOCUMENTO A CRÉDITO
                {
                    echo "<br>EXTORNAMOS DOCUMENTO A CREDITO :".$documento->documentId;
                    $PROCESO = 1; //EXTORNO A CREDITO
                    $serieNumber = $arrayNroDoc[0];
                    $docNumber = $arrayNroDoc[1];
                    $monto_pagado_documento = (float) $documento->amounts[0]->amount;
                    $regSaldo = $this->retorna_doc_cliente_saldo_interface_fac($serieNumber, $docNumber);
                    $codigo_cliente = $regSaldo->ABCODCLI;
                    $tipo_identificacion_cliente = $regSaldo->AKTIPIDE;
                    $numero_identificacion_cliente = $regSaldo->NUMERO_IDENTIFICACION;
                    $sucursal = $regSaldo->ABCODSUC;
                    $tipo_documento = $regSaldo->ABTIPDOC;
                    $numero_documento = $regSaldo->ABNRODOC;
                    $fecha_deposito = \Carbon\Carbon::parse($extorno->created_at)->format('Ymd');


                    $sucursal_deposito = ($regSaldo->ABCODSUC === '02') ? '01' : $regSaldo->ABCODSUC;
                    if ($datosDeposito = $this->retorna_datos_deposito_bancario($codCia, $sucursal_deposito, $bankAccount[0]->erp_code, $operationId, $fecha_deposito)) {
                        $DP_nro_interno = $datosDeposito->ypdepint;
                        $numero_boleta_deposito = $datosDeposito->ypnrodep;
                        $fecha_deposito = $datosDeposito->ypfecdep;
                        $importe_deposito = $datosDeposito->ypimpdep;
                        $importe_aplicado = $datosDeposito->ypimpapl;
                        $cuenta = $datosDeposito->ypnrocta;
                        $moneda = $datosDeposito->ypcodmon;
                        $deposito_bancario = $datosDeposito->ypnroopr;
                        $sucursal_deposito = $datosDeposito->ypcodsuc;
                        $banco_deposito  = $datosDeposito->ypcodbco;
                        $saldo_actual = null;
                        $importe_documento = 0;


                        if ($regSaldo = $this->retorna_registro_saldo_documento($codCia, $sucursal, $codigo_cliente, $tipo_documento, $numero_documento, $monto_pagado_documento)) {
                            $saldo_as400 = round(floatval($regSaldo->eiimpsld), 2);
                            $importe_total_as400 = round(floatval($regSaldo->eiimpccc), 2);
                            $importe_documento = $importe_total_as400;
                            $monto_pagado_documento = round(floatval($monto_pagado_documento), 2);
                            $saldo = round(($saldo_as400 + $monto_pagado_documento), 2);
                            $saldo_actual = $saldo;
                        }

                        if (!$this->verifica_paso_proceso(1, $PROCESO, $TABLA, $ID_EXTORNO)) {
                            //Desactivar Depósito Bancario (Tabla MMYPREP)
                            $this->desactivar_deposito_bancario($codCia, $sucursal_deposito, $banco_deposito, $operationId, $fecha_deposito, $TABLA, $ID_EXTORNO, $PROCESO);
                        }


                        if (!$regPlanillaCobranzas = $this->retorna_planilla_cobranzas($codCia, $sucursal_deposito, $fecha_deposito, $this->codCobrador)) {
                            die('PLANILLA DE DEPÓSITO NO ENCONTRADA');
                        } else {
                            $correlativo_planilla_cobranzas = $regPlanillaCobranzas->dlnropll;
                            if (!$this->verifica_paso_proceso(2, 1, $TABLA, $ID_EXTORNO)) {
                                //Actualizar monto em planilla de cobranzas (Tabla MMDMREP)
                                $this->actualiza_monto_planilla_cobranzas($codCia, $correlativo_planilla_cobranzas, $codigo_cliente, $tipo_documento, $numero_documento, $monto_pagado_documento, $TABLA, $ID_EXTORNO);
                            }

                            if (!$this->verifica_paso_proceso(3, 1, $TABLA, $ID_EXTORNO)) {
                                //Desactivar registro DEPÓSITO-CLIENTE-PLANILLA (Tabla MMDNREP)
                                $this->desactivar_registro_tabla_mmdnrep($codCia, $correlativo_planilla_cobranzas, $numero_boleta_deposito, $codigo_cliente, $TABLA, $ID_EXTORNO);
                            }

                            if (!$this->verifica_paso_proceso(4, 1, $TABLA, $ID_EXTORNO)) {
                                //Desactivar registro LANILLA-DEPÓSITO-CLIENTE-DOCUMENTO (Tabla MMDOREP)
                                $this->desactivar_registro_tabla_mmdorep($codCia, $correlativo_planilla_cobranzas, $numero_boleta_deposito, $codigo_cliente, $tipo_documento, $numero_documento, $TABLA, $ID_EXTORNO);
                            }

                            if (!$this->verifica_paso_proceso(5, 1, $TABLA, $ID_EXTORNO)) {
                                //Actualizar saldo tabla auxiliar (Tabla CCAPLBCO)
                                $this->actualizar_saldo_tabla_auxiliar($codCia, $sucursal, $codigo_cliente, $tipo_documento, $numero_documento, $monto_pagado_documento, $TABLA, $ID_EXTORNO);
                            }


                            if ($regSaldo) {
                                if ($saldo <= $importe_total_as400) {
                                    if (!$this->verifica_paso_proceso(6, 1, $TABLA, $ID_EXTORNO)) {
                                        //Actualizar saldo en tabla principal (Tabla MMEIREP)
                                        $this->actualizar_saldo_tabla_principal($codCia, $sucursal, $codigo_cliente, $tipo_documento, $numero_documento, $monto_pagado_documento, $regSaldo, $TABLA, $ID_EXTORNO);
                                    }
                                } else {
                                    echo '<br>SALDO DEBE SER MENOR O IGUAL QUE IMPORTE TOTAL DE DOCUMENTO';
                                    echo "<br>Importe AS400: $importe_total_as400 - Saldo AS400: $saldo_as400 - Importe Pagado Documento: $monto_pagado_documento - Saldo: $saldo <br>";
                                    exit;
                                }
                            }

                            $historico_hijo = null;
                            if (!$this->verifica_paso_proceso(7, 1, $TABLA, $ID_EXTORNO)) {
                                $historico_hijo = 1;
                                //Desactivar Registro de depósito en históricos de pagos HIJO (Tabla MMEJREP)
                                $this->actualiza_tabla_historico_saldos_hijo($codCia, $sucursal_deposito, $codigo_cliente, '81', $numero_boleta_deposito, $importe_deposito, $TABLA, $ID_EXTORNO, $PROCESO);
                            }

                            if ($saldo <= $importe_total_as400 || $historico_hijo) {
                                if (!$this->verifica_paso_proceso(8, 1, $TABLA, $ID_EXTORNO)) {
                                    //DESACTIVAR REGISTRO PADRE (Tabla MMEJREP)
                                    $this->actualiza_tabla_historico_saldos_padre($codCia, $sucursal, $codigo_cliente, $tipo_documento, $numero_documento, $monto_pagado_documento, $regSaldo, $TABLA, $ID_EXTORNO);
                                }
                            }

                            if (!$this->verifica_paso_proceso(9, 1, $TABLA, $ID_EXTORNO)) {
                                //Desactivar registro en tabla de aplicaciones (Tabla MMELREP)
                                $this->desactivar_registro_tabla_aplicaciones_mmelrep($codCia, $sucursal, $codigo_cliente, $numero_documento, $numero_boleta_deposito, $TABLA, $ID_EXTORNO);
                            }



                            if (!$this->verifica_paso_proceso(10, $PROCESO, $TABLA, $ID_EXTORNO)) {
                                //Accion tabla mmcdreca
                                $this->elimina_registro_tabla_mmcdreca($codCia, $bankAccount[0]->erp_code, $fecha_deposito, $moneda, $cuenta, $deposito_bancario, $TABLA, $ID_EXTORNO, $PROCESO);
                                //EXTORNO FINALIZADO
                                $arrayWhere = array(
                                    ['id', '=', $ID_EXTORNO]
                                );
                                $arrayUpdate = array('updated_at' => date("Y-m-d H:i:s"));
                                $this->actualiza_tabla_postgres('bank_return_requests', $arrayWhere, $arrayUpdate);
                                echo '<BR>EXTORNO EXPORTADO A AS400...';
                            }
                        }
                    } else {
                        echo "<br>DEPOÓSITO NO ENCONTRADO: $codCia --- Suc.: $sucursal_deposito --- Bank: " . $bankAccount[0]->erp_code . " --- Dep.: $operationId ---- Fecha: $fecha_deposito";
                        //exit;
                    }
                } else //EXTORNO DOCUMENTO DE CONTADO
                {
                    $PROCESO = 2;
                    echo "<br>EXTORNAMOS DOCUMENTO A CONTADO :".$documento->documentId;
                    $tipo_documento = $arrayNroDoc[1];
                    $numero_documento = $arrayNroDoc[2];
                    $monto_pagado_documento = (float) $documento->amounts[0]->amount;
                    //echo '<pre>';
                    //print_r($documento);
                    //echo "<br>$customerIdentificationCode - $codCia  - $tipo_documento - $numero_documento - $monto_pagado_documento<br>";
                    $regSaldo = $this->retorna_doc_cliente_saldo_interface_ped($codCia, $customerIdentificationCode, $tipo_documento, $numero_documento);
                    //die(print_r($regSaldo));

                    $codigo_cliente = $regSaldo->ABCODCLI;
                    //$tipo_identificacion_cliente = $regSaldo->AKTIPIDE;
                    //$numero_identificacion_cliente = $regSaldo->NUMERO_IDENTIFICACION;
                    $sucursal = $regSaldo->ABCODSUC;
                    //$tipo_documento = $regSaldo->ABTIPDOC;
                    //$numero_documento = $regSaldo->ABNRODOC;
                    $fecha_deposito = \Carbon\Carbon::parse($extorno->created_at)->format('Ymd');

                    if ($datosDeposito = $this->retorna_datos_deposito_bancario($codCia, $sucursal, $bankAccount[0]->erp_code, $operationId, $fecha_deposito)) {
                        $DP_nro_interno = $datosDeposito->ypdepint;
                        $numero_boleta_deposito = $datosDeposito->ypnrodep;
                        $fecha_deposito = $datosDeposito->ypfecdep;
                        $importe_deposito = $datosDeposito->ypimpdep;
                        $importe_aplicado = $datosDeposito->ypimpapl;
                        $cuenta = $datosDeposito->ypnrocta;
                        $moneda = $datosDeposito->ypcodmon;
                        $deposito_bancario = $datosDeposito->ypnroopr;
                        $sucursal_deposito = $datosDeposito->ypcodsuc;
                        $banco_deposito  = $datosDeposito->ypcodbco;


                        $regSaldo = Null;
                        if ($regSaldo = $this->retorna_registro_saldo_documento($codCia, $sucursal, $codigo_cliente, $tipo_documento, $numero_documento, $monto_pagado_documento)) {
                            $saldo_actual = null;
                            $saldo_as400 = null;
                            $importe_documento = 0;
                            $saldo_as400 = round(floatval($regSaldo->eiimpsld), 2);
                            $importe_total_as400 = round(floatval($regSaldo->eiimpccc), 2);
                            $importe_documento = $importe_total_as400;
                            $monto_pagado_documento = round(floatval($monto_pagado_documento), 2);
                            $saldo = round(($saldo_as400 + $monto_pagado_documento), 2);
                            $saldo_actual = $saldo;
                        }

                        if (!$this->verifica_paso_proceso(1, $PROCESO, $TABLA, $ID_EXTORNO)) {
                            //Desactivar Depósito Bancario (Tabla MMYPREP)
                            $this->desactivar_deposito_bancario($codCia, $sucursal_deposito, $banco_deposito, $operationId, $fecha_deposito, $TABLA, $ID_EXTORNO, $PROCESO);
                        }


                        if (!$regPlanillaCobranzas = $this->retorna_planilla_contado_actual_db2($codCia, $sucursal, $fecha_deposito)) {
                            die('PLANILLA DE DEPÓSITO NO ENCONTRADA');
                        } else {

                            $correlativo_planilla_cobranzas = $regPlanillaCobranzas->cjapnpll;

                            //DESACTIVA REGISTRO EN TABLA MMCJFCB
                            if (!$this->verifica_paso_proceso(12, $PROCESO, $TABLA, $ID_EXTORNO)) {
                                echo "<br>DESACTIVA REGISTRO EN TABLA MMCJFCB<br>";
                                if ($this->get_from_table_MMCJFCB($codCia, $sucursal, $codigo_cliente, $tipo_documento, $numero_documento)) {
                                    $this->desactiva_registro_tabla_MMCJFCB($codCia, $sucursal, $codigo_cliente, $tipo_documento, $numero_documento, $TABLA, $ID_EXTORNO, $PROCESO);
                                    echo ' -> Actualizado';
                                }
                            }

                            ///DESACTIVA REGISTRO EN TABLA MMCJFDT
                            if (!$this->verifica_paso_proceso(13, $PROCESO, $TABLA, $ID_EXTORNO)) {
                                echo "<br>DESACTIVA REGISTRO EN TABLA MMCJFDT<br>";
                                if ($this->retorna_det_pagos($codCia, $sucursal, $numero_documento, $correlativo_planilla_cobranzas, $numero_boleta_deposito)) {
                                    $this->desactiva_registro_tabla_MMCJFDT($codCia, $sucursal, $numero_documento, $correlativo_planilla_cobranzas, $TABLA, $ID_EXTORNO, $PROCESO);
                                    echo ' -> Actualizado';
                                } else {
                                    return $this->registra_paso_proceso(13, $PROCESO, $TABLA, $ID_EXTORNO);
                                }
                            }

                            if (!$this->verifica_paso_proceso(5, $PROCESO, $TABLA, $ID_EXTORNO)) {
                                //Actualizar saldo tabla auxiliar (Tabla CCAPLBCO)
                                $this->actualizar_saldo_tabla_auxiliar($codCia, $sucursal, $codigo_cliente, $tipo_documento, $numero_documento, $monto_pagado_documento, $TABLA, $ID_EXTORNO, $PROCESO);
                            }

                            if (!$this->verifica_paso_proceso(6, $PROCESO, $TABLA, $ID_EXTORNO)) {
                                if ($regSaldo) {
                                    if ($saldo <= $importe_total_as400) {
                                        //Actualizar saldo en tabla principal (Tabla MMEIREP)
                                        $this->actualizar_saldo_tabla_principal($codCia, $sucursal, $codigo_cliente, $tipo_documento, $numero_documento, $monto_pagado_documento, $regSaldo, $TABLA, $ID_EXTORNO, $PROCESO);
                                    } else {
                                        echo '<br>SALDO DEBE SER MENOR O IGUAL QUE IMPORTE TOTAL DE DOCUMENTO';
                                        echo "<br>--Importe AS400: $importe_total_as400 - Saldo AS400: $saldo_as400 - Importe Pagado Documento: $monto_pagado_documento - Saldo: $saldo <br>";
                                        exit;
                                    }
                                }
                            }

                            if (!$this->verifica_paso_proceso(7, $PROCESO, $TABLA, $ID_EXTORNO)) {
                                //Desactivar Registro de depósito en históricos de pagos HIJO (Tabla MMEJREP)
                                echo ' xxx ';
                                $this->actualiza_tabla_historico_saldos_hijo($codCia, $sucursal, $codigo_cliente, '81', $numero_boleta_deposito, $importe_deposito, $TABLA, $ID_EXTORNO, $PROCESO);
                            }


                            if (!$this->verifica_paso_proceso(8, $PROCESO, $TABLA, $ID_EXTORNO)) {
                                //DESACTIVAR REGISTRO PADRE (Tabla MMEJREP)
                                $this->actualiza_tabla_historico_saldos_padre($codCia, $sucursal, $codigo_cliente, $tipo_documento, $numero_documento, $importe_documento, $regSaldo, $TABLA, $ID_EXTORNO, $PROCESO);
                            }

                            if (!$this->verifica_paso_proceso(9, $PROCESO, $TABLA, $ID_EXTORNO)) {
                                //Desactivar registro en tabla de aplicaciones (Tabla MMELREP)
                                $this->desactivar_registro_tabla_aplicaciones_mmelrep($codCia, $sucursal, $codigo_cliente, $numero_documento, $numero_boleta_deposito, $TABLA, $ID_EXTORNO, $PROCESO);
                            }

                            if (!$this->verifica_paso_proceso(10, $PROCESO, $TABLA, $ID_EXTORNO)) {
                                //Accion tabla mmcdreca
                                $this->elimina_registro_tabla_mmcdreca($codCia, $bankAccount[0]->erp_code, $fecha_deposito, $moneda, $cuenta, $deposito_bancario, $TABLA, $ID_EXTORNO, $PROCESO);
                                //EXTORNO FINALIZADO
                                $arrayWhere = array(
                                    ['id', '=', $ID_EXTORNO]
                                );
                                $arrayUpdate = array('updated_at' => date("Y-m-d H:i:s"));
                                $this->actualiza_tabla_postgres('bank_return_requests', $arrayWhere, $arrayUpdate);
                                echo '<BR>EXTORNO EXPORTADO A AS400...';
                            }
                        }
                    }
                } //FIN IF
            } //FIN FOREACH
            echo "<br>FIN EXTORNO :".$extorno->id;
        }// FIN RECORRIDO EXTORNO 
    }

    public function registra_paso_proceso($PASO, $PROCESO, $TABLA, $ID)
    {
        echo "<br>PROCESO: $PROCESO - PASO: $PASO - TABLA: $TABLA - ID: $ID";
        $arrayInsert = array(
            'log_table' => $TABLA,
            'log_table_id' => $ID,
            'process_id' => $PROCESO,
            'step_id' => $PASO,
            'reg_status' => 1,
            'created_at' => date("Y-m-d H:i:s"),
        );
        return DB::table('sync_logs')
            ->insert([$arrayInsert]);
    }

    public function verifica_paso_proceso($PASO, $PROCESO, $TABLA, $ID)
    {
        $arrayWhere = array(
            ['log_table', '=', $TABLA],
            ['log_table_id', '=', $ID],
            ['process_id', '=', $PROCESO],
            ['step_id', '=', $PASO],
        );
        $result = $this->selecciona_from_tabla('sync_logs', $arrayWhere);
        return (is_array($result) && sizeof($result) > 0) ? $result[0] : false;
    }

    public function retorna_pasos_faltantes_extornos($proceso, $tabla, $id)
    {
        $rs = DB::table('sync_steps AS s')
            ->join('sync_process_steps AS ps', 's.id', '=', 'ps.step_id')
            ->leftJoin(
                'sync_logs as l',
                'ps.step_id',
                '=',
                DB::raw('l.step_id AND ps.process_id = l.process_id')
            )
            ->where('ps.process_id', '=', $proceso)
            //->where('l.log_table', '=', $tabla)
            ->where('l.log_table_id', '=', $id)
            //->get()->toArray();
            ->toSql();
        die($rs);

        //'l.step_id', 'and', 'ps.process_id', '=', 'l.process_id'

        return $rs = (is_array($rs) && sizeof($rs) > 0) ? $rs : array();
    }

    public function retorna_procesos_sync_extornos()
    {
        $procesos = array(
            '1' => 'DESACTIVAR DEPÓSITO BANCARIO',
            '2' => 'ACTUALIZAR MMDMREP',
            '3' => 'DESACTIVAR REGISTRO MMDNREP',
            '4' => 'DESACTIVAR REGISTRO MMDOREP',
            '5' => 'ACTUALIZAR SALDO EN AUXILIAR DE SALDOS',
            '6' => 'ACTUALIZAR SALDO EN PRINCIPAL DE SALDOS',
            '7' => 'HISTÓRICOS SALDOS (HIJO)',
            '8' => 'HISTÓRICOS SALDOS (PADRE)',
            '9' => 'DESACTIVAR REGISTRO MMELREP',
            '10' => 'TABLA MMCDRECA',
            '11' => 'TABLA MMCCRECA',
        );
        return $procesos;
    }


    public function retorna_datos_deposito_bancario($codCia, $sucursal, $ErpBankCode, $operationId, $fecha_deposito)
    {
        $sucursal = ($sucursal === '02') ? '01' : $sucursal;
        $arrayWhere = array(
            ['YPCODCIA', '=', $codCia],
            ['YPCODSUC', '=', $sucursal],
            ['YPCODBCO', '=', $ErpBankCode],
            ['YPNROOPR', '=', $operationId],
            ['YPFECDEP', '=', $fecha_deposito],
        );
        //echo '<pre>';
        //print_r($arrayWhere);
        //exit;
        return $this->selecciona_from_tabla_db2('LIBPRDDAT.MMYPREP', $arrayWhere);
    }

    public function desactivar_deposito_bancario($codCia, $sucursal, $ErpBankCode, $operationId, $fecha_deposito, $TABLA, $ID, $PROCESO = 1)
    {
        $PASO = 1;
        echo '<br>DESACTIVAR DEPÓSITO: ' . $operationId;
        $arrayWhereYP = array(
            ['YPCODCIA', '=', $codCia],
            ['YPCODSUC', '=', $sucursal],
            ['YPCODBCO', '=', $ErpBankCode], //$bankAccount[0]->erp_code],
            ['YPNROOPR', '=', $operationId],
            ['YPFECDEP', '=', $fecha_deposito],
        );
        if ($this->selecciona_from_tabla_db2('LIBPRDDAT.MMYPREP', $arrayWhereYP)) {
            $arrayUpdate = array(
                'YPSTS' => 'I',
                'YPUSRC' => $this->user,
                'YPJDTC' => date("Ymd"),
                'YPJTMC' => date("His"),
                'YPPGMC' => $this->app
            );
            $this->actualiza_tabla_db2('LIBPRDDAT.MMYPREP', $arrayWhereYP, $arrayUpdate);
            $this->registra_paso_proceso($PASO, $PROCESO, $TABLA, $ID);
        } else {
            echo "<br>DEPOSITO " . $operationId . " NO ENCONTRADO<br>";
            exit;
        }
    }

    public function retorna_planilla_cobranzas($codCia, $sucursal, $fecha_deposito, $codCobrador)
    {
        $sucursal = ($sucursal === '02') ? '01' : $sucursal;
        $arrayWhere = array(
            ['DLCODCIA', '=', $codCia],
            ['DLCODSUC', '=', $sucursal],
            ['DLFECPLL', '=', $fecha_deposito],
            ['DLCODCBR', '=', $codCobrador]
        );
        return $this->selecciona_from_tabla_db2('LIBPRDDAT.MMDLREP', $arrayWhere);
    }

    public function actualiza_monto_planilla_cobranzas($codCia, $correlativo_planilla_cobranzas, $codigo_cliente, $tipo_documento, $numero_documento, $monto_pagado_documento, $TABLA, $ID, $PROCESO = 1)
    {
        $PASO = 2;
        $arrayWhere = array(
            ['DMCODCIA', '=', $codCia],
            ['DMSTS', '=', 'A'],
            ['DMNROPLL', '=', $correlativo_planilla_cobranzas],
            ['DMCODCLI', '=', $codigo_cliente],
            ['DMTIPDOC', '=', $tipo_documento],
            ['DMNRODOC', '=', $numero_documento]
        );
        //ACTUALIZA MONTO EN PLANILLA DE COBRANZAS (MMDMREP)
        echo "<br>ACTUALIZA MONTO EN PLANILLA DE COBRANZAS (MMDMREP)";
        $cliente_edo_cta_doc = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMDMREP', $arrayWhere);
        $monto_ccc = (round(floatval($cliente_edo_cta_doc->dmimpccc), 2) - $monto_pagado_documento);
        $monto_inf = (round(floatval($cliente_edo_cta_doc->dmimpinf), 2) - $monto_pagado_documento);

        if ($monto_ccc >= 0) {
            $arrayUpdate = array(
                'DMIMPCCC' => $monto_ccc,
                'DMIMPINF' => $monto_inf,
                'DMSTS' => 'I'
            );
            if (!$this->actualiza_tabla_db2('LIBPRDDAT.MMDMREP', $arrayWhere, $arrayUpdate)) {
                echo "<br>ERROR ACTUALIZANDO PLANILLA</br>";
            }
            $this->registra_paso_proceso($PASO, $PROCESO, $TABLA, $ID);
        } else {
            echo "<br>PLANILLA NO ACTUALIZADA: monto_ccc = $monto_ccc";
        }
    }


    public function desactivar_registro_tabla_mmdnrep($codCia, $correlativo_planilla_cobranzas, $numero_boleta_deposito, $codigo_cliente, $TABLA, $ID, $PROCESO = 1)
    {
        $PASO = 3;
        echo "<br>DESACTIVAR REGISTRO EN TABLA MMDNREP (RELACIÓN DEPÓSITO-CLIENTE-PLANILLA)";
        $arrayWhere = array(
            ['DNCODCIA', '=', $codCia],
            ['DNNROPLL', '=', $correlativo_planilla_cobranzas],
            ['DNNROBOL', '=', $numero_boleta_deposito],
            ['DNCODCLI', '=', $codigo_cliente],
        );

        if (!$this->selecciona_from_tabla_db2('LIBPRDDAT.MMDNREP', $arrayWhere)) {
            die('DEPÓSITO NO EXISTE');
        } else {
            $arrayUpdate = array(
                'DNSTS' => 'I'
            );
            if ($this->actualiza_tabla_db2('LIBPRDDAT.MMDNREP', $arrayWhere, $arrayUpdate)) {
                echo "<br>REGISTRO DESACTIVADO: Correlativo Planilla Cob.: $correlativo_planilla_cobranzas - Nro. Boleta Deposito: $numero_boleta_deposito - - Cliente: $codigo_cliente";
                $this->registra_paso_proceso($PASO, $PROCESO, $TABLA, $ID);
            } else {
                echo "<br>*** ERROR: NO SE DESACTIVÓ REGISTRO -> Correlativo Planilla Cob.: $correlativo_planilla_cobranzas - Nro. Boleta Deposito: $numero_boleta_deposito - Cliente: $codigo_cliente";
            }
        }
    }

    public function desactivar_registro_tabla_mmdorep($codCia, $correlativo_planilla_cobranzas, $numero_boleta_deposito, $codigo_cliente, $tipo_documento, $numero_documento, $TABLA, $ID, $PROCESO = 1)
    {
        $PASO = 4;
        //TABLA MMDOREP (RELACIÓN PLANILLA-DEPÓSITO-CLIENTE-DOCUMENTO)
        //DESACTIVAR REGISTRO EN TABLA MMDOREP (RELACIÓN PLANILLA-DEPÓSITO-CLIENTE-DOCUMENTO)
        echo "<br>DESACTIVAR REGISTRO EN TABLA MMDOREP (RELACIÓN PLANILLA-DEPÓSITO-CLIENTE-DOCUMENTO)";
        $arrayWhere = array(
            ['DOCODCIA', '=', $codCia],
            ['DONROPLL', '=', $correlativo_planilla_cobranzas],
            ['DONROBOL', '=', $numero_boleta_deposito],
            ['DOCODCLI', '=', $codigo_cliente],
            ['DOTIPDOC', '=', $tipo_documento],
            ['DONRODOC', '=', $numero_documento]
        );
        if (!$this->selecciona_from_tabla_db2('LIBPRDDAT.MMDOREP', $arrayWhere)) {
            die('REGISTRO NO EXISTE');
        } else {
            $arrayUpdate = array(
                'DOSTS' => 'I'
            );

            if ($this->actualiza_tabla_db2('LIBPRDDAT.MMDOREP', $arrayWhere, $arrayUpdate)) {
                echo "<br>REGISTRO DESACTIVADO -> CLIENTE: $codigo_cliente - CORRELATIVO PLANILLA: $correlativo_planilla_cobranzas - DEPOSITO: $numero_boleta_deposito - NRO DOCUMENTO: $numero_documento";
                $this->registra_paso_proceso($PASO, $PROCESO, $TABLA, $ID);
            } else {
                echo "<br>*** ERROR: *** ";
                print_r($arrayWhere);
            }
        }
        //FIN DESACTIVAR REGISTRO EN TABLA MMDOREP (RELACIÓN PLANILLA-DEPÓSITO-CLIENTE-DOCUMENTO)
    }

    public function actualizar_saldo_tabla_auxiliar($codCia, $sucursal, $codigo_cliente, $tipo_documento, $numero_documento, $monto_pagado_documento, $TABLA, $ID, $PROCESO = 1)
    {
        $PASO = 5;
        //ACTUALIZAR TABLA CCAPLBCO
        echo "<BR>ACTUALIZAR TABLA DE SALDOS AUXILIAR (CCAPLBCO)";
        $arrayWhere = array(
            ['ABCODCIA', '=', $codCia],
            ['ABCODSUC', '=', $sucursal],
            ['ABCODCLI', '=', $codigo_cliente],
            ['ABTIPDOC', '=', $tipo_documento],
            ['ABNRODOC', '=', $numero_documento],
        );
        if ($regAuxSaldo = $this->selecciona_from_tabla_db2('LIBPRDDAT.CCAPLBCO', $arrayWhere)) {
            $saldo_as400 = round(floatval($regAuxSaldo->abimpsld), 2);
            $importe_total_as400 = round(floatval($regAuxSaldo->abimpccc), 2);
            $monto_pagado_documento = round(floatval($monto_pagado_documento), 2);
            $saldo = round(($saldo_as400 + $monto_pagado_documento), 2);

            if ($saldo <= $importe_total_as400) {
                $arrayUpdate = array(
                    'ABSTS' => 'A',
                    'ABIMPSLD' => $saldo,
                    'FECEXWEB' => date("Ymd"),
                    'HMSEXWEB' => date("His"),
                    'RUPDATE' => '0'
                );
                if ($this->actualiza_tabla_db2('LIBPRDDAT.CCAPLBCO', $arrayWhere, $arrayUpdate)) {
                    echo "<br>SALDO ACTUALIZADO EN TABLA CCPLBCO";
                    $this->registra_paso_proceso($PASO, $PROCESO, $TABLA, $ID);
                } else {
                    echo "<br>*** ERROR: NO SE ACTUALIZÓ SALDO EN TABLA CCPLBCO";
                }
            } else {
                echo "<br>*** ERROR: SALDO DEBE SER MENOR O IGUAL QUE MONTO DOCUMENTO";
                echo "<br>*Saldo: $saldo - Monto pagado: $monto_pagado_documento - Importe total AS400: $importe_total_as400 - Saldo AS400: $saldo_as400";
            }
        }
        //FIN ACTUALIZAR TABLA CCAPLBCO
    }

    public function retorna_registro_saldo_documento($codCia, $sucursal, $codigo_cliente, $tipo_documento, $numero_documento, $monto_pagado_documento)
    {
        $arrayWhere = array(
            ['EICODCIA', '=', $codCia],
            ['EICODSUC', '=', $sucursal],
            ['EICODCLI', '=', $codigo_cliente],
            ['EITIPDOC', '=', $tipo_documento],
            ['EINRODOC', '=', $numero_documento],
        );
        return $this->selecciona_from_tabla_db2('LIBPRDDAT.MMEIREP', $arrayWhere);
    }

    public function actualizar_saldo_tabla_principal($codCia, $sucursal, $codigo_cliente, $tipo_documento, $numero_documento, $monto_pagado_documento, $regSaldo, $TABLA, $ID, $PROCESO = 1)
    {
        $PASO = 6;
        //ACTUALIZAR TABLA MMEIREP (PRINCIPAL DE SALDOS)
        echo "<br>ACTUALIZAR TABLA MMEIREP (PRINCIPAL DE SALDOS)";
        $arrayWhere = array(
            ['EICODCIA', '=', $codCia],
            ['EICODSUC', '=', $sucursal],
            ['EICODCLI', '=', $codigo_cliente],
            ['EITIPDOC', '=', $tipo_documento],
            ['EINRODOC', '=', $numero_documento],
        );
        if ($regSaldo) {
            $saldo_as400 = round(floatval($regSaldo->eiimpsld), 2);
            $importe_total_as400 = round(floatval($regSaldo->eiimpccc), 2);
            $monto_pagado_documento = round(floatval($monto_pagado_documento), 2);
            $saldo = round(($saldo_as400 + $monto_pagado_documento), 2);

            if ($saldo <=  $importe_total_as400) {
                $arrayUpdate = array(
                    'EISTS' => 'A',
                    'EIIMPSLD' => $saldo,
                );
                if ($this->actualiza_tabla_db2('LIBPRDDAT.MMEIREP', $arrayWhere, $arrayUpdate)) {
                    echo "<br>SALDO ACTUALIZADO EN TABLA MMEIREP";
                    $this->registra_paso_proceso($PASO, $PROCESO, $TABLA, $ID);
                } else {
                    echo "<br>*** ERROR: NO SE ACTUALIZÓ SALDO EN TABLA CCPLBCO";
                }
            } else {
                echo "<br>*** ERROR: SALDO DEBE SER MENOR O IGUAL QUE MONTO DOCUMENTO";
            }
        }
    }


    public function actualiza_tabla_historico_saldos_hijo($codCia, $sucursal_deposito, $codigo_cliente, $tipo_documento = '81', $numero_boleta_deposito, $importe_deposito, $TABLA, $ID, $PROCESO)
    {
        $PASO = 7;
        // ACTUALIZAR TABLA DE SALDOS HISTORICOS (HIJO)
        echo "<br>DESACTIVAR REGISTRO EN TABLA DE SALDOS HISTORICOS (HIJO)";
        $arrayWhere = array(
            ['EJCODCIA', '=', $codCia],
            ['EJCODSUC', '=', $sucursal_deposito],
            ['EJCODCLI', '=', $codigo_cliente],
            ['EJTIPDOC', '=', $tipo_documento],
            ['EJNRODOC', '=', $numero_boleta_deposito],
            ['EJIMPCCC', '=', $importe_deposito],
            ['EJFECCAN', '=', date("Ymd")],
            ['EJSTS', '=', 'A']
        );
        $arrayUpdate = array(
            'EJSTS' => 'I'
        );
        if ($this->actualiza_tabla_db2('LIBPRDDAT.MMEJREP', $arrayWhere, $arrayUpdate)) {
            echo "<br>SE DESACTIVÓ REGISTRO EN TABLA DE SALDOS HISTÓRICOS - HIJO";
            $this->registra_paso_proceso($PASO, $PROCESO, $TABLA, $ID);
        } else {
            echo "<br>***ERROR: NO SE ACTUALIZÓ TABLA DE SALDOS HISTÓRICOS - HIJO";
        }
        // FIN ACTUALIZAR TABLA DE SALDOS HISTORICOS (HIJO)


    }

    public function actualiza_tabla_historico_saldos_padre($codCia, $sucursal, $codigo_cliente, $tipo_documento, $numero_documento, $importe_documento, $regSaldo, $TABLA, $ID, $PROCESO = 1)
    {
        $PASO = 8;
        // ACTUALIZAR TABLA HISTÓRICO DE SALDOS (MMEJREP) PADRE (AREA CRÉDITO)
        echo "<br>DESACTIVAR REGISTRO EN TABLA HISTÓRICO DE SALDOS (MMEJREP) PADRE";
        $arrayWhere = array(
            ['EJCODCIA', '=', $codCia],
            ['EJCODSUC', '=', $sucursal],
            ['EJCODCLI', '=', $codigo_cliente],
            ['EJTIPDOC', '=', $tipo_documento],
            ['EJNRODOC', '=', $numero_documento],
            ['EJIMPCCC', '=', $importe_documento], //regSaldo->ABIMPCCC
            ['EJIMPSLD', '=', 0],
            ['EJSTS', '=', 'A'], //Estado de registro
            ['EJSTSDOC', '=', 'A'],  //Estado de Documento
            ['EJSTSCOA', '=', 'C']  //Estado de Cargo/Abon
        );
        $arrayUpdate = array(
            'EJSTS' => 'I'
        );
        if ($this->actualiza_tabla_db2('LIBPRDDAT.MMEJREP', $arrayWhere, $arrayUpdate)) {
            echo "<br>SE DESACTIVÓ REGISTRO EN TABLA DE SALDOS HISTÓRICOS - PADRE";
            $this->registra_paso_proceso($PASO, $PROCESO, $TABLA, $ID);
        } else {
            echo "<br>***ERROR: NO SE ACTUALIZÓ TABLA DE SALDOS HISTÓRICOS - PADRE";
        }
        //FIN ACTUALIZAR HISTÓRICO DE SALDOS PADRE
    }

    public function desactivar_registro_tabla_aplicaciones_mmelrep($codCia, $sucursal, $codigo_cliente, $numero_documento, $numero_boleta_deposito, $TABLA, $ID, $PROCESO = 1)
    {
        $PASO = 9;
        // ACTUALIZAR TABLA DE APLICACIONES MMELREP
        echo "<br>ACTUALIZAR REGISTRO EN TABLA DE APLICACIONES MMELREP";
        $arrayWhere = array(
            ['ELSTS', '=', 'A'],
            ['ELCIAPDR', '=', $codCia],
            ['ELSUCPDR', '=', $sucursal],
            ['ELCLIPDR', '=', $codigo_cliente],
            ['ELDOCPDR', '=', $numero_documento],
            ['ELTIPHIJ', '=', '81'],
            ['ELDOCHIJ', '=', $numero_boleta_deposito]
        );
        $arrayUpdate = array(
            'ELSTS' => 'I'
        );
        if ($this->actualiza_tabla_db2('LIBPRDDAT.MMELREP', $arrayWhere, $arrayUpdate)) {
            echo "<br>SE DESACTIVÓ REGISTRO EN TABLA DE APLICACIONES";
            $this->registra_paso_proceso($PASO, $PROCESO, $TABLA, $ID);
        } else {
            echo "<br>***ERROR: NO SE ACTUALIZÓ TABLA DE APLICACIONES";
            echo '<pre>';
            die(print_r($arrayWhere));
        }
        // FIN ACTUALIZAR TABLA DE APLICACIONES MMELREP
    }

    public function elimina_registro_tabla_mmcdreca($cia, $banco, $fecha, $moneda, $nro_cuenta, $nro_operacion, $TABLA, $ID, $PROCESO)
    {
        $PASO = 10;
        $arrayWhere = array(
            ['CDCODCIA', '=', $cia],
            ['CDCODBCO', '=', $banco],
            ['CDFECPRO', '=', $fecha],
            ['CDCODMON', '=', $moneda],
            ['CDNROCTA', '=', $nro_cuenta],
            ['CDNROOPE', '=', $nro_operacion],
        );
        $deleted = DB::connection('ibmi')->table('LIBPRDDAT.MMCDRECA')
            ->where($arrayWhere)
            ->delete();
        echo '<br>Cantidad Eliminados MMCDRECA: ' . $deleted;
        $this->registra_paso_proceso($PASO, $PROCESO, $TABLA, $ID);
    }

    public function restar_totales_tabla_mmccreca($cia, $banco, $fecha, $moneda, $nro_cuenta, $monto_a_restar, $TABLA, $ID, $PROCESO)
    {
        $PASO = 11;
        $arrayWhere = array(
            ['CCCODCIA', '=', $cia],
            ['CCCODBCO', '=', $banco],
            ['CCFECPRO', '=', $fecha],
            ['CCCODMON', '=', $moneda],
            ['CCNROCTA', '=', $nro_cuenta],
        );
        if (!$datos_mmccreca = DB::connection('ibmi')->table('LIBPRDDAT.MMCCRECA')
            ->where($arrayWhere)
            ->first()) {
            die("<br>REGISTRO NO ENCONTRADO EN MMCCRECA");
        }
        $importe_total = floatval(round($datos_mmccreca->ccimptot, 2));
        $importe_saldo = floatval(round($datos_mmccreca->ccimpsal, 2));
        $monto_a_restar = floatval(round($monto_a_restar, 2));
        if ($importe_total - $monto_a_restar < 0) {
            die("<br>IMPORTE TOTAL NO DEBE SER MENOR DE CERO");
        }
        $arrayUpdate = array(
            'CCIMPTOT' => ($importe_total < $monto_a_restar),
            'CCIMPSAL' => ($importe_total < $monto_a_restar)
        );
        $this->actualiza_tabla_db2('LIBPRDDAT.MMCCRECA', $arrayWhere, $arrayUpdate);
        $this->registra_paso_proceso($PASO, $PROCESO, $TABLA, $ID);
    }

    public function get_bank_payments_for_update_db2()
    {
        $rs = DB::table('customer_payments')
            ->where('fecha_hora_actualizacion_db2', '=', null)
            ->where('return_request_id', '=', null)
            ->orderBy('is_sync','ASC')
            ->get()
            ->toArray();
        return (is_array($rs) && sizeof($rs) > 0) ? $rs : false;
    }

    public function get_bank_return_requests_for_update_db2()
    {
        $rs = DB::table('bank_return_requests AS ext')
            ->join('customer_payments AS pay', 'ext.id', '=', 'pay.return_request_id')
            ->where('pay.fecha_hora_actualizacion_db2', '<>', null)
            ->where('ext.updated_at', '=', null)
            ->select(['ext.*', 'pay.id AS payment_id', 'pay.paymentType', 'pay.operationNumber', 'pay.operationId', 'pay.serviceId', 'pay.paidDocuments', 'pay.check', 'pay.currencyCode']) //
            ->orderBy('is_sync','ASC')
            ->get()
            ->toArray();
        return (is_array($rs) && sizeof($rs) > 0) ? $rs : false;
    }

    public function get_customer_debts_paid_by_payment_id($payment_id, $customerIdentificationCode)
    {
        $rs = DB::table('cliente_saldos')
            ->join('customer_debts_payments', 'cliente_saldos.id', '=', 'customer_debts_payments.id_deuda')
            ->where('cliente_saldos.fecha_hora_actualizacion_db2', '=', null)
            //->where('cliente_saldos.ABSTS', '=', 'A')
            ->where('customer_debts_payments.payment_id', '=', $payment_id)
            ->where('cliente_saldos.NUMERO_IDENTIFICACION', '=', $customerIdentificationCode)
            ->select(['cliente_saldos.*'])
            ->get()->toArray();
        return (is_array($rs) && sizeof($rs) > 0) ? $rs : false;
    }

    public function leer_nuevos_registros_db2()
    {
        $registros = DB::connection('ibmi')
            ->table($this->vista_registros_nuevos_db2)
            ->get()->toArray();
        /*
        foreach ($registros as $registro) {
            if ($registro->absts === 'A') {
                if ($this->verifica_es_factura_consolidada($registro)) {
                    $registro->absts = 'I';
                }
            }
        }
        */
        $clientIP = request()->ip();
        $arrayIn = array(
            'tabla' => 'LIBPRDDAT.MMCJFGT',
            'mensaje' => 'registro nuevos '.$clientIP.' fecha'. date("d-m-Y H:i:s"),
            'otro' => json_encode($registros)
        );
        DB::table('log_migraciones')->insert($arrayIn);
        return $registros;
    }

    public function verifica_es_factura_consolidada($registro)
    {
        return DB::connection('ibmi')->table('LIBPRDDAT.CCDDFCNEG')
            ->where('FNCODCIA', $registro->abcodcia)
            ->where('FNCODSUC', $registro->abcodsuc)
            ->where('FNCODCLI', $registro->abcodcli)
            ->where('FNTIPDOC', $registro->abtipdoc)
            ->where('FNNRODOC', $registro->abnrodoc)
            ->where('AASTS', 'A')
            ->first();
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

    public function retorna_doc_cliente_saldo_interface($codCia, $codSuc, $customerIdentificationCode, $docType, $docNumber)
    {
        return DB::table('cliente_saldos')
            ->where('ABCODCIA', '=', $codCia)
            ->where('ABCODSUC', '=', $codSuc)
            ->where('NUMERO_IDENTIFICACION', '=', $customerIdentificationCode)
            ->where('ABTIPDOC', '=', $docType)
            ->where('ABNRODOC', '=', $docNumber)
            ->first();
    }

    public function retorna_doc_cliente_saldo_interface_ped($codCia, $customerIdentificationCode, $docType, $docNumber)
    {
        return DB::table('cliente_saldos')
            ->where('ABCODCIA', '=', $codCia)
            ->where('NUMERO_IDENTIFICACION', '=', $customerIdentificationCode)
            ->where('ABTIPDOC', '=', $docType)
            ->where('ABNRODOC', '=', $docNumber)
            ->first();
    }

    public function retorna_doc_cliente_saldo_interface_fac($serieNumber, $docNumber)
    {
        return DB::table('cliente_saldos')
            ->where('CBNROSER', '=', $serieNumber)
            ->where('CBNROCOR', '=', $docNumber)
            ->first();
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

    public function  get_max_deposito_db2($codCia)
    {
        return DB::connection('ibmi')
            ->table('MMYPREL3')
            ->where('YPCODCIA', '=', $codCia)
            ->max('YPDEPINT');
    }

    public function get_bank_accounts($codCia = '10', $banksbsCode = '', $currencyCode = '', $accountType = '', $accountCode = '')
    {
        $arrayWhere = array();
        array_push($arrayWhere,  ['banks.active', '=', true]);
        array_push($arrayWhere,  ['bank_accounts.active', '=', true]);
        if (!empty($codCia)) array_push($arrayWhere,  ['banks.cod_cia', '=', $codCia]);
        if (!empty($banksbsCode)) array_push($arrayWhere,  ['sbs_code', '=', $banksbsCode]);
        if (!empty($currencyCode)) array_push($arrayWhere,  ['currency_code', '=', $currencyCode]);
        if (!empty($accountType)) array_push($arrayWhere,  ['account_type', '=', $accountType]);
        if (!empty($accountCode)) array_push($arrayWhere,  ['account_code', '=', $accountCode]);

        $rs = DB::table('banks')
            ->join('bank_accounts', 'banks.id', '=', 'bank_accounts.bank_id')
            ->where($arrayWhere)
            ->select(['bank_id', 'cod_cia', 'erp_code', 'sbs_code', 'description as banco', 'bank_accounts.id as account_id', 'currency_code as moneda', 'account_code as nro_cuenta'])
            ->get()->toArray();
        //->toSql();
        return $rs;
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

    public function getMaxIdFromTableDb2($table, $arrayWhere, $maxField)
    {
        return DB::connection('ibmi')
            ->table($table)
            ->where($arrayWhere)
            ->max($maxField);
    }

    public function get_from_table_MMCJFCB($codCia, $codSuc, $customerCode, $docType, $docNumber)
    {
        return DB::connection('ibmi')
            ->table('LIBPRDDAT.MMCJFCB')
            ->where('CJCBEST', '=', 'A')
            ->where('CJCBCCIA', '=', $codCia)
            ->where('CJCBCSUC', '=', $codSuc)
            ->where('CJCBCCLI', '=', $customerCode)
            ->where('CJCBTIPD', '=', $docType)
            ->where('CJCBNPDC', '=', $docNumber)
            ->first();
    }

    public function desactiva_registro_tabla_MMCJFCB($codCia, $sucursal, $codigo_cliente, $tipo_documento, $numero_documento, $TABLA, $ID, $PROCESO)
    {
        $PASO = 12;
        $arrayUpdate = array('CJCBEST' => 'I');
        $arrayWhere = array(
            ['CJCBCCIA', '=', $codCia],
            ['CJCBCSUC', '=', $sucursal],
            ['CJCBCCLI', '=', $codigo_cliente],
            ['CJCBTIPD', '=', $tipo_documento],
            ['CJCBNPDC', '=', $numero_documento],
        );
        $this->actualiza_tabla_db2('LIBPRDDAT.MMCJFCB', $arrayWhere, $arrayUpdate);
        return $this->registra_paso_proceso($PASO, $PROCESO, $TABLA, $ID);
    }

    public function desactiva_registro_tabla_MMCJFDT($codCia, $sucursal, $numero_documento, $correlativo_planilla_cobranzas, $TABLA, $ID, $PROCESO)
    {
        $PASO = 13;
        $arrayUpdate = array('CJDTEST' => 'I');
        $arrayWhere = array(
            ['CJDTCCIA', '=', $codCia],
            ['CJDTCSUC', '=', $sucursal],
            ['CJDTNPDC', '=', $numero_documento],
            ['CJDTNPLL', '=', $correlativo_planilla_cobranzas],
        );

        $this->actualiza_tabla_db2('LIBPRDDAT.MMCJFDT', $arrayWhere, $arrayUpdate);
        return $this->registra_paso_proceso($PASO, $PROCESO, $TABLA, $ID);
    }

    public function retorna_planilla_contado_actual_db2($codCia, $codSuc, $fecha)
    {
        $codSuc = ($codSuc === '02') ? '01' : $codSuc;
        return DB::connection('ibmi')
            ->table('LIBPRDDAT.MMCJFAP')
            ->where('CJAPSTS', '=', 'A')
            ->where('CJAPCCIA', '=', $codCia)
            ->where('CJAPCSUC', '=', $codSuc)
            ->where('CJAPFEPL', '=', $fecha)
            ->orderBy('CJAPNPLL', 'ASC')
            ->first();
    }

    public function actualiza_enc_pagos($arrayWhere, $arrayUpdate)
    {
        return DB::connection('ibmi')
            ->table('LIBPRDDAT.MMCJFCB')
            ->where($arrayWhere)
            ->update($arrayUpdate);
    }

    public function registra_enc_pagos($arrayEncPago)
    {
        return DB::connection('ibmi')->table('LIBPRDDAT.MMCJFCB')->insert([
            $arrayEncPago
        ]);
    }

    public function registra_det_pagos($arrayDetPago)
    {
        return DB::connection('ibmi')->table('LIBPRDDAT.MMCJFDT')->insert([
            $arrayDetPago
        ]);
    }

    public function retorna_det_pagos($codCia, $codSuc, $docNumber, $planilla, $correlativo_deposito)
    {
        return DB::connection('ibmi')
            ->table('LIBPRDDAT.MMCJFDT')
            ->where('CJDTEST', '=', 'A')
            ->where('CJDTCCIA', '=', $codCia)
            ->where('CJDTCSUC', '=', $codSuc)
            ->where('CJDTNPDC', '=', $docNumber)
            ->where('CJDTNPLL', '=', $planilla)
            ->where('CJDTNROP', '=', $correlativo_deposito)
            ->first();
    }

    public function retorna_all_det_pagos($codCia, $codSuc, $docNumber, $planilla)
    {
        return DB::connection('ibmi')
            ->table('LIBPRDDAT.MMCJFDT')
            ->where('CJDTEST', '=', 'A')
            ->where('CJDTCCIA', '=', $codCia)
            ->where('CJDTSERC', '=', $codSuc)
            ->where('CJDTNPDC', '=', $docNumber)
            ->where('CJDTNPLL', '=', $planilla)
            ->get();
    }

    public function actualiza_tabla_db2($tabla_db2, $arrayWhere, $arrayUpdate)
    {
        return DB::connection('ibmi')
            ->table($tabla_db2)
            ->where($arrayWhere)
            ->update($arrayUpdate);
    }

    public function actualiza_tabla_postgres($tabla, $arrayWhere, $arrayUpdate)
    {
        return DB::table($tabla)
            ->where($arrayWhere)
            ->update($arrayUpdate);
    }


    public function selecciona_from_tabla_db2($tabla_db2, $arrayWhere)
    {
        return DB::connection('ibmi')
            ->table($tabla_db2)
            ->where($arrayWhere)
            ->first();
    }

    public function selecciona_all_from_tabla_db2($tabla_db2, $arrayWhere)
    {
        return DB::connection('ibmi')
            ->table($tabla_db2)
            ->where($arrayWhere)
            ->get()
            ->toArray();
    }

    public function selecciona_from_tabla($tabla, $arrayWhere)
    {
        return DB::table($tabla)
            ->where($arrayWhere)
            ->get()
            ->toArray();
    }

    public function selecciona_fila_from_tabla($tabla, $arrayWhere)
    {
        return DB::table($tabla)
            ->where($arrayWhere)
            ->first();
    }

    public function inserta_into_tabla_db2($tabla_db2, $arrayInsert)
    {
        return DB::connection('ibmi')
            ->table($tabla_db2)
            ->insert([$arrayInsert]);
    }

    public function sincronizar_tracking_a_db2()
    {
        echo '<br>sincroniza_trcking_con_db2';
        $arrayWhere = array(
            ['sincronizado_as400', '=', null],
            ['username', '=', "SISTEMAS"]
        );
        //if (!$registros = $this->selecciona_pedidos_actualzar_db2('dispatch_details_tracking', $arrayWhere)) {
        if (!$registros = $this->selecciona_pedidos_actualzar_db2()) {
            echo '<BR>NO HAY REGISTROS';
        }
        if (is_array($registros)) {
            foreach ($registros as $fila) {
                //INSERTA DETALLE PEDIDO DB2
                $arrayInsert = array(
                    'Q0CODCIA' => $fila->company_code,
                    'Q0CODSUC' => $fila->branch_code,
                    'Q0NROPED' => $fila->order_number,
                    'Q0NROPDC' => $fila->document_order_number,
                    'Q0ESTADO' => $fila->transit_status_id,
                    'Q0OBSERV' => ($fila->description_status <> '') ? $fila->description_status : 'hola',
                    'Q0STA' => 'A',
                    'Q0USU' => 'SISTEMAS',
                    'Q0DATE' => date("Ymd"),
                    'Q0HORA' => date("His")
                );
                $this->inserta_into_tabla_db2('LIBPRDDAT.MMQ0REP', $arrayInsert);

                //ACTUALIZA PEDIDOS POSTGRES
                $arrayUpdate = array(
                    'sincronizado_as400' => date("Y-m-s H:i:s")
                );
                $arrayWhere = array(['id', '=', $fila->detail_id]);
                $this->actualiza_tabla_postgres('dispatch_details_tracking', $arrayWhere, $arrayUpdate);

                //ACTUALIZA CABECERA PEDIDOS TRACKING DB2
                if (in_array($fila->transit_status_id, array('31', '32'))) {
                    $arrayUpdate = array('Q1ESTAMV' => $fila->transit_status_id);
                    $arrayWhere = array(
                        ['Q1CODCIA', '=', $fila->company_code],
                        ['Q1CODSUC', '=', $fila->branch_code],
                        ['Q1NROPED', '=', $fila->order_number],
                        ['Q1NROPDC', '=', $fila->document_order_number],
                        ['Q1STS', '=', "A"]
                    );
                    $this->actualiza_tabla_db2('LIBPRDDAT.MMQ1REP', $arrayWhere, $arrayUpdate);
                }
            }
        }
    }

    public function selecciona_pedidos_actualzar_db2_old()
    {
        $rs = DB::table('dispatch AS ped')
            ->join('dispatch_details_tracking AS pedet', 'ped.id', '=', 'pedet.dispatch_tracking_id')
            ->where('pedet.sincronizado_as400', '=', null)
            ->where('pedet.username', '=', 'SISTEMAS')
            ->select(['*', 'pedet.id as detail_id'])
            ->get()
            ->toArray();
        if ($rs && sizeof($rs) > 0) return $rs;
        else return false;
    }

    public function selecciona_pedidos_actualzar_db2()
    {
        $rs = DB::table('dispatch AS ped')
            ->join('dispatch_tracking as pet','ped.id','=','pet.dispatch_id')
            ->join('dispatch_details_tracking AS pedet', 'pet.id', '=', 'pedet.dispatch_tracking_id')
            ->where('pedet.sincronizado_as400', '=', null)
            ->where('pedet.username', '=', 'SISTEMAS')
            ->select(['*', 'pedet.id as detail_id'])
            ->get()
            ->toArray();
        if ($rs && sizeof($rs) > 0) return $rs;
        else return false;
    }

    public function redirecciona($url = '', $tiempo = 27)
    {
        die('<br> ' . date("Y-m-d H:i:s") . ' Fin de proceso...');
        $tiempo = ($tiempo < 27) ? 30 : $tiempo;
        if (!empty($url)) {
            $url = env('APP_URL') . $url;
            echo '<br>Por favor espere un momento...';
            sleep($tiempo);
            echo '<script type="text/javascript">';
            echo 'const url_redirect = "' . $url . '";';
            echo 'window.location.href = url_redirect;';
            echo '</script>';
            echo '<noscript>';
            echo '<meta http-equiv="refresh" content="0;url="+url_redirect />';
            echo '</noscript>';
        }
    }

    public function escribe_actualiza_tabla_mmccreca($codCia, $bankAccount, $pago, $correlativoPlanillaCobranzas)
    {
        // BUSCAR/REGISTRAR EN TABLA MMCCRECA
        $arrayWhere = array(
            ['CCCODCIA', '=', $codCia],
            ['CCCODBCO', '=', $bankAccount[0]->erp_code],
            ['CCFECPRO', '=', date("Ymd")],
            ['CCCODMON', '=', $bankAccount[0]->moneda],
            ['CCNROCTA', '=', $bankAccount[0]->nro_cuenta],
        );
        if (!$dep_apl_cab = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMCCRECA', $arrayWhere)) {
            //REGISTRAR
            $arrayInsert = array(
                'CCCODCIA' => $codCia,
                'CCCODBCO' => $bankAccount[0]->erp_code,
                'CCFECPRO' => date("Ymd"),
                'CCCODMON' => $bankAccount[0]->moneda,
                'CCNROCTA' => $bankAccount[0]->nro_cuenta,
                'CCIMPTOT' => $pago->totalAmount,
                'CCIMPSAL' => 0,
                'CCUSUCAR' => $this->user,
                'CCFECCAR' => date("Ymd"),
                'CCHORCAR' => date("His"),
                'CCUSUAPL' => $this->user,
                'CCFECAPL' => date("Ymd"),
                'CCHORAPL' => date("His"),
                'CCNROPLL' => $correlativoPlanillaCobranzas
            );
            if (!$this->inserta_into_tabla_db2('LIBPRDDAT.MMCCRECA', $arrayInsert)) {
                die('<BR>NO SE PUDO REGISTRAR EN LA TABLA (MMCCRECA)');
            } else {
                echo '<br>SE REGISTRÓ CORRECTAMENTE EN LA TABLA (MMCCRECA)';
                $dep_apl_cab = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMCCRECA', $arrayWhere);
            }
        } else {
            echo '<br>ACTUALIZAR SALDOS EN TABLA (MMCCRECA)';
            $this->actualiza_saldos_mmccreca($codCia, $bankAccount[0]->nro_cuenta, date("Ymd"));
        }
    }

    public function actualiza_saldos_mmccreca($codCia, $numero_cuenta, $fecha_proceso)
    {
        $total = DB::connection('ibmi')
            ->table('LIBPRDDAT.MMCDRECA')
            ->where('CDCODCIA', '=', $codCia)
            ->where('CDNROCTA', '=', $numero_cuenta)
            ->where('CDFECPRO', '=', $fecha_proceso)
            ->whereIn('CDSTATUS', ['A', 'S'])
            ->sum('CDMONTO');
        $saldo = DB::connection('ibmi')
            ->table('LIBPRDDAT.MMCDRECA')
            ->where('CDCODCIA', '=', $codCia)
            ->where('CDNROCTA', '=', $numero_cuenta)
            ->where('CDFECPRO', '=', $fecha_proceso)
            ->where('CDSTATUS', '=', 'A')
            ->sum('CDMONTO');
        echo ("<br>CUENTA: $numero_cuenta --- FECHA: $fecha_proceso");
        echo ("<br>TOTAL: $total --- SALDO: $saldo --- FECHA: $fecha_proceso");
        return DB::connection('ibmi')
            ->table('LIBPRDDAT.MMCCRECA')
            ->where('CCCODCIA', '=', $codCia)
            ->where('CCNROCTA', '=', $numero_cuenta)
            ->where('CCFECPRO', '=', $fecha_proceso)
            ->update(['CCIMPTOT' => $total, 'CCIMPSAL' => $saldo]);
    }

    public function registra_planilla_cobranzas_credito($codCia, $currencyCode, $documentAmountPaid)
    {
        echo "<br>REGISTRAR PLANILLA COBRANZAS CREDITO";
        $codSucDeposito = '01';
        $arrayWhere = array(
            ['DLCODCIA', '=', $codCia],
            ['DLCODSUC', '=', $codSucDeposito],
            ['DLFECPLL', '=', date("Ymd")],
            ['DLSTS', '=', 'A'],
            ['DLSTSPLL', '=', 'A'],
            ['DLCODCBR', '=', $this->codCobrador]
        );

        if (!$regPlanillaCobranzas = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMDLREP', $arrayWhere)) {
            echo '<br>CORRELATIVO DE PLANILLA DE COBRANZAS -> NUMERADORES (MMFCREL0): ';
            $correlativoPlanillaCobranzas = $this->getCorrelativeNumberByDocType($codCia, $codSucDeposito, '05'); // '05' -> PLANILLA DE COBRANZAS
            echo $correlativoPlanillaCobranzas;

            echo '<br>REGISTRAR PLANILLA DE COBRANZAS EN TABLA (MMDLREP)';
            $arrayInsert = array(
                'DLCODCIA' => $codCia,
                'DLCODSUC' => $codSucDeposito,
                'DLFECPLL' => date("Ymd"),
                'DLSTS' => 'A',
                'DLNROPLL' => $correlativoPlanillaCobranzas,
                'DLCODCBR' => $this->codCobrador,
                'DLNROHRT' => '0',
                'DLIMPCRS' => ($currencyCode == '01') ? $documentAmountPaid : 0, //'0', //TOTAL CARGOS (SOLES)
                'DLIMPCRD' => ($currencyCode == '02') ? $documentAmountPaid : 0, //'0', //TOTAL CARGOS (DÓLARES)
                'DLIMPABS' => ($currencyCode == '01') ? $documentAmountPaid : 0, //'0', //TOTAL ABONOS (SOLES)
                'DLIMPABD' => ($currencyCode == '02') ? $documentAmountPaid : 0, //'0', //TOTAL ABONOS (DOLARES)
                'DLSTSPLL' => 'A',
                'DLUSR' => $this->user,
                'DLJOB' => $this->app,
                'DLJDT' => date("Ymd"),
                'DLJTM' => date("His")
            );
            if (!$this->inserta_into_tabla_db2('LIBPRDDAT.MMDLREP', $arrayInsert)) {
                die('<BR>ERROR REGISTRANDO PLANILLA DE COBRANZAS');
            }
            $regPlanillaCobranzas = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMDLREP', $arrayWhere);
            echo '<br>PLANILLA DE COBRANZAS (' . $regPlanillaCobranzas->dlnropll . ') REGISTRADA!';
        } else {
            echo "<br>PLANILLA REGISTRADA PREVIAMENTE: " . $regPlanillaCobranzas->dlnropll;
        }
        echo "<br>FIN - REGISTRAR PLANILLA COBRANZAS CREDITO";
        return $regPlanillaCobranzas;
    }

    public function registra_planilla_cobranzas_dia($codCia, $correlativoPlanillaCobranzas, $codCobrador, $tipoPlanillaCredito, $user, $app)
    {
        $codSucDeposito = '01';
        $arrayWhere = array(
            ['IECODCIA', '=', $codCia],
            ['IECODSUC', '=', $codSucDeposito],
            ['IENROPLL', '=', $correlativoPlanillaCobranzas],
        );
        if (!$regTipoPlanilla = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMIEREP', $arrayWhere)) {
            $arrayInsert = array(
                'IECODCIA' => $codCia,
                'IECODSUC' => $codSucDeposito,
                'IENROPLL' => $correlativoPlanillaCobranzas,
                'IEFECPLL' => date("Ymd"),
                'IECODCBR' => $codCobrador,
                'IETIPPLL' => $tipoPlanillaCredito,
                'IESTS' => 'A',
                'IEUSR' => $user,
                'IEJOB' => $app,
                'IEJDT' => date("Ymd"),
                'IEJTM' => date("His")
            );
            if (!$this->inserta_into_tabla_db2('LIBPRDDAT.MMIEREP', $arrayInsert)) {
                die('NO SE PUDO GENERAR LA NUEVA PLANILLA DE COBRANZAS: ' . $correlativoPlanillaCobranzas);
            }
            $regTipoPlanilla = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMIEREP', $arrayWhere);
        }
        return $regTipoPlanilla;
    }

    public function retorna_datos_deuda_cliente_saldos($datos)
    {
        $arrayWhere = array(
            ['ABCODCIA', '=', $datos->abcodcia],
            ['ABCODSUC', '=', $datos->abcodsuc],
            ['ABCODCLI', '=', $datos->abcodcli],
            ['ABTIPDOC', '=', $datos->abtipdoc],
            ['ABNRODOC', '=', $datos->abnrodoc],
        );
        return $this->selecciona_fila_from_tabla('cliente_saldos', $arrayWhere);
    }

    public function procesa_registro_tabla_mmdorep($codCia, $correlativoPlanillaCobranzas, $regSaldo, $correlativoBoletaDeposito, $documentAmountPaid, $currencyCode, $saldo_actual_as400 = 0)
    {
        $codSucDeposito = '01';
        $arrayWhere = array(
            ['DOSTS', '=', 'A'],
            ['DOCODCIA', '=', $codCia],
            ['DOCODSUC', '=', $codSucDeposito],
            ['DONROPLL', '=', $correlativoPlanillaCobranzas],
            ['DONROBOL', '=', $correlativoBoletaDeposito],
            ['DOCODCLI', '=', $regSaldo->abcodcli],
            ['DOTIPDOC', '=', $regSaldo->abtipdoc],
            ['DONRODOC', '=', $regSaldo->abnrodoc],
        );
        if (!$this->selecciona_from_tabla_db2('LIBPRDDAT.MMDOREP', $arrayWhere)) {
            $arrayInsert = array(
                'DOSTS' => 'A',
                'DOCODCIA' => $codCia,
                'DOCODSUC' => $codSucDeposito,
                'DONROPLL' => $correlativoPlanillaCobranzas,
                'DONROBOL' => $correlativoBoletaDeposito,
                'DOCODMON' => $currencyCode,
                'DOCODCLI' => $regSaldo->abcodcli,
                'DOTIPDOC' => $regSaldo->abtipdoc,
                'DONRODOC' => $regSaldo->abnrodoc,
                'DOIMPASG' => $documentAmountPaid,
                'DOIMPPGD' => $documentAmountPaid,
                'DOUSR' => $this->user,
                'DOJOB' => $this->app,
                'DOJDT' => date("Ymd"),
                'DOJTM' => date("His")
            );
            if (!$this->inserta_into_tabla_db2('LIBPRDDAT.MMDOREP', $arrayInsert))
                die('<br>ERROR REGISTRANDO EN TABLA MMDOREP');
            else {
                echo '<br>REGISTRO INGRESADO EN TABLA MMDOREP';
                return true;
            }
        } else {
            echo "<br>REGISTRO EN TABLA MMDOREP EFECTUADO PREVIAMENTE";
            return true;
        }
    }

    public function actualizar_tabla_saldos_auxiliar($codCia, $regSaldo, $documentAmountPaid, $operationId, $bankAccount, $nuevo_saldo, $rupdate = 0)
    {
        //AUXILIAR
        $arrayWhere = array(
            ['ABCODCIA', '=', $codCia],
            ['ABCODSUC', '=', $regSaldo->abcodsuc],
            ['ABCODCLI', '=', $regSaldo->abcodcli],
            ['ABTIPDOC', '=', $regSaldo->abtipdoc],
            ['ABNRODOC', '=', $regSaldo->abnrodoc]
        );
        $arrayUpdate = array(
            'ABIMPSLD' => $nuevo_saldo,
            'NOPEPWEB' => $operationId,
            'CODBPWEB' => $bankAccount[0]->erp_code,
            'IMPORWEB' => $documentAmountPaid,
            'FECPGWEB' => date('Ymd'),
            'HMSPGWEB' => date("His"),
            'ABSTS' => 'A',
            'RUPDATE' => $rupdate
        );
        if ($this->actualiza_tabla_db2('LIBPRDDAT.CCAPLBCO', $arrayWhere, $arrayUpdate)) {
            return true;
        } else {

            return false;
        }
    }

    public function actualizar_tabla_saldos_principal($codCia, $regSaldo, $nuevo_saldo)
    {
        //PRINCIPAL
        $arrayWhere = array(
            ['EISTS', '=', 'A'],
            ['EICODCIA', '=', $codCia],
            ['EICODSUC', '=', $regSaldo->abcodsuc],
            ['EICODCLI', '=', $regSaldo->abcodcli],
            ['EITIPDOC', '=', $regSaldo->abtipdoc],
            ['EINRODOC', '=', $regSaldo->abnrodoc]
        );
        if ($nuevo_saldo == 0) $eists = 'I';
        else $eists = 'A';
        $arrayUpdate = array(
            'EIIMPSLD' => $nuevo_saldo,
            'EISTS' => $eists,
            'EICODCBR' => $this->codCobrador,
            'EIJOB' => $this->app
        );
        if ($this->actualiza_tabla_db2('LIBPRDDAT.MMEIREP', $arrayWhere, $arrayUpdate)) {
            echo '<br>TABLA PRINCIPAL DE SALDOS ACTUALIZADA.';
            return true;
        }
    }

    public function registra_historicos_padre($codCia, $regSaldo, $saldo_actual)
    {
        echo "<br>Método: registra_historicos_padre";
        echo "<br>Saldo actual: $saldo_actual";
        // REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) PADRE
        if (intval($saldo_actual) == 0) {
            $arrayWhere = array(
                ['EJSTS', '=', $regSaldo->absts],
                ['EJCODCIA', '=', $codCia],
                ['EJCODSUC', '=', $regSaldo->abcodsuc],
                ['EJCODCLI', '=', $regSaldo->abcodcli],
                ['EJTIPDOC', '=', $regSaldo->abtipdoc],
                ['EJNRODOC', '=', $regSaldo->abnrodoc],
            );

            if (!$datos_hist = $this->selecciona_from_tabla_db2($this->tablas['tabla_historicos_mmejrep'], $arrayWhere)) {
                $arrayHist = array(
                    'EJCODCIA' => $codCia,
                    'EJCODSUC' => $regSaldo->abcodsuc,
                    'EJCODCLI' => $regSaldo->abcodcli,
                    'EJTIPDOC' => $regSaldo->abtipdoc,
                    'EJNRODOC' => $regSaldo->abnrodoc,
                    'EJFECTCM' => $regSaldo->abfectcm,
                    'EJFECEMI' => $regSaldo->abfecemi,
                    'EJFECVCT' => $regSaldo->abfecvct,
                    'EJFECCAN' => date("Ymd"), //FECHA DE CANCELACION
                    'EJCODMON' => $regSaldo->abcodmon,
                    'EJIMPCCC' => $regSaldo->abimpccc,
                    'EJIMPSLD' => $saldo_actual,
                    'EJFRMPAG' => $regSaldo->abfrmpag,
                    'EJMODPAG' => $regSaldo->abmodpag,
                    'EJCNDPAG' => $regSaldo->abcndpag,
                    'EJCODVEN' => $regSaldo->abcodven,
                    'EJUSR' => $this->user,
                    'EJJOB' => $this->app,
                    'EJJDT' => date("Ymd"),
                    'EJJTM' => date("His"),
                    'EJSTS' => 'A', //Estado de registro
                    'EJSTSDOC' => 'A',  //Estado de Documento
                    'EJSTSCOA' => 'C'  //Estado de Cargo/Abon
                );
                if (!$this->inserta_into_tabla_db2($this->tablas['tabla_historicos_mmejrep'], $arrayHist)) {
                    die('ERROR REGISTRANDO EN TABLA HISTORICOS DE SALDOS');
                } else return true;
            } else {
                echo "<br>Registro de histórico padre, efectuado previamente. <br> Se actualizó el saldo";
                echo "<br>Suc.: {$regSaldo->abcodsuc} - Cod. Cli.:{$regSaldo->abcodcli} - Tip. Doc.:{$regSaldo->abtipdoc} - Nro. Doc.:{$regSaldo->abnrodoc} - Total Doc.: {$regSaldo->abimpccc} - SA: $saldo_actual";
                $arrayUpdate = array(
                    'EJIMPSLD' => $saldo_actual,
                );
                $this->actualiza_tabla_db2($this->tablas['tabla_historicos_mmejrep'], $arrayWhere, $arrayUpdate);
                return true;
            }
        } else {
            echo "<br>Saldo actual es mayor que 0: $saldo_actual";
            return true;
        }
    }

    public function registra_tabla_historicos_hijo($codCia, $correlativoBoletaDeposito, $regSaldo, $nuevo_saldo, $monto_pagado_documento, $currencyCode)
    {
        // BUSCAR EN SALDOS HISTORICOS (HIJO) SI EXISTE DEPOSITO REGISTRADO
        $codSucDeposito = '01';
        $arrayWhere = array(
            ['EJCODCIA', '=', $codCia],
            ['EJCODSUC', '=', $codSucDeposito],
            ['EJCODCLI', '=', $regSaldo->abcodcli],
            ['EJNRODOC', '=', $correlativoBoletaDeposito],
            ['EJIMPCCC', '=', $regSaldo->abimpccc],
            ['EJFECCAN', '=', date("Ymd")],
            ['EJSTS', '=', 'A']
        );
        if (!$this->selecciona_from_tabla_db2($this->tablas['tabla_historicos_mmejrep'], $arrayWhere)) {
            // REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) HIJO
            $today = date("Ymd");
            $arrayHist = array(
                'EJCODCIA' => $codCia,
                'EJCODSUC' => $codSucDeposito,
                'EJCODCLI' => $regSaldo->abcodcli,
                'EJTIPDOC' => '81',
                'EJNRODOC' => $correlativoBoletaDeposito,
                // 'EJFECTCM' => $regSaldo->abfectcm,
                // 'EJFECEMI' => $regSaldo->abfecemi,
                // 'EJFECVCT' => $regSaldo->abfecvct,
                'EJFECTCM' => $today,
                'EJFECEMI' => $today,
                'EJFECVCT' => $today,
                'EJFECCAN' => $today, //FECHA DE CANCELACION
                'EJCODMON' => $currencyCode,
                'EJIMPCCC' => $monto_pagado_documento,
                'EJIMPSLD' => $nuevo_saldo,
                'EJFRMPAG' => $regSaldo->abfrmpag,
                'EJMODPAG' => $regSaldo->abmodpag,
                'EJCNDPAG' => $regSaldo->abcndpag,
                'EJCODVEN' => $regSaldo->abcodven,
                'EJUSR' => $this->user,
                'EJJOB' => $this->app,
                'EJJDT' => date("Ymd"),
                'EJJTM' => date("His"),
                'EJSTS' => 'A', //Estado de registro
                'EJSTSDOC' => 'A',  //Estado de Documento
                'EJSTSCOA' => 'A'  //Estado de Cargo/Abon
            );
            if (!$this->inserta_into_tabla_db2($this->tablas['tabla_historicos_mmejrep'], $arrayHist)) {
                die('ERROR REGISTRANDO EN TABLA HISTORICOS DE SALDOS - HIJO');
            } else return true;
        } else {
            echo "<br>REGISTRO YA EXISTE EN TABLA DE HISTÓRICO DE SALDOS - HIJO";
            return true;
        }
    }

    public function registrar_tabla_aplicaciones($codCia, $regSaldo, $correlativoBoletaDeposito, $montoPagadoPadre, $montoPagadoHijo)
    {
        //REGISTRAR EN TABLA MMELREP (TABLA DE APLICACIONES)
        $codSucHijo = '01';
        $arrayAplic = array(
            'ELCIAPDR' => $codCia,
            'ELSUCPDR' => $regSaldo->abcodsuc,
            'ELCLIPDR' => $regSaldo->abcodcli,
            'ELTIPPDR' => $regSaldo->abtipdoc,
            'ELDOCPDR' => $regSaldo->abnrodoc,
            'ELCIAHIJ' => $codCia,
            'ELSUCHIJ' => $codSucHijo,
            'ELCLIHIJ' => $regSaldo->abcodcli,
            'ELTIPHIJ' => '81', //
            'ELDOCHIJ' => $correlativoBoletaDeposito, //NUMERO INTERNO DEPOSITO
            'ELIMPPDR' => $montoPagadoPadre,
            'ELIMPHIJ' => $montoPagadoHijo,
            'ELFECAPL' => date("Ymd"),
            'ELSTS' => 'A',
            'ELUSR' => $this->user,
            'ELJOB' => $this->app,
            'ELJDT' => date('Ymd'),
            'ELJTM' => date("His")
        );
        return $this->inserta_into_tabla_db2('LIBPRDDAT.MMELREP', $arrayAplic);
    }


    public function registrar_pagos_parciales_en_mmejrep($codCia, $datos_doc_db2, $pagos_parciales)
    {
        $qty_registrados = 0;
        foreach ($pagos_parciales as $pago) {
            $arrayWhere = array(
                ['EJCODCIA', '=', $codCia],
                ['EJCODSUC', '=', $pago->cjdtserc],
                ['EJCODCLI', '=', $datos_doc_db2->abcodcli],
                ['EJNRODOC', '=', $pago->cjdtnrop],
                ['EJIMPCCC', '=', $pago->cjdtimpd],
                ['EJFECCAN', '=', $pago->cjdtfecp],
                ['EJSTS', '=', 'A']
            );
            if (!$this->selecciona_from_tabla_db2($this->tablas['tabla_historicos_mmejrep'], $arrayWhere)) {
                // REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) HIJO
                if (isset($pago->cjdttpdo) && $pago->cjdttpdo != 81) {
                    $arrayHist = array(
                        'EJCODCIA' => $codCia,
                        'EJCODSUC' => $pago->cjdtserc,
                        'EJCODCLI' => $datos_doc_db2->abcodcli,
                        'EJTIPDOC' => $pago->cjdttpdo,
                        'EJNRODOC' => $pago->cjdtnrop,
                        'EJFECTCM' => $datos_doc_db2->abfectcm,
                        'EJFECEMI' => $datos_doc_db2->abfecemi,
                        'EJFECVCT' => $datos_doc_db2->abfecvct,
                        'EJFECCAN' => $pago->cjdtfecp,
                        'EJCODMON' => $pago->cjdtmone,
                        'EJIMPCCC' => $pago->cjdtimpd,
                        //'EJIMPSLD' => $nuevo_saldo,
                        'EJFRMPAG' => $datos_doc_db2->abfrmpag,
                        'EJMODPAG' => $datos_doc_db2->abmodpag,
                        'EJCNDPAG' => $datos_doc_db2->abcndpag,
                        'EJCODVEN' => $datos_doc_db2->abcodven,
                        'EJUSR' => $pago->cjdtusr,
                        'EJJOB' => $this->app,
                        'EJJDT' => date("Ymd"),
                        'EJJTM' => date("His"),
                        'EJSTS' => 'A', //Estado de registro
                        'EJSTSDOC' => 'A',  //Estado de Documento
                        'EJSTSCOA' => 'A'  //Estado de Cargo/Abon
                    );
                }else{
                    $arrayHist = array(
                        'EJCODCIA' => $codCia,
                        'EJCODSUC' => $pago->cjdtserc,
                        'EJCODCLI' => $datos_doc_db2->abcodcli,
                        'EJTIPDOC' => $pago->cjdttpdo,
                        'EJNRODOC' => $pago->cjdtnrop,
                        'EJFECTCM' => $pago->cjdtfecp,
                        'EJFECEMI' => $pago->cjdtfecp,
                        'EJFECVCT' => $pago->cjdtfecp,
                        'EJFECCAN' => $pago->cjdtfecp,
                        'EJCODMON' => $pago->cjdtmone,
                        'EJIMPCCC' => $pago->cjdtimpd,
                        //'EJIMPSLD' => $nuevo_saldo,
                        'EJFRMPAG' => $datos_doc_db2->abfrmpag,
                        'EJMODPAG' => $datos_doc_db2->abmodpag,
                        'EJCNDPAG' => $datos_doc_db2->abcndpag,
                        'EJCODVEN' => $datos_doc_db2->abcodven,
                        'EJUSR' => $pago->cjdtusr,
                        'EJJOB' => $this->app,
                        'EJJDT' => date("Ymd"),
                        'EJJTM' => date("His"),
                        'EJSTS' => 'A', //Estado de registro
                        'EJSTSDOC' => 'A',  //Estado de Documento
                        'EJSTSCOA' => 'A'  //Estado de Cargo/Abon
                    );
                }
                
                if (!$this->inserta_into_tabla_db2($this->tablas['tabla_historicos_mmejrep'], $arrayHist)) {
                    die('ERROR REGISTRANDO EN TABLA HISTORICOS DE SALDOS - HIJO');
                } else {
                    $qty_registrados++;
                }
            } else {
                echo '<br>PAGO REGISTRADO PREVIAMENTE';
                $qty_registrados++;
            }
        }
        return ($qty_registrados == sizeof($pagos_parciales)) ? true : false;
    }

    public function registrar_pagos_parciales_en_mmelrep($codCia, $datos_doc_db2, $pagos_parciales)
    {
        $qty_registrados = 0;
        foreach ($pagos_parciales as $pago) {
            $arrayWhere = array(
                ['ELCIAPDR', '=', $codCia],
                ['ELSUCPDR', '=', $datos_doc_db2->abcodsuc],
                ['ELCLIPDR', '=', $datos_doc_db2->abcodcli],
                ['ELTIPPDR', '=', $datos_doc_db2->abtipdoc],
                ['ELDOCPDR', '=', $datos_doc_db2->abnrodoc],
                ['ELTIPHIJ', '=', $pago->cjdttpdo],
                ['ELDOCHIJ', '=', $pago->cjdtnrop],
                ['ELSTS', '=', 'A']
            );
            if (!$this->selecciona_from_tabla_db2($this->tablas['tabla_aplicaciones_mmelrep'], $arrayWhere)) {
                $arrayAplic = array(
                    'ELCIAPDR' => $codCia,
                    'ELSUCPDR' => $datos_doc_db2->abcodsuc,
                    'ELCLIPDR' => $datos_doc_db2->abcodcli,
                    'ELTIPPDR' => $datos_doc_db2->abtipdoc,
                    'ELDOCPDR' => $datos_doc_db2->abnrodoc,
                    'ELCIAHIJ' => $codCia,
                    'ELSUCHIJ' => $pago->cjdtcsuc,
                    'ELCLIHIJ' => $datos_doc_db2->abcodcli,
                    'ELTIPHIJ' => $pago->cjdttpdo,
                    'ELDOCHIJ' => $pago->cjdtnrop,
                    'ELIMPPDR' => $pago->cjdtimpd,
                    'ELIMPHIJ' => $pago->cjdtimpd,
                    'ELFECAPL' => $pago->cjdtfecp,
                    'ELSTS' => 'A',
                    'ELUSR' => $pago->cjdtusr,
                    'ELJOB' => $this->app,
                    'ELJDT' => date('Ymd'),
                    'ELJTM' => date("His")
                );
                if (!$this->inserta_into_tabla_db2('LIBPRDDAT.MMELREP', $arrayAplic)) {
                    die('ERROR REGISTRANDO EN TABLA DE APLICACIONES');
                } else {
                    echo '<br>PAGO REGISTRADO CORRECTAMENTE EN TABLA DE APLICACIONES';
                    $qty_registrados++;
                }
            } else {
                echo '<br>PAGO YA EXISTE EN TABLA DE APLICACIONES';
                $qty_registrados++;
            }
        }
        return ($qty_registrados == sizeof($pagos_parciales)) ? true : false;
    }

    public function registra_tabla_mmcdreca($codCia, $bankAccount, $regSaldo, $correlativoBoletaDeposito, $operationId, $correlativoPlanillaCobranzas, $pago)
    {
        // BUSCAR/REGISTRAR EN TABLA MMCDRECA
        $arrayWhere = array(
            ['CDCODCIA', '=', $codCia],
            ['CDCODBCO', '=', $bankAccount[0]->erp_code],
            ['CDFECPRO', '=', date("Ymd")],
            ['CDCODMON', '=', $bankAccount[0]->moneda],
            ['CDNROCTA', '=', $bankAccount[0]->nro_cuenta],
            ['CDCODCLI', '=', $regSaldo->abcodcli],
            ['CDNROBOL', '=', $correlativoBoletaDeposito],
            ['CDNROOPE', '=', $operationId],
            ['CDNROPLL', '=', $correlativoPlanillaCobranzas]
        );
        if (!$this->selecciona_from_tabla_db2('LIBPRDDAT.MMCDRECA', $arrayWhere)) {
            //REGISTRAR EN TABLA MMCDRECA
            $arrayInsert = array(
                'CDCODCIA' => $codCia,
                'CDCODBCO' => $bankAccount[0]->erp_code,
                'CDFECPRO' => date("Ymd"),
                'CDCODMON' => $bankAccount[0]->moneda,
                'CDNROCTA' => $bankAccount[0]->nro_cuenta,
                'CDCODCLI' => $regSaldo->abcodcli,
                'CDMONTO' => $pago->totalAmount,
                'CDSALDO' => 0,
                'CDNROOPE' => $operationId,
                'CDHOROPE' => date("His"),
                'CDNROPLL' =>  $correlativoPlanillaCobranzas,
                'CDFORPAGO' => $regSaldo->abfrmpag,
                'CDFECVAL' => '',
                'CDSUCAGE' => '',
                'CDSTATUS' => 'S', // A -> ACTIVO, S -> SALDADO, P -> PENDIENTE
                'CDUSUCAR' => $this->user,
                'CDFECCAR' => date("Ymd"),
                'CDHORCAR' => date("His"),
                'CDUSUAPL'  => $this->user,
                'CDFECAPL' => date("Ymd"),
                'CDHORAPL' => date("His"),
                'CDIMPAPL' => $pago->totalAmount,
                'CDNROBOL' => $correlativoBoletaDeposito,
                'CDFECDEP' => date("Ymd"),
                'CDNOBLOQ' => '1',
                'CDDESCRI' => 'PAGO/ABONO'
            );
            if (!$this->inserta_into_tabla_db2('LIBPRDDAT.MMCDRECA', $arrayInsert)) {
                die('<br>ERROR REGISTRANDO EN TABLA (MMCDRECA)');
            } else {
                echo '<br>REGISTRO EFECTUADO EN TABLA (MMCDRECA)';
                return true;
            }
        } else {
            echo '<br>REGISTRO REALIZADO PREVIAMENTE EN TABLA (MMCDRECA)';
            return true;
        }
    }

    public function registra_actualiza_encabezado_pagos_contado_mmcjfcb($codCia, $codSuc, $docNumber, $regSaldo, $regPlanilla)
    {
        //ACTUALIZAR/REGISTRAR EN TABLA ENCABEZADO DE PAGOS (MMCJFCB)
        sleep(3);

        //RETORNA TIPO DE CAMBIO DEL DIA
        $tipo_cambio_dolar = $this->retorna_tipo_cambio_dolar_mym('02');

        //selecciona pedido de mmcbrep
        $arrayWhere = array(
            ['CBCODCIA', '=', $codCia],
            ['CBCODSUC', '=', $codSuc],
            ['CBNROPDC', '=', $docNumber],
            ['CBCODCLI', '=', $regSaldo->abcodcli]
        );

        if (!$datos_pedido = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMCBREP', $arrayWhere)) {
            echo ('PEDIDO ' . $codSuc . '-' . $docNumber . ' NO ENCONTRADO');
            return false;
        } else {
            if (strlen(trim($datos_pedido->cbnrocor)) == 0) {
                echo '<br>CLIENTE: (' . $datos_pedido->cbcodcli . ') - PEDIDO: (' . $datos_pedido->cbcodcia . ' - ' . $datos_pedido->cbcodsuc . ' - ' . $datos_pedido->cbnropdc . ') NO TIENE FACTURA CREADA AÚN';
                return false;
            }

            if ($codSuc === '02') {
                $codSuc = '01';
                $codSuc2 = '02';

                $arrayUpdate = array(
                    'CJCBIMCA' => $regSaldo->abimpccc,
                    'CJCBNSER' => $datos_pedido->cbnroser,
                    'CJCBNCOR' => $datos_pedido->cbnrocor,
                    'CJCBSUDO' => $codSuc2,
                    'CJCBSPED' => '0'
                );
            } else {
                $codSuc2 = $codSuc;
                $arrayUpdate = array(
                    'CJCBIMCA' => $regSaldo->abimpccc,
                    'CJCBNSER' => $datos_pedido->cbnroser,
                    'CJCBNCOR' => $datos_pedido->cbnrocor,
                    'CJCBSPED' => '0'
                );
            }

            $arrayWhere = array(
                ['CJCBCCIA', '=', $codCia],
                ['CJCBCSUC', '=', $codSuc],
                ['CJCBCCLI', '=', $regSaldo->abcodcli],
                ['CJCBNPDC', '=', $docNumber]
            );

            if ($regPlanilla) {
                if ($this->selecciona_from_tabla_db2('LIBPRDDAT.MMCJFCB', $arrayWhere)) {
                    return $this->actualiza_enc_pagos($arrayWhere, $arrayUpdate);
                } else {
                    $arrayEncPago = array(
                        'CJCBCCIA' => $codCia,
                        'CJCBCSUC' => $codSuc,
                        'CJCBCCLI' => $regSaldo->abcodcli,
                        'CJCBNPDC' => $docNumber,
                        'CJCBNSER' => $datos_pedido->cbnroser,
                        'CJCBNCOR' => $datos_pedido->cbnrocor,
                        'CJCBNPLL' => $regPlanilla->cjapnpll,
                        'CJCBFDOC' => $regSaldo->abfecemi, //FECHA DOCUMENTO
                        'CJCBFRPG' => $regSaldo->abfrmpag,
                        'CJCBMOND' => $regSaldo->abcodmon,
                        'CJCBTCCM' => $tipo_cambio_dolar->mym_buying_price,
                        'CJCBTCVT' => $tipo_cambio_dolar->mym_selling_price,
                        'CJCBCVEN' => $regSaldo->abcodven,
                        'CJCBTIPD' => $regSaldo->abtipdoc,
                        'CJCBIMDO' => $regSaldo->abimpccc,
                        'CJCBIMCA' => $regSaldo->abimpccc,
                        'CJCBSPED' => '0',
                        'CJCBSTSH' => '',
                        'CJCBSUDO' => $codSuc2,
                        'CJCBEST' => 'A',
                        'CJCBJDT' => date("Ymd"),
                        'CJCBJTM' => date("His"),
                        'CJCBPGM' => $this->app
                    );

                    if (!$this->registra_enc_pagos($arrayEncPago)) {
                        echo '<br>NO SE PUDO ACTUALIZAR NI AGREGAR ENCABEZADO DE PAGOS ' . date("Ymd");
                        return false;
                    } else return true;
                }
            }
        }
    }

    public function registra_detalle_pagos_contado_mmcjfdt($codCia, $codSuc, $docNumber, $regPlanilla, $correlativeNumber, $operationId, $bankAccount, $currencyCode, $regSaldo, $documentAmountPaid)
    {


        if (!$this->retorna_det_pagos($codCia, $codSuc, $docNumber, $regPlanilla->cjapnpll, $correlativeNumber)) {

            $codSuc2 = ($codSuc === '02') ? '01' : $codSuc;


            //REGISTRAR EN TABLA DETALLE DE PAGOS (MMCJFDT)
            $arrayDetPago = array(
                'CJDTCCIA' => $codCia,
                'CJDTCSUC' => $codSuc,
                'CJDTSERC' => $codSuc2, //Sucursal de Caja
                'CJDTNPLL' => $regPlanilla->cjapnpll,
                'CJDTNPDC' => $docNumber,
                'CJDTSECR' => 0,
                'CJDTFECP' => date("Ymd"),
                'CJDTCPAG' => 'BD',
                'CJDTSECU' => 0,
                'CJDTNROP' =>  $correlativeNumber,
                'CJDTTPDO' => '81', //TIPO BOLETA DE DEPOSITO
                'CJDTNRDO' => $operationId, //NRO DE OPERACION (DEPOSITO/TRANSF/PAGO/TARJETA) 
                'CJDTBNCO' => $bankAccount[0]->erp_code,
                'CJDTNCTA' => $bankAccount[0]->nro_cuenta,
                'CJDTPAIS' => '001',
                'CJDTCIUD' => '001',
                'CJDTFECD' => date("Ymd"),
                'CJDTPOSD' => '', //ESTADO POSTDATADO (VACIO)
                'CJDTNTAR' => '0', //NRO INTERNO TARJETA
                'CJDTPRCO' => '0',
                'CJDTIPTA' => 'DB',
                'CJDTMONE' => $currencyCode,
                'CJDTCPER' => '', //Codigo Personal (VACIO)
                'CJDTIMPD' => $documentAmountPaid,
                'CJDTSNVU' => 'S', //Tipo vuelto
                'CJDTLIB1' => '0',
                'CJDTLIB2' => '', //vacio
                'CJDTEST' => 'A',
                'CJDTUSR' => $this->user,
                'CJDTJDT' => date("Ymd"),
                'CJDTJTM' => date("His"),
                'CJDTJOB' => '',
                'CJDTPGM' => $this->app
            );
            if (!$this->registra_det_pagos($arrayDetPago)) {
                echo '<br>NO SE PUDO AGREGAR DETALLE DE PAGO';
                exit;
            } else return true;
        } else {
            echo '<br>Detalle registrado previamente';
            return true;
        }
    }

    public function actualizar_estado_documento_grupo($datos_grupo)
    {
        return DB::connection('ibmi')
            ->table($this->tablas['tabla_doc_comp_cab'])
            ->where('DACGRU', '=', $datos_grupo->dacgru)
            ->where('DACCIA', '=', $datos_grupo->daccia)
            ->where('DACCLI', '=', $datos_grupo->daccli)
            ->update(['DACSTS' => 'C']);
    }

    public function retorna_tipo_cambio_dolar_mym($currencyCode)
    {
        $arrayWhere = array(
            ['currency_code', '=', $currencyCode],
            ['reg_status', '=', 1],
            ['mym_selling_price', '>', 0]
        );
        return DB::table('currency_exchange_rates')
            ->where($arrayWhere)
            ->orderBy('reg_date', 'DESC')
            ->first();
    }

    public function incrementar_flag_sync($tabla,$id){
        return DB::table($tabla)
            ->where('id',$id)
            ->increment('is_sync');
    }

    public function validate_pago_sincronizado($id){
        $validate = DB::table('customer_payments')
            ->where('id',$id)
            ->where('fecha_hora_actualizacion_db2', '=', null)
            ->where('return_request_id', '=', null)
            ->first();
        if (is_object($validate)) {
            return true;
        }else{
            return false;
        }
    }  

    public function validate_extorno_sincronizado($id){
        $validate = DB::table('bank_return_requests AS ext')
                    ->join('customer_payments AS pay', 'ext.id', '=', 'pay.return_request_id')
                    ->where('ext.id',$id)
                    ->where('pay.fecha_hora_actualizacion_db2', '<>', null)
                    ->where('ext.updated_at', '=', null)
                    ->select(['ext.*'])
                    ->first();
        if (is_object($validate)) {
            return true;
        }else{
            return false;
        }
    }
}
