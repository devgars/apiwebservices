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

class QueueSyncBank implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $vista_registros_nuevos_db2;
    private $tabla_saldos_aux_db2;
    private $tablas;
    private $codCia;
    private $codBoletaDeposito;
    private $codCobrador;
    private $tipoPlanillaCredito;
    private $app;
    private $user;
    private $min_descarga_pagos_contado;
    private $cax_documents;
    private $codcli_mym;
    private $count_temp;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->vista_registros_nuevos_db2 = 'LIBPRDDAT.CCAPLBCOL01';
        $this->tabla_saldos_aux_db2 = 'LIBPRDDAT.CCAPLBCO';
        $this->tablas = array(
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

        $this->codCia = '10';
        $this->codBoletaDeposito = '06';
        $this->codCobrador = '0T1299';
        $this->tipoPlanillaCredito = '02';
        $this->app = 'APPBANCOS';
        $this->user = 'SISTEMAS';
        $this->min_descarga_pagos_contado = env('min_descarga_pagos_contado', 10);
        $this->cax_documents = 'DP';
        $this->codcli_mym = '010077';
        $this->count_temp = 0;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->empezar_sincronizacion();
    }

    public function empezar_sincronizacion(){
        print_r(":::: empezar_sincronizacion - " . date("d-m-Y H:i:s")." ::::\n");
        $this->count_temp = $this->count_temp + 1;
        //try {
            /*:: PROCEDEMOS A SINCRONIZAR LAS TABLAS DE CCAPLBCO Y CLIENTES_SALDOS*/
            $this->sincronizar_documentos();
            /*:::: SE EJECUTA SINCRONIZACION DE PAGOS Y EXTORNOS ::::*/
            if ($this->sincronizar_pagos()) {
                print_r("contador temporal : ".$this->count_temp."\n");
                $this->retornar_flag_cliente_saldos(); //retornamos el flag en 0 para que vuelvan a ser tomados por la CCAPLBCO con RUPDATE 1 O 3
                $this->empezar_sincronizacion();
            }else{
                print_r("contador temporal : ".$this->count_temp."\n");
                $this->retornar_flag_cliente_saldos(); //retornamos el flag en 0 para que vuelvan a ser tomados por la CCAPLBCO con RUPDATE 1 O 3
                sleep(60);
                $this->empezar_sincronizacion();
            }
            
            /*:::: FINALIZAMOS EJECUTANDO LA RECURSIVIDAD ::::::*/
        /*} catch (\Exception $e) {
            echo '<br>:::: error: finalizo_sincronizacion - ' . date("d-m-Y H:i:s").' ::::';
            return $e->getMessage();
        }*/
        
    }
    /*::::: BLOQUE DE SINCRONIZACION DE PAGOS :::::::::::::: */
    public function sincronizar_pagos(){
        $codCia = $this->codCia;
        print_r("Sincronizamos los pagos  - " .date("d-m-Y H:i:s")."\n");
        if ($pago = $this->get_bank_payment_for_update_db2()) {
            print_r("PROCESAMOS EL PAGO ID: ".$pago->id."\n");
            /* ::: actualizamos el campo is_sync::: */
            print_r("PROCEDEMOS A INCREMENTAR EL CAMPO IS_SYNC \n");
            $this->incrementar_flag_sync('customer_payments',$pago->id);
            // VALIDAMOS PAGO SI EL PAGO FUE POR VENTANILLA Y PASARON LOS 10 MIN
            $pago->related_debts = $this->get_customer_debts_paid_by_payment_id($pago->id, $pago->customerIdentificationCode);
            print_r("VALIDAMOS TIEMPO DE PAGO EN VENTANILLA \n");
            $sincronizar = $this->valida_tiempo_pagos_de_contado($pago, $pago->bankCode);
            if ($sincronizar) {
                print_r("SINCRONIZAR PAGO - Tiempo: " . $this->min_descarga_pagos_contado."\n");
                //EJECUTAMOS FUNCIONALIDAD DE PAGO SEGUN EL BANCO
                if (strlen($pago->bankCode)>0) {
                    switch ($pago->bankCode) {
                        case '009'://scotiabank
                            print_r("BANCO SCOTIABANK \n");
                            return $this->processPaymentBanks($pago,$codCia);
                            //return true;
                            break;
                        case '011'://continental
                            print_r("BANCO BBVA \n");
                            return $this->processPaymentBanks($pago,$codCia);
                            //return true;
                            break;
                        case '002'://bcp
                            print_r("BANCO BCP \n");
                            return $this->processPaymentBanks($pago,$codCia);
                            print_r("FIN sincroniza_middleware_con_db2  - " . date("d-m-Y H:i:s"). "\n");
                            //return true;
                            break;
                        default:
                            print_r("NO SE ENCONTRO BANCO REGISTRADO \n");
                            return false;
                            break;
                    }
                }else{
                    print_r("NO EXISTE CODIGO DE BANCO \n");
                    return false;
                }
            }else{
                print_r("PAGO AÚN NO SERÁ SINCRONIZADO CON AS400... Tiempo: " . $this->min_descarga_pagos_contado."\n");
                return false;
            }
        }else{//extornos
            print_r("NO SE ENCONTRARON PAGOS PENDIENTES \n");
            if ($this->sincronizamos_extornos()) {
                return true;
            }else{
                return false;
            }
            
        }
    }

    /* ::::::::::::::::::::: extornos :::::::::::::::::::::::::: */
    public function sincronizamos_extornos(){
        $codCia = $this->codCia;
        print_r("Sincronizar Extorno " . date("Y-m-d H:i:s") . "\n");
        $extorno = $this->get_bank_return_request_for_update_db2();
        if (is_object($extorno)) {
            print_r("Extornos: " . ($extorno->id)."\n");
            print_r("Extornamos requestId:".$extorno->requestId." - operationAnullment:".$extorno->operationNumberAnnulment. "\n");
            $this->incrementar_flag_sync('bank_return_requests',$extorno->id);
            /* ::: Por incidencias de ejecucion doble, volvemos a validar si el extorno no ha sido procesado ::*/
            if(!$this->validate_extorno_sincronizado($extorno->id)){
                print_r("ESTE EXTORNO YA FUE SINCRONIZADO PAGO:".$extorno->id." OPERATIONANULLMENT:".$extorno->operationNumberAnnulment."\n");
            }else{
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
                print_r("Ident: ".$customerIdentificationCode." --- Id_Extorno: ".$extorno->id."\n");
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
                        $formaPago = 'R';
                        print_r("EXTORNAMOS DOCUMENTO A CREDITO :".$documento->documentId."\n");
                        $PROCESO = 1; //EXTORNO A CREDITO
                        $serieNumber = $arrayNroDoc[0];
                        $docNumber = $arrayNroDoc[1];
                        $monto_pagado_documento = (float) $documento->amounts[0]->amount;
                        $regSaldo = $this->retorna_doc_cliente_saldo_interface_fac($customerIdentificationCode,$formaPago,$serieNumber, $docNumber);
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
                                print_r("PLANILLA DE DEPÓSITO NO ENCONTRADA");
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
                                        print_r("SALDO DEBE SER MENOR O IGUAL QUE IMPORTE TOTAL DE DOCUMENTO \n");
                                        print_r("Importe AS400: ".$importe_total_as400." - Saldo AS400: ".$saldo_as400." - Importe Pagado Documento: ".$monto_pagado_documento." - Saldo: ".$saldo."\n");
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
                                    print_r("EXTORNO EXPORTADO A AS400... \n");
                                }
                            }
                        } else {
                            print_r("DEPOÓSITO NO ENCONTRADO:".$codCia." --- Suc.:".$sucursal_deposito." --- Bank: " . $bankAccount[0]->erp_code . "--- Dep.: ".$operationId."---- Fecha: ".$fecha_deposito."\n");
                            //exit;
                        }
                    }else{//EXTORNO DOCUMENTO DE CONTADO
                        $PROCESO = 2;
                        print_r("EXTORNAMOS DOCUMENTO A CONTADO :".$documento->documentId."\n");
                        $tipo_documento = $arrayNroDoc[1];
                        $numero_documento = $arrayNroDoc[2];
                        $monto_pagado_documento = (float) $documento->amounts[0]->amount;
                        //echo "<br>$customerIdentificationCode - $codCia  - $tipo_documento - $numero_documento - $monto_pagado_documento<br>";
                        $regSaldo = $this->retorna_doc_cliente_saldo_interface_ped($codCia, $customerIdentificationCode, $tipo_documento, $numero_documento);
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
                                print_r("PLANILLA DE DEPÓSITO NO ENCONTRADA \n");
                            } else {
                                $correlativo_planilla_cobranzas = $regPlanillaCobranzas->cjapnpll;
                                //DESACTIVA REGISTRO EN TABLA MMCJFCB
                                if (!$this->verifica_paso_proceso(12, $PROCESO, $TABLA, $ID_EXTORNO)) {
                                    print_r("DESACTIVA REGISTRO EN TABLA MMCJFCB \n");
                                    if ($this->get_from_table_MMCJFCB($codCia, $sucursal, $codigo_cliente, $tipo_documento, $numero_documento)) {
                                        $this->desactiva_registro_tabla_MMCJFCB($codCia, $sucursal, $codigo_cliente, $tipo_documento, $numero_documento, $TABLA, $ID_EXTORNO, $PROCESO);
                                        print_r("-> Actualizado \n");
                                    }
                                }
                                ///DESACTIVA REGISTRO EN TABLA MMCJFDT
                                if (!$this->verifica_paso_proceso(13, $PROCESO, $TABLA, $ID_EXTORNO)) {
                                    print_r("DESACTIVA REGISTRO EN TABLA MMCJFDT \n");
                                    if ($this->retorna_det_pagos($codCia, $sucursal, $numero_documento, $correlativo_planilla_cobranzas, $numero_boleta_deposito)) {
                                        $this->desactiva_registro_tabla_MMCJFDT($codCia, $sucursal, $numero_documento, $correlativo_planilla_cobranzas, $TABLA, $ID_EXTORNO, $PROCESO);
                                        print_r(" -> Actualizado \n");
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
                                            print_r("SALDO DEBE SER MENOR O IGUAL QUE IMPORTE TOTAL DE DOCUMENTO \n");
                                            print_r("Importe AS400:".$importe_total_as400."- Saldo AS400:".$saldo_as400."- Importe Pagado Documento:".$monto_pagado_documento."- Saldo:".$saldo."\n");
                                        }
                                    }
                                }
                                if (!$this->verifica_paso_proceso(7, $PROCESO, $TABLA, $ID_EXTORNO)) {
                                    //Desactivar Registro de depósito en históricos de pagos HIJO (Tabla MMEJREP)
                                    print_r("xxx \n");
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
                                    print_r("EXTORNO EXPORTADO A AS400... \n");
                                }
                            }
                        }
                    }
                }
            }
            return true;
        }else{
            print_r("NO HAY EXTORNOS \n");
            return false;
        }
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

    public function get_bank_return_request_for_update_db2()
    {
        $rs = DB::table('bank_return_requests AS ext')
            ->join('customer_payments AS pay', 'ext.id', '=', 'pay.return_request_id')
            ->where('pay.fecha_hora_actualizacion_db2', '<>', null)
            ->where('ext.updated_at', '=', null)
            ->select(['ext.*', 'pay.id AS payment_id', 'pay.paymentType', 'pay.operationNumber', 'pay.operationId', 'pay.serviceId', 'pay.paidDocuments', 'pay.check', 'pay.currencyCode']) //
            ->orderBy('is_sync','ASC')
            ->first();
        return (is_object($rs)) ? $rs : false;
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

    public function desactivar_deposito_bancario($codCia, $sucursal, $ErpBankCode, $operationId, $fecha_deposito, $TABLA, $ID, $PROCESO = 1)
    {
        $PASO = 1;
        print_r("DESACTIVAR DEPÓSITO: " . $operationId."\n");
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
            return true;
        } else {
            print_r("DEPOSITO " . $operationId . " NO ENCONTRADO \n");
            return false;
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
        print_r("ACTUALIZA MONTO EN PLANILLA DE COBRANZAS (MMDMREP) \n");
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
                print_r("ERROR ACTUALIZANDO PLANILLA \n");
                return false;
            }else{
                $this->registra_paso_proceso($PASO, $PROCESO, $TABLA, $ID);
                return true;
            }
        } else {
            print_r("PLANILLA NO ACTUALIZADA: monto_ccc = ".$monto_ccc."\n");
            return false;
        }
    }

    public function desactivar_registro_tabla_mmdnrep($codCia, $correlativo_planilla_cobranzas, $numero_boleta_deposito, $codigo_cliente, $TABLA, $ID, $PROCESO = 1)
    {
        $PASO = 3;
        print_r("DESACTIVAR REGISTRO EN TABLA MMDNREP (RELACIÓN DEPÓSITO-CLIENTE-PLANILLA) \n");
        $arrayWhere = array(
            ['DNCODCIA', '=', $codCia],
            ['DNNROPLL', '=', $correlativo_planilla_cobranzas],
            ['DNNROBOL', '=', $numero_boleta_deposito],
            ['DNCODCLI', '=', $codigo_cliente],
        );

        if (!$this->selecciona_from_tabla_db2('LIBPRDDAT.MMDNREP', $arrayWhere)) {
            print_r("DEPÓSITO NO EXISTE \n");
            return false;
        } else {
            $arrayUpdate = array(
                'DNSTS' => 'I'
            );
            if ($this->actualiza_tabla_db2('LIBPRDDAT.MMDNREP', $arrayWhere, $arrayUpdate)) {
                print_r("REGISTRO DESACTIVADO: Correlativo Planilla Cob.: ".$correlativo_planilla_cobranzas." - Nro. Boleta Deposito: ".$numero_boleta_deposito." - - Cliente: ".$codigo_cliente."\n");
                $this->registra_paso_proceso($PASO, $PROCESO, $TABLA, $ID);
                return true;
            } else {
                print_r("*** ERROR: NO SE DESACTIVÓ REGISTRO -> Correlativo Planilla Cob.:".$correlativo_planilla_cobranzas." - Nro. Boleta Deposito: ".$numero_boleta_deposito." - Cliente:". $codigo_cliente ."\n");
                return false;
            }
        }
    }

    public function desactivar_registro_tabla_mmdorep($codCia, $correlativo_planilla_cobranzas, $numero_boleta_deposito, $codigo_cliente, $tipo_documento, $numero_documento, $TABLA, $ID, $PROCESO = 1)
    {
        $PASO = 4;
        //TABLA MMDOREP (RELACIÓN PLANILLA-DEPÓSITO-CLIENTE-DOCUMENTO)
        //DESACTIVAR REGISTRO EN TABLA MMDOREP (RELACIÓN PLANILLA-DEPÓSITO-CLIENTE-DOCUMENTO)
        print_r("DESACTIVAR REGISTRO EN TABLA MMDOREP (RELACIÓN PLANILLA-DEPÓSITO-CLIENTE-DOCUMENTO) \n");
        $arrayWhere = array(
            ['DOCODCIA', '=', $codCia],
            ['DONROPLL', '=', $correlativo_planilla_cobranzas],
            ['DONROBOL', '=', $numero_boleta_deposito],
            ['DOCODCLI', '=', $codigo_cliente],
            ['DOTIPDOC', '=', $tipo_documento],
            ['DONRODOC', '=', $numero_documento]
        );
        if (!$this->selecciona_from_tabla_db2('LIBPRDDAT.MMDOREP', $arrayWhere)) {
            print_r("REGISTRO NO EXISTE \n");
            return false;
        } else {
            $arrayUpdate = array(
                'DOSTS' => 'I'
            );
            if ($this->actualiza_tabla_db2('LIBPRDDAT.MMDOREP', $arrayWhere, $arrayUpdate)) {
                print_r("REGISTRO DESACTIVADO -> CLIENTE: ".$codigo_cliente." - CORRELATIVO PLANILLA: ".$correlativo_planilla_cobranzas." - DEPOSITO: ".$numero_boleta_deposito." - NRO DOCUMENTO: ".$numero_documento." \n");
                $this->registra_paso_proceso($PASO, $PROCESO, $TABLA, $ID);
                return true;
            } else {
                print_r("*** ERROR: *** \n");
                print_r($arrayWhere);
                return false;
            }
        }
        //FIN DESACTIVAR REGISTRO EN TABLA MMDOREP (RELACIÓN PLANILLA-DEPÓSITO-CLIENTE-DOCUMENTO)
    }
    public function actualizar_saldo_tabla_auxiliar($codCia, $sucursal, $codigo_cliente, $tipo_documento, $numero_documento, $monto_pagado_documento, $TABLA, $ID, $PROCESO = 1)
    {
        $PASO = 5;
        //ACTUALIZAR TABLA CCAPLBCO
        print_r("ACTUALIZAR TABLA DE SALDOS AUXILIAR (CCAPLBCO) \n");
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
                    print_r("SALDO ACTUALIZADO EN TABLA CCPLBCO \n");
                    $this->registra_paso_proceso($PASO, $PROCESO, $TABLA, $ID);
                    return true;
                } else {
                    print_r("*** ERROR: NO SE ACTUALIZÓ SALDO EN TABLA CCPLBCO \n");
                    return false;
                }
            } else {
                print_r("*** ERROR: SALDO DEBE SER MENOR O IGUAL QUE MONTO DOCUMENTO \n");
                print_r("*Saldo: ".$saldo." - Monto pagado: ".$monto_pagado_documento." - Importe total AS400: ".$importe_total_as400." - Saldo AS400: ".$saldo_as400."\n");
                return false;
            }
        }
        //FIN ACTUALIZAR TABLA CCAPLBCO
    }

    public function actualizar_saldo_tabla_principal($codCia, $sucursal, $codigo_cliente, $tipo_documento, $numero_documento, $monto_pagado_documento, $regSaldo, $TABLA, $ID, $PROCESO = 1)
    {
        $PASO = 6;
        //ACTUALIZAR TABLA MMEIREP (PRINCIPAL DE SALDOS)
        print_r("ACTUALIZAR TABLA MMEIREP (PRINCIPAL DE SALDOS) \n");
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
                    print_r("SALDO ACTUALIZADO EN TABLA MMEIREP \n");
                    $this->registra_paso_proceso($PASO, $PROCESO, $TABLA, $ID);
                    return true;
                } else {
                    print_r("*** ERROR: NO SE ACTUALIZÓ SALDO EN TABLA CCPLBCO \n");
                    return false;
                }
            } else {
                print_r("ERROR: SALDO DEBE SER MENOR O IGUAL QUE MONTO DOCUMENTO \n");
                return false;
            }
        }else{
            return false;
        }
    }

    public function actualiza_tabla_historico_saldos_hijo($codCia, $sucursal_deposito, $codigo_cliente, $tipo_documento = '81', $numero_boleta_deposito, $importe_deposito, $TABLA, $ID, $PROCESO)
    {
        $PASO = 7;
        // ACTUALIZAR TABLA DE SALDOS HISTORICOS (HIJO)
        print_r("DESACTIVAR REGISTRO EN TABLA DE SALDOS HISTORICOS (HIJO) \n");
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
            print_r("SE DESACTIVÓ REGISTRO EN TABLA DE SALDOS HISTÓRICOS - HIJO \n");
            $this->registra_paso_proceso($PASO, $PROCESO, $TABLA, $ID);
            return true;
        } else {
            print_r("***ERROR: NO SE ACTUALIZÓ TABLA DE SALDOS HISTÓRICOS - HIJO \n");
            return false;
        }
        // FIN ACTUALIZAR TABLA DE SALDOS HISTORICOS (HIJO)
    }

    public function actualiza_tabla_historico_saldos_padre($codCia, $sucursal, $codigo_cliente, $tipo_documento, $numero_documento, $importe_documento, $regSaldo, $TABLA, $ID, $PROCESO = 1)
    {
        $PASO = 8;
        // ACTUALIZAR TABLA HISTÓRICO DE SALDOS (MMEJREP) PADRE (AREA CRÉDITO)
        print_r("DESACTIVAR REGISTRO EN TABLA HISTÓRICO DE SALDOS (MMEJREP) PADRE \n");
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
            print_r("SE DESACTIVÓ REGISTRO EN TABLA DE SALDOS HISTÓRICOS - PADRE \n");
            $this->registra_paso_proceso($PASO, $PROCESO, $TABLA, $ID);
            return true;
        } else {
            print_r("***ERROR: NO SE ACTUALIZÓ TABLA DE SALDOS HISTÓRICOS - PADRE \n");
            return false;
        }
        //FIN ACTUALIZAR HISTÓRICO DE SALDOS PADRE
    }

    public function desactivar_registro_tabla_aplicaciones_mmelrep($codCia, $sucursal, $codigo_cliente, $numero_documento, $numero_boleta_deposito, $TABLA, $ID, $PROCESO = 1)
    {
        $PASO = 9;
        // ACTUALIZAR TABLA DE APLICACIONES MMELREP
        print_r("ACTUALIZAR REGISTRO EN TABLA DE APLICACIONES MMELREP \n");
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
            print_r("SE DESACTIVÓ REGISTRO EN TABLA DE APLICACIONES \n");
            $this->registra_paso_proceso($PASO, $PROCESO, $TABLA, $ID);
            return true;
        } else {
            print_r("***ERROR: NO SE ACTUALIZÓ TABLA DE APLICACIONES \n");
            return false;
            //die(print_r($arrayWhere));
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
        print_r("Cantidad Eliminados MMCDRECA: " . $deleted ."\n");
        $this->registra_paso_proceso($PASO, $PROCESO, $TABLA, $ID);
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
    /* ::::::::::::::::::::: fin extornos :::::::::::::::::::::: */

    /* ::::::::::::::::::::: PAGOS :::::::::::::::: */
    public function processPaymentBanks($pago,$codCia){
        $paidDocuments = json_decode($pago->paidDocuments);
        $bankCode = $pago->bankCode;
        if (($bankCode === '011' || $bankCode === '009') && $pago->operationNumber) {
            $operationId = $pago->operationNumber;
        }else {
            $operationId = ($pago->operationId) ? $pago->operationId : $pago->requestId;
        }
        //$operationId = ($pago->operationId) ? $pago->operationId : $pago->requestId;
        $customerIdentificationCode = $pago->customerIdentificationCode;
        $channel = $pago->channel;
        $paymentType = $pago->paymentType;
        $currencyCode = ($pago->currencyCode === 'USD') ? '02' : '01';
        $bankAccount = $this->get_bank_accounts($codCia, $bankCode, $currencyCode, '01');
        $flag_result = false;
        if ($paidDocuments && is_array($paidDocuments) && sizeof($paidDocuments) > 1) //BCP
        {
            print_r("NO ESTÁ PERMITIDO EL PAGO MULTIPLE - BCP \n");
        } else {
            $sumPaidDocs = 0.0;
            if ($currencyCode === '01') {
                if ($tipo_cambio_dolar = $this->retorna_tipo_cambio_dolar_mym('02')) {
                    $precio_dolar_mym_venta = (float) round($tipo_cambio_dolar->mym_selling_price, 2);
                }
            }
            if ($paidDocuments && is_array($paidDocuments)) {
                foreach ($paidDocuments as $paidDoc) {
                    print_r("RECORREMOS EL DOCUMENTID: ".$paidDoc->documentId. "\n");
                    $documentAmountPaid = (float) round($paidDoc->amounts[0]->amount, 2);
                    $sumPaidDocs += $documentAmountPaid;
                    $arrayNroDoc = explode('-', $paidDoc->documentId);
                    print_r("arrayNROdOC SIZEOF: ".sizeof($arrayNroDoc). "\n");
                    if (sizeof($arrayNroDoc) == 2){ //DOCUMENTO A CRÉDITO
                        $formaPago = 'R';
                        $PROCESO = 3;
                        $ID_PAGO = $pago->id;
                        $docNumber = $arrayNroDoc[1];
                        $serieNumber = $arrayNroDoc[0];
                        if ($regSaldo = $this->retorna_doc_cliente_saldo_interface_fac($customerIdentificationCode,$formaPago,$serieNumber, $docNumber)) {
                            $docType = $regSaldo->ABTIPDOC;
                            $codSucDeposito = ($regSaldo->ABCODSUC === '01') ? '02' : $regSaldo->ABCODSUC;
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
                            //VERIFICAR/REGISTRAR PLANILLA DE COBRANZAS (CREDITO) TABLA (MMDLREP)
                            $arrayWhere = array(
                                ['DLCODCIA', '=', $codCia],
                                ['DLCODSUC', '=', $codSucDeposito],
                                ['DLFECPLL', '=', date("Ymd")],
                                ['DLSTS', '=', 'A'],
                                ['DLSTSPLL', '=', 'A'],
                                ['DLCODCBR', '=', $this->codCobrador]
                            );
                            //FIN VERIFICAR/REGISTRAR PLANILLA DE COBRANZAS (CREDITO) TABLA (MMDLREP)
                            if (!$regPlanillaCobranzas = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMDLREP', $arrayWhere)) {
                                $regPlanillaCobranzas = $this->registra_planilla_cobranzas_credito($codCia, $currencyCode, $documentAmountPaid);
                            }
                            //VALIDAMOS SI REGISTRO U OBTUVIMOS LA PLANILLA
                            if (is_object($regPlanillaCobranzas)) {
                                $correlativoPlanillaCobranzas = $regPlanillaCobranzas->dlnropll;
                                //VERIFICAR/REGISTRAR SI EXISTE PLANILLA DE COBRANZAS DEL DÍA
                                print_r("PLANILLA DE COBRANZAS CREDITO : " . $correlativoPlanillaCobranzas."\n");
                                $arrayWhere = array(
                                    ['IECODCIA', '=', $codCia],
                                    ['IECODSUC', '=', $codSucDeposito],
                                    ['IENROPLL', '=', $correlativoPlanillaCobranzas],
                                );
                                if (!$regTipoPlanilla = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMIEREP', $arrayWhere)) {
                                    //OBTENER CORRELATIVO DE PLANILLA DE COBRANZAS EN TABLA NUMERADORES (MMFCREL0)
                                    $regTipoPlanilla = $this->registra_planilla_cobranzas_dia($codCia, $correlativoPlanillaCobranzas, $this->codCobrador, $this->tipoPlanillaCredito, $this->user, $this->app);
                                }
                                print_r("PLANILLA DE COBRANZAS DEL DÍA: " . $correlativoPlanillaCobranzas."\n");

                                // PROCEDEMOS A REGISTRAR EL DEPOSITO
                                $datosDeposito = $this->registra_deposito_bancario_mmyprep($codCia, $regSaldo->ABCODSUC, $bankAccount[0]->erp_code, $operationId, $regSaldo, $bankAccount, $pago);
                                $correlativoBoletaDeposito = $datosDeposito->ypnrodep;
                                print_r("Depósito ". $correlativoBoletaDeposito ."   --- Fecha: " . date('Y-m-d H:i:s')."\n");
                                $this->registra_paso_proceso(14, $PROCESO, $this->tablas['tabla_mmyprep'], $ID_PAGO);

                                //<br> BUSQUEDA / REGISTRO DE DOCUMENTO EN TABLA MMDMREP
                                if (!$this->verifica_paso_proceso(17, $PROCESO, $this->tablas['tabla_mmdmrep'], $ID_PAGO)) {
                                    if ($this->procesa_registro_tabla_mmdmrep($codCia, $correlativoPlanillaCobranzas, $datos_doc_db2, $monto_usd_documento_pagado, $datos_doc_db2->abcodmon)) {
                                        $this->registra_paso_proceso(17, $PROCESO, $this->tablas['tabla_mmdmrep'], $ID_PAGO);
                                        print_r("FIN BUSQUEDA / REGISTRO DE DOCUMENTO EN TABLA MMDMREP\n");
                                    } else {
                                        print_r("ATENCIÓN::: PROCESO DE BÚSQUEDA/REGISTRO DE DOCUMENTO EN TABLA MMDMREP NO REALIZADO \n");
                                    }
                                }

                                print_r("BÚSQUEDA/REGISTRO DE DOCUMENTO EN TABLA MMDNREP - 18 \n");
                                if (!$this->verifica_paso_proceso(18, $PROCESO, $this->tablas['tabla_mmdnrep'], $ID_PAGO)) {

                                    if ($this->procesa_registro_tabla_mmdnrep($codCia, $correlativoPlanillaCobranzas, $datos_doc_db2->abcodcli, $correlativoBoletaDeposito, $documentAmountPaid, $operationId, $bankAccount, $currencyCode)) {
                                        $this->registra_paso_proceso(18, $PROCESO, $this->tablas['tabla_mmdnrep'], $ID_PAGO);
                                        print_r("FIN BUSQUEDA / REGISTRO DE DOCUMENTO EN TABLA MMDNREP - 18 \n");
                                    } else {
                                        print_r("ATENCIÓN::: PROCESO DE BÚSQUEDA/REGISTRO DE DOCUMENTO EN TABLA MMDNREP NO REALIZADO \n");
                                    }
                                }

                                print_r("BUSQUEDA / REGISTRO DE DOCUMENTO EN TABLA MMDOREP - 19 \n");
                                if (!$this->verifica_paso_proceso(19, $PROCESO, $this->tablas['tabla_mmdorep'], $ID_PAGO)) {
                                    if ($this->procesa_registro_tabla_mmdorep($codCia, $correlativoPlanillaCobranzas, $datos_doc_db2, $correlativoBoletaDeposito, $monto_usd_documento_pagado, $datos_doc_db2->abcodmon)) {
                                        $this->registra_paso_proceso(19, $PROCESO, $this->tablas['tabla_mmdorep'], $ID_PAGO);
                                        print_r("FIN BUSQUEDA / REGISTRO DE DOCUMENTO EN TABLA MMDOREP - 19 \n");
                                    } else {
                                        print_r("ATENCIÓN::: PROCESO DE BÚSQUEDA/REGISTRO DE DOCUMENTO EN TABLA MMDOREP NO REALIZADO \n");
                                    }
                                }
                                //print_r("MOSTRAMOS LOS VALORES DE CLIENTE SALDO ".json_encode($regSaldo)."\n");
                                print_r("ACTUALIZAR TABLA DE SALDOS AUXILIAR (CCAPLBCO) - 20 \n");
                                if (!$this->verifica_paso_proceso(20, $PROCESO, $this->tablas['tabla_saldos_aux_db2'], $ID_PAGO)) {
                                    if ($this->actualizar_tabla_saldos_auxiliar($codCia, $datos_doc_db2, $documentAmountPaid, $operationId, $bankAccount, $regSaldo->ABIMPSLD, 0)) {
                                        $this->registra_paso_proceso(20, $PROCESO, $this->tablas['tabla_saldos_aux_db2'], $ID_PAGO);
                                        print_r("FIN ACTUALIZAR TABLA DE SALDOS AUXILIAR (CCAPLBCO) - 20 \n");
                                    } else {
                                        print_r("ERROR ACTUALIZANDO TABLA DE SALDOS AUXILIAR (CCAPLBCO) - 20 \n");
                                        // die('<br> ERROR ACTUALIZANDO TABLA DE SALDOS AUXILIAR (CCAPLBCO) - 20');
                                    }
                                }else{
                                    print_r("ERROR ESTE PROCESO YA FUE APLICADO ID_PAGO:".$ID_PAGO." AUXILIAR (CCAPLBCO) - 20 \n");
                                }

                                print_r("ACTUALIZAR TABLA DE SALDOS PRINCIPAL (MMEIREP) - 21 \n");
                                if (!$this->verifica_paso_proceso(21, $PROCESO, $this->tablas['tabla_saldos_principal'], $ID_PAGO)) {
                                    if ($this->actualizar_tabla_saldos_principal($codCia, $datos_doc_db2, $regSaldo->ABIMPSLD)) {
                                        $this->registra_paso_proceso(21, $PROCESO, $this->tablas['tabla_saldos_principal'], $ID_PAGO);
                                        print_r("FIN ACTUALIZAR TABLA DE SALDOS PRINCIPAL (MMEIREP) - 21 \n");
                                    } else {
                                        print_r("ERROR ACTUALIZANDO TABLA DE SALDOS PRINCIPAL (MMEIREP) - 21 \n");
                                        // die('<br> ERROR ACTUALIZANDO TABLA DE SALDOS PRINCIPAL (MMEIREP) - 21');
                                    }
                                }else{
                                    print_r("ERROR ESTE PROCESO YA FUE APLICADO ID_PAGO:".$ID_PAGO." PRINCIPAL (MMEIREP) - 21 \n");
                                }

                                print_r("REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) PADRE - 22 \n");
                                if (!$this->verifica_paso_proceso(22, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_PAGO)) {
                                    if ($this->registra_historicos_padre($codCia, $datos_doc_db2, $regSaldo->ABIMPSLD)) {
                                        $this->registra_paso_proceso(22, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_PAGO);
                                        print_r("FIN REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) PADRE - 22 \n");
                                    } else {
                                        print_r("ATENCIÓN::: PROCESO DE REGISTRO EN TABLA HISTÓRICO DE SALDOS (MMEJREP) PADRE NO REALIZADO \n");
                                        // die("<br>ATENCIÓN::: PROCESO DE REGISTRO EN TABLA HISTÓRICO DE SALDOS (MMEJREP) PADRE NO REALIZADO");
                                    }
                                }

                                print_r("REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) HIJO - 23 \n");
                                if (!$this->verifica_paso_proceso(23, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_PAGO)) {
                                    if ($this->registra_tabla_historicos_hijo($codCia, $correlativoBoletaDeposito, $datos_doc_db2, $nuevo_saldo, $documentAmountPaid, $currencyCode)) {
                                        $this->registra_paso_proceso(23, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_PAGO);
                                        print_r("FIN REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) HIJO - 23 \n");
                                    } else {
                                        print_r("ATENCIÓN::: PROCESO DE REGISTRO EN TABLA HISTÓRICO DE SALDOS (MMEJREP) HIJO NO REALIZADO \n");
                                    }
                                }

                                print_r("REGISTRAR EN TABLA DE APLICACIONES (MMELREP) - 24 \n");
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
                                        print_r("FIN REGISTRAR EN TABLA DE APLICACIONES (MMELREP) - 24 \n");
                                    } else {
                                        print_r("ATENCIÓN::: PROCESO DE REGISTRO EN TABLA DE APLICACIONES (MMELREP) NO REALIZADO \n");
                                    }
                                }

                                print_r("REGISTRAR EN TABLA MMCDRECA - 26 \n");
                                if (!$this->verifica_paso_proceso(26, $PROCESO, $this->tablas['tabla_mmcdreca'], $ID_PAGO)) {
                                    if ($this->registra_tabla_mmcdreca($codCia, $bankAccount, $datos_doc_db2, $correlativoBoletaDeposito, $operationId, $correlativoPlanillaCobranzas, $pago)) {
                                        $this->registra_paso_proceso(26, $PROCESO, $this->tablas['tabla_mmcdreca'], $ID_PAGO);
                                        print_r("FIN REGISTRAR EN TABLA MMCDRECA - 26 \n");
                                    } else {
                                        print_r("ATENCIÓN::: PROCESO DE REGISTRO EN TABLA (MMCDRECA) NO REALIZADO \n");
                                    }
                                }

                                print_r("REGISTRAR EN TABLA MMCCRECA \n");
                                $this->escribe_actualiza_tabla_mmccreca($codCia, $bankAccount, $pago, $correlativoPlanillaCobranzas);
                                print_r("FIN REGISTRAR EN TABLA MMCCRECA \n");
                                print_r("FIN DOCUMENTO:".$arrayNroDoc[1]." - CREDITO \n");
                                $flag_result=true;
                            }else{
                                print_r("NO SE ENCONTRO PLANILLA COBRANZA CREDITO \n");
                            }//FIN IF PLANILLA COBRANZA
                            
                        }
                    }elseif (sizeof($arrayNroDoc) == 3)//DOCUMENTO DE CONTADO
                    {
                        $formaPago='C';
                        print_r("PROCESAMOS EL DOCUMENTO:".$arrayNroDoc[2]." - CONTADO \n");
                        $docNumber = $arrayNroDoc[2]; //NRO PEDIDO EN SALDOS
                        $docType = $arrayNroDoc[1];
                        $codSuc = $pago->related_debts[0]->ABCODSUC;
                        $PROCESO = 4;
                        $ID_PAGO = $pago->id;
                        //OBTENER DOC DESDE TABLA DE SALDOS
                        if ($regSaldo = $this->retorna_doc_cliente_saldo_interface($codCia, $codSuc, $customerIdentificationCode, $formaPago,$docType, $docNumber)) {
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
                                print_r("1.- NO HAY PLANILLA CREADA PARA LA FECHA ". date("Ymd") . "- Cia: " . $codCia . " - Suc: " . $codSucDeposito."\n");
                                print_r("DETENEMOS RECORRIDO CONTADO \n");                                
                            }else{
                                //PROCEDEMOS A REGISTRAR EL DEPOSITO EN LA MMYPREP
                                $datosDeposito = $this->registra_deposito_bancario_mmyprep($codCia, $regSaldo->ABCODSUC, $bankAccount[0]->erp_code, $operationId, $regSaldo, $bankAccount, $pago);
                                $correlativoBoletaDeposito = $datosDeposito->ypnrodep;
                                print_r("Depósito ". $correlativoBoletaDeposito ."   --- Fecha: " . date('Y-m-d H:i:s')."\n");
                                $this->registra_paso_proceso(14, $PROCESO, $this->tablas['tabla_mmyprep'], $ID_PAGO);

                                $correlativoPlanillaCobranzas = $regPlanilla->cjapnpll;
                                print_r(" - PLANILLA COBRANZAS: $correlativoPlanillaCobranzas \n");
                                $documentAmountPaid = (float)round($documentAmountPaid, 2);
                                $nuevo_saldo = 0;
                                print_r("ACTUALIZAR TABLA DE SALDOS AUXILIAR (CCAPLBCO) - 20 \n");
                                if (!$this->verifica_paso_proceso(20, $PROCESO, $this->tablas['tabla_saldos_aux_db2'], $ID_PAGO)) {
                                    if ($this->actualizar_tabla_saldos_auxiliar($codCia, $datos_doc_db2, $documentAmountPaid, $operationId, $bankAccount, $nuevo_saldo, 2)) {
                                        $this->registra_paso_proceso(20, $PROCESO, $this->tablas['tabla_saldos_aux_db2'], $ID_PAGO);
                                    } else {
                                        print_r("ERROR ACTUALIZANDO TABLA DE SALDOS AUXILIAR (CCAPLBCO) - 20 \n");
                                    }
                                }
                                print_r("FIN ACTUALIZAR TABLA DE SALDOS AUXILIAR (CCAPLBCO) - 20 \n");

                                print_r("ACTUALIZAR TABLA DE SALDOS PRINCIPAL (MMEIREP) - 21 \n");
                                if (!$this->verifica_paso_proceso(21, $PROCESO, $this->tablas['tabla_saldos_principal'], $ID_PAGO)) {
                                    $this->actualizar_tabla_saldos_principal($codCia, $datos_doc_db2, $nuevo_saldo);
                                    $this->registra_paso_proceso(21, $PROCESO, $this->tablas['tabla_saldos_principal'], $ID_PAGO);
                                }
                                print_r("FIN ACTUALIZAR TABLA DE SALDOS PRINCIPAL (MMEIREP) - 21 \n");

                                print_r("REGISTRA/ACTUALIZA ENCABEZADO DE PAGOS - MMCJFCB - 27 \n");
                                if (!$this->verifica_paso_proceso(27, $PROCESO, $this->tablas['tabla_mmdorep'], $ID_PAGO)) {
                                    if ($this->registra_actualiza_encabezado_pagos_contado_mmcjfcb($codCia, $codSuc, $docNumber, $datos_doc_db2, $regPlanilla)) {
                                        $this->registra_paso_proceso(27, $PROCESO, $this->tablas['tabla_mmdorep'], $ID_PAGO);
                                        print_r("FIN REGISTRA/ACTUALIZA ENCABEZADO DE PAGOS - MMCJFCB - 27 \n");
                                    } else {
                                        print_r("ERROR REGISTRANDO/ACTUALIZANDO ENCABEZADO DE PAGOS - MMCJFCB - 27 \n");
                                    }
                                }

                                print_r("REGISTRA DETALLE DE PAGOS - MMCJFDT - 28 \n");
                                if (!$this->verifica_paso_proceso(28, $PROCESO, $this->tablas['tabla_mmdorep'], $ID_PAGO)) {
                                    if ($this->registra_detalle_pagos_contado_mmcjfdt($codCia, $codSuc, $docNumber, $regPlanilla, $correlativoBoletaDeposito, $operationId, $bankAccount, $currencyCode, $datos_doc_db2, $documentAmountPaid)) {
                                        $this->registra_paso_proceso(28, $PROCESO, $this->tablas['tabla_mmdorep'], $ID_PAGO);
                                        print_r("FIN REGISTRA DETALLE DE PAGOS - MMCJFDT - 28 \n");
                                    } else {
                                        print_r("ERROR REGISTRANDO/ACTUALIZANDO DETALLE DE PAGOS - MMCJFDT - 28 \n");
                                    }
                                }

                                print_r("REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) PADRE - 22 \n");
                                if (!$this->verifica_paso_proceso(22, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_PAGO)) {
                                    if ($this->registra_historicos_padre($codCia, $datos_doc_db2, $regSaldo->ABIMPSLD)) {
                                        $this->registra_paso_proceso(22, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_PAGO);
                                        print_r("FIN REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) PADRE - 22 \n");
                                    } else {
                                        print_r("ATENCIÓN::: PROCESO DE REGISTRO EN TABLA HISTÓRICO DE SALDOS (MMEJREP) PADRE NO REALIZADO \n");
                                    }
                                }

                                print_r("REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) HIJO - 23 \n");
                                if (!$this->verifica_paso_proceso(23, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_PAGO)) {
                                    if ($this->registra_tabla_historicos_hijo($codCia, $correlativoBoletaDeposito, $datos_doc_db2, $nuevo_saldo, $documentAmountPaid, $currencyCode)) {
                                        $this->registra_paso_proceso(23, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_PAGO);
                                        print_r("FIN REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) HIJO - 23 \n");
                                    } else {
                                        print_r("ATENCIÓN::: PROCESO DE REGISTRO EN TABLA HISTÓRICO DE SALDOS (MMEJREP) HIJO NO REALIZADO \n");
                                    }
                                }

                                print_r("REGISTRAR EN TABLA DE APLICACIONES (MMELREP) - 24 \n");
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
                                        print_r("FIN REGISTRAR EN TABLA DE APLICACIONES (MMELREP) - 24");
                                    } else {
                                        print_r("ATENCIÓN::: PROCESO DE REGISTRO EN TABLA DE APLICACIONES (MMELREP) NO REALIZADO \n");
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
                                                print_r("FIN REGISTRAR PAGOS PARCIALES EN TABLA DE HISTÓRICO DE SALDOS (MMEJREP) HIJO - 29 \n");
                                            } else {
                                                print_r("ATENCIÓN::: PROCESO DE PAGOS PARCIALES EN TABLA DE HISTÓRICO DE SALDOS (MMEJREP) HIJO NO REALIZADO \n");
                                            }
                                        }

                                        if (!$this->verifica_paso_proceso(30, $PROCESO, $this->tablas['tabla_aplicaciones_mmelrep'], $ID_PAGO)) {
                                            if ($this->registrar_pagos_parciales_en_mmelrep($codCia, $datos_doc_db2, $pagos_parciales)) {
                                                $this->registra_paso_proceso(30, $PROCESO, $this->tablas['tabla_aplicaciones_mmelrep'], $ID_PAGO);
                                                print_r("FIN REGISTRAR PAGOS PARCIALES EN TABLA DE APLICACIONES (MMELREP) - 30 \n");
                                            } else {
                                                print_r("ATENCIÓN::: PROCESO DE PAGOS PARCIALES EN TABLA DE APLICACIONES (MMELREP) NO REALIZADO \n");
                                            }
                                        }
                                    }
                                }
                                //FIN - REGISTRAR PAGOS PARCIALES EN TABLAS MMEJREP Y MMELREP
                                print_r("REGISTRAR EN TABLA MMCDRECA - 26 \n");
                                if (!$this->verifica_paso_proceso(26, $PROCESO, $this->tablas['tabla_mmcdreca'], $ID_PAGO)) {
                                    if ($this->registra_tabla_mmcdreca($codCia, $bankAccount, $datos_doc_db2, $correlativoBoletaDeposito, $operationId, $correlativoPlanillaCobranzas, $pago)) {
                                        $this->registra_paso_proceso(26, $PROCESO, $this->tablas['tabla_mmcdreca'], $ID_PAGO);
                                        print_r("FIN REGISTRAR EN TABLA MMCDRECA - 26 \n");
                                    } else {
                                        print_r("ATENCIÓN::: PROCESO DE REGISTRO EN TABLA (MMCDRECA) NO REALIZADO \n");
                                    }
                                }

                                print_r("REGISTRAR EN TABLA MMCCRECA \n");
                                $this->escribe_actualiza_tabla_mmccreca($codCia, $bankAccount, $pago, $correlativoPlanillaCobranzas);
                                print_r("FIN REGISTRAR EN TABLA MMCCRECA \n");
                                print_r("FIN DOCUMENTO: - CONTADO \n");
                                $flag_result= true;
                            }
                            
                        }
                    }else{ // DOCUMENTOS (DA, DP)
                        $totalAmountPaid = (float) round($pago->totalAmount, 2);
                        $vector = explode('_', $paidDocuments[0]->documentId);
                        switch ($vector[0]) {
                            case 'DA':
                                print_r("DOCUMENTO COMPUESTO: " . $paidDocuments[0]->documentId. "\n");
                                $numero_grupo = $vector[1];
                                $formaPago = $vector[2];
                                $docType = 'DA';
                                //OBTENER DOC DESDE TABLA DE SALDOS
                                if ($regSaldoCompuesto = $this->retorna_doc_cliente_saldo_interface($codCia, '', $customerIdentificationCode, $formaPago,$docType, $numero_grupo)) {
                                    $docType = $vector[0];
                                    
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
                                    print_r("DEPOSITO: $totalAmountPaid | SA USD: $total_saldo_USD - DEPOSITO USD: $total_monto_pagado_USD \n");
                                    //VALIDAR QUE EL MONTO DEL DEPÓSITO COINCIDA CON LA SUMA DEL SALDO DE TODOS LOS DOCUMENTOS
                                    if ($this->validar_monto_deposito_documento_compuesto($datos_documentos, $total_monto_pagado_USD)) {
                                        $codigo_cliente = $datos_grupo->daccli;
                                        //PROCESA DEPÓSITO
                                        $datosDeposito = $this->registra_deposito_bancario_mmyprep($codCia, '01', $bankAccount[0]->erp_code, $operationId, $regSaldoCompuesto, $bankAccount, $pago);
                                        $correlativoBoletaDeposito = $datosDeposito->ypnrodep;
                                        print_r("Depósito " . $correlativoBoletaDeposito."\n");
                                        //$currencyCode = $datos_grupo->dacmon;
                                        print_r("RECORREMOS LA TABLA : LIBPRDDAT.CCDOCAGD \n");
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

                                            }

                                            if ($datos_doc_db2->abfrmpag == 'R') {//DOCUMENTO A CRÉDITO
                                                print_r("DOCUMENTO AGRUPADO A CRÉDITO \n");
                                                $PROCESO = 3;
                                                $datos_deuda = $this->retorna_datos_deuda_cliente_saldos($datos_doc_db2);
                                                if (is_object($datos_deuda)) {
                                                    print_r("Deuda de documento no encontrada \n");
                                                    $ID_DEUDA = $datos_deuda->id;
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
                                                    print_r("OK -> Correlativo: $correlativoPlanillaCobranzas \n");
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
                                                    print_r("PLANILLA DE COBRANZAS DEL DÍA: " . $correlativoPlanillaCobranzas."\n");
                                                    //FIN GENERACION PLANILLA DE COBRANZAS
                                                    //$documentAmountPaid = (float) round($datos_doc_db2->abimpsld, 2);
                                                    //<br> BUSQUEDA / REGISTRO DE DOCUMENTO EN TABLA MMDMREP
                                                    if (!$this->verifica_paso_proceso(17, $PROCESO, $this->tablas['tabla_mmdmrep'], $ID_DEUDA)) {
                                                        if ($this->procesa_registro_tabla_mmdmrep($codCia, $correlativoPlanillaCobranzas, $datos_doc_db2, $datos_doc_db2->abimpsld, $datos_doc_db2->abcodmon)) {
                                                            $this->registra_paso_proceso(17, $PROCESO, $this->tablas['tabla_mmdmrep'], $ID_DEUDA);
                                                            print_r("FIN BUSQUEDA / REGISTRO DE DOCUMENTO EN TABLA MMDMREP \n");
                                                        }
                                                    }
                                                    print_r("BUSQUEDA / REGISTRO DE DOCUMENTO EN TABLA MMDNREP - 18 \n");
                                                    if (!$this->verifica_paso_proceso(18, $PROCESO, $this->tablas['tabla_mmdnrep'], $ID_DEUDA)) {
                                                        if ($this->procesa_registro_tabla_mmdnrep($codCia, $correlativoPlanillaCobranzas, $datos_doc_db2->abcodcli, $correlativoBoletaDeposito, $totalAmountPaid, $operationId, $bankAccount, $currencyCode)) {
                                                            $this->registra_paso_proceso(18, $PROCESO, $this->tablas['tabla_mmdnrep'], $ID_DEUDA);
                                                            print_r("FIN BUSQUEDA / REGISTRO DE DOCUMENTO EN TABLA MMDNREP - 18 \n");
                                                        }
                                                    }
                                                    print_r("BUSQUEDA / REGISTRO DE DOCUMENTO EN TABLA MMDOREP - 19 \n");
                                                    if (!$this->verifica_paso_proceso(19, $PROCESO, $this->tablas['tabla_mmdorep'], $ID_DEUDA)) {
                                                        if ($this->procesa_registro_tabla_mmdorep($codCia, $correlativoPlanillaCobranzas, $datos_doc_db2, $correlativoBoletaDeposito, $datos_doc_db2->abimpsld, $datos_doc_db2->abcodmon)) { //IMPORTE ORIGINAL
                                                            $this->registra_paso_proceso(19, $PROCESO, $this->tablas['tabla_mmdorep'], $ID_DEUDA);
                                                            print_r("FIN BUSQUEDA / REGISTRO DE DOCUMENTO EN TABLA MMDOREP - 19 \n");
                                                        }
                                                    }
                                                    $nuevo_saldo = 0;
                                                    //die("<br>Pago: $documentAmountPaid - SA: $saldo_actual - NS: $nuevo_saldo");
                                                    print_r("ACTUALIZAR TABLA DE SALDOS AUXILIAR (CCAPLBCO) - 20 \n");
                                                    if (!$this->verifica_paso_proceso(20, $PROCESO, $this->tablas['tabla_saldos_aux_db2'], $ID_DEUDA)) {
                                                        if ($this->actualizar_tabla_saldos_auxiliar($codCia, $datos_doc_db2, $totalAmountPaid, $operationId, $bankAccount, $nuevo_saldo, 0)) {
                                                            $this->registra_paso_proceso(20, $PROCESO, $this->tablas['tabla_saldos_aux_db2'], $ID_DEUDA);
                                                            print_r("FIN ACTUALIZAR TABLA DE SALDOS AUXILIAR (CCAPLBCO) - 20 \n");
                                                        } else {
                                                            print_r("ERROR ACTUALIZANDO TABLA DE SALDOS AUXILIAR (CCAPLBCO) - 20 \n");
                                                        }
                                                    }
                                                    print_r("ACTUALIZAR TABLA DE SALDOS PRINCIPAL (MMEIREP) - 21 \n");
                                                    if (!$this->verifica_paso_proceso(21, $PROCESO, $this->tablas['tabla_saldos_principal'], $ID_DEUDA)) {
                                                        if ($this->actualizar_tabla_saldos_principal($codCia, $datos_doc_db2, $nuevo_saldo)) {
                                                            $this->registra_paso_proceso(21, $PROCESO, $this->tablas['tabla_saldos_principal'], $ID_DEUDA);
                                                            echo '<br>FIN ACTUALIZAR TABLA DE SALDOS PRINCIPAL (MMEIREP) - 21';
                                                        } else {
                                                            print_r("ERROR ACTUALIZANDO TABLA DE SALDOS PRINCIPAL (MMEIREP) - 21 \n");
                                                        }
                                                    }
                                                    print_r("REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) PADRE - 22 \n");
                                                    if (!$this->verifica_paso_proceso(22, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_DEUDA)) {
                                                        if ($this->registra_historicos_padre($codCia, $datos_doc_db2, $nuevo_saldo)) {
                                                            $this->registra_paso_proceso(22, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_DEUDA);
                                                            print_r("FIN REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) PADRE - 22 \n");
                                                        } else {
                                                            print_r("ATENCIÓN::: PROCESO DE REGISTRO EN TABLA HISTÓRICO DE SALDOS (MMEJREP) PADRE NO REALIZADO \n");
                                                        }
                                                    }
                                                    print_r("REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) HIJO - 23 \n");
                                                    if (!$this->verifica_paso_proceso(23, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_DEUDA)) {
                                                        if ($this->registra_tabla_historicos_hijo($codCia, $correlativoBoletaDeposito, $datos_doc_db2, $nuevo_saldo, $saldo_actual_documento, $currencyCode)) {
                                                            $this->registra_paso_proceso(23, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_DEUDA);
                                                            print_r("FIN REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) HIJO - 23 \n");
                                                        } else {
                                                            print_r("ATENCIÓN::: PROCESO DE REGISTRO EN TABLA HISTÓRICO DE SALDOS (MMEJREP) HIJO NO REALIZADO \n");
                                                        }
                                                    }
                                                    print_r("REGISTRAR EN TABLA DE APLICACIONES (MMELREP) - 24 \n");
                                                    if (!$this->verifica_paso_proceso(24, $PROCESO, $this->tablas['tabla_aplicaciones_mmelrep'], $ID_DEUDA)) {
                                                        if ($this->registrar_tabla_aplicaciones($codCia, $datos_doc_db2, $correlativoBoletaDeposito, $datos_doc_db2->abimpsld, $saldo_actual_documento)) {
                                                            $this->registra_paso_proceso(24, $PROCESO, $this->tablas['tabla_aplicaciones_mmelrep'], $ID_DEUDA);
                                                            print_r("FIN REGISTRAR EN TABLA DE APLICACIONES (MMELREP) - 24 \n");
                                                        } else {
                                                            print_r("ATENCIÓN::: PROCESO DE REGISTRO EN TABLA DE APLICACIONES (MMELREP) NO REALIZADO \n");
                                                        }
                                                    }
                                                    print_r("REGISTRAR EN TABLA MMCDRECA - 26 \n");
                                                    if (!$this->verifica_paso_proceso(26, $PROCESO, $this->tablas['tabla_mmcdreca'], $ID_DEUDA)) {
                                                        if ($this->registra_tabla_mmcdreca($codCia, $bankAccount, $datos_doc_db2, $correlativoBoletaDeposito, $operationId, $correlativoPlanillaCobranzas, $pago)) {
                                                            $this->registra_paso_proceso(26, $PROCESO, $this->tablas['tabla_mmcdreca'], $ID_DEUDA);
                                                            print_r("FIN REGISTRAR EN TABLA MMCDRECA - 26 \n");
                                                        } else {
                                                            print_r("ATENCIÓN::: PROCESO DE REGISTRO EN TABLA (MMCDRECA) NO REALIZADO \n");
                                                        }
                                                    }

                                                    print_r("REGISTRAR EN TABLA MMCCRECA \n");
                                                    $this->escribe_actualiza_tabla_mmccreca($codCia, $bankAccount, $pago, $correlativoPlanillaCobranzas);
                                                    //$this->actualiza_saldos_mmccreca($codCia, $bankAccount[0]->nro_cuenta, date("Ymd"));
                                                    print_r("FIN REGISTRAR EN TABLA MMCCRECA \n");
                                                    print_r("FIN DOCUMENTO AGRUPADO A CRÉDITO \n");
                                                    $flag_result=true;
                                                } else {
                                                    print_r("Deuda de documento no encontrada \n");
                                                    $flag_result= false;
                                                    break;
                                                }
                                            }else{ //DOCUMENTO AGRUPADO CONTADO
                                                // *** DOCUMENTO DE CONTADO ***  //
                                                print_r("DOCUMENTO AGRUPADO CONTADO \n");
                                                $PROCESO = 4;
                                                $datos_deuda = $this->retorna_datos_deuda_cliente_saldos($datos_doc_db2);
                                                if (is_object($datos_deuda)) {
                                                    $ID_DEUDA = $datos_deuda->id;
                                                    $codSuc = $datos_doc_db2->abcodsuc;
                                                    $docNumber = $datos_doc_db2->abnrodoc; //NRO PEDIDO EN SALDOS
                                                    $docType = $datos_doc_db2->abtipdoc;
                                                    //OBTENER PLANILLA DEL DÍA
                                                    if (!$regPlanilla = $this->retorna_planilla_contado_actual_db2($codCia, $codSuc, date("Ymd"))) {
                                                        print_r("2.- NO HAY PLANILLA CREADA PARA LA FECHA " . date("Ymd") . " - Cia: " . $codCia . " - Suc: " . $codSuc . "\n");
                                                        $flag_result= false;
                                                    }else{
                                                        $nuevo_saldo = 0;
                                                        print_r("ACTUALIZAR TABLA DE SALDOS AUXILIAR (CCAPLBCO) - 20 \n");
                                                        if (!$this->verifica_paso_proceso(20, $PROCESO, $this->tablas['tabla_saldos_aux_db2'], $ID_DEUDA)) {
                                                            if ($this->actualizar_tabla_saldos_auxiliar($codCia, $datos_doc_db2, $totalAmountPaid, $operationId, $bankAccount, $nuevo_saldo, 2)) {
                                                                $this->registra_paso_proceso(20, $PROCESO, $this->tablas['tabla_saldos_aux_db2'], $ID_DEUDA);
                                                            } else {
                                                                print_r("ERROR ACTUALIZANDO TABLA DE SALDOS AUXILIAR (CCAPLBCO) - 20 \n");
                                                            }
                                                        }
                                                        print_r("FIN ACTUALIZAR TABLA DE SALDOS AUXILIAR (CCAPLBCO) - 20 \n");
                                                        print_r("ACTUALIZAR TABLA DE SALDOS PRINCIPAL (MMEIREP) - 21 \n");
                                                        if (!$this->verifica_paso_proceso(21, $PROCESO, $this->tablas['tabla_saldos_principal'], $ID_DEUDA)) {
                                                            $this->actualizar_tabla_saldos_principal($codCia, $datos_doc_db2, $nuevo_saldo);
                                                            $this->registra_paso_proceso(21, $PROCESO, $this->tablas['tabla_saldos_principal'], $ID_DEUDA);
                                                        }
                                                        print_r("FIN ACTUALIZAR TABLA DE SALDOS PRINCIPAL (MMEIREP) - 21 \n");
                                                        print_r("REGISTRA/ACTUALIZA ENCABEZADO DE PAGOS - MMCJFCB - 27 \n");
                                                        if (!$this->verifica_paso_proceso(27, $PROCESO, $this->tablas['tabla_mmdorep'], $ID_DEUDA)) {
                                                            if ($this->registra_actualiza_encabezado_pagos_contado_mmcjfcb($codCia, $codSuc, $docNumber, $datos_doc_db2, $regPlanilla)) {
                                                                $this->registra_paso_proceso(27, $PROCESO, $this->tablas['tabla_mmdorep'], $ID_DEUDA);
                                                            } else {
                                                                print_r("ERROR REGISTRANDO/ACTUALIZANDO ENCABEZADO DE PAGOS - MMCJFCB - 27 \n");
                                                            }
                                                        }
                                                        print_r("FIN REGISTRA/ACTUALIZA ENCABEZADO DE PAGOS - MMCJFCB - 27 \n");
                                                        print_r("REGISTRA DETALLE DE PAGOS - MMCJFDT - 28 \n");
                                                        if (!$this->verifica_paso_proceso(28, $PROCESO, $this->tablas['tabla_mmdorep'], $ID_DEUDA)) {
                                                            $this->registra_detalle_pagos_contado_mmcjfdt($codCia, $codSuc, $docNumber, $regPlanilla, $correlativoBoletaDeposito, $operationId, $bankAccount, $currencyCode, $datos_doc_db2, $saldo_actual_documento);
                                                            $this->registra_paso_proceso(28, $PROCESO, $this->tablas['tabla_mmdorep'], $ID_DEUDA);
                                                        }
                                                        print_r("FIN REGISTRA DETALLE ENCABEZADO DE PAGOS - MMCJFDT - 28 \n");
                                                        print_r("REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) PADRE - 22 \n");
                                                        if (!$this->verifica_paso_proceso(22, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_DEUDA)) {
                                                            $this->registra_historicos_padre($codCia, $datos_doc_db2, $nuevo_saldo);
                                                            $this->registra_paso_proceso(22, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_DEUDA);
                                                        }
                                                        print_r("FIN REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) PADRE - 22 \n");
                                                        print_r("REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) HIJO - 23 \n");
                                                        if (!$this->verifica_paso_proceso(23, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_DEUDA)) {
                                                            $this->registra_tabla_historicos_hijo($codCia, $correlativoBoletaDeposito, $datos_doc_db2, $nuevo_saldo, $saldo_actual_documento, $currencyCode);
                                                            $this->registra_paso_proceso(23, $PROCESO, $this->tablas['tabla_historicos_mmejrep'], $ID_DEUDA);
                                                        }
                                                        print_r("FIN REGISTRAR EN TABLA HISTÓRICO DE SALDOS (MMEJREP) HIJO - 23 \n");
                                                        print_r("REGISTRAR EN TABLA DE APLICACIONES (MMELREP) - 24 \n");
                                                        if (!$this->verifica_paso_proceso(24, $PROCESO, $this->tablas['tabla_aplicaciones_mmelrep'], $ID_DEUDA)) {
                                                            $this->registrar_tabla_aplicaciones($codCia, $datos_doc_db2, $correlativoBoletaDeposito, $datos_doc_db2->abimpsld, $saldo_actual_documento);
                                                            $this->registra_paso_proceso(24, $PROCESO, $this->tablas['tabla_aplicaciones_mmelrep'], $ID_DEUDA);
                                                        }
                                                        print_r("FIN REGISTRAR EN TABLA DE APLICACIONES (MMELREP) - 24 \n");
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
                                                                        print_r("FIN REGISTRAR PAGOS PARCIALES EN TABLA DE HISTÓRICO DE SALDOS (MMEJREP) HIJO - 29 \n");
                                                                    } else {
                                                                        print_r("ATENCIÓN::: PROCESO DE PAGOS PARCIALES EN TABLA DE HISTÓRICO DE SALDOS (MMEJREP) HIJO NO REALIZADO \n");
                                                                    }
                                                                }
                                                                if (!$this->verifica_paso_proceso(30, $PROCESO, $this->tablas['tabla_aplicaciones_mmelrep'], $ID_DEUDA)) {
                                                                    if ($this->registrar_pagos_parciales_en_mmelrep($codCia, $datos_doc_db2, $pagos_parciales)) {
                                                                        $this->registra_paso_proceso(30, $PROCESO, $this->tablas['tabla_aplicaciones_mmelrep'], $ID_DEUDA);
                                                                        print_r("FIN REGISTRAR PAGOS PARCIALES EN TABLA DE APLICACIONES (MMELREP) - 30 \n");
                                                                    } else {
                                                                        print_r("ATENCIÓN::: PROCESO DE PAGOS PARCIALES EN TABLA DE APLICACIONES (MMELREP) NO REALIZADO \n");
                                                                    }
                                                                }
                                                            }
                                                        }
                                                        //FIN - REGISTRAR PAGOS PARCIALES EN TABLAS MMEJREP Y MMELREP
                                                        print_r("REGISTRAR EN TABLA MMCDRECA - 26 \n");
                                                        if (!$this->verifica_paso_proceso(26, $PROCESO, $this->tablas['tabla_mmcdreca'], $ID_DEUDA)) {
                                                            $this->registra_tabla_mmcdreca($codCia, $bankAccount, $datos_doc_db2, $correlativoBoletaDeposito, $operationId, $regPlanilla->cjapnpll, $pago);
                                                            $this->registra_paso_proceso(26, $PROCESO, $this->tablas['tabla_mmcdreca'], $ID_DEUDA);
                                                        }
                                                        print_r("FIN REGISTRAR EN TABLA MMCDRECA - 26 \n");
                                                        print_r("REGISTRAR EN TABLA MMCCRECA \n");
                                                        $this->escribe_actualiza_tabla_mmccreca($codCia, $bankAccount, $pago, $regPlanilla->cjapnpll);
                                                        print_r("FIN REGISTRAR EN TABLA MMCCRECA \n");
                                                        // *** FIN PAGO DOCUMENTO DE CONTADO *** //
                                                        print_r("FIN DOCUMENTO AGRUPADO - CONTADO \n");
                                                        $flag_result= true;
                                                    }
                                                } else {
                                                    print_r("Deuda de documento no encontrada \n");
                                                    $flag_result= false;
                                                } 
                                            }//FIN DOCUMENTO AGRUPADO AL CONTADO
                                        }//FIN FOREACH DOCUMENTOS
                                        //ACTUALIZA ESTATUS CANCELANDO EN TABLA DE GRUPOS
                                        print_r("ACTUALIZAMOS DOCUMENTO AGRUPADO \n");
                                        $this->actualizar_estado_documento_grupo($datos_grupo);
                                        print_r("FIN DOCUMENTOS AGRUPADOS \n");

                                    }else{ // FIN VALIDAR MONTO DEPOSITO DOCUMENTO COMPUESTO
                                        print_r("DETENEMOS PROCESO DA \n");
                                        print_r("MONTO DE DEPÓSITO NO COINCIDE CON SUMA TOTAL DE SALDOS DE DOCUMENTOS \n");
                                        $flag_result= false;
                                         //die("<br>MONTO DE DEPÓSITO NO COINCIDE CON SUMA TOTAL DE SALDOS DE DOCUMENTOS");
                                    }
                                }//FIN IF REGISTRO SALDO COMPUESTO
                                break;
                            case 'PP':
                                print_r("DOCUMENTO PRE PAGO: " . $paidDocuments[0]->documentId. "\n");
                                $PROCESO = 5;
                                $ID_PAGO = $pago->id;
                                $numero_documento = $vector[1];
                                $tipoPago = $vector[2]; //documento al contado 'C' o credito 'R'
                                $motivo = '02';
                                $moneda = ($pago->currencyCode === 'USD') ? '02' : '01';
                                $tipo_cambio_dolar = $this->retorna_tipo_cambio_dolar_mym('02');
                                $correlativoPlanillaCobranzas = 0; //al aplicar desde caja, se llenara esta planilla
                                print_r("NUMERO DE DOCUMENTO PP: ". $numero_documento." \n");
                                print_r("TIPO PAGO DOCUMENTO PP: ". $tipoPago." \n");
                                $docType = 'PP';
                                if ($tipoPago === 'C') {
                                    print_r("RELATED DEBTS DOCUMENTO PP: ". json_encode($pago->related_debts)." \n");
                                    $codSuc = $pago->related_debts[0]->ABCODSUC;
                                    $PROCESO = 4;
                                    $ID_PAGO = $pago->id;
                                    //OBTENER DOC DESDE TABLA DE SALDOS
                                    if ($regSaldo = $this->retorna_doc_cliente_saldo_interface($codCia, $codSuc, $customerIdentificationCode, $tipoPago, $docType, $numero_documento)) {
                                        print_r("PROCESAMOS EL REGITRO DE CLIENTE SALDOS:". $regSaldo->id." \n");
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
                                        //PROCEDEMOS A REGISTRAR EL DEPOSITO EN LA MMYPREP
                                        print_r("PROCEDEMOS A INSERTAR EN LA MMYPREP \n");
                                        $datosDeposito = $this->registra_deposito_bancario_mmyprep_pp($codCia, $regSaldo->ABCODSUC, $bankAccount[0]->erp_code, $operationId, $regSaldo, $bankAccount, $pago);
                                        $correlativoBoletaDeposito = $datosDeposito->ypnrodep;
                                        print_r("Depósito ". $correlativoBoletaDeposito ."   --- Fecha: " . date('Y-m-d H:i:s')."\n");
                                        $this->registra_paso_proceso(14, $PROCESO, $this->tablas['tabla_mmyprep'], $ID_PAGO);

                                        $documentAmountPaid = (float)round($documentAmountPaid, 2);
                                        $nuevo_saldo = 0;
                                        print_r("ACTUALIZAR TABLA DE SALDOS AUXILIAR (CCAPLBCO) - 20 \n");
                                        if (!$this->verifica_paso_proceso(20, $PROCESO, $this->tablas['tabla_saldos_aux_db2'], $ID_PAGO)) {
                                            if ($this->actualizar_tabla_saldos_auxiliar($codCia, $datos_doc_db2, $documentAmountPaid, $operationId, $bankAccount, $nuevo_saldo, 2)) {
                                                $this->registra_paso_proceso(20, $PROCESO, $this->tablas['tabla_saldos_aux_db2'], $ID_PAGO);
                                            } else {
                                                print_r("ERROR ACTUALIZANDO TABLA DE SALDOS AUXILIAR (CCAPLBCO) - 20 \n");
                                            }
                                        }
                                        print_r("FIN ACTUALIZAR TABLA DE SALDOS AUXILIAR (CCAPLBCO) - 20 \n");
                                        print_r("REGISTRAR EN TABLA MMCDRECA - 26 \n");
                                        if (!$this->verifica_paso_proceso(26, $PROCESO, $this->tablas['tabla_mmcdreca'], $ID_PAGO)) {
                                            if ($this->registra_tabla_mmcdreca_pp($codCia, $bankAccount, $datos_doc_db2, $correlativoBoletaDeposito, $operationId, $correlativoPlanillaCobranzas, $pago, $nuevo_saldo)) {
                                                $this->registra_paso_proceso(26, $PROCESO, $this->tablas['tabla_mmcdreca'], $ID_PAGO);
                                                print_r("FIN REGISTRAR EN TABLA MMCDRECA - 26 \n");
                                            } else {
                                                print_r("ATENCIÓN::: PROCESO DE REGISTRO EN TABLA (MMCDRECA) NO REALIZADO \n");
                                            }
                                        }

                                        print_r("REGISTRAR EN TABLA MMCCRECA \n");
                                        $this->escribe_actualiza_tabla_mmccreca($codCia, $bankAccount, $pago, $correlativoPlanillaCobranzas);
                                        print_r("FIN REGISTRAR EN TABLA MMCCRECA \n");
                                        print_r("FIN DOCUMENTO:".$numero_documento." - CREDITO \n");
                                        $flag_result = true;
                                    }else{
                                        print_r("NO EXISTE REGITRO DE CLIENTE SALDOS: \n");
                                        $flag_result = false;
                                    }
                                }else{
                                    $codSuc = $pago->related_debts[0]->ABCODSUC;
                                    print_r("RELATED DEBTS DOCUMENTO PP CREDITO: ". json_encode($pago->related_debts)." \n");
                                    if ($regSaldo = $this->retorna_doc_cliente_saldo_interface($codCia, $codSuc, $customerIdentificationCode, $tipoPago, $docType, $numero_documento)) {
                                        print_r("PROCESAMOS EL REGITRO DE CLIENTE SALDOS:". $regSaldo->id." \n");
                                        $docType = $regSaldo->ABTIPDOC;
                                        $codSucDeposito = ($regSaldo->ABCODSUC === '01') ? '02' : $regSaldo->ABCODSUC;
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
                                        //VERIFICAR/REGISTRAR PLANILLA DE COBRANZAS (CREDITO) TABLA (MMDLREP)
                                        $arrayWhere = array(
                                            ['DLCODCIA', '=', $codCia],
                                            ['DLCODSUC', '=', $codSucDeposito],
                                            ['DLFECPLL', '=', date("Ymd")],
                                            ['DLSTS', '=', 'A'],
                                            ['DLSTSPLL', '=', 'A'],
                                            ['DLCODCBR', '=', $this->codCobrador]
                                        );

                                        $datosDeposito = $this->registra_deposito_bancario_mmyprep_pp($codCia, $regSaldo->ABCODSUC, $bankAccount[0]->erp_code, $operationId, $regSaldo, $bankAccount, $pago);
                                        $correlativoBoletaDeposito = $datosDeposito->ypnrodep;
                                        print_r("Depósito ". $correlativoBoletaDeposito ."   --- Fecha: " . date('Y-m-d H:i:s')."\n");
                                        $this->registra_paso_proceso(14, $PROCESO, $this->tablas['tabla_mmyprep'], $ID_PAGO);

                                        print_r("ACTUALIZAR TABLA DE SALDOS AUXILIAR (CCAPLBCO) - 20 \n");
                                        if (!$this->verifica_paso_proceso(20, $PROCESO, $this->tablas['tabla_saldos_aux_db2'], $ID_PAGO)) {
                                            if ($this->actualizar_tabla_saldos_auxiliar($codCia, $datos_doc_db2, $documentAmountPaid, $operationId, $bankAccount, $regSaldo->ABIMPSLD, 0)) {
                                                $this->registra_paso_proceso(20, $PROCESO, $this->tablas['tabla_saldos_aux_db2'], $ID_PAGO);
                                                print_r("FIN ACTUALIZAR TABLA DE SALDOS AUXILIAR (CCAPLBCO) - 20 \n");
                                            } else {
                                                print_r("ERROR ACTUALIZANDO TABLA DE SALDOS AUXILIAR (CCAPLBCO) - 20 \n");
                                                // die('<br> ERROR ACTUALIZANDO TABLA DE SALDOS AUXILIAR (CCAPLBCO) - 20');
                                            }
                                        }
                                        print_r("REGISTRAR EN TABLA MMCDRECA - 26 \n");
                                        if (!$this->verifica_paso_proceso(26, $PROCESO, $this->tablas['tabla_mmcdreca'], $ID_PAGO)) {
                                            if ($this->registra_tabla_mmcdreca_pp($codCia, $bankAccount, $datos_doc_db2, $correlativoBoletaDeposito, $operationId, $correlativoPlanillaCobranzas, $pago, $regSaldo->ABIMPSLD)) {
                                                $this->registra_paso_proceso(26, $PROCESO, $this->tablas['tabla_mmcdreca'], $ID_PAGO);
                                                print_r("FIN REGISTRAR EN TABLA MMCDRECA - 26 \n");
                                            } else {
                                                print_r("ATENCIÓN::: PROCESO DE REGISTRO EN TABLA (MMCDRECA) NO REALIZADO \n");
                                            }
                                        }

                                        print_r("REGISTRAR EN TABLA MMCCRECA \n");
                                        $this->escribe_actualiza_tabla_mmccreca($codCia, $bankAccount, $pago, $correlativoPlanillaCobranzas);
                                        print_r("FIN REGISTRAR EN TABLA MMCCRECA \n");
                                        print_r("FIN DOCUMENTO:".$numero_documento." - CREDITO \n");
                                                $flag_result = true;
                                            }else{
                                                print_r("NO EXISTE REGITRO DE CLIENTE SALDOS CREDITO: \n");
                                                $flag_result = false;
                                            }
                                        }
                                //insertamos en la mmyprep
                                print_r("FIN DEPOSITO DE PRE PAGO (PP) \n");
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
                                $formaPago='';
                                if ($vector[4] === 'A') //A -> AYER, H -> HOY
                                {
                                    //BUSCAR LA PLANILLA DEL DÍA ANTERIOR, SINO EL ANTERIOR
                                    $numero_planilla = 0;
                                    $i = 0;
                                    while ($numero_planilla == 0) {
                                        $i++;
                                        $fecha = Utilidades::retorna_fecha_formateada('Y-m-d H:i:s', 'Ymd', Utilidades::sumar_restar_dias_fecha($fecha, 1, 'restar'));
                                        print_r("Fecha (".$i."):". $fecha."\n");
                                        //OBTENER PLANILLA DEL DÍA
                                        if (!$regPlanilla = $this->retorna_planilla_contado_actual_db2($codCia, $codSuc, $fecha)) {
                                            print_r("3.- NO HAY PLANILLA CREADA PARA LA FECHA " . $fecha . " - Cia: " . $codCia . " - Suc: " . $codSuc."\n");
                                            $flag_result= false;
                                        } else {
                                            $numero_planilla = $regPlanilla->cjapnpll;
                                            print_r("PLANILLA DIA ANTERIOR:". $numero_planilla. "\n");
                                        }
                                    }
                                } else {
                                    if (!$regPlanilla = $this->retorna_planilla_contado_actual_db2($codCia, $codSuc, $fecha)) {
                                        print_r("4.- NO HAY PLANILLA CREADA PARA LA FECHA " . $fecha . " - Cia: " . $codCia . " - Suc: " . $codSuc."\n");
                                        $flag_result=false;
                                        //exit;
                                    } else {
                                        $numero_planilla = $regPlanilla->cjapnpll;
                                        print_r("PLANILLA DIA ACTUAL: ".$numero_planilla. "\n");
                                    }
                                }

                                if (isset($regPlanilla) && is_object($regPlanilla)) {
                                    //OBTENER PLANILLA DEL DÍA
                                    $numero_planilla = $regPlanilla->cjapnpll;
                                    print_r("PLANILLA DIA ACTUAL:". $numero_planilla. "\n");
                                    print_r("Nro Planilla: ".$numero_planilla."\n");
                                    $secuencia = DB::connection('ibmi')->table('LIBPRDDAT.MMCJFGT')
                                        ->where('CJGTNPL', $numero_planilla)->max('CJGTSEC') + 1;
                                    print_r(" - Secuencia: $secuencia \n");
                                    //print_r($regPlanilla);
                                    print_r("DOCUMENTO TIPO CAX: " . $paidDocuments[0]->documentId . "\n");
                                    $docType = $this->cax_documents;
                                    //(print_r($bankAccount));
                                    $arrayWhere = [
                                        ['CJGTCIA', '=', $codCia],
                                        ['CJGTNOP', '=', $pago->operationNumber],
                                        ['CJGTEST', '=', 'A'],
                                        ['CJGTFEG', '=', date("Ymd")],
                                        ['CJGTBCO', '=', $bankAccount[0]->erp_code],
                                    ];
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
                                            print_r("No se pudo insertar en la MMCJFGT \n");
                                            $flag_result= false;
                                        }else{
                                            print_r("Se inserto correctamente en la MMCJFGT \n");
                                            $flag_result= true;
                                        }
                                        //}
                                    }else {
                                        print_r("DEPÓSITO YA EXISTE (".$pago->operationNumber.") \n");
                                        $flag_result= true;
                                    }
                                    $regSaldo = $this->retorna_doc_cliente_saldo_interface($codCia, $codSuc, $customerIdentificationCode, $formaPago,$docType, $numero_documento);
                                    $regSaldo->ABCODCLI = $this->codcli_mym; //ASIGNAR EL CLIENTE MYM AL USUARIO QUE HIZO EL DEPÓSITO
                                    $datosDeposito = $this->registra_deposito_bancario_mmyprep($codCia, $codSuc, $bankAccount[0]->erp_code, $pago->operationNumber, $regSaldo, $bankAccount, $pago);
                                }else{
                                    $flag_result= false;
                                }
                            print_r("FIN DEPOSITO CAJAS (DP) \n");
                            break;
                        }
                    }
                    print_r("FIN DOCUMENTID: ".$paidDoc->documentId. "\n");
                    $this->actualiza_saldos_mmccreca($codCia, $bankAccount[0]->nro_cuenta, date("Ymd"));
                }
            }
        }// validacion si es págo multiple bcp
        if ($flag_result) {
            $arrayWhere = array(
            'id' =>  $pago->id,
            'fecha_hora_actualizacion_db2' => null
            );
            $arrayUpdate = array(
                'fecha_hora_actualizacion_db2' => date("Y-m-d H:i:s")
            );
            $this->actualiza_tabla_postgres('customer_payments', $arrayWhere, $arrayUpdate);
            print_r("FINALIZAMOS EL PAGO ID: ".$pago->id."\n");
        }else{
            print_r("PROBLEAS: REVISAR PAGO ID: ".$pago->id."\n");
        }
        return $flag_result;
    }

    public function get_bank_payment_for_update_db2()
    {
        $rs = DB::table('customer_payments')
            ->where('fecha_hora_actualizacion_db2', '=', null)
            ->where('return_request_id', '=', null)
            ->orderBy('is_sync','ASC')
            ->first();
        return (is_object($rs))? $rs : false;
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

    public function registra_planilla_cobranzas_credito($codCia, $currencyCode, $documentAmountPaid)
    {
        print_r("REGISTRAR PLANILLA COBRANZAS CREDITO \n");
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
            print_r("CORRELATIVO DE PLANILLA DE COBRANZAS -> NUMERADORES (MMFCREL0): \n");
            $correlativoPlanillaCobranzas = $this->getCorrelativeNumberByDocType($codCia, $codSucDeposito, '05'); // '05' -> PLANILLA DE COBRANZAS
            print_r("NUMERO DE CORRELATIVO PLANILLA COBRANZA: ".$correlativoPlanillaCobranzas."\n");

            print_r("REGISTRAR PLANILLA DE COBRANZAS EN TABLA (MMDLREP) \n");
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
                print_r("ERROR REGISTRANDO PLANILLA DE COBRANZAS \n");
                return false;
            }
            $regPlanillaCobranzas = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMDLREP', $arrayWhere);
            print_r("PLANILLA DE COBRANZAS (" . $regPlanillaCobranzas->dlnropll . ") REGISTRADA! \n");
        } else {
            print_r("PLANILLA REGISTRADA PREVIAMENTE: " . $regPlanillaCobranzas->dlnropll. "\n");
        }
        print_r("FIN - REGISTRAR PLANILLA COBRANZAS CREDITO\n");
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
                print_r("NO SE PUDO GENERAR LA NUEVA PLANILLA DE COBRANZAS: " . $correlativoPlanillaCobranzas."\n");
                return false;
                //die('NO SE PUDO GENERAR LA NUEVA PLANILLA DE COBRANZAS: ' . $correlativoPlanillaCobranzas);
            }
            $regTipoPlanilla = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMIEREP', $arrayWhere);
        }else{
            print_r("PLANILLA COBRANZA DEL DIA REGISTRADA PREVIAMENTE: " . $regTipoPlanilla->ienropll. "\n");
        }
        return $regTipoPlanilla;
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

    public function valida_tiempo_pagos_de_contado($pago, $bankCode)
    {
        $pagos_relacionados = $pago->related_debts;
        if (is_array($pagos_relacionados)) {
            foreach ($pagos_relacionados as $pago_rel) {
                $fechaTransaccion = new \Carbon\Carbon($pago->transactionDate);
                $fechaActual = new \Carbon\Carbon("now");
                $minutos_transcurridos = $fechaTransaccion->diffInMinutes($fechaActual);
                print_r(" operationNumber :" .$pago->operationNumber."\n");
                print_r($pago_rel->ABFRMPAG." --- ".$pago_rel->id." --- ".$pago_rel->NUMERO_IDENTIFICACION." --- ".$pago_rel->ABCODCLI." ---  ".$fechaTransaccion." --- Tiempo Transcurrido: ".$minutos_transcurridos."\n");

                if ($pago_rel->ABFRMPAG === 'C' && $fechaActual >= $fechaTransaccion && $minutos_transcurridos < $this->min_descarga_pagos_contado) {
                    if ($pago->bankCode === '009' && $pago->channel !== '90') return true;
                    if ($pago->bankCode === '011' && $pago->channel !== 'TF') return true;
                    if ($pago->bankCode === '002' && $pago->channel !== 'FI') return true;

                    return false;
                } else return true;
            }
        } else {
            print_r("NO TIENE PAGOS RELACIONADOS \n");
            return false;
        }
    }

    public function retorna_doc_cliente_saldo_interface_fac($customerIdentificationCode,$formaPago,$serieNumber, $docNumber)
    {
        return DB::table('cliente_saldos')
            ->where('CBNROSER', '=', $serieNumber)
            ->where('CBNROCOR', '=', $docNumber)
            ->where('ABFRMPAG', '=', $formaPago)
            ->where('NUMERO_IDENTIFICACION', '=', $customerIdentificationCode)
            ->first();
    }

    public function retorna_doc_cliente_saldo_interface($codCia, $codSuc, $customerIdentificationCode, $formaPago,$docType, $docNumber)
    {
        $result = DB::table('cliente_saldos')
            ->where('ABCODCIA', '=', $codCia)
            ->where('ABCODSUC', '=', $codSuc)
            ->where('NUMERO_IDENTIFICACION', '=', $customerIdentificationCode)
            ->where('ABTIPDOC', '=', $docType)
            ->where('ABNRODOC', '=', $docNumber);
        if ($docType !== $this->cax_documents) {
            $result = $result->where('ABFRMPAG','=',$formaPago);
        }
        $result = $result->first();
        return $result;
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
            print_r("CORRELATIVO DE BOLETA DE DEPOSITO -> NUMERADORES (MMFCREL0): \n");
            $correlativoBoletaDeposito = $this->getCorrelativeNumberByDocType($codCia, $sucursal_deposito, $this->codBoletaDeposito);
            $statusAplicacion = 'S';
            $montoAplicado = $pago->totalAmount;
            $tipDoc = ''; //SE USARA EN CASO DE PRE PAGOS
            $nroDoc = 0; //SE USARA EN CASO DE PRE PAGOS
            if ($regSaldo->ABTIPDOC == 'PP') {
                $statusAplicacion = 'N';
                $montoAplicado= 0;
                $tipDoc = 'PP';
                $nroDoc= $regSaldo->ABNRODOC;

            }
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
                'YPSTSAPL' => $statusAplicacion, //P -> PENDIENTE, N -> CONFIRMADO TESORERÍA, S -> DEPÓSITO APLICADO (Default)
                'YPFECDEP' => date("Ymd"),
                'YPIMPDEP' => $pago->totalAmount,
                'YPIMPAPL' => $montoAplicado, //$documentAmountPaid, //*** MONTO APLICADO = MONTO DEL DOCUMENTO PAGADO ***
                'YPCLIREF' => $regSaldo->ABCODCLI,
                'YPCODCLI' => $regSaldo->ABCODCLI,
                'YPPGM' => $this->app,
                'YPUSR' => $this->user,
                'YPJDT' =>  date("Ymd"),
                'YPJTM' => date("His"),
                'YPCODVEN' => $regSaldo->ABCODVEN,
                'YPSUCDOC' => $regSaldo->ABCODSUC,
                'YPLIBRE1' => $tipDoc,
                'YPLIBRE2' => $nroDoc
            );
            print_r("INSERTAMOS EN LA MMYPREP DATOS:".json_encode($arrayNewDep)."\n");
            if(DB::connection('ibmi')->table('LIBPRDDAT.MMYPREP')->insert([$arrayNewDep])){
                print_r("SE INSERTO CORRECTAMENTE EN LA MMYPREP \n");
            }else{
                print_r("NO SE INSERTO EN LA MMYPREP \n");
            }
            return $this->selecciona_from_tabla_db2('LIBPRDDAT.MMYPREP', $arrayWhere);
        } else {
            echo '<br>REGISTRO EXISTENTE EN LA MMYPREP';
            return $datos_deposito;
        }
    }

    public function registra_deposito_bancario_mmyprep_pp($codCia, $sucursal_deposito, $codigo_banco_as, $numero_deposito, $regSaldo, $bankAccount, $pago)
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
            print_r("CORRELATIVO DE BOLETA DE DEPOSITO -> NUMERADORES (MMFCREL0): \n");
            $correlativoBoletaDeposito = $this->getCorrelativeNumberByDocType($codCia, $sucursal_deposito, $this->codBoletaDeposito);
            $statusAplicacion = 'S';
            $montoAplicado = $pago->totalAmount;
            $tipDoc = ''; //SE USARA EN CASO DE PRE PAGOS
            $nroDoc = 0; //SE USARA EN CASO DE PRE PAGOS
            if ($regSaldo->ABTIPDOC == 'PP') {
                $tipDoc = 'PP';
                $nroDoc= $regSaldo->ABNRODOC;
                if ($regSaldo->ABFRMPAG=='C') {
                    $statusAplicacion = 'N';
                    $montoAplicado= 0;
                }else{
                    $statusAplicacion = 'N';
                    $montoAplicado= 0;
                    
                }
            }
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
                'YPNROPDC' => "",
                'YPCODBCO' => $codigo_banco_as,
                'YPNROCTA' => $bankAccount[0]->nro_cuenta,
                'YPCODMON' => $bankAccount[0]->moneda,
                'YPNROOPR' => $numero_deposito,
                'YPFRMPAG' => $regSaldo->ABFRMPAG, //C -> CONTADO, R -> CRÉDITO
                'YPTIPDOC' => "", //TIPO DE DOCUMENTO (PEDIDO/FACTURA/BOLETA)
                'YPCODPAI' => '001',
                'YPCODCIU' => '001',
                'YPSTS' => 'A',
                'YPSTSAPL' => $statusAplicacion, //P -> PENDIENTE, N -> CONFIRMADO TESORERÍA, S -> DEPÓSITO APLICADO (Default)
                'YPFECDEP' => date("Ymd"),
                'YPIMPDEP' => $pago->totalAmount,
                'YPIMPAPL' => $montoAplicado, //$documentAmountPaid, //*** MONTO APLICADO = MONTO DEL DOCUMENTO PAGADO ***
                'YPCLIREF' => $regSaldo->ABCODCLI,
                'YPCODCLI' => "",
                'YPPGM' => $this->app,
                'YPUSR' => $this->user,
                'YPJDT' =>  date("Ymd"),
                'YPJTM' => date("His"),
                'YPCODVEN' => $regSaldo->ABCODVEN,
                'YPSUCDOC' => $regSaldo->ABCODSUC,
                'YPLIBRE1' => $tipDoc,
                'YPLIBRE2' => $nroDoc
            );
            print_r("INSERTAMOS EN LA MMYPREP DATOS:".json_encode($arrayNewDep)."\n");
            if(DB::connection('ibmi')->table('LIBPRDDAT.MMYPREP')->insert([$arrayNewDep])){
                print_r("SE INSERTO CORRECTAMENTE EN LA MMYPREP \n");
            }else{
                print_r("NO SE INSERTO EN LA MMYPREP \n");
            }
            return $this->selecciona_from_tabla_db2('LIBPRDDAT.MMYPREP', $arrayWhere);
        } else {
            echo '<br>REGISTRO EXISTENTE EN LA MMYPREP';
            return $datos_deposito;
        }
    }

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
                print_r("ERROR AL REGISTRAR REGISTRO EN TABLA (MMDMREP) \n");
                return false;
            }
            $datosDoc = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMDMREP', $arrayWhere);
        } else {
            $importePagado = (float) round($datosDoc->dmimpccc, 2) + $documentAmountPaid;
            $arrayUpdate = array(
                'DMIMPCCC' => $importePagado,
                'DMIMPINF' => $importePagado
            );
            if (!$this->actualiza_tabla_db2('LIBPRDDAT.MMDMREP', $arrayWhere, $arrayUpdate)) {
                print_r("ERROR ACTUALIZANDO MMDMREP \n");
                return false;
            }
        }

        return $datosDoc;
        //FIN BUSQUEDA / REGISTRO DE DOCUMENTO EN TABLA MMDMREP
    }

    //FUNCIÓN QUE REGISTRA/ACTUALIZA TABLA MMDNREP (RELACION DOCUMENTOS PLANILLA)
    public function procesa_registro_tabla_mmdnrep($codCia, $correlativoPlanillaCobranzas, $codigo_cliente, $correlativoBoletaDeposito, $monto_pagado, $operationId, $bankAccount, $currencyCode) //$pago,
    {
        print_r("REGISTRO EN TABLA (MMDNREP)\n");
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
            if (!$this->inserta_into_tabla_db2('LIBPRDDAT.MMDNREP', $arrayInsert)){
                print_r("ERROR AL INSERTAR REGISTRO EN TABLA MMDNREP \n");
                return false;
            }else {
                print_r("EGISTRO  AGREGADO EN TABLA MMDNREP \n");
                return true;
            }
        } else {
            print_r("REGISTRO YA EXISTE EN TABLA (MMDNREP) \n");
            return true;
        }
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
            if (!$this->inserta_into_tabla_db2('LIBPRDDAT.MMDOREP', $arrayInsert)){
                print_r("ERROR REGISTRANDO EN TABLA MMDOREP \n");
                return false;
            }else {
                print_r("REGISTRO INGRESADO EN TABLA MMDOREP \n");
                return true;
            }
        } else {
            print_r("REGISTRO EN TABLA MMDOREP EFECTUADO PREVIAMENTE \n");
            return true;
        }
    }

    public function actualizar_tabla_saldos_auxiliar($codCia, $regSaldo, $documentAmountPaid, $operationId, $bankAccount, $nuevo_saldo, $rupdate = 0)
    {
        //AUXILIAR
        print_r("ACTUALIZANDO LA TABLA AUXILIAR CCAPLBCO NUEVO SALDO:" .$nuevo_saldo ."\n");
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
            print_r("TABLA PRINCIPAL DE SALDOS ACTUALIZADA. \n");
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
            print_r("PEDIDO " . $codSuc . "-" . $docNumber . " NO ENCONTRADO \n");
            return false;
        } else {
            if (strlen(trim($datos_pedido->cbnrocor)) == 0) {
                print_r("CLIENTE: (" . $datos_pedido->cbcodcli . ") - PEDIDO: (" . $datos_pedido->cbcodcia . " - " . $datos_pedido->cbcodsuc . " - " . $datos_pedido->cbnropdc . ") NO TIENE FACTURA CREADA AÚN \n");
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
                        print_r("NO SE PUDO ACTUALIZAR NI AGREGAR ENCABEZADO DE PAGOS " . date("Ymd"). "\n");
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
                print_r("NO SE PUDO AGREGAR DETALLE DE PAGO \n");
                return false;
            } else return true;
        } else {
            print_r("Detalle registrado previamente \n");
            return true;
        }
    }

    public function registra_historicos_padre($codCia, $regSaldo, $saldo_actual)
    {
        print_r("Método: registra_historicos_padre \n");
        print_r("Saldo actual:".$saldo_actual."\n");
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
                    print_r("ERROR REGISTRANDO EN TABLA HISTORICOS DE SALDOS \n");
                    return false;
                } else return true;
            } else {
                print_r("Registro de histórico padre, efectuado previamente - Se actualizó el saldo \n");
                print_r("Suc.: ".$regSaldo->abcodsuc." - Cod. Cli.:".$regSaldo->abcodcli." - Tip. Doc.:".$regSaldo->abtipdoc." - Nro. Doc.:".$regSaldo->abnrodoc." - Total Doc.: ".$regSaldo->abimpccc." - SA: ".$saldo_actual."\n");
                $arrayUpdate = array(
                    'EJIMPSLD' => $saldo_actual,
                );
                $this->actualiza_tabla_db2($this->tablas['tabla_historicos_mmejrep'], $arrayWhere, $arrayUpdate);
                return true;
            }
        } else {
            print_r("Saldo actual es mayor que 0: $saldo_actual \n");
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
                print_r("ERROR REGISTRANDO EN TABLA HISTORICOS DE SALDOS - HIJO \n");
                return false;
                // die('ERROR REGISTRANDO EN TABLA HISTORICOS DE SALDOS - HIJO');
            } else return true;
        } else {
            print_r("REGISTRO YA EXISTE EN TABLA DE HISTÓRICO DE SALDOS - HIJO \n");
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
                print_r("ERROR REGISTRANDO EN TABLA (MMCDRECA) \n");
                return false;
            } else {
                print_r("REGISTRO EFECTUADO EN TABLA (MMCDRECA) \n");
                return true;
            }
        } else {
            print_r("REGISTRO REALIZADO PREVIAMENTE EN TABLA (MMCDRECA) \n");
            return true;
        }
    }

    public function registra_tabla_mmcdreca_pp($codCia, $bankAccount, $regSaldo, $correlativoBoletaDeposito, $operationId, $correlativoPlanillaCobranzas, $pago, $saldo)
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
            $status = 'S';
            if ($regSaldo->abfrmpag=='C') {
                $new_saldo = $saldo;
                $status='S';
            }else{
                $new_saldo = $pago->totalAmount;
                $status='A';
            }
            //REGISTRAR EN TABLA MMCDRECA
            $arrayInsert = array(
                'CDCODCIA'  => $codCia,
                'CDCODBCO'  => $bankAccount[0]->erp_code,
                'CDFECPRO'  => date("Ymd"),
                'CDCODMON'  => $bankAccount[0]->moneda,
                'CDNROCTA'  => $bankAccount[0]->nro_cuenta,
                'CDCODCLI'  => $regSaldo->abcodcli,
                'CDMONTO'   => $pago->totalAmount,//$pago->totalAmount,
                'CDSALDO'   => $new_saldo,
                'CDNROOPE'  => $operationId,
                'CDHOROPE'  => date("His"),
                'CDNROPLL'  =>  $correlativoPlanillaCobranzas,
                'CDFORPAGO' => $regSaldo->abfrmpag,
                'CDFECVAL'  => '',
                'CDSUCAGE'  => '',
                'CDSTATUS'  => $status, // A -> ACTIVO, S -> SALDADO, P -> PENDIENTE
                'CDUSUCAR'  => $this->user,
                'CDFECCAR'  => date("Ymd"),
                'CDHORCAR'  => date("His"),
                'CDUSUAPL'  => $this->user,
                'CDFECAPL'  => date("Ymd"),
                'CDHORAPL'  => date("His"),
                'CDIMPAPL'  => $pago->totalAmount,//$pago->totalAmount,
                'CDNROBOL'  => $correlativoBoletaDeposito,
                'CDFECDEP'  => date("Ymd"),
                'CDNOBLOQ'  => '1',
                'CDDESCRI'  => 'PAGO/ABONO'
            );
            if (!$this->inserta_into_tabla_db2('LIBPRDDAT.MMCDRECA', $arrayInsert)) {
                print_r("ERROR REGISTRANDO EN TABLA (MMCDRECA) \n");
                return false;
            } else {
                print_r("REGISTRO EFECTUADO EN TABLA (MMCDRECA) \n");
                return true;
            }
        } else {
            print_r("REGISTRO REALIZADO PREVIAMENTE EN TABLA (MMCDRECA) \n");
            return true;
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
                print_r("NO SE PUDO REGISTRAR EN LA TABLA (MMCCRECA) \n");
                return false;
                // die('<BR>NO SE PUDO REGISTRAR EN LA TABLA (MMCCRECA)');
            } else {
                print_r("SE REGISTRÓ CORRECTAMENTE EN LA TABLA (MMCCRECA) \n");
                $dep_apl_cab = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMCCRECA', $arrayWhere);
            }
        } else {
            print_r("ACTUALIZAR SALDOS EN TABLA (MMCCRECA) \n");
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
                    print_r("ERROR REGISTRANDO EN TABLA HISTORICOS DE SALDOS - HIJO \n");
                    return false;
                } else {
                    $qty_registrados++;
                }
            } else {
                print_r("PAGO REGISTRADO PREVIAMENTE \n");
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
                    print_r("ERROR REGISTRANDO EN TABLA DE APLICACIONES \n");
                    return false;
                } else {
                    print_r("PAGO REGISTRADO CORRECTAMENTE EN TABLA DE APLICACIONES");
                    $qty_registrados++;
                }
            } else {
                print_r("PAGO YA EXISTE EN TABLA DE APLICACIONES \n");
                $qty_registrados++;
            }
        }
        return ($qty_registrados == sizeof($pagos_parciales)) ? true : false;
    }

    /*:::::::::::::::::: BLOQUE ACTUALIZACION DE DOCUMENTOS :::::::::::::::::::::::*/
    public function sincronizar_documentos(){
        if ($registros = $this->leer_nuevos_registros_db2()) {
            print_r("Registros a procesar: " . sizeof($registros)."\n");
            $i = 0;
            foreach ($registros as $fila) {
                $fecha = date('Ymd');
                $hora = date('His');
                $fila_interface = new \stdClass();
                $fila_interface->accion = null;
                try {
                    $objClienteSaldo = $this->retorna_registro_interface($fila);
                    //Registra en tabla interface
                    if ($this->insertar_actualizar_registro_tabla_interface($fila)) {
                        //Actualizar campos (FECMMWEB, HMSMMWEB) en registro en DB2
                        if ($objClienteSaldo) {
                            if ($objClienteSaldo->int_iterate > 0) {
                                print_r("Registro en uso del AS400 \n");
                            }else{
                                if (!$this->actualiza_estatus_tabla_aux_saldos_db2($fila)) {
                                    print_r("Registro no actualizado en AS400 \n");
                                }else{
                                    print_r("Registro actualizado en AS400 \n");
                                }
                            }
                        }else{
                            if (!$this->actualiza_estatus_tabla_aux_saldos_db2($fila)) {
                                print_r("Registro no actualizado en AS400 \n");
                            }else{
                                print_r("Registro actualizado en AS400 \n");
                            }
                        }
                    }
                    print_r("NroDoc: " . $fila->abnrodoc . " - F: " . $fecha . " - H: " . $hora ."\n");
                } catch (Throwable $e) {
                    print_r("Error en proceso de actualizar documentos".$e." \n");
                    //report($e);
                    continue;
                }
            }
        }
    }

    public function insertar_actualizar_registro_tabla_interface($registro)
    {
        if ($this->retorna_registro_interface_v1($registro)) {
            //ACTUALIZAR REGISTRO EN INTERFACE
            return $this->actualiza_cliente_saldos_interface($registro);
        } else {
            //ESCRIBIR REGISTRO EN INTERFACE
            return $this->inserta_cliente_saldos_interface($registro);
        }
    }

    public function leer_nuevos_registros_db2()
    {
        print_r("procedemos a traer los registros\n");
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

    public function retorna_registro_interface_v1($registro)
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

    public function retorna_registro_interface($registro)
    {
        if ($registro->abtipdoc === 'DA' || $registro->abtipdoc === $this->cax_documents) {
            $rs = DB::table('cliente_saldos')
                ->where('ABCODCIA', '=', $registro->abcodcia)
                ->where('ABCODCLI', '=', $registro->abcodcli)
                ->where('ABTIPDOC', '=', $registro->abtipdoc)
                ->where('ABNRODOC', '=', $registro->abnrodoc)
                ->first();
        } else {
            $rs = DB::table('cliente_saldos')
                ->where('ABCODCIA', '=', $registro->abcodcia)
                ->where('ABCODSUC', '=', $registro->abcodsuc)
                ->where('ABCODCLI', '=', $registro->abcodcli)
                ->where('ABTIPDOC', '=', $registro->abtipdoc)
                ->where('ABNRODOC', '=', $registro->abnrodoc)
                ->first();
        }

        if (is_object($rs)) return $rs;
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
            'int_iterate' => 1,
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


    public function retornar_flag_cliente_saldos()
    {
        print_r("RETORNAMOS LOS FLAG CLIENTES EN 0 \n");
        $arrayWhere = array(
            ['id', '>', 0],
            ['int_iterate', '=', 1]
        );

        $arrayUpdate = array(
            'int_iterate' => 0
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
            'int_iterate' => 1,
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

    public function actualiza_tabla_postgres($tabla, $arrayWhere, $arrayUpdate)
    {
        return DB::table($tabla)
            ->where($arrayWhere)
            ->update($arrayUpdate);
    }

    public function incrementar_flag_sync($tabla,$id){
        return DB::table($tabla)
            ->where('id',$id)
            ->increment('is_sync');
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

    public function registra_paso_proceso($PASO, $PROCESO, $TABLA, $ID)
    {
        print_r("PROCESO: ".$PROCESO." - PASO: ".$PASO." - TABLA: ".$TABLA." - ID: ".$ID."\n");
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

    public function inserta_into_tabla_db2($tabla_db2, $arrayInsert)
    {
        return DB::connection('ibmi')
            ->table($tabla_db2)
            ->insert([$arrayInsert]);
    }

    public function actualiza_tabla_db2($tabla_db2, $arrayWhere, $arrayUpdate)
    {
        return DB::connection('ibmi')
            ->table($tabla_db2)
            ->where($arrayWhere)
            ->update($arrayUpdate);
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

    public function registra_det_pagos($arrayDetPago)
    {
        return DB::connection('ibmi')->table('LIBPRDDAT.MMCJFDT')->insert([
            $arrayDetPago
        ]);
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

    public function selecciona_fila_from_tabla($tabla, $arrayWhere)
    {
        return DB::table($tabla)
            ->where($arrayWhere)
            ->first();
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

    public function  get_max_deposito_db2($codCia)
    {
        return DB::connection('ibmi')
            ->table('MMYPREL3')
            ->where('YPCODCIA', '=', $codCia)
            ->max('YPDEPINT');
    }

    public function selecciona_from_tabla($tabla, $arrayWhere)
    {
        return DB::table($tabla)
            ->where($arrayWhere)
            ->get()
            ->toArray();
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
}
