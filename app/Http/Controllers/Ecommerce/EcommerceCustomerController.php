<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;
use App\Models\Ubigeo;
use App\Models\GenResourceDetail;
use App\Models\CustomerAddress;
use App\Models\Vistas\VCustomers;
use App\Http\Controllers\Sync\Utilidades;
use App\Models\Customers\CustomerPaymentMethod;
use App\Models\Vistas\VCustomerPaymentMethods;
use Carbon\Carbon;
use Database\Seeders\UbigeoTypeSeeder;
use Illuminate\Support\Facades\Validator;
use stdClass;

class EcommerceCustomerController extends Controller
{
    protected $codCia = '10';
    protected $oriPed = 'VW';
    protected $codUser = '000293';
    protected $userId = 'ECOMMERCE';
    protected $job_as = 'WSAPI';


    public function get_identification_types()
    {
        $rs = DB::table('v_identification_types')
            ->select(['ident_type_code AS codElemento', 'ident_type_abrv AS descripAbreviatura', 'ident_type_desc AS descripLarga'])
            ->where('ident_type_code', '<>', '99')
            ->get();
        return response()->json($rs, 200);
    }

    public function get_employments()
    {
        $rs = DB::table('v_employments')
            ->select(['employment_code as codElemento', 'employment_name AS descripAbreviatura', 'employment_description AS descripLarga'])
            ->get();
        return response()->json($rs, 200);
    }


    public function put_customer_contact(Request $request)
    {
        echo '<pre>';
        print_r($request);
    }

    public function get_customer_addresses(Request $request)
    {
        $select = [
            'customer_address_id as id', 'customer_code as user_code', 'address_order as address_code',
            'tp_dir_code as address_type_code', 'tipo_direccion as road_type_code', 'number as address_number',
            'zone_code as zone_type_code', 'dpto_code as department_code', 'prov_code as province_code',
            'dist_code as district_code', 'road_name', 'district_name', 'province_name', 'department_name as department', 'zone_name', 'block as block_number',
            'floor as floor_number', 'allotment as lot_number', 'contact_phone as phone_number',
            'contact_email as email', 'apartment as apartment_number'
        ];
        $direcciones = DB::table('v_direcciones')
            ->select($select)
            ->selectRaw("concat(direccion_completa,' - ',region) as address, '$request->business_id' as business_id")
            ->where('customer_code', $request->codigo)->get()->toArray();

        return response()->json($direcciones, 200);
    }


    public function get_customer_address(Request $request)
    {
        $select = [
            'customer_address_id as id', 'customer_code as user_code', 'address_order as address_code',
            'tp_dir_code as address_type_code', 'tipo_direccion as road_type_code', 'number as address_number',
            'zone_code as zone_type_code', 'dpto_code as department_code', 'prov_code as province_code',
            'dist_code as district_code', 'road_name', 'district_name', 'province_name', 'department_name as department', 'zone_name', 'block as block_number',
            'floor as floor_number', 'allotment as lot_number', 'contact_phone as phone_number',
            'contact_email as email', 'apartment as apartment_number'
        ];
        $direccion = DB::table('v_direcciones')
            ->select($select)
            ->selectRaw("concat(direccion_completa,' - ',region) as address, '$request->business_id' as business_id")
            ->where('customer_code', $request->codigo)
            ->where('address_order', $request->address_code)
            ->first();

        return response()->json($direccion, 200);
    }


    public function ecommerce_add_customer_address(Request $request)
    {
        $util = new Utilidades();

        $code = strtoupper(trim($request->idCliente));

        if ($datos_cliente = Customer::where('code', $code)->first()) {
            $whereInField = 'resource_id';
            $whereInArray = array(3, 4, 5);
            $arraySelect = ['id', 'code', 'name', 'resource_id'];
            $array_tipos = $util->selecciona_from_tabla_where_in('gen_resource_details', $whereInField, $whereInArray, $arraySelect);

            $arrayWhere = array(
                ['dpto_code', '=', $request->departamento],
                ['prov_code', '=', $request->provincia],
                ['dist_code', '=', $request->distrito],
            );


            if ($region = $util->selecciona_fila_from_tabla('dist_prov_dpto_peru', $arrayWhere))
                $distrito_id = $region->dist_id;
            else $distrito_id = 1807; //1807 -> Distrito Lima
            if (!$tipo_direccion_id = $util->busca_datos_vector($request->tipoDireccionCliente, 3, $array_tipos)) $tipo_direccion_id = 11; //TIPO DIR LEGAL
            if (!$tipo_via_id = $util->busca_datos_vector($request->viaDireccion, 4, $array_tipos)) $tipo_via_id = 40;
            $tipo_zona_id = 56; //VACIO

            $address_order = DB::table('customer_addresses')->where('reg_status', 1)->where('customer_id', $datos_cliente->id)->max('address_order') + 1;
            $arrayWhere = array(
                ['customer_id', '=', $datos_cliente->id],
                ['address_order', '=', $address_order],
            );

            $arrayInsert = array(
                'customer_id' => $datos_cliente->id,
                'address_order' => (int)$address_order, //$request->nroDireccion,
                'country_id' => ($datos_cliente->country_id) ? $datos_cliente->country_id : 163,
                'address_type_id' => $tipo_direccion_id,
                'road_type_id' => $tipo_via_id,
                'road_name' => strtoupper(trim(($request->descripDireccion))),
                'number' => $request->nroDireccion,
                'apartment' => $request->nroDepartamento,
                'floor' => $request->nroPiso,
                'block' => $request->nroManzana,
                'allotment' => $request->nroLote,
                'zone_type_id' => $tipo_zona_id,
                'zone_name' => strtoupper(trim(($request->descripZonaDirecc))),
                'region_id' => $distrito_id,
                'contact_name' => "",
                'contact_phone' => $request->numTelefono1,
                'contact_email' => ($request->email) ? $request->email : "",
                'reg_status' => 1,
                'created_at' => date("Y-m-d H:i:s"),
            );

            CustomerAddress::updateOrCreate(
                $arrayWhere,
                $arrayInsert
            );

            $new_add = $this->ecommerce_add_customer_address_as400($datos_cliente->id, $address_order);
            if ($new_add) {
                $response = [
                    'codigo' => '1700',
                    'mensajeapi' => [
                        'titulo'  => 'ClienteDirecion',
                        'codigo'  => 1700,
                        'mensaje' => 'Dirección creada para el cliente',
                    ],
                    'respuesta' => 'Dirección creada para el cliente'
                ];
            } else {
                $response = [
                    'codigo' => '1701',
                    'mensajeapi' => [
                        'titulo'  => 'ClienteDirecion',
                        'codigo'  => 1701,
                        'mensaje' => 'CLIENTE-DIRECCION NO FUE CREADO',
                    ],
                    'respuesta' => 'CLIENTE-DIRECCION NO FUE CREADO'
                ];
            }
        } else {
            $response = [
                'codigo' => '1701',
                'mensajeapi' => [
                    'titulo'  => 'ClienteDirecion',
                    'codigo'  => 1701,
                    'mensaje' => 'CLIENTE NO EXISTE',
                ],
                'respuesta' => 'CLIENTE NO EXISTE'
            ];
        }
        return response()->json($response, 200);
    }

    public function ecommerce_add_customer_address_as400($customer_id, $address_number)
    {
        $util = new Utilidades();
        $arrayWhere = array(
            ['customer_id', '=', $customer_id],
            ['address_order', '=', $address_number],
        );
        $direccion_cliente = $util->selecciona_fila_from_tabla('v_direcciones', $arrayWhere);

        $arrayWhere = array(
            ['ALCODCLI', '=', $direccion_cliente->customer_code],
            ['ALITEM01', '=', $address_number],
            ['ALSTS', '=', "A"],
        );
        if (!$util->selecciona_fila_from_tabla_db2('LIBPRDDAT.MMALREP', $arrayWhere)) {
            $direccion_completa = (strlen(($direccion_cliente->direccion_completa)) > 30) ? substr(($direccion_cliente->direccion_completa), 0, 30) : ($direccion_cliente->direccion_completa);
            $zone_name = (strlen(($direccion_cliente->zone_name)) > 20) ? substr(($direccion_cliente->zone_name), 0, 20) : ($direccion_cliente->zone_name);

            $arrayInsertAs = array(
                'ALCODCLI' => $direccion_cliente->customer_code,
                'ALITEM01' => $direccion_cliente->address_order,
                'ALTIPDIR' => $direccion_cliente->tp_dir_code,
                'ALVIADIR' => $direccion_cliente->tp_via_code,
                'ALDSCDIR' => ($direccion_completa),
                'ALNRODIR' => ($direccion_cliente->number) ? $direccion_cliente->number : 1,
                'ALNRODPT' => ($direccion_cliente->apartment) ? $direccion_cliente->apartment : "",
                'ALNROPSO' => ($direccion_cliente->floor ? $direccion_cliente->floor : ""),
                'ALNROMZA' => ($direccion_cliente->block) ? $direccion_cliente->block : "",
                'ALNROLTE' => ($direccion_cliente->allotment) ? $direccion_cliente->allotment : "",
                'ALZONDIR' => $direccion_cliente->zone_code,
                'ALDSCZDR' => ($zone_name) ? utf8_encode($zone_name) : "",
                'ALDEPART' => $direccion_cliente->dpto_code,
                'ALPROVIN' => $direccion_cliente->prov_code,
                'ALDISTRI' => $direccion_cliente->dist_code,
                'ALPLNGEO' => "99",
                'ALFILUBI' => "X",
                'ALCOLUBI' => "99",
                'ALCODPAI' => "001",
                'ALCODCIU' => "001",
                'ALNROTL1' => ($direccion_cliente->contact_phone) ? $direccion_cliente->contact_phone : "",
                'ALEMAIL' => ($direccion_cliente->contact_email) ? $direccion_cliente->contact_email : "",
                'ALURL' => "",
                'ALSTS' => "A",
                'ALUSR' => Utilidades::ECOMMERCEUSER,
                'ALJOB' => Utilidades::ECOMMERCEJOB,
                'ALJDT' => date("Ymd"),
                'ALJTM' => date("His")
            );
            return ($util->inserta_into_tabla_as400('LIBPRDDAT.MMALREP', $arrayInsertAs)) ? true : false;
        } else return 1;
    }

    public function get_customer_payment_method(Request $request)
    {
        $rs = DB::table('v_customer_payment_methods')
            ->select(['payment_method_id as id', 'way_to_pay_code as code', 'way_to_pay_name as name', 'way_to_pay_name as enName'])
            ->where('customer_code', '=', $request->customerCode)
            ->get();

        $respuesta = [
            "paymentMethods" => $rs,
            "bankAccounts" => $this->get_bank_accounts('10', 1),
            "currency" => $this->get_currencies()
        ];

        return response()->json($respuesta, 200);
    }

    public function get_bank_accounts($codCia = '10', $extraInfo = false)
    {
        if (!$extraInfo) {
            $sql = 'cod_cia=\'' . $codCia . '\' and is_collecting_account = false';
        } else {
            $sql = 'cod_cia=\'' . $codCia . '\' ';
        }
        $bancos = DB::table('banks')
            ->distinct()
            ->select(['banks.id as bank_id', 'banks.description as bank', 'banks.erp_code as bank_code'])
            ->join('bank_accounts', 'banks.id', '=', 'bank_accounts.bank_id')
            ->whereRaw($sql)
            ->get()
            ->toArray();

        if ($bancos && is_array($bancos)) {

            foreach ($bancos as $banco) {
                $banco->accounts = $this->retorna_cuentas_bancarias_moneda($banco->bank_id, $extraInfo);
            }
            return $bancos;
        } else return 'no hay cuentas';
    }

    public function retorna_cuentas_bancarias_moneda($banco_id, $extraInfo = false)
    {
        if (!$extraInfo) {
            $sql = 'bank_id=\'' . $banco_id . '\' and is_collecting_account = false';
            $select = 'currency_code as "codMoneda", account_code as "nroCuenta"';
        } else {
            $sql = 'bank_id=\'' . $banco_id . '\' ';
            $select = 'currency_code as "codMoneda", case 
            when (is_collecting_account = true) then extra_info->>\'nombre_convenio\'
            when (is_collecting_account = false) then account_code
            end as "nroCuenta"';
        }
        return DB::table('bank_accounts')
            ->selectRaw($select)
            ->whereRaw($sql)
            ->get();
    }

    public function get_currencies()
    {
        $util = new Utilidades();
        $monedas = DB::table('gen_resource_details')
            ->select(['code as code', 'name as nroCuenta', 'abrv as symbol'])
            ->where('resource_id', '=', 12)
            ->whereIn('code', array('01', '02'))
            ->get()
            ->toArray();

        if ($monedas && is_array($monedas)) {
            $tipo_cambio = $util->retorna_tipo_cambio_dolar_mym('02');
            $respuesta = [
                "monedas" => $monedas,
                "tipo_cambio" => $tipo_cambio->mym_selling_price
            ];
            return $respuesta;
        }
        return false;
    }

    public function get_actual_exchange_rate()
    {
        $util = new Utilidades();
        $tipo_cambio = $util->retorna_tipo_cambio_dolar_mym('02');
        return response()->json($tipo_cambio->mym_selling_price, 200);
    }

    public function get_customer_account_status(Request $request)
    {
        $datos_cliente = VCustomers::where('customer_code', $request->customerCode)->first();
        $datos_cliente->total_intereses = 0.0;
        $datos_cliente->deudas  = $this->retorna_saldos_cliente_as400($request->customerCode);
        $arrayDocsSinInteres = array('07', '08', '15');
        $fac_cal_int = $this->retorna_factores_calculo_intereses();
        $datos_cliente->factor_calculo = round(((float)$fac_cal_int->mbtsaidr / (float)$fac_cal_int->mbprdint / 100), 6);
        if ($datos_cliente->deudas && is_array($datos_cliente->deudas)) {
            $datos_cliente->total_deuda = 0;

            foreach ($datos_cliente->deudas as $deuda) {
                $deuda->calculo = ($deuda->eitipdoc === '07') ? round(((float)($deuda->eiimpsld) * -1), 2) : round(((float)($deuda->eiimpsld)), 2);
                $datos_cliente->total_deuda += $deuda->calculo;
                if (in_array($deuda->eitipdoc, $arrayDocsSinInteres)) {
                    $deuda->dias_vencidos = 0;
                    $deuda->importe_mora = 0;
                } else {
                    $fecha_vencimiento = Carbon::createFromFormat('Ymd', $deuda->eifecvct, 'America/Lima');
                    $fecha_actual = Carbon::now();
                    $deuda->dias_vencidos = $fecha_actual->diffInDays($fecha_vencimiento, 'days');
                    $deuda->importe_mora = round((($deuda->calculo) * $datos_cliente->factor_calculo * $deuda->dias_vencidos), 2);
                    $datos_cliente->total_intereses += $deuda->importe_mora;
                }
            }
        }

        $datos_cliente->disponible = 0;
        $datos_cliente->total_cargo = 0;
        $datos_cliente->total_intereses = round($datos_cliente->total_intereses, 2);
        return response()->json($datos_cliente, 200);
    }

    public function retorna_factores_calculo_intereses()
    {

        $sql = "select MBTSAIDR, MBPRDINT FROM MMMBREL0 WHERE MBTIPTSA='01' and MBSTSTSA='D'";
        $rs = DB::connection('ibmi')->select(DB::raw($sql));
        return (sizeof($rs) > 0) ? $rs[0] : false;
    }

    public function retorna_saldos_cliente_as400($codcli)
    {
        $sql = "select a.EICODSUC,a.EITIPDOC,a.EINRODOC,a.EICODMON,b.EUDSCABR,a.EIFECEMI,a.EIFECVCT,a.EIIMPCCC,a.EIIMPSLD,a.EISTSCOA,a.EISTSRCL 
        FROM MMEIREP a 
        inner join MMEUREL0 b on b.EUCODELE=a.EITIPDOC 
        WHERE a.EICODCLI=:cliente AND a.EISTS ='A' AND a.EIIMPSLD >0 and b.EUCODTBL='03' --and a.EITIPDOC IN ('01','03','07','08','25') -- and EISTSRCL='' 
        order by a.EIFECVCT ASC";
        $rs = DB::connection('ibmi')->select(DB::raw($sql), array('cliente' => $codcli));
        return (is_array($rs) && sizeof($rs) > 0) ? $rs : false;
    }

    public function customerAdd(Request $request)
    {

        $rules = [
            'nroPiso' => 'required|numeric|digits_between:1,2',
            'numberAddress' => 'digits_between:1,2',
            'tipoDocIdentida' => 'required',
            'primerNombre' => 'required|max:15',
            'primerApellido' => 'required|max:15',
            'nroDocIdentida' => 'required|max:11',
            'nombreComercial' => 'required',
            'email' => 'required|email'
        ];
        $messages = [
            'required' => 'El campo :attribute es obligatorio',
            'numeric' => 'Debe colocar un valor numérico en el campo :attribute',
            'digits_between' => 'El valor colocado debe ser de 2 dígitos máximo en el campo :attribute',
            'max' => 'El valor colocado debe ser de :max dígitos máximo en el campo :attribute',
            'email' => 'Debe indicar un correo válido para el campo :attribute '
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            $response = [
                'codigo' => '1702',
                'mensajeapi' => [
                    'titulo'  => 'Cliente',
                    'codigo'  => 1702,
                    'mensaje' => $validator->errors()->first(),
                ],
                'respuesta' => $validator->errors()->first(),
            ];
            return response()->json($response, 200);
        }


        //buscar si el cliente existe
        $select = ['customer_id as id', 'customer_code as code', 'company_type_code AS tipoEmpresa', 'company_type_name', 'document_type_code', 'document_type_name', 'document_number', 'name_social_reason', 'economic_group_id', 'client_class AS claseClienteId', 'max_credit_limit', 'reg_status', 'country_code', 'country_name'];
        $cliente = VCustomers::select($select)->where('document_number', $request->nroDocIdentida)->first();


        //si el cliente ya existe no es necesario crearlo en la bd intermedia
        if ($cliente) {
            //BUSCAR CLIENTE METODOS DE PAGO - CLIENTE
            $select = ['document_type AS tipoDocumento', 'way_to_pay_code AS formaPago', 'payment_modality_code AS modalidadPago', 'payment_condition_code AS condicionPago'];
            $cliente->clientemodalidadpago = VCustomerPaymentMethods::select($select)->where('customer_id', $cliente->id)->get();

            //buscar si existe la direccion
            $direccion_item = DB::table('v_direcciones')->where('customer_id', $cliente->id)->max('address_order') + 1;
            $response = [
                'codigo' => '1700',
                'mensajeapi' => [
                    'titulo'  => 'Cliente',
                    'codigo'  => $cliente->code,
                    'cliente' => $cliente,
                    'item_direccion' => $direccion_item,
                    'mensaje' => '* ya Existe el cliente en el AS400 *',
                ],
                'respuesta' => 'ya Existe el cliente en el AS400',
            ];
            return response()->json($response, 200);
        }

        //crearlo en el as 400
        $cliente_code_as = $this->post_customer_as400($request);

        $company_type_id = (strlen($request->nroDocIdentida) == 11) ? 2 : 1;
        $document_type = GenResourceDetail::where('resource_id', 2)->where('code', $request->tipoDocIdentida)->first();
        $buscar_region = Ubigeo::where('ubigeo_type_id', 4)->where('code', $request->distrito)->first();

        if (!$cliente = Customer::where('document_number', $request->nroDocIdentida)->first()) {
            $cliente = Customer::create([
                'code' => $cliente_code_as,
                'company_type_id' => $company_type_id,
                'document_type_id' => $document_type->id,
                'document_number'  => $request->nroDocIdentida,
                'name_social_reason' => $request->razonSocial,
                'tradename' => $request->nombreComercial,
                'country_id' => 163,
                'region_id' =>  $buscar_region ? $buscar_region->id : 1807,
                'currency_code' => '02',
                'client_class' => 04,
                'reg_date' => date('Ymd'),
                'reg_status' => 1,
                'max_credit_limit' => 0,
                'economic_group_id' => null,
                'business_turn' => null,
                'tax_condition' => '',
                'created_at' => date("Y-m-d H:i:s")
            ]);

            $buscar_road = GenResourceDetail::where('resource_id', 4)->where('code', $request->viaDireccion)->first();

            //creamos la direccion
            $cliente_direccion = CustomerAddress::create([
                'customer_id' => $cliente->id,
                'address_order' => 1,
                'address_type_id' => 11,
                'road_type_id' => $buscar_road ? $buscar_road->id : 40,
                'road_name' => $request->descripDireccion,
                'number' => $request->nroDireccion,
                'apartment' => '',
                'floor' => $request->nroPiso,
                'block' => '',
                'allotment' => '',
                'zone_type_id' => 40,
                'zone_name' => '',
                'country_id' => 163,
                'region_id' => $buscar_region ? $buscar_region->id : 1807,
                'contact_name' => "$request->primerApellido $request->segundoApellido $request->primerNombre $request->segundoNombre",
                'contact_phone' => $request->numTelefono1,
                'contact_email' => $request->email,
                'reg_status' => 1
            ]);
            $cliente->direccion = $cliente_direccion;

            $cliente->claseClienteId = $cliente->client_class;
            $cliente->tipoEmpresa = ($company_type_id == 2) ? '02' : '01';

            //REGISTRAR CLIENTE FORMA-MODALIDAD DE PAGO EN BD INTERMEDIA
            CustomerPaymentMethod::create([
                'customer_id' => $cliente->id,
                'payment_method_id' => 1456,
                'payment_modality_id' => 1458,
                'payment_condition_id' => 1468,
                'days_to_pay' => 0,
                'reg_status' => 1,
                'created_at' => date("Y-m-d H:i:s")
            ]);
            //BUSCAR CLIENTE METODOS DE PAGO - CLIENTE
            $select = ['document_type AS tipoDocumento', 'way_to_pay_code AS formaPago', 'payment_modality_code AS modalidadPago', 'payment_condition_code AS condicionPago'];
            $cliente->clientemodalidadpago = VCustomerPaymentMethods::select($select)->where('customer_id', $cliente->id)->get();
            //FIN - REGISTRAR CLIENTE FORMA-MODALIDAD DE PAGO BD INTERMEDIA

            $response = [
                'codigo' => '1700',
                'mensajeapi' => [
                    'titulo'  => 'Cliente',
                    'codigo'  => $cliente->code,
                    'cliente' => $cliente,
                    'item_direccion' => 1,
                    'mensaje' => '*Se creo el cliente en el AS400 *',
                ],
                'respuesta' => 'Se creo el cliente en el AS400',
            ];
        } else {
            $response = [
                'codigo' => '1701',
                'mensajeapi' => [
                    'titulo'  => 'Cliente ya existe',
                    'codigo'  => $cliente->code,
                    'cliente' => $cliente,
                    'item_direccion' => 1,
                    'mensaje' => '* Cliente ya existe *',
                ],
                'respuesta' => 'Cliente ya existe en el AS400',
            ];
        }

        return response()->json($response, 200);
    }


    public function armar_vector_cliente($request, $nuevo_cliente)
    {
        $razon_social = substr(mb_strtoupper(utf8_decode($request->razonSocial)), 0, 40);
        $nombre_comercial = substr(mb_strtoupper(utf8_decode($request->nombreComercial)), 0, 20);
        $nro_identificacion = substr($request->nroDocIdentida, 0, 10);
        $ruc_corto = substr($request->nroDocIdentida, 0, 8);

        return [
            'AKCODCLI' => $nuevo_cliente,
            'AKRAZSOC' => $razon_social,
            'AKNOMCOM' => $nombre_comercial,
            'AKTIPIDE' => $request->tipoDocIdentida,
            'AKNROIDE' => $nro_identificacion,
            'AKNRORUC' => $ruc_corto,
            'AKCODPAI' => '001',
            'AKCLSCLT' => '20',
            'AKTIPEMP' => $request->tipoEmpresa,
            'AKFECINS' => date("Ymd"),
            'AKCODMON' => '02',
            'AKIMPLMT' => 0,
            'AKIMPCSM' => 0,
            'AKBLQVTA' => '',
            'AKBLQCRD' => '',
            'AKSTS' => 'A',
            'AKUSR' => Utilidades::ECOMMERCEUSER,
            'AKJOB' => Utilidades::ECOMMERCEJOB,
            'AKJDT' => date("Ymd"),
            'AKJTM' => date("His")
        ];
    }

    public function armar_vector_cliente_direccion($request, $codigo_cliente)
    {
        $direccion_completa = substr(mb_strtoupper(utf8_decode($request->descripDireccion)), 0, 30);
        $zone_name = substr(mb_strtoupper(utf8_decode($request->zonaDireccion)), 0, 20);
        $direccion_item = DB::connection('ibmi')->table('LIBPRDDAT.MMALREP')->where('ALCODCLI', $codigo_cliente)->max('ALITEM01') + 1;
        $nro_dpto = ($request->nroDepartamento) ? substr(trim($request->nroDepartamento), 0, 5) : 1;
        $nro_dir = ($request->nroDireccion && intval($request->nroDireccion) < 9999)  ? $request->nroDireccion : 1;
        $nro_piso = ($request->nroPiso && ($request->nroPiso < 99)) ? $request->nroPiso : 1;
        $nro_manzana = ($request->nroManzana) ? substr(mb_strtoupper(utf8_decode($request->nroManzana)), 0, 4) : '';
        $nro_lote = ($request->nroLote) ? substr(mb_strtoupper(utf8_decode($request->nroLote)), 0, 4) : '';
        $nro_tlf1 = ($request->numTelefono1) ? substr(mb_strtoupper(utf8_decode($request->numTelefono1)), 0, 15) : '';
        $email = ($request->email) ? substr(mb_strtoupper(utf8_decode($request->email)), 0, 30) : '';

        return [
            'ALCODCLI' => $codigo_cliente,
            'ALITEM01' => $direccion_item,
            'ALTIPDIR' => (strlen($request->tipoDireccionCliente) > 0) ? $request->tipoDireccionCliente : '01',
            'ALVIADIR' => $request->viaDireccion,
            'ALDSCDIR' => $direccion_completa,
            'ALNRODIR' => $nro_dir,
            'ALNRODPT' => $nro_dpto,
            'ALNROPSO' => $nro_piso,
            'ALNROMZA' => $nro_manzana,
            'ALNROLTE' => $nro_lote,
            'ALZONDIR' => '99',
            'ALDSCZDR' => ($zone_name) ? $zone_name : "",
            'ALDEPART' => $request->departamento,
            'ALPROVIN' => $request->provincia,
            'ALDISTRI' => $request->distrito,
            'ALPLNGEO' => "99",
            'ALFILUBI' => "X",
            'ALCOLUBI' => "99",
            'ALCODPAI' => "001",
            'ALCODCIU' => "001",
            'ALNROTL1' => $nro_tlf1,
            'ALEMAIL' => $email,
            'ALURL' => "",
            'ALSTS' => "A",
            'ALUSR' => Utilidades::ECOMMERCEUSER,
            'ALJOB' => Utilidades::ECOMMERCEJOB,
            'ALJDT' => date("Ymd"),
            'ALJTM' => date("His")
        ];
    }

    public function post_customer_as400($request)
    {
        $util = new Utilidades;
        $arrayWhereNroCli = array(
            ['fccodele', '=', '01'],
            ['fcdscabr', '=', 'CLI'],
            ['FCSTS', '=', 'A'],
        );

        $nuevo_cliente = ($util->retorna_nuevo_numero_tabla_numeradores_mmfcrep(0, 0, 0, $arrayWhereNroCli));
        $ruc = substr($request->nroDocIdentida, 0, 11);

        $array_cliente = $this->armar_vector_cliente($request, $nuevo_cliente);

        if ($util->inserta_into_tabla_as400('LIBPRDDAT.MMAKREP', $array_cliente)) {

            //ACTUALIZA NUMERADOR DE CLIENTES
            $arrayUpdate = array(
                'FCCANACT' => $nuevo_cliente
            );
            DB::connection('ibmi')
                ->table('LIBPRDDAT.MMFCREP')
                ->where($arrayWhereNroCli)
                ->update($arrayUpdate);
            //FIN -ACTUALIZA NUMERADOR DE CLIENTES

            //REGISTRAR EN TABLA CLIENTE-RUC
            $arrayRuc = array(
                'IFCODCLI' => $nuevo_cliente,
                'IFNRORUC' => $array_cliente['AKNRORUC'],
                'IFNVORUC' => $ruc,
                'IFSTS' => 'A',
                'IFJDT' => date("Ymd"),
                'IFJTM' => DATE("His")
            );
            $util->inserta_into_tabla_as400('LIBPRDDAT.MMIFREP', $arrayRuc);
            //FIN - REGISTRAR EN TABLA CLIENTE-RUC

            //REGISTRAR EN TABLA CLIENTE-DIRECCION
            $array_cliente_direccion = $this->armar_vector_cliente_direccion($request, $nuevo_cliente);
            $util->inserta_into_tabla_as400('LIBPRDDAT.MMALREP', $array_cliente_direccion);
            //FIR - REGISTRAR EN TABLA CLIENTE-DIRECCION

            //REGISTRAR CLIENTE FORMA-MODALIDAD DE PAGO EN AS400
            $arrayFM = array(
                'ARCODCIA' => '10',
                'ARCODCLI' => $nuevo_cliente,
                'ARFRMPAG' => 'C',
                'ARMODPAG' => 'CO',
                'ARSTS' => 'A',
                'ARUSR' => Utilidades::ECOMMERCEUSER,
                'ARJOB' => Utilidades::ECOMMERCEJOB,
                'ARJDT' => date("Ymd"),
                'ARJTM' => date("His")
            );
            $util->inserta_into_tabla_as400('LIBPRDDAT.MMARREP', $arrayFM);
            //FIN - REGISTRAR CLIENTE FORMA-MODALIDAD DE PAGO EN AS400

            return $nuevo_cliente;
        } else {
            return false;
        }
    }

    public function get_transport_agency_by_ruc_as400($ruc)
    {
        $select = [
            'prov.AHCODPRV as idproveedor',
            'ruc.IPNVORUC as nroidentificacion',
            'prov.AHRAZSOC as razonsocial'
        ];
        return DB::connection('ibmi')
            ->table('LIBPRDDAT.MMAHREP AS prov')
            ->join('LIBPRDDAT.MMIPREP AS ruc', 'prov.AHCODPRV', '=', 'ruc.IPCODCLI')
            ->where('prov.AHSTS', 'A')
            ->where('prov.AHTIPPRV', '=', 'TR')
            ->where('ruc.IPNVORUC', '=', $ruc)
            ->select($select)
            ->first();
    }

    public function transport_agencies_add(Request $request)
    {
        $util = new Utilidades;

        if (!$this->get_transport_agency_by_ruc_as400($request->nroDocIdentida)) {
            //GENERAR NUEVO NUMERO DE PROVEEDOR
            $arrayWhere = array(
                ['fccodele', '=', '02'],
                ['fcdscabr', '=', 'PRV'],
                ['FCSTS', '=', 'A'],
            );
            $nuevo_codigo = sprintf("%'.06d", ($util->retorna_nuevo_numero_tabla_numeradores_mmfcrep(0, 0, 0, $arrayWhere)));
            //FIN - GENERAR NUEVO NUMERO DE PROVEEDOR

            //AGREGAR AGENCIA DE ENVIOS EN TABLA DE PROVEEDORES (MMAHREP)
            $razon_social = substr(mb_strtoupper(utf8_decode($request->razonSocial)), 0, 40);
            $nombre_comercial = substr(mb_strtoupper(utf8_decode($request->nombreComercial)), 0, 20);
            $arrayInsert = [
                'AHCODPRV' => $nuevo_codigo,
                'AHRAZSOC' => $razon_social,
                'AHNOMCOM' => $nombre_comercial,
                'AHPRVMTZ' => '',
                'AHPRVREP' => '',
                'AHGRONEG' => '',
                'AHGRPECO' => '',
                'AHTIPPRV' => 'TR',
                'AHTIPEMP' => '02',
                'AHCLSPRV' => '01',
                'AHCATPRV' => 'A1',
                'AHCODPAI' => '001',
                'AHNRORUC' => '',
                'AHCNDTRB' => '01',
                'AHTIPIDE' => '',
                'AHNROIDE' => '',
                'AHIMPMCP' => 0,
                'AHPRDCAL' => 0,
                'AHSTSCAL' => '',
                'AHCODCII' => '',
                'AHSTS' => 'A',
                'AHUSR' => $this->userId,
                'AHJOB' => $this->job_as,
                'AHJDT' => date("Ymd"),
                'AHJTM' => date("His"),
                'AHCODMON' => '02'
            ];
            $util->inserta_into_tabla_as400('LIBPRDDAT.MMAHREP', $arrayInsert);
            //FIN - AGREGAR AGENCIA DE ENVIOS EN TABLA DE PROVEEDORES (MMAHREP)

            //ACTUALIZA NUMERADOR DE PROVEEDORES
            $arrayUpdate = array(
                'FCCANACT' => $nuevo_codigo
            );
            DB::connection('ibmi')
                ->table('LIBPRDDAT.MMFCREP')
                ->where($arrayWhere)
                ->update($arrayUpdate);
            //FIN -ACTUALIZA NUMERADOR DE PROVEEDORES

            //AGREGAR AGENCIA DE ENVIOS EN TABLA DE RUCS DE PROVEEDORES (MMIPREP)
            $arrayInsert = [
                'IPCODCLI' => $nuevo_codigo,
                'IPNRORUC' => substr($request->nroDocIdentida, 0, 8),
                'IPNVORUC' => $request->nroDocIdentida,
                'IPSTS' => 'A',
                'IPUSR' => $this->userId,
                'IPJOB' => $this->job_as,
                'IPJDT' => date("Ymd"),
                'IPJTM' => date("His")
            ];
            $util->inserta_into_tabla_as400('LIBPRDDAT.MMIPREP', $arrayInsert);
            //FIN - AGREGAR AGENCIA DE ENVIOS EN TABLA DE RUCS DE PROVEEDORES (MMIPREP)

            $response = new \stdClass();
            $response->Transporteid = $nuevo_codigo;
            return response()->json($response, 200);
        }
    }
}
