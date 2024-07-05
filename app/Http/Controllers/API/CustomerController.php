<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sync\Utilidades;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Stmt\Else_;
use Illuminate\Support\Str;
use PhpParser\Node\Stmt\Echo_;
use Illuminate\Support\Arr;
use PhpParser\Node\Stmt\Switch_;
use stdClass;
use Carbon\Carbon;

class CustomerController extends Controller
{
    private $arrayBankList = array(); //LISTADOS DE BANCOS - INTERCONECTADO
    private $maxDebtListByBank = array(); //CANTIDAD DE DEUDAS DE CLIENTES A ENTREGAR POR BANCO
    private $minutesToReturnByBank = array(); //MINUTOS MÁXIMOS PARA PERMITIR EXTORNOS
    private $minCreditPayment = 1;
    private $cax_documents = 'DP';
    private $codCia = '10';
    private $app = 'ASRETURN';
    private $user = 'SISTEMAS';
    private $codCobrador = '0T1299';
    private $urlBase;

    public function __construct()
    {
        $this->arrayBankList = ['002', '009', '011'];

        $this->maxDebtListByBank = [
            '002' => env('MAX_DEBT_LIST_BCP', 1),
            '009' => env('MAX_DEBT_LIST_SCOTIA', 10),
            '011' => env('MAX_DEBT_LIST_BBVA', 8)
        ];

        $this->minutesToReturnByBank = [
            '002' => env('MAX_MINUTOS_PERMITIR_EXTORNOS_BCP', 10),
            '009' => env('MAX_MINUTOS_PERMITIR_EXTORNOS_SCOTIA', 10),
            '011' => env('MAX_MINUTOS_PERMITIR_EXTORNOS_BBVA', 10)
        ];

        $this->urlBase = 'http://192.168.1.34:81';
    }

    public function customer_debts_list(Request $request)
    {
        $MON = ($request->currencyCode === 'PEN' ? '01' : '02');
        $debts = $this->get_db_customer_debts_list($MON);
        if (!$debts) return response()->json('NO HAY DEUDAS DE CLIENTES REGISTRADAS', 200);
        else {
            return response()->json($debts, 200);
        }
    }

    public function customer_debt_inquiries(Request $request)
    {
        try {
            $rules = [
                'processId' => 'required',
                'bankCode' => [
                    'required',
                    Rule::in($this->arrayBankList),
                ],
                'currencyCode' => [
                    'required',
                    Rule::in(['USD', 'PEN']),
                ],
                'requestId' => 'required',
                'channel' => 'required',
                'customerIdentificationCode' => 'required',
                'transactionDate' => 'required'
            ];
            $messages = [
                'required' => 'El campo :attribute es obligatorio'
            ];
            $arrayIn = array(
                    'tabla' => 'customer_debt_inquiries',
                    'mensaje' => 'Emzamos bbva - fecha:'.date("d-m-Y H:i:s"),
                    'otro' => json_encode($request->all())
                );
                DB::table('log_migraciones')->insert($arrayIn);
            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }
            $numero_documento = $request->customerIdentificationCode;
            $arrayWhere = array(
                'document_number' => $numero_documento //$request->customerIdentificationCode
            );
            $request_id = $request->requestId;

            if ($request->bankCode === '009' && $request_id == 1) {
                $request_id = $request->bankCode . '-' . Str::random(27);
            }

            if ($this->verifyDebtConsultationTransactionExist($request->bankCode, $request_id)) {
                $arrayIn = array(
                    'tabla' => 'customer_debt_inquiries',
                    'mensaje' => 'validamos operationId:'.$request_id.' - fecha:'.date("d-m-Y H:i:s"),
                    'otro' => json_encode($request)
                );
                DB::table('log_migraciones')->insert($arrayIn);
                //DB::rollBack();
                return response()->json('CONSULTA DE DEUDA REALIZADA PREVIAMENTE', 400);
            }

            $MON = ($request->currencyCode === 'PEN' ? '01' : '02');
            $customerDebts = $this->get_db_customer_debts($numero_documento, $MON, $this->maxDebtListByBank[$request->bankCode]);

            if (!$customerDebts) return response()->json(['message' => 'CLIENTE SIN DEUDAS'], 200);
            
            $deudas = new \stdClass();
            $vector_deudas = array();
            $i = 0;
            $arrayIn = array(
                    'tabla' => 'customer_debt_inquiries',
                    'mensaje' => 'Consulta deudas operationId:'.$request_id.' - fecha:'.date("d-m-Y H:i:s"),
                    'otro' => json_encode($arrayWhere)
                );
            DB::table('log_migraciones')->insert($arrayIn);
            $fecha_actual = 0;
            foreach ($customerDebts as $row) {
                $arrayIn = array(
                    'tabla' => 'customer_debt_inquiries',
                    'mensaje' => $i.' recorrido de deudas fecha:'.date("d-m-Y H:i:s"),
                    'otro' => json_encode($row)
                );
                DB::table('log_migraciones')->insert($arrayIn);
                $validate = @file_get_contents($this->urlBase .'/api/ValidateAmountSld/'.$row->id);
                //$validate = utf8_decode($validate);
                $resultValidate = (string)$validate;
                $objValidate = json_decode($resultValidate);
                $arrayIn = array(
                    'tabla' => 'customer_debt_inquiries',
                    'mensaje' => $i.' validate type: -'.gettype($resultValidate).' -fecha:'.date("d-m-Y H:i:s"),
                    'otro' => $resultValidate
                );
                DB::table('log_migraciones')->insert($arrayIn);
                if (is_object($objValidate)) {
                    if (isset($objValidate->status) && $objValidate->status) {
                        //SI EL BANCO ES BCP, ORGANIZAR FECHAS DE VENCIMIENTO PARA QUE NO SE REPITAN
                        if ($request->bankCode === '002') {
                            //echo "--> FA: $fecha_actual - FV: $row->ABFECVCT";
                            switch ($row->ABFECVCT) {
                                case ((int)$row->ABFECVCT > (int)$fecha_actual):
                                    $fecha_actual = $row->ABFECVCT;
                                    break;
                                case ((int)$row->ABFECVCT < (int)$fecha_actual):
                                    $row->ABFECVCT = $fecha_actual;
                                    $date = Carbon::createFromFormat("Ymd", $row->ABFECVCT, 'America/Lima');
                                    $date->addDay(1);
                                    $row->ABFECVCT = (int)$date->format('Ymd');
                                    $fecha_actual = $row->ABFECVCT;
                                    break;
                                default:
                                    $date = Carbon::createFromFormat("Ymd", $row->ABFECVCT, 'America/Lima');
                                    $date->addDay(1);
                                    $row->ABFECVCT = (int)$date->format('Ymd');
                                    $fecha_actual = $row->ABFECVCT;
                                    break;
                            }
                        }
                        //FIN - SI EL BANCO ES BCP, ORGANIZAR FECHAS DE VENCIMIENTO PARA QUE NO SE REPITAN
                        switch ($row->ABTIPDOC) {
                            case '01':
                            case '03':
                                switch ($row->ABFRMPAG) {
                                    case 'C': //CONTADO
                                        $documentId = 'PED-' . $row->ABTIPDOC . '-' . $row->ABNRODOC;
                                        $payment_description = 'PAGO DE CONTADO';
                                        $minimun_payment = number_format($row->ABIMPSLD, 2, '.', '');
                                        break;
                                    case 'R': //CRÉDITO
                                        $documentId = $row->CBNROSER . '-' . $row->CBNROCOR;
                                        $payment_description = 'PAGO DEUDA';
                                        $saldo_actual = floatval($row->ABIMPSLD);
                                        if ($saldo_actual > 0 && $saldo_actual < 1) $minimun_payment = number_format($saldo_actual, 2, '.', '');
                                        else $minimun_payment = number_format($this->minCreditPayment, 2, '.', '');
                                        break;
                                }
                                break;
                            case 'DA':
                                $documentId = $row->ABTIPDOC . '_' . $row->ABNRODOC . '_' . $row->ABFRMPAG;
                                $payment_description = 'PAGO DOCUMENTO COMPUESTO';
                                $minimun_payment = number_format($row->ABIMPSLD, 2, '.', '');
                                break;
                            case 'PP':
                                //$documentId = $row->ABTIPDOC .'_' . $row->ABNRODOC.'_'.$row->ABFRMPAG;
                                //$payment_description = 'PAGO DOCUMENTO PRE COMPRA';
                                //$minimun_payment = number_format($row->ABIMPSLD, 2, '.', '');
                                switch ($row->ABFRMPAG) {
                                    case 'C': //CONTADO
                                        $documentId = $row->ABTIPDOC .'_' . $row->ABNRODOC.'_'.$row->ABFRMPAG;
                                        $payment_description = 'PAGO DOCUMENTO PRE COMPRA';
                                        $minimun_payment = number_format($row->ABIMPSLD, 2, '.', '');
                                        break;
                                    case 'R': //CRÉDITO
                                        $documentId = $row->ABTIPDOC .'_' . $row->ABNRODOC.'_'.$row->ABFRMPAG;
                                        $payment_description = 'PAGO DOCUMENTO PRE COMPRA';
                                        $saldo_actual = floatval($row->ABIMPSLD);
                                        if ($saldo_actual > 0 && $saldo_actual < 1) $minimun_payment = number_format($saldo_actual, 2, '.', '');
                                        else $minimun_payment = number_format($this->minCreditPayment, 2, '.', '');
                                        break;
                                }
                                break;
                            case $this->cax_documents: //DP
                                if (($row->ABNRODOC % 2) == 0) {
                                    $str_dia = '_C_' . $row->ABCODSUC . '_H';
                                } else {
                                    $str_dia = '_C_' . $row->ABCODSUC . '_A';
                                }
                                $documentId = $row->ABTIPDOC . '_' . $row->ABNRODOC . $str_dia;
                                $payment_description = 'DEPOSITO EFECTIVO CAJA';
                                $minimun_payment = number_format(1, 2, '.', '');
                                break;
                        }

                        $vector_deudas[$i] = [
                            'documentId' => $documentId,
                            'description' => $payment_description,
                            'issuanceDate' => $row->ABFECEMI,
                            'expirationDate' => $row->ABFECVCT,
                            'totalAmount' => number_format($row->ABIMPSLD, 2, '.', ''),
                            'minimumAmount' => $minimun_payment,
                        ];
                        if (isset($row->ABCODMON_ORIG)) {
                            $reg = new \stdClass();
                            $reg->documentId = $documentId;
                            $reg->description = $payment_description;
                            $reg->issuanceDate = $row->ABFECEMI;
                            $reg->expirationDate = $row->ABFECVCT;
                            $reg->totalAmount = number_format($row->ABIMPSLD, 2, '.', '');
                            $reg->minimumAmount = $minimun_payment;
                            $reg->originalDocCurrencyCode = $row->ABCODMON_ORIG;
                            $reg->originalTotalAmount = $row->ABIMPSLD_ORIG;
                            $reg->exchangeCurrencyRate = $row->TIPO_CAMBIO;
                            $vector_deudas[$i] = $reg;
                        }
                        $i++;
                    }
                }//fin validate
            }// fin recorridos deudas
            $userId = request()->user()->id;
            $arrayConsultation = array(
                'bankCode' => $request->bankCode,
                'requestId' => $request_id,
                'processId' => $request->processId,
                'channel' => $request->channel,
                'currencyCode' => $request->currencyCode,
                'serviceId' => $request->serviceId,
                'customerIdentificationCode' => $request->customerIdentificationCode,
                'transactionDate' => $request->transactionDate,
                'customerDebts' => json_encode($vector_deudas),
                'created_at' => date("Y-m-d H:i:s"),
                'userId' => $userId
            );
            //die(print_r($arrayConsultation));
            //$deudas->operationId = $this->customer_debts_bank_consultations($request->bankCode, $request->processId, $request_id, $request->channel,  $request->currencyCode, $request->serviceId, $request->customerIdentificationCode, $request->transactionDate, json_encode($vector_deudas));
            $deudas->operationId = $this->put_customer_debts_bank_consultation($arrayConsultation);
            //die('<br>D: ' . $deudas->operationId);
            if ($request->bankCode === '002') $deudas->operationId = (string)$deudas->operationId;
            $deudas->customerIdentificationCode = $request->customerIdentificationCode;
            $deudas->customerName = $customerDebts[0]->AKRAZSOC;
            $deudas->currencyCode = $request->currencyCode;
            $deudas->debtorStatus = 'V';
            $deudas->transactionDate = date("Y-m-d H:i:s");
            $deudas->cronologicIndicator = 0;
            $deudas->statusIndicator = 2;
            $deudas->paymentRestriction = '';
            $deudas->documents = $vector_deudas;
            return response()->json($deudas, 200);
        } catch (Exception $e) {
            $arrayIn = array(
                    'tabla' => 'customer_debt_inquiries',
                    'mensaje' => 'catch de deudas - fecha:'.date("d-m-Y H:i:s"),
                    'otro' => json_encode($e)
                );
            DB::table('log_migraciones')->insert($arrayIn);
            return response()->json($e, 500);
        }
    }

    public function get_db_customer_debts($customerIdentificationNumber, $currencyCode, $maxDebtLimit){
        $whereRawDocTypes = '(("ABTIPDOC"=\'DA\' AND "ABIMPCCC"=0) OR ("ABTIPDOC"=\'' . $this->cax_documents . '\' AND "ABIMPCCC"=0) OR ("ABIMPCCC" >= "ABIMPSLD"))';
        $deudas = DB::table('cliente_saldos')
                ->where('ABSTS', '=', 'A')
                ->where('NUMERO_IDENTIFICACION', '=', $customerIdentificationNumber)
                //->where('ABCODMON', '=', $currencyCode)
                ->where('ABIMPSLD', '>', 0)
                ->whereRaw($whereRawDocTypes)
                ->orderBy('ABTIPDOC', 'DESC')
                ->orderBy('ABFECVCT', 'ASC')
                ->orderBy('ABNRODOC', 'ASC') //ordenamos por numero de  documento
                ->limit($maxDebtLimit)
                ->get()->toArray();
        if (is_array($deudas) && sizeof($deudas) > 0) {
            $USD = '02';
            $tipo_cambio_dolar = $this->retorna_tipo_cambio_dolar_mym($USD);
            $precio_dolar_mym_venta = (float) round($tipo_cambio_dolar->mym_selling_price, 2);
            foreach ($deudas as $d => $deuda) {
                if ($currencyCode === '02') { //si la cuenta del cliente es en dolares
                    if ($deuda->ABCODMON === '01') {
                        $deuda->ABCODMON_ORIG = '01';//MONEDA ORIGEN DE LA DEUDA
                        $deuda->ABIMPSLD_ORIG = $deuda->ABIMPSLD;
                        $deuda->ABIMPCCC_ORIG = $deuda->ABIMPCCC;
                        $deuda->TIPO_CAMBIO = $precio_dolar_mym_venta;
                        $deuda->ABCODMON = '02';
                        $deuda->ABIMPSLD =  round(((float)$deuda->ABIMPSLD / $precio_dolar_mym_venta), 2);
                        $deuda->ABIMPCCC =  round(((float)$deuda->ABIMPCCC / $precio_dolar_mym_venta), 2);
                    }
                }else{ //si la cuenta del cliente es en soles
                    if ($deuda->ABCODMON === '02') {
                        $deuda->ABCODMON_ORIG = '02';//MONEDA ORIGEN DE LA DEUDA
                        $deuda->ABIMPSLD_ORIG = $deuda->ABIMPSLD;
                        $deuda->ABIMPCCC_ORIG = $deuda->ABIMPCCC;
                        $deuda->TIPO_CAMBIO = $precio_dolar_mym_venta;
                        $deuda->ABCODMON = '01';
                        $deuda->ABIMPSLD =  round(((float)$deuda->ABIMPSLD * $precio_dolar_mym_venta), 2);
                        $deuda->ABIMPCCC =  round(((float)$deuda->ABIMPCCC * $precio_dolar_mym_venta), 2);
                    }
                }
            }
        }
        return (is_array($deudas) && sizeof($deudas) > 0) ? $deudas : false;
    }

    public function customer_debts_bank_consultations(
        $bankCode,
        $processId,
        $requestId,
        $channel,
        $currencyCode,
        $serviceId,
        $customerIdentificationCode,
        $transactionDate,
        $vector_deudas
    ) {
        return DB::table('customer_debts_bank_consultations')->insertGetId(
            [
                'bankCode' => $bankCode,
                'processId' => $processId,
                'requestId' => $requestId,
                'channel' => $channel,
                'currencyCode' => $currencyCode,
                'serviceId' => $serviceId,
                'customerIdentificationCode' => $customerIdentificationCode,
                'transactionDate' => $transactionDate,
                'created_at' => date("Y-m-d H:i:s"),
                'customerDebts' => $vector_deudas
            ]
        );
    }

    public function put_customer_debts_bank_consultation($arrayInsert)
    {
        //echo '<pre>';
        //die(print_r($arrayInsert));
        return DB::table('customer_debts_bank_consultations')->insertGetId($arrayInsert);
    }

    public function validate_customer_payment_process($request)
    {
        $util = new Utilidades;
        /*
            .- VERIFICAR QUE TRANSACCION NO EXISTA AUN
            .- REGISTRAR EL PAGO
            .- BAJAR LOS SALDOS EN LA TABLA CLIENTE SALDOS PARA LOS DOCUMENTOS PAGADOS
            .- RETORNAR RESPUESTA
            */
        $arrayIn = array(
            'tabla' => 'customer_payments',
            'mensaje' => 'Parámetros recibidos - Inicio Proceso',
            'otro' => json_encode($request->all())
        );
        $util->inserta_into_tabla('log_migraciones', $arrayIn);

        $MON = ($request->currencyCode === 'PEN' ? '01' : '02');
        $payment_response = new \stdClass();
        $userId = request()->user()->id;
        $numero_documento =  $request->customerIdentificationCode;
        $operationNumber = ($request->operationNumber) ? $request->operationNumber : $request->requestId;
        $operationNumber = ($request->bankCode === '009') ? $request->requestId : $operationNumber;

        // ** VALIDAR NUMERO DE DEPOSITO **/
        $arrayWhere = array(
            ['bankCode', '=', $request->bankCode],
            ['customerIdentificationCode' , '=', $request->customerIdentificationCode],
            ['operationNumber', '=', $operationNumber],
            ['operation_date', '=', date("Y-m-d")],
        );
        if ($this->getPaymentDeposit($arrayWhere)) {
            $mensaje = [
                'CODIGO' => 'DEPOSITO_BANCARIO',
                'MENSAJE' => 'NÚMERO DE DEPÓSITO REGISTRADO PREVIAMENTE',
                'ERROR_CODE' => 400
            ];
            $arrayIn = array(
                'tabla' => 'customer_payments',
                'mensaje' => $mensaje['MENSAJE'],
                'otro' => json_encode($request->all())
            );
            $util->inserta_into_tabla('log_migraciones', $arrayIn);

            return $mensaje;
        }
        // ** FIN VALIDAR NUMERO DE DEPOSITO **/

        $arrayWhere = array(
            'document_number' => $numero_documento
        );
        if (!$this->verifyCustomerExist($arrayWhere)) {
            $mensaje = [
                'CODIGO' => 'RUC_NO_EXISTE',
                'MENSAJE' => 'NÚMERO DE REFERENCIA NO EXISTE',
                'ERROR_CODE' => 400
            ];
            $arrayIn = array(
                'tabla' => 'customer_payments',
                'mensaje' => $mensaje['MENSAJE'],
                'otro' => json_encode($arrayWhere)
            );
            $util->inserta_into_tabla('log_migraciones', $arrayIn);

            return $mensaje;
        }
        if ($this->verifyPaymentTransactionExist($request->bankCode, $request->requestId, $request->customerIdentificationCode)) {
            $mensaje = [
                'CODIGO' => 'TRANSACCION_REGISTRADA_PREVIAMENTE',
                'MENSAJE' => 'TRANSACCIÓN REGISTRADA PREVIAMENTE',
                'ERROR_CODE' => 400
            ];
            $arrayIn = array(
                'tabla' => 'customer_payments',
                'mensaje' => $mensaje['MENSAJE'],
                'otro' => json_encode($request->all())
            );
            $util->inserta_into_tabla('log_migraciones', $arrayIn);

            return $mensaje;
        }

        $paidDocuments = json_decode($request->paidDocuments);
        if (!is_array($paidDocuments)) {
            $mensaje = [
                'CODIGO' => 'FALTA_ARRAY_DOCS_PAGADOS',
                'MENSAJE' => 'Error: Falta array de documentos pagados',
                'ERROR_CODE' => 400
            ];
            $arrayIn = array(
                'tabla' => 'customer_payments',
                'mensaje' => $mensaje['MENSAJE'],
                'otro' => json_encode($request->all())
            );
            $util->inserta_into_tabla('log_migraciones', $arrayIn);

            return $mensaje;
        } else {
            $arrayIn = array(
            'tabla' => 'customer_payments',
            'mensaje' => 'Parámetros recibidos 2 - Inicio Proceso',
            'otro' => json_encode($request->all())
            );
            $util->inserta_into_tabla('log_migraciones', $arrayIn);
            $balance = (float)$request->totalAmount;
            $total_deuda = 0.0;
            $total_saldo = 0.0;

            foreach ($paidDocuments as $document) {
                if (!$doc = $this->getCustomerDebtDocument($document, $numero_documento, $MON)) {
                    $mensaje = [
                        'CODIGO' => 'NRO_REFERENCIA_NO_EXISTE',
                        'MENSAJE' => 'NUMERO DE REFERENCIA NO EXISTE',
                        'ERROR_CODE' => 400
                    ];
                    $arrayIn = array(
                        'tabla' => 'customer_payments',
                        'mensaje' => $mensaje['MENSAJE'],
                        'otro' => json_encode($request->all())
                    );
                    $util->inserta_into_tabla('log_migraciones', $arrayIn);

                    return $mensaje;
                } else {
                    $total_deuda += floatval(round($doc->ABIMPCCC, 2));
                    $total_saldo += floatval(round($doc->ABIMPSLD, 2));
                }
            }

            if (sizeof($paidDocuments) > 1 && (float)$total_saldo <> (float)$request->totalAmount) {
                $mensaje = [
                    'CODIGO' => 'MONTO_PAGADO_NO_COINCIDE',
                    'MENSAJE' => 'MONTO TOTAL PAGADO NO COINCIDE CON MONTO PAGADO POR DOCUMENTO',
                    'ERROR_CODE' => 400
                ];
                $arrayIn = array(
                    'tabla' => 'customer_payments',
                    'mensaje' => $mensaje['MENSAJE'],
                    'otro' => json_encode($request->all())
                );
                $util->inserta_into_tabla('log_migraciones', $arrayIn);

                return $mensaje;
            }

            if (!$this->verifyTotalAmountVsPaidDocuments($balance, $paidDocuments, $total_saldo)) {
                $mensaje = [
                    'CODIGO' => 'MONTO_PAGADO_NO_COINCIDE',
                    'MENSAJE' => 'MONTO TOTAL PAGADO NO COINCIDE CON MONTO PAGADO POR DOCUMENTO',
                    'ERROR_CODE' => 400
                ];
                $arrayIn = array(
                    'tabla' => 'customer_payments',
                    'mensaje' => $mensaje['MENSAJE'],
                    'otro' => json_encode($request->all())
                );
                $util->inserta_into_tabla('log_migraciones', $arrayIn);

                return $mensaje;
            }

            foreach ($paidDocuments as $document) {
                //Validar que el documento pagado existe 
                if (!$doc = $this->getCustomerDebtDocument($document, $numero_documento, $MON)) { //$request->customerIdentificationCode
                    $mensaje = [
                        'CODIGO' => 'NRO_REFERENCIA_NO_EXISTE',
                        'MENSAJE' => 'NUMERO DE REFERENCIA NO EXISTE',
                        'ERROR_CODE' => 400
                    ];
                    $arrayIn = array(
                        'tabla' => 'customer_payments',
                        'mensaje' => $mensaje['MENSAJE'],
                        'otro' => json_encode($request->all())
                    );
                    $util->inserta_into_tabla('log_migraciones', $arrayIn);

                    return $mensaje;
                } else {
                    $saldo_doc = (float)$doc->ABIMPSLD;
                    $monto_pagado_doc = (float) $document->amounts[0]->amount;

                    if ($doc->ABTIPDOC === 'DA' && sizeof($paidDocuments) > 1) {
                        $mensaje = [
                            'CODIGO' => 'DEBE_SER_PAGO_UNICO',
                            'MENSAJE' => 'PAGO DE DOCUMENTO COMPUESTO DEBE SER ÚNICO',
                            'ERROR_CODE' => 400
                        ];
                        $arrayIn = array(
                            'tabla' => 'customer_payments',
                            'mensaje' => $mensaje['MENSAJE'],
                            'otro' => json_encode($request->all())
                        );
                        $util->inserta_into_tabla('log_migraciones', $arrayIn);

                        return $mensaje;
                    }

                    if ($saldo_doc == 0) {
                        $mensaje = [
                            'CODIGO' => 'DOCUMENTO_YA_PAGADO',
                            'MENSAJE' => 'DOCUMENTO YA FUE PAGADO',
                            'ERROR_CODE' => 200
                        ];
                        $arrayIn = array(
                            'tabla' => 'customer_payments',
                            'mensaje' => $mensaje['MENSAJE'],
                            'otro' => json_encode($request->all())
                        );
                        $util->inserta_into_tabla('log_migraciones', $arrayIn);

                        return $mensaje;
                    }

                    if ($monto_pagado_doc <= 0) {
                        $mensaje = [
                            'CODIGO' => 'ERROR_PAGO_MINIMO',
                            'MENSAJE' => 'ERROR MONTO PAGADO DEBE SER MAYOR QUE MONTO MÍNIMO',
                            'ERROR_CODE' => 200
                        ];
                        $arrayIn = array(
                            'tabla' => 'customer_payments',
                            'mensaje' => $mensaje['MENSAJE'],
                            'otro' => json_encode($request->all())
                        );
                        $util->inserta_into_tabla('log_migraciones', $arrayIn);

                        return $mensaje;
                    }

                    if ($doc->ABTIPDOC !== $this->cax_documents) {
                        if ($doc->ABFRMPAG == 'C' && $saldo_doc <> $monto_pagado_doc) {
                            $mensaje = [
                                'CODIGO' => 'DOCUMENTO_CONTADO_DEBE_SER_PAGO_COMPLETO',
                                'MENSAJE' => 'DOCUMENTO DE CONTADO. DEBE SER PAGADO POR COMPLETO',
                                'ERROR_CODE' => 200
                            ];
                            $arrayIn = array(
                                'tabla' => 'customer_payments',
                                'mensaje' => $mensaje['MENSAJE'],
                                'otro' => json_encode($request->all())
                            );
                            $util->inserta_into_tabla('log_migraciones', $arrayIn);

                            return $mensaje;
                        }
                    }

                    $mensaje2 = $this->validateCustomerDocBalance($document, $doc, $balance);
                    $arrayIn = array(
                        'tabla' => 'customer_payments',
                        'mensaje' => 'Método validateCustomerDocBalance',
                        'otro' => json_encode($mensaje2)
                    );
                    $util->inserta_into_tabla('log_migraciones', $arrayIn);

                    if ($mensaje2['CODIGO'] !== 'OK') {
                        return $mensaje2;
                    }
                }
            }
        }

        $mensaje = [
            'CODIGO' => 'OK',
            'MENSAJE' => 'VALIDACION APROBADA',
            'ERROR_CODE' => 200
        ];
        $arrayIn = array(
            'tabla' => 'customer_payments',
            'mensaje' => $mensaje['MENSAJE'],
            'otro' => json_encode($request->all())
        );
        $util->inserta_into_tabla('log_migraciones', $arrayIn);

        return $mensaje;
    }

    public function customer_payment_process(Request $request)
    {
        $util = new Utilidades;

        try {
            $rules = [
                //'processId' => 'required', //serviceId en BCP y SCOTIA
                //'serviceId' => 'required', //processId en BBVA
                'bankCode' => [
                    'required',
                    Rule::in($this->arrayBankList),
                ],
                'currencyCode' => [
                    'required',
                    Rule::in(['USD', 'PEN']),
                ],
                'requestId' => 'required',
                //'operationId' => 'required', //Para BCP
                'channel' => 'required',
                'customerIdentificationCode' => 'required',
                'transactionDate' => 'required',
                'paymentType' => 'required',
                'paidDocuments' => 'required',
                'totalAmount' => 'required',
                'transactionCurrencyCode' => [
                    'required',
                    Rule::in(['USD', 'PEN']),
                ],
                'currencyExchange' => 'required',
            ];
            $messages = [
                'required' => 'El campo :attribute es obligatorio'
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $MON = ($request->currencyCode === 'PEN' ? '01' : '02');
            $payment_response = new \stdClass();
            $userId = request()->user()->id;
            $numero_documento =  $request->customerIdentificationCode;
            $operationNumber = ($request->operationNumber) ? $request->operationNumber : $request->requestId;
            if ($request->bankCode === '009') $operationNumber = $request->requestId;

            $mensaje = $this->validate_customer_payment_process($request);

            if ($mensaje['CODIGO'] === 'OK') {
                $payment_array = [
                    'requestId' => $request->requestId,
                    'bankCode' => $request->bankCode,
                    'currencyCode' => $request->currencyCode,
                    'channel' => $request->channel,
                    'customerIdentificationCode' => $numero_documento,
                    'serviceId' => $request->serviceId,
                    'operationId' => $request->operationId,
                    'operationNumber' => $operationNumber,
                    'paymentType' => $request->paymentType,
                    'transactionDate' => $request->transactionDate,
                    'created_at' => date("Y-m-d H:i:s"),
                    'transactionCurrencyCode' => $request->transactionCurrencyCode,
                    'currencyExchange' => $request->currencyExchange,
                    'totalAmount' => $request->totalAmount,
                    'paidDocuments' => $request->paidDocuments,
                    'check' => $request->check,
                    'otherFields' => json_encode($request->all()),
                    'userId' => $userId
                ];

                $arrayIn = array(
                    'tabla' => 'customer_payments',
                    'mensaje' => 'REGISTRO DE PAGO',
                    'otro' => json_encode($payment_array)
                );
                $util->inserta_into_tabla('log_migraciones', $arrayIn);

                DB::beginTransaction();
                $payment_array['generatedId'] = (string)DB::table('customer_payments')->insertGetId($payment_array);

                $balance = (float)$request->totalAmount;
                $paidDocuments = json_decode($request->paidDocuments);
                foreach ($paidDocuments as $document) {
                    $doc = $this->getCustomerDebtDocument($document, $numero_documento, $MON);
                    //Actualizar Saldo de documento actual
                    $balance = $this->updateCustomerDocBalance($document, $doc, $payment_array, $balance, $MON);
                    //echo 'New Balance: ' . $balance;
                    /*
                    if (!is_numeric($balance)) {


                        DB::rollBack();
                        return response()->json(['ERROR EN MONTO'], 400);
                    }
                    */

                    if ($doc->ABTIPDOC === $this->cax_documents) {
                        $this->save_customer_debts_payments_cax_documents($document, $doc, $payment_array['generatedId'], $MON);
                        $balance = 0;
                    }
                }
                $payment_response->operationNumberCompany = $payment_array['generatedId'];
                $payment_response->transactionDate = date("Y-m-d H:i:s");
                $payment_response->clientName = $doc->AKRAZSOC;
                $payment_response->clientIdentificacion = $numero_documento;
                $payment_response->description = 'TRANSACCION REALIZADA CON EXITO';
                DB::commit();
                return response()->json($payment_response, 201);
            } else {
                return response()->json($mensaje['MENSAJE'], $mensaje['ERROR_CODE']);
            }
        } catch (\Exception $e) {
            return response()->json($e, 500);
        }
    }


    public function getCustomerDebtDocument($object, $customerIdentificationCode, $currencyCode = '02'){
        $arrayDoc = explode('-', $object->documentId);
        $arrayWhere = array();

        $USD = '02';

        switch (sizeof($arrayDoc)) {
            case 2: //DOCUMENTO A CRÉDITO (FAC O BOL)
                array_push($arrayWhere,  ['CBNROSER', '=', $arrayDoc[0]]);
                array_push($arrayWhere,  ['CBNROCOR', '=', $arrayDoc[1]]);
                $deuda = DB::table('cliente_saldos')
                    ->where('ABSTS', '=', 'A')
                    //->where('ABCODMON', '=', $USD)
                    ->where('NUMERO_IDENTIFICACION', '=', $customerIdentificationCode)
                    ->where($arrayWhere)
                    ->first();
                break;
            case 3: //DOCUMENTO DE CONTADO (PED)
                array_push($arrayWhere,  ['ABTIPDOC', '=', $arrayDoc[1]]);
                array_push($arrayWhere,  ['ABNRODOC', '=', $arrayDoc[2]]);
                $deuda = DB::table('cliente_saldos')
                    ->where('ABSTS', '=', 'A')
                    //->where('ABCODMON', '=', $USD)
                    ->where('NUMERO_IDENTIFICACION', '=', $customerIdentificationCode)
                    ->where($arrayWhere)
                    ->first();
                break;

            case 1: //DOCUMENTO COMPUESTO (PAGO COMPLETO)
                $arrayDoc = explode('_', $object->documentId);
                array_push($arrayWhere,  ['ABTIPDOC', '=', $arrayDoc[0]]);
                array_push($arrayWhere,  ['ABNRODOC', '=', $arrayDoc[1]]);
                $deuda = DB::table('cliente_saldos')
                    ->where('ABSTS', '=', 'A')
                    //->where('ABCODMON', '=', $USD)
                    ->where('NUMERO_IDENTIFICACION', '=', $customerIdentificationCode)
                    ->where($arrayWhere)
                    ->first();
                break;
        }

        if (is_object($deuda)) {
            $tipo_cambio_dolar = $this->retorna_tipo_cambio_dolar_mym('02');
            if ($currencyCode === '02') { //si la cuenta del cliente es en dolares
                if ($deuda->ABCODMON === '01' && $tipo_cambio_dolar) {
                    $precio_dolar_mym_venta = (float) round($tipo_cambio_dolar->mym_selling_price, 2);
                    $deuda->ABCODMON_ORIG = '01';//MONEDA ORIGEN DE LA DEUDA
                    $deuda->ABIMPSLD_ORIG = $deuda->ABIMPSLD;
                    $deuda->ABIMPCCC_ORIG = $deuda->ABIMPCCC;
                    $deuda->TIPO_CAMBIO = $precio_dolar_mym_venta;
                    $deuda->ABCODMON = '02';
                    $deuda->ABIMPSLD =  round(((float)$deuda->ABIMPSLD / $precio_dolar_mym_venta), 2);
                    $deuda->ABIMPCCC =  round(((float)$deuda->ABIMPCCC / $precio_dolar_mym_venta), 2);
                }
            }else{ //si la cuenta del cliente es en soles
                if ($deuda->ABCODMON === '02' && $tipo_cambio_dolar) {
                    $precio_dolar_mym_venta = (float) round($tipo_cambio_dolar->mym_selling_price, 2);
                    $deuda->ABCODMON_ORIG = '02';//MONEDA ORIGEN DE LA DEUDA
                    $deuda->ABIMPSLD_ORIG = $deuda->ABIMPSLD;
                    $deuda->ABIMPCCC_ORIG = $deuda->ABIMPCCC;
                    $deuda->TIPO_CAMBIO = $precio_dolar_mym_venta;
                    $deuda->ABCODMON = '01';
                    $deuda->ABIMPSLD =  round(((float)$deuda->ABIMPSLD * $precio_dolar_mym_venta), 2);
                    $deuda->ABIMPCCC =  round(((float)$deuda->ABIMPCCC * $precio_dolar_mym_venta), 2);
                }
            }
        }
        return $deuda;
    }



    public function validateCustomerDocBalance($doc_request, $doc_db, $balance)
    {
        $util = new Utilidades;

        //echo "<br>1- Balance: $balance";
        $balance = (float) round($balance, 2);
        $saldo_documento = (float) round($doc_db->ABIMPSLD, 2);
        $monto_pagado_transaccion = (float) round($doc_request->amounts[0]->amount, 2);
        //echo "<br>1- Balance: $balance --- Doc Pag.: $monto_pagado_transaccion";

        if ($balance >= $saldo_documento) {
            if ($doc_db->ABFRMPAG === 'C' && $saldo_documento <> $monto_pagado_transaccion) {
                $mensaje = [
                    'CODIGO' => 'VALIDA_PAGO_DOC_CONTADO',
                    'MENSAJE' => 'DEBE PAGAR SALDO COMPLETO PARA DOCUMENTOS DE CONTADO',
                    'ERROR_CODE' => 200
                ];
                $arrayIn = array(
                    'tabla' => 'customer_payments',
                    'mensaje' => $mensaje['CODIGO'] . '-' . $mensaje['MENSAJE'],
                    'otro' => json_encode([
                        'balance' => $balance,
                        'saldo_documento' => $saldo_documento,
                        'monto_pagado_transaccion' => $monto_pagado_transaccion,
                        'forma_pago_doc' => $doc_db->ABFRMPAG
                    ])
                );
                $util->inserta_into_tabla('log_migraciones', $arrayIn);

                return $mensaje;
            }

            $balance = (float)round(($balance - $monto_pagado_transaccion), 2);
            $saldo = (float)round(($saldo_documento - $monto_pagado_transaccion), 2);
            $monto_pagado_documento = $monto_pagado_transaccion;
        } else {
            if ($doc_db->ABFRMPAG === 'C') {
                $mensaje = [
                    'CODIGO' => 'MONTO_PAGADO_MENOR_A_SALDO_DOC_CONTADO',
                    'MENSAJE' => 'SALDO NO ES SUFICIENTE',
                    'ERROR_CODE' => 200
                ];
                $arrayIn = array(
                    'tabla' => 'customer_payments',
                    'mensaje' => $mensaje['CODIGO'] . '-' . $mensaje['MENSAJE'],
                    'otro' => json_encode([
                        'balance' => $balance,
                        'saldo_documento' => $saldo_documento,
                        'monto_pagado_transaccion' => $monto_pagado_transaccion,
                        'forma_pago_doc' => $doc_db->ABFRMPAG
                    ])
                );
                $util->inserta_into_tabla('log_migraciones', $arrayIn);

                return $mensaje;
            }
        }
        //echo "<br>2- Balance: $balance --- Saldo: $saldo --- Monto total depositado: $monto_pagado_transaccion --- Monto Pagado Doc. $monto_pagado_documento";

        //echo "<br>3- Balance: $balance";
        $mensaje = [
            'CODIGO' => 'OK',
            'MENSAJE' => 'BALANCE OK',
            'ERROR_CODE' => 200,
            'balance' => (float)round($balance, 2)
        ];
        return $mensaje;
    }




    public function updateCustomerDocBalance($doc_request, $doc_db, $payment_array, $balance, $currency_code)
    {
        //echo "<br>1- Balance: $balance";
        $balance = (float) round($balance, 2);
        $saldo_documento = (float) round($doc_db->ABIMPSLD, 2);
        $monto_pagado_transaccion = (float) round($doc_request->amounts[0]->amount, 2);
        //echo "<br>1- Balance: $balance --- Doc Pag.: $monto_pagado_transaccion";

        if ($balance >= $saldo_documento) {
            if ($doc_db->ABFRMPAG === 'C' && $saldo_documento <> $monto_pagado_transaccion) {
                return 'DEBE PAGAR SALDO COMPLETO PARA DOCUMENTOS DE CONTADO';
            }

            $balance = (float)round(($balance - $monto_pagado_transaccion), 2);
            $saldo = (float)round(($saldo_documento - $monto_pagado_transaccion), 2);
            $monto_pagado_documento = $monto_pagado_transaccion;
        } else {
            if ($doc_db->ABFRMPAG === 'C') {
                return 'SALDO NO ES SUFICIENTE';
            }

            $saldo = (float) round(($saldo_documento - $monto_pagado_transaccion), 2);
            $balance = 0.0;
            $monto_pagado_documento = $monto_pagado_transaccion;
        }
        //echo "<br>2- Balance: $balance --- Saldo: $saldo --- Monto total depositado: $monto_pagado_transaccion --- Monto Pagado Doc. $monto_pagado_documento";

        $tipo_cambio_dolar = $this->retorna_tipo_cambio_dolar_mym('02');
        $precio_dolar_mym_venta = round($tipo_cambio_dolar->mym_selling_price, 2);


        if ($doc_db->ABTIPDOC !== $this->cax_documents) {
            //Escribir en tabla "customer_debts_payments"
            $arrayInsert = [
                'id_deuda' => $doc_db->id,
                'payment_id' => $payment_array['generatedId'],
                'payment_amount' => $monto_pagado_documento,
                'currency_code' => $currency_code,
                'exchange_rate' => $precio_dolar_mym_venta
            ];
            DB::table('customer_debts_payments')->insert($arrayInsert);
            if ($doc_db->ABTIPDOC === 'DA') {
                if (isset($doc_db->ABCODMON_ORIG)) {
                    switch ($doc_db->ABCODMON_ORIG) {
                        case '01': //si la deuda esta en soles
                            $tipo_cambio = (float) round($doc_db->TIPO_CAMBIO, 2);
                            $saldo = round(($saldo * $tipo_cambio), 2);
                            break;
                        case '02': //si la deuda esta en dolares
                            $tipo_cambio = (float) round($doc_db->TIPO_CAMBIO, 2);
                            $saldo = round(($saldo / $tipo_cambio), 2);
                            break;
                        default:
                            return 'NO EXISTE MONEDA EN ESTE DOCUMENTO AGRUPADO';
                            break;
                    }
                }

                DB::table('cliente_saldos')
                ->where('ABSTS', 'A')
                ->where('ABCODCIA', $doc_db->ABCODCIA)
                ->where('ABCODCLI', $doc_db->ABCODCLI)
                ->where('ABTIPDOC', $doc_db->ABTIPDOC)
                ->where('ABNRODOC', $doc_db->ABNRODOC)
                ->update(['ABIMPSLD' => $saldo, 'updated_at' => date("Y-m-d H:i:s")]);

            }else{
                if (isset($doc_db->ABCODMON_ORIG)) {
                    switch ($doc_db->ABCODMON_ORIG) {
                        case '01':
                            $tipo_cambio = (float) round($doc_db->TIPO_CAMBIO, 2);
                            $saldo = round(($saldo * $tipo_cambio), 2);
                            break;
                        case '02':
                            $tipo_cambio = (float) round($doc_db->TIPO_CAMBIO, 2);
                            $saldo = round(($saldo / $tipo_cambio), 2);
                            break;
                        default:
                            return 'NO EXISTE MONEDA EN ESTE DOCUMENTO';
                            break;
                    }
                }

                DB::table('cliente_saldos')
                ->where('ABSTS', 'A')
                ->where('ABCODCIA', $doc_db->ABCODCIA)
                ->where('ABCODSUC', $doc_db->ABCODSUC)
                ->where('ABCODCLI', $doc_db->ABCODCLI)
                ->where('ABTIPDOC', $doc_db->ABTIPDOC)
                ->where('ABNRODOC', $doc_db->ABNRODOC)
                ->update(['ABIMPSLD' => $saldo, 'updated_at' => date("Y-m-d H:i:s")]);
            }
        }
        //echo "<br>3- Balance: $balance";
        return (float)round($balance, 2);
    }

    public function save_customer_debts_payments_cax_documents($doc_request, $doc_db, $payment_number, $currency_code)
    {
        $util = new Utilidades;

        $monto_pagado_transaccion = (float) round($doc_request->amounts[0]->amount, 2);
        $tipo_cambio_dolar = $this->retorna_tipo_cambio_dolar_mym('02');
        $precio_dolar_mym_venta = round($tipo_cambio_dolar->mym_selling_price, 2);

        //Escribir en tabla "customer_debts_payments"
        $arrayInsert = [
            'id_deuda' => $doc_db->id,
            'payment_id' => $payment_number,
            'payment_amount' => $monto_pagado_transaccion,
            'currency_code' => $currency_code,
            'exchange_rate' => $precio_dolar_mym_venta
        ];

        $arrayIn = array(
            'tabla' => 'customer_debts_payments',
            'mensaje' => 'INSERTAR PAGO DEUDA',
            'otro' => json_encode($arrayInsert)
        );
        $util->inserta_into_tabla('log_migraciones', $arrayIn);

        DB::table('customer_debts_payments')->insert($arrayInsert);
    }

    public function verifyPaymentTransactionExist($bankCode, $transactionId,$customerIdentificationCode)
    {
        return DB::table('customer_payments')
            ->where('requestId', $transactionId)
            ->where('bankCode', $bankCode)
            ->where('customerIdentificationCode',$customerIdentificationCode)
            ->where('operation_date',date("Y-m-d"))
            ->first();
    }

    public function verifyDebtConsultationTransactionExist($bankCode, $transactionId)
    {
        $rs = DB::table('customer_debts_bank_consultations')
            ->where('requestId', $transactionId)
            ->where('bankCode', $bankCode)
            ->get();
        if ($rs && sizeof($rs) > 0) return true;
        else return false;
    }

    public function getPaidDebtByCustomer($arrayWhere)
    {
        return DB::table('customer_payments')
            ->where($arrayWhere)
            ->first();
    }

    public function get_db_customer_debts_list($currencyCode)
    {

        $result = DB::table('cliente_saldos')
            ->select('ABCODCLI as codigo_cliente', 'NUMERO_IDENTIFICACION as identificacion', 'AKRAZSOC as razon_social', 'ABCODCIA as empresa', 'ABCODSUC as sucursal', 'ABTIPDOC as tipo_documento', 'ABNRODOC as numero_interno', 'ABFECEMI as fecha_emision', 'ABFECVCT as fecha_vencimiento', 'ABCODMON as moneda', 'ABIMPSLD as deuda', 'ABFRMPAG as forma_pago', 'CBNROSER as serie_nro', 'CBNROCOR as nro_correlativo')
            ->where('ABSTS', '=', 'A')
            ->where('ABCODMON', '=', $currencyCode)
            ->where('ABIMPSLD', '>', 0)
            ->whereRaw('"ABIMPCCC" >= "ABIMPSLD"')
            ->orderBy('ABFECEMI', 'DESC')
            ->limit(75)
            ->get()->toArray();
        return (is_array($result) && sizeof($result) > 0) ? $result : false;
    }

    public function genera_codigo_alfanumerico($prefijo, $tam)
    {
    }

    public function bank_return_request(Request $request)
    {
        try {
            $rules = [
                'bankCode' => [
                    'required',
                    Rule::in(['002', '009', '011']),
                ],
                'customerIdentificationCode' => 'required',
                'requestId' => 'required',
                'operationNumberAnnulment' => 'required',
                'transactionDate' => 'required',
                'channel' => 'required',
            ];
            $messages = [
                'required' => 'El campo :attribute es obligatorio'
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            if ($this->getReturnRequest(array(['bankCode', '=', $request->bankCode], ['requestId', '=', $request->requestId]))) {
                return response()->json('SOLICITUD DE EXTORNO REGISTRADA PREVIAMENTE', 400);
            }

            $userId = request()->user()->id;

            $returnType = 'M';
            if ($request->bankCode === '009') {
                $request->requestId = $request->bankCode . '-' . Str::random(15);
                //die('<br>RequestId-ScotiaBank: ' . $request->requestId);
                $returnType = $request->returnType;
            }

            //Registrar Extorno
            $arrayExtorno = array(
                'bankCode' => $request->bankCode,
                'requestId' => $request->requestId,
                'customerIdentificationCode' => $request->customerIdentificationCode,
                'channel' => $request->channel,
                'transactionDate' => $request->transactionDate,
                'created_at' => date("Y-m-d H:i:s"),
                'operationNumberAnnulment' => $request->operationNumberAnnulment,
                'userId' => $userId,
                'returnType' => $returnType,
                'otherFields' => json_encode($request->all())
            );
            $new_bank_return_request = $this->registraExtorno($arrayExtorno);

            DB::beginTransaction();

            //VERIFICA SI ES ANULACIÓN DE EXTORNO
            if ($returnType === 'A') {
                //01.- BUSCAR PAGO REALIZADO CON EXTORNO APLICADO
                $arrayWhere = array(
                    ['requestId', '=', $request->operationNumberAnnulment],
                    ['bankCode', '=', $request->bankCode],
                    ['customerIdentificationCode', '=', $request->customerIdentificationCode],
                );
                if (!$paidDebt = $this->getPaidDebtByCustomer($arrayWhere)) {
                    return response()->json('DOCUMENTO SOLICITADO PARA EXTORNAR NO ENCONTRADO', 400);
                }

                $fechaTransaccion = new \Carbon\Carbon($paidDebt->transactionDate);
                $fechaActual = new \Carbon\Carbon("now");
                $minutos = $fechaTransaccion->diffInMinutes($fechaActual);
                if ($minutos >= $this->minutesToReturnByBank[$request->bankCode]) {
                    return response()->json('AGOTADO EL TIEMPO PARA SOLICITAR EXTORNO', 400);
                }

                //02.- BUSCAR DOCUMENTOS RELACIONADOS CON PAGO
                //Buscar documentos pagados y actualizar saldo en cliente_saldos
                //Inactivar Pago realizado
                $paidRows = $this->getPaidDocuments($request->bankCode, $request->operationNumberAnnulment, $request->customerIdentificationCode);
                if (!$paidRows[0]->return_request_id) {
                    return response()->json('DOCUMENTO NO HA SIDO EXTORNADO PREVIAMENTE', 200);
                }
                $payment_id = $paidRows[0]->id;

                $totalMontoPagado = 0;
                foreach ($paidRows as $row) {
                    if ($row->formaPago == 'R') {
                        $nroDoc = $row->serie . '-' . $row->numeroDocumento;
                    } else {
                        if ($row->TipoDoc === 'DA') $nroDoc = 'DA_' . $row->numeroPedido;
                        else $nroDoc = 'PED-' . $row->TipoDoc . '-' . $row->numeroPedido;
                    }
                    $montoExtornar = (float) round($this->buscaDoc($nroDoc, json_decode($row->paidDocuments)), 2);
                    if ($montoExtornar > 0) {
                        $saldo = (float) round($row->saldo, 2);
                        $monto_actualizar = (float) round(($saldo - $montoExtornar), 2);
                        $this->process_annulment_return_paid_document($row->clienteSaldoId, $monto_actualizar);
                    }
                }
                //ACTUALIZAR ID_RETORNO EN CUSTOMER_PAYMENT
                $arrayWhere = array(
                    ['id', '=', $payment_id]
                );
                $arrayUpdate = array(
                    'return_request_id' => null,
                    //'return_request_date' => null
                );
                $this->updateCustomerPayment($arrayWhere, $arrayUpdate);
                DB::commit();
                return response()->json('ANULACION DE EXTORNO REALIZADA', 200);
            }


            $arrayWhere = array(
                'document_number' => $request->customerIdentificationCode
            );
            if (!$this->verifyCustomerExist($arrayWhere)) {
                return response()->json('NÚMERO DE REFERENCIA NO EXISTE', 400);
            }

            $arrayWhere = array(
                ['requestId', '=', $request->operationNumberAnnulment],
                ['bankCode', '=', $request->bankCode],
                ['customerIdentificationCode', '=', $request->customerIdentificationCode],
            );
            if (!$paidDebt = $this->getPaidDebtByCustomer($arrayWhere)) {
                return response()->json('DOCUMENTO SOLICITADO PARA EXTORNAR NO ENCONTRADO', 400);
            }

            $fechaTransaccion = new \Carbon\Carbon($paidDebt->transactionDate);
            $fechaActual = new \Carbon\Carbon("now");
            $minutos = $fechaTransaccion->diffInMinutes($fechaActual);
            if ($minutos >= $this->minutesToReturnByBank[$request->bankCode]) {
                //echo '<br>' . $fechaTransaccion . ' - ' . $fechaActual . ' -> ' . $minutos;
                return response()->json('AGOTADO EL TIEMPO PARA SOLICITAR EXTORNO', 400);
            }


            //$return_response = new \stdClass();
            /*
            1.- Registrar en tabla de extornos
            2.- Colocar saldo anterior a los documentos pagados en tabla de saldos
            3.- Inactivar registro en tabla de pagos
            */



            //Buscar documentos pagados y actualizar saldo en cliente_saldos
            //Inactivar Pago realizado
            $paidRows = $this->getPaidDocuments($request->bankCode, $request->operationNumberAnnulment, $request->customerIdentificationCode);
            //echo '<pre>';
            //die(print_r($paidRows));
            if ($paidRows[0]->return_request_id) {
                return response()->json('DOCUMENTO EXTORNADO PREVIAMENTE', 400);
            }
            $payment_id = $paidRows[0]->id;

            $totalMontoPagado = 0;
            foreach ($paidRows as $row) {
                $montoPagado = 0;
                if ($row->formaPago == 'R') {
                    $nroDoc = $row->serie . '-' . $row->numeroDocumento;
                    $vecNroDoc = array($row->serie, $row->numeroDocumento);
                } else {
                    //$nroDoc = 'PED-' . $row->TipoDoc . '-' . $row->numeroPedido;
                    //$vecNroDoc = array('PED', $row->TipoDoc, $row->numeroPedido);
                    if ($row->TipoDoc === 'DA') $nroDoc = 'DA_' . $row->numeroPedido;
                    else $nroDoc = 'PED-' . $row->TipoDoc . '-' . $row->numeroPedido;
                }
                //$nroDoc = ($row->formaPago == 'R') ? $row->serie . '-' . $row->numeroDocumento : 'PED-' . $row->codCliente . '-' . $row->TipoDoc . '-' . $row->numeroPedido;
                $montoExtornar = $this->buscaDoc($nroDoc, json_decode($row->paidDocuments));
                if ($montoExtornar > 0) {
                    $saldo = $row->saldo;
                    //die('Saldo: ' . $saldo . ' - Ext: ' . $montoExtornar . ' - EXT + SALD: ' . ($saldo + $montoExtornar));
                    $this->process_return_paid_document($row->clienteSaldoId, ($montoExtornar + $saldo));
                }
                $totalMontoPagado += $montoExtornar;
            }

            //ACTUALIZAR ID_RETORNO EN CUSTOMER_PAYMENT
            $arrayWhere = array(
                ['id', '=', $payment_id]
            );
            $arrayUpdate = array(
                'return_request_id' => $new_bank_return_request,
                'return_request_date' => date("Y-m-d H:i:s")
            );
            $this->updateCustomerPayment($arrayWhere, $arrayUpdate);

            DB::commit();
            return response()->json('EXTORNO REALIZADO', 200);
        } catch (\Exception $e) {
            return response()->json($e, 500);
        }
    }

    public function buscaDoc($nroDoc, $paidDocuments)
    {
        foreach ($paidDocuments as $document) {
            if ($nroDoc === $document->documentId) {
                return $document->amounts[0]->amount;
            }
        }
        return 0;
    }

    public function verifyTotalAmountVsPaidDocuments($balance, $paidDocuments, $total_saldo)
    {
        $total_saldo = (float)round($total_saldo, 2);
        $balance = (float)round($balance, 2);
        $balance_inicial = $balance;
        $total_depositos = 0.0;
        //echo ("<br>B: $balance - BI: $balance_inicial --- TD: $total_depositos");
        foreach ($paidDocuments as $document) {
            $total_pagado_doc = (float)(round($document->amounts[0]->amount, 2));
            $total_depositos = (float)round(($total_depositos + $total_pagado_doc), 2);
            $balance = (float)round(($balance - $total_pagado_doc), 2);
            //echo "<br>TPD: $total_pagado_doc - TD: $total_depositos - B: $balance";
        }
        return ($balance_inicial <> $total_depositos) ? false : (($total_saldo < $total_depositos) ? false : true);
    }

    public function verifyCustomerExist($arrayWhere)
    {
        return DB::table('customers')
            ->where($arrayWhere)
            ->first();
    }

    public function registraExtorno($arrayInsert)
    {
        return DB::table('bank_return_requests')->insertGetId($arrayInsert);
    }

    public function getPaidDocuments($bankCode, $operationNumberAnnulment, $customerIdentificationCode)
    {
        //die($bankCode . ' - ' . $operationNumberAnnulment . ' - ' . $customerIdentificationCode);
        //RETORNAR DOCUMENTOS PAGADOS RELACIONADOS CON TABLA RE SALDOS
        $rs = DB::table('customer_payments AS cp')
            ->join('customer_debts_payments AS cdp', 'cp.id', '=', 'cdp.payment_id')
            ->join('cliente_saldos AS cs', 'cs.id', '=', 'cdp.id_deuda')
            ->where('cp.bankCode', '=', $bankCode)
            ->where('cp.requestId', '=', $operationNumberAnnulment)
            ->where('cp.customerIdentificationCode', '=', $customerIdentificationCode)
            //->select(['cs.*'])
            ->select(['cs.id as clienteSaldoId', 'cs.ABCODCLI AS codCliente', 'cs.AKTIPIDE as TipoCliente', 'cs.AKRAZSOC as RazonSocial', 'cs.ABCODCIA as codCia', 'cs.ABCODSUC as codSuc', 'cs.ABTIPDOC as TipoDoc', 'cs.ABNRODOC as numeroPedido', 'cs.ABCODMON as moneda', 'cs.ABIMPCCC as montoTotal', 'cs.ABIMPSLD as saldo', 'cs.ABFRMPAG as formaPago', 'cs.CBNROSER as serie', 'cs.CBNROCOR as numeroDocumento', 'cp.*'])
            ->get()->toArray();
        return (is_array($rs) && sizeof($rs) > 0) ? $rs : false;
    }

    public function process_return_paid_document($id_cliente_saldo, $montoSaldoActualizar)
    {
        $fecha = date("Ymd");
        $hora = date("His");
        //die("<br>id: $id_cliente_saldo - Monto Ex.: $montoSaldoActualizar - F: $fecha H: $hora");
        return DB::table('cliente_saldos')
            ->where('id', '=', $id_cliente_saldo)
            ->update(['ABIMPSLD' => $montoSaldoActualizar, 'fecha_extorno' => $fecha, 'hora_extorno' => $hora]);
    }

    public function process_annulment_return_paid_document($id_cliente_saldo, $montoSaldoActualizar)
    {
        $hora = date("His");
        //die("<br>id: $id_cliente_saldo - Monto Ex.: $montoSaldoActualizar - HAE: $hora");
        return DB::table('cliente_saldos')
            ->where('id', '=', $id_cliente_saldo)
            ->update(['ABIMPSLD' => $montoSaldoActualizar, 'hora_anulacion_extorno' => $hora]);
    }

    public function getReturnRequest($arrayWhere)
    {
        return DB::table('bank_return_requests')
            ->where($arrayWhere)
            ->first();
    }

    public function updateCustomerPayment($arrayWhere, $arrayUpdate)
    {
        return DB::table('customer_payments')
            ->where($arrayWhere)
            ->update($arrayUpdate);
    }

    public function getPaymentDeposit($arrayWhere)
    {
        return DB::table('customer_payments')
            ->where($arrayWhere)
            ->first();
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

    public function as400_return_request(Request $request){
        try {
            $rules = [
                'serieNumber' => 'required',
                'docNumber' => 'required',
                'currencyCode' => 'required',
                'montoPagado' => 'required',
                'bankCode' => 'required',
                'fechaDeposito' => 'required',
                'operationNumber' => 'required'
            ];
            // var_dump($request->all());
            $messages = [
                'required' => 'El campo :attribute es obligatorio'
            ];

            $validator = Validator::make($request->all(), $rules, $messages);
            // var_dump($validator->fails());
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }
            // dd($request->all());
            $operationId = $request->operationNumber;
            $TABLA = 'as400_return_request';
            $codCia = $this->codCia;
            $bankCode = $request->bankCode;
            $currencyCode = $request->currencyCode;
            $bankAccount = $this->get_bank_accounts($codCia, $bankCode, $currencyCode, '01');
            $PROCESO = 1; //EXTORNO A CREDITO
            $serieNumber = $request->serieNumber;
            $docNumber = $request->docNumber;
            // $monto_pagado_documento = (float) $request->documentAmount;
            $monto_pagado_documento = (float) $request->montoPagado;
            $response = new \stdClass();
            $response->started = true;
            $response->bankAccount = $bankAccount;
            
            // var_dump($serieNumber);
            // var_dump($docNumber);
            $regClientSaldo = $this->retorna_doc_cliente_saldo_interface_fac($serieNumber, $docNumber);
            // var_dump($regClientSaldo);
            if (is_object($regClientSaldo)) {
                $response->clienteSaldo = "registro encontrado";
                $codigo_cliente = $regClientSaldo->ABCODCLI;
                $tipo_identificacion_cliente = $regClientSaldo->AKTIPIDE;
                $numero_identificacion_cliente = $regClientSaldo->NUMERO_IDENTIFICACION;
                $sucursal = $regClientSaldo->ABCODSUC;
                $tipo_documento = $regClientSaldo->ABTIPDOC;
                $numero_documento = $regClientSaldo->ABNRODOC;
                //$fecha_deposito = \Carbon\Carbon::parse($extorno->created_at)->format('Ymd');
                $fecha_deposito= $request->fechaDeposito;
                $sucursal_deposito = ($regClientSaldo->ABCODSUC === '02') ? '01' : $regClientSaldo->ABCODSUC;
                /* obtenemos el deposito realizado de la tabla MMYPREP*/
                $datosDeposito = $this->retorna_datos_deposito_bancario($codCia, $sucursal_deposito, $bankAccount[0]->erp_code, $operationId, $fecha_deposito);
                if (is_object($datosDeposito)) {
                    $response->deposito_bancario="Se encontró la MMYPREP";
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
                    /* obtenemos el saldo de la tabla MMEIREP*/
                    $regSaldo = $this->retorna_registro_saldo_documento($codCia, $sucursal, $codigo_cliente, $tipo_documento, $numero_documento, $monto_pagado_documento);
                    if (is_object($regSaldo)) {
                        $response->saldo_documento = "Se encontro el registro en la MMEIREP";
                        $saldo_as400 = round(floatval($regSaldo->eiimpsld), 2);
                        $importe_total_as400 = round(floatval($regSaldo->eiimpccc), 2);
                        $importe_documento = $importe_total_as400;
                        $monto_pagado_documento = round(floatval($monto_pagado_documento), 2);
                        $saldo = round(($saldo_as400 + $monto_pagado_documento), 2);
                        $saldo_actual = $saldo;
                    }else{
                        $response->saldo_documento = "No se encontró el registro en la MMEIREP";
                    }

                    if (!$this->verifica_paso_proceso(1, $PROCESO, $TABLA, $regClientSaldo->ABCODCLI)) {
                        //Desactivar Depósito Bancario (Tabla MMYPREP)
                        $this->desactivar_deposito_bancario($codCia, $sucursal_deposito, $banco_deposito, $operationId, $fecha_deposito, $TABLA, $regClientSaldo->ABCODCLI, $PROCESO);
                        $response->desactivar_deposito_bancario = true;
                    }

                    if (!$regPlanillaCobranzas = $this->retorna_planilla_cobranzas($codCia, $sucursal_deposito, $fecha_deposito, $this->codCobrador)) {
                        $response->retorna_planilla_cobranzas = "PLANILLA DE DEPÓSITO NO ENCONTRADA";
                        die('PLANILLA DE DEPÓSITO NO ENCONTRADA');
                    }else{
                        $response->retorna_planilla_cobranzas = "PLANILLA ENCONTRADA: ".$regPlanillaCobranzas->dlnropll;
                        $correlativo_planilla_cobranzas = $regPlanillaCobranzas->dlnropll;
                        if (!$this->verifica_paso_proceso(2, 1, $TABLA, $regClientSaldo->ABCODCLI)) {
                            //Actualizar monto em planilla de cobranzas (Tabla MMDMREP)
                            $this->actualiza_monto_planilla_cobranzas($codCia, $correlativo_planilla_cobranzas, $codigo_cliente, $tipo_documento, $numero_documento, $monto_pagado_documento, $TABLA, $request->operationNumber);
                            $response->actualiza_monto_planilla_cobranzas = true;
                        }
                        if (!$this->verifica_paso_proceso(3, 1, $TABLA, $regClientSaldo->ABCODCLI)) {
                            //Desactivar registro DEPÓSITO-CLIENTE-PLANILLA (Tabla MMDNREP)
                            $this->desactivar_registro_tabla_mmdnrep($codCia, $correlativo_planilla_cobranzas, $numero_boleta_deposito, $codigo_cliente, $TABLA, $regClientSaldo->ABCODCLI);
                            $response->desactivar_registro_tabla_mmdnrep = true;
                        }
                        if (!$this->verifica_paso_proceso(4, 1, $TABLA, $regClientSaldo->ABCODCLI)) {
                            //Desactivar registro LANILLA-DEPÓSITO-CLIENTE-DOCUMENTO (Tabla MMDOREP)
                            $this->desactivar_registro_tabla_mmdorep($codCia, $correlativo_planilla_cobranzas, $numero_boleta_deposito, $codigo_cliente, $tipo_documento, $numero_documento, $TABLA, $regClientSaldo->ABCODCLI);
                            $response->desactivar_registro_tabla_mmdorep = true;
                        }
                        if (!$this->verifica_paso_proceso(5, 1, $TABLA, $regClientSaldo->ABCODCLI)) {
                            //Actualizar saldo tabla auxiliar (Tabla CCAPLBCO)
                            $this->actualizar_saldo_tabla_auxiliar($codCia, $sucursal, $codigo_cliente, $tipo_documento, $numero_documento, $monto_pagado_documento, $TABLA, $regClientSaldo->ABCODCLI);
                            $response->actualizar_saldo_tabla_auxiliar = true;
                        }
                        if ($regSaldo) {
                            if ($saldo <= $importe_total_as400) {
                                if (!$this->verifica_paso_proceso(6, 1, $TABLA, $regClientSaldo->ABCODCLI)) {
                                    //Actualizar saldo en tabla principal (Tabla MMEIREP)
                                    $this->actualizar_saldo_tabla_principal($codCia, $sucursal, $codigo_cliente, $tipo_documento, $numero_documento, $monto_pagado_documento, $regSaldo, $TABLA, $regClientSaldo->ABCODCLI);
                                    $response->actualizar_saldo_tabla_principal = true;
                                }
                            } else {
                                $response->actualizar_saldo_tabla_principal ="Importe AS400: $importe_total_as400 - Saldo AS400: $saldo_as400 - Importe Pagado Documento: $monto_pagado_documento - Saldo: $saldo";
                                exit;
                            }
                        }
                        $historico_hijo = null;
                        if (!$this->verifica_paso_proceso(7, 1, $TABLA, $regClientSaldo->ABCODCLI)) {
                            $historico_hijo = 1;
                            //Desactivar Registro de depósito en históricos de pagos HIJO (Tabla MMEJREP)
                            $this->actualiza_tabla_historico_saldos_hijo($codCia, $sucursal_deposito, $codigo_cliente, '81', $numero_boleta_deposito, $importe_deposito, $TABLA, $regClientSaldo->ABCODCLI, $PROCESO);
                            $response->actualiza_tabla_historico_saldos_hijo = true;
                        }
                        if ($saldo <= $importe_total_as400 || $historico_hijo) {
                            if (!$this->verifica_paso_proceso(8, 1, $TABLA, $regClientSaldo->ABCODCLI)) {
                                //DESACTIVAR REGISTRO PADRE (Tabla MMEJREP)
                                $this->actualiza_tabla_historico_saldos_padre($codCia, $sucursal, $codigo_cliente, $tipo_documento, $numero_documento, $monto_pagado_documento, $regSaldo, $TABLA, $regClientSaldo->ABCODCLI);
                                $response->actualiza_tabla_historico_saldos_padre = true;
                            }
                        }
                        if (!$this->verifica_paso_proceso(9, 1, $TABLA, $regClientSaldo->ABCODCLI)) {
                            //Desactivar registro en tabla de aplicaciones (Tabla MMELREP)
                            $this->desactivar_registro_tabla_aplicaciones_mmelrep($codCia, $sucursal, $codigo_cliente, $numero_documento, $numero_boleta_deposito, $TABLA, $regClientSaldo->ABCODCLI);
                            $response->desactivar_registro_tabla_aplicaciones_mmelrep = true;
                        }
                        if (!$this->verifica_paso_proceso(10, $PROCESO, $TABLA, $regClientSaldo->ABCODCLI)) {
                            //Accion tabla mmcdreca
                            $this->elimina_registro_tabla_mmcdreca($codCia, $bankAccount[0]->erp_code, $fecha_deposito, $moneda, $cuenta, $deposito_bancario, $TABLA, $regClientSaldo->ABCODCLI, $PROCESO);
                            $response->elimina_registro_tabla_mmcdreca = true;
                            //EXTORNO FINALIZADO
                            // $arrayWhere = array(
                            //     ['id', '=', $ID_EXTORNO]
                            // );
                            $response->finalizado = true;
                        }
                    }//fin else
                }else{
                    $response->deposito_bancario="DEPOÓSITO NO ENCONTRADO: $codCia --- Suc.: $sucursal_deposito --- Bank: " . $bankAccount[0]->erp_code . " --- Dep.: $operationId ---- Fecha: $fecha_deposito";
                } // fin datosDeposito
            }else{
                $response->clienteSaldo = "No existe registro";
            }//fin regClientSaldo
            return response()->json($response, 200, array('Content-Type' => 'application/json; charset=utf-8'));
        }catch (\Exception $e) {
            var_dump($e);
            return response()->json($e, 500, array('Content-Type' => 'application/json; charset=utf-8')); 
        }
    }

    public function retorna_doc_cliente_saldo_interface_fac($serieNumber, $docNumber)
    {
        return DB::table('cliente_saldos')
            ->where('CBNROSER', '=', $serieNumber)
            ->where('CBNROCOR', '=', $docNumber)
            ->first();
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
        // var_dump($arrayWhereYP);
        if ($this->selecciona_from_tabla_db2('LIBPRDDAT.MMYPREP', $arrayWhereYP)) {
            $arrayUpdate = array(
                'YPSTS' => 'I',
                'YPUSRC' => $this->user,
                'YPJDTC' => date("Ymd"),
                'YPJTMC' => date("His"),
                'YPPGMC' => $this->app
            );
            // var_dump($arrayUpdate);
            echo '<br>APLICAMOS EL UPDATE AL DEPÓSITO: ' . $operationId;
            $this->actualiza_tabla_db2('LIBPRDDAT.MMYPREP', $arrayWhereYP, $arrayUpdate);
            $this->registra_paso_proceso($PASO, $PROCESO, $TABLA, $ID);
        } else {
            echo "<br>DEPOSITO " . $operationId . " NO ENCONTRADO<br>";
            exit;
        }
    }


    public function selecciona_from_tabla_db2($tabla_db2, $arrayWhere)
    {
        return DB::connection('ibmi')
            ->table($tabla_db2)
            ->where($arrayWhere)
            ->first();
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

    public function actualiza_tabla_db2($tabla_db2, $arrayWhere, $arrayUpdate)
    {
        return DB::connection('ibmi')
            ->table($tabla_db2)
            ->where($arrayWhere)
            ->update($arrayUpdate);
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

    public function selecciona_from_tabla($tabla, $arrayWhere)
    {
        return DB::table($tabla)
            ->where($arrayWhere)
            ->get()
            ->toArray();
    }
    
    public function retorna_registro_saldo_auxiliar($codCia, $sucursal, $codigo_cliente, $tipo_documento, $numero_documento)
    {   
        if ($tipo_documento === 'DA') {
            $arrayWhere = array(
                ['ABCODCIA', '=', trim($codCia)],
                ['ABCODCLI', '=', trim($codigo_cliente)],
                ['ABTIPDOC', '=', trim($tipo_documento)],
                ['ABNRODOC', '=', trim($numero_documento)]
            );
        } else {
            $arrayWhere = array(
                ['ABCODCIA', '=', trim($codCia)],
                ['ABCODSUC', '=', trim($sucursal)],
                ['ABCODCLI', '=', trim($codigo_cliente)],
                ['ABTIPDOC', '=', trim($tipo_documento)],
                ['ABNRODOC', '=', trim($numero_documento)]
            );
        }
        $arrayIn = array(
                    'tabla' => 'customer_debt_inquiries',
                    'mensaje' => 'retorna_registro_saldo_auxiliar:'.date("d-m-Y H:i:s"),
                    'otro' => json_encode($arrayWhere)
        );
        DB::table('log_migraciones')->insert($arrayIn);
        return $this->selecciona_from_tabla_db2('LIBPRDDAT.CCAPLBCO', $arrayWhere);
    }

    public function customer_debt_inquiries_validate(Request $request)
    {
        $idClienteSaldo = $request->id;
        try{
            $result = new stdClass();
            $result->status = false;
            $result->message = "Error en la validación";
            $result->id = intval($idClienteSaldo);
            $objClienteSaldo = DB::table('cliente_saldos')->where('id', '=', $idClienteSaldo)->first();
            //$result->obj = $objClienteSaldo;
            if (is_object($objClienteSaldo)) {
                $codCia = $objClienteSaldo->ABCODCIA;
                $sucursal = $objClienteSaldo->ABCODSUC;
                $codigo_cliente = $objClienteSaldo->ABCODCLI;
                $tipo_documento = $objClienteSaldo->ABTIPDOC;
                $numero_documento = $objClienteSaldo->ABNRODOC;
                $saldo_cliente = (float)$objClienteSaldo->ABIMPSLD;
                if ($tipo_documento !== $this->cax_documents) {
                    $regAuxSaldo = $this->retorna_registro_saldo_auxiliar($codCia, $sucursal, $codigo_cliente, $tipo_documento, $numero_documento);
                //$result->result = $regAuxSaldo;
                //$result->objGasto = $objGasto;
                    if (is_object($regAuxSaldo)) {
                        $saldo_as400 = (float)$regAuxSaldo->abimpsld;
                        if ($saldo_cliente !== $saldo_as400) {
                            $result->status = false;
                            $result->message = "No coincide el saldo de cliente_saldos con respecto a la CCAPLBCO";
                        }else{
                            $result->status = true;
                            $result->message = "validacion correcta";
                        }
                    }else{
                        $result->status = false;
                        $result->message = "No existe el registro en la CCAPLBCO";
                    }
                }else{
                    $result->status = true;
                    $result->message = "Este documento es un pago de caja";
                }
                //$regAuxSaldo = DB::connection('ibmi')->table('LIBPRDDAT.CCAPLBCO')->where($arrayWhere)->first();
            }else{
                $result->status = false;
                $result->message = "No existe el registro en la cliente_saldos";
            }
            return response()->json($result, 200, array('Content-Type' => 'application/json; charset=utf-8'));
        }catch(PDOException $Exception){
            return $Exception->getMessage();
        }
    }
}
