<?php

namespace App\Http\Controllers\MMTrack;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MMTrack\Dispatch;
use App\Models\MMTrack\DispatchDetailMts;
use App\Models\MMTrack\DispatchTracking;
use App\Models\MMTrack\Qualification;
use App\Models\MMTrack\Transit;
use App\Models\Customer;
use App\Models\MMTrack\DispatchDetailsTracking;
use App\Models\GenResourceDetail;
use App\Models\CustomerContact;
use DB;
use Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Artisan;
use App\Mail\AlertasPedidos;
use Config;

use App\Http\Controllers\Sync\SyncCustomer;
use PhpParser\Node\Stmt\Else_;

class ServicesController extends Controller
{
    public function Dispatchs(Request $request)
    {

        //validando el datos necesarios
        $validacion = Validator::make($request->all(), [
            'cliente_id' => 'required',
        ]);

        //verificar si hay errores en la validacion
        if ($validacion->fails()) {
            return response()->json($validacion->errors(), 422);
        }

        //buscar cliente en la base de datos intermedia  
        $cliente = Customer::where('reg_status', 1)
            ->where(function ($query) use ($request) {
                $query->where('document_number', $request->cliente_id)
                    ->orWhere('code', $request->cliente_id);
            })
            ->first();

        $direccion_envio = "";

        if ($cliente) {

            $lista_negra =  DB::table('black_list_customers')->where('customer_id', $cliente->id)->count();

            if ($lista_negra > 0) {
                $response = [
                    "mensaje" => "Consulta Exitosa",
                    "cliente" => null,
                ];
                return response()->json($response, 200);
            }

            //validar si el cliente pasa el filtro de sus pedidos
            if ($request->estatus == '') {
                $cliente->pedidos = null;

                //contador de pedidos recibidos
                $pedidos_recibidos = Dispatch::whereHas('tracking', function ($query) {
                    $query->whereIn('transit_status_id', ["2"]);
                })->where('registration_status', "A")->whereDate('created_at', '>', '2022-09-11')->where('customer_code', $cliente->code)->count();

                //contador de pedidos en ruta
                $pedidos_en_ruta = Dispatch::whereHas('tracking', function ($query) {
                    $query->whereIn('transit_status_id', ["31","18"]);
                })->where('registration_status', "A")->whereDate('created_at', '>', '2022-09-11')->where('customer_code', $cliente->code)->count();

                //contador de pedidos en preparacion
                $pedidos_en_preparacion = Dispatch::whereHas('tracking', function ($query) {
                    $query->whereIn('transit_status_id', [3, 4, 5, 6, 7, 8]);
                })->where('registration_status', "A")->whereDate('created_at', '>', '2022-09-11')->where('customer_code', $cliente->code)->count();

                //contador de pedidos listo para delivery
                $pedidos_listo_delivery = Dispatch::whereHas('tracking', function ($query) {
                    $query->whereIn('transit_status_id', [9]);
                })->where('registration_status', "A")->whereDate('created_at', '>', '2022-09-11')->where('customer_code', $cliente->code)->count();

                //contador de pedidos listo para delivery
                $pedidos_punto_destino = Dispatch::whereHas('tracking', function ($query) {
                    $query->whereIn('transit_status_id', [33]);
                })->where('registration_status', "A")->whereDate('created_at', '>', '2022-09-11')->where('customer_code', $cliente->code)->count();

                //contador de pedidos entregados
                $pedidos_entregados = Dispatch::whereHas('tracking', function ($query) {
                    $query->whereIn('transit_status_id', [32]);
                })->where('registration_status', "A")->whereDate('created_at', '>', '2022-09-11')->where('customer_code', $cliente->code)->count();

                $cliente->conteo = [
                    ["titulo" => "Pedidos Recibidos", "valor" => $pedidos_recibidos, "color" => "secondary", "estatus" => "recibido"],
                    ["titulo" => "Pedidos en Preparacion", "valor" => $pedidos_en_preparacion, "color" => "warning", "estatus" => "en_preparacion"],
                    ["titulo" => "Pedidos Listo Para Delivery", "valor" => $pedidos_listo_delivery, "color" => "info", "estatus" => "delivery"],
                    ["titulo" => "Pedidos en Ruta", "valor" => $pedidos_en_ruta, "color" => "primary", "estatus" => "en_ruta"],
                    ["titulo" => "Pedidos en Punto de Entrega", "valor" => $pedidos_punto_destino, "color" => "light", "estatus" => "en_punto_cliente"],
                    ["titulo" => "Pedidos Entregados", "valor" => $pedidos_entregados, "color" => "success", "estatus" => "entregado"],
                ];

                $response = [
                    "mensaje" => "Consulta Exitosa",
                    "cliente" => $cliente,

                ];

                return response()->json($response, 200);
            }

            $estados = [];

            //validar estado del pedido
            switch ($request->estatus) {
                case 'recibido':
                    $estados = [2];
                    break;

                case 'en_ruta':
                    $estados = [31];

                    break;
                case 'en_preparacion':

                    $estados = [3, 4, 5, 6, 7, 8, 9, 18];
                    break;

                case 'entregado':

                    $estados = [32];
                    break;
                case 'delivery':

                    $estados = [9];
                    break;
                    
                case 'en_punto_cliente':

                    $estados = [33];
                    break;
            }

             
            //buscar pedidos del cliente 
            $pedidos = Dispatch::with(['tracking.details.transit', 'details', 'detail', 'tracking.vehiculo', 'calificacion', 'tracking.details' => function ($query) {
                $query->validos();
                $query->orderBy('id', 'asc');
            }])->where('customer_code', $cliente->code)->where('registration_status', "A")->whereDate('created_at', '>', '2022-09-11');


            $pedidos = $pedidos->whereHas('tracking', function ($query) use ($estados) {
                $query->whereIn('transit_status_id', $estados);
            })->orderBy('created_at', 'desc')->get();


            //CONSULTAR DIRECCION
            $direccion = DB::table('v_direcciones_entrega')
                ->select('direccion_completa as direccion_envio')
                ->where('customer_id', $cliente->id)
                ->first();


            $direccion_envio = $direccion ? $direccion->direccion_envio : '';

            //agregar los pedidos al cliente
            $cliente->pedidos = $pedidos;


            //iterar pedidos
            foreach ($cliente->pedidos as $pedido) {

                $pedido->transportista = "";
                $pedido->transportista2 = "";

                if ($pedido->tracking) {

                    # buscar transportista
                    $transportista = GenResourceDetail::where('resource_id', 23)->where('code', $pedido->tracking->carrier_code)->first();
                    $transportista2 = null;

                    if ($pedido->tracking->carrier_code_secondary) {
                        $transportista2 = GenResourceDetail::where('resource_id', 23)->where('code', $pedido->tracking->carrier_code_secondary)->first();
                    }


                    $pedido->direccion_envio = $direccion_envio;

                    if ($transportista) {
                        $pedido->transportista = $transportista->name;
                    }

                    if ($transportista2) {
                        $pedido->transportista2 = $transportista2->name;
                    }
                }
            }

            $response = [
                "mensaje" => "Consulta Exitosa",
                "cliente" => $cliente,
            ];

            return response()->json($response, 200);
        }

        $response = [
            "mensaje" => "No cuenta con pedidos",
            "cliente" => $cliente,
        ];

        return response()->json($response, 200);
    }

    public function calificar(Request $request)
    {
        //validando el datos necesarios
        $validacion = Validator::make($request->all(), [
            'valor' => 'required',
            'pedido' => 'required',
        ]);

        //verificar si hay errores en la validacion
        if ($validacion->fails()) {
            return response()->json($validacion->errors(), 422);
        }

        Qualification::updateOrCreate(
            ['dispatch_id' =>  $request->pedido],
            [
                'fecha' => date('Y-m-d H:i:s'),
                'valor' => $request->valor
            ]
        );

        $response = [
            "mensaje" => "Se Guardo su Calificación",

        ];


        return response()->json($response, 200);
    }

    /**
     * metodo encargado de sincronizar los pedidos
     */
    public function SincronizarPedidosAs400()
    {

        //fecha para busqueda en el as400
        $fecha_del_dia_as400 = date('Ymd');

        //obtener registros de la tabla pedidos del ibm
        $pedidos_as400 = DB::connection('ibmi')
            ->table('MMCBREP')
            //->join('contacts', 'users.id', '=', 'contacts.user_id')
            ->where('cbfecdoc', $fecha_del_dia_as400)
            ->orderBy('cbfecdoc', 'desc')
            ->get();

        $i = 0;
        //iterar registros del as400 e insertarlos en pedidos
        foreach ($pedidos_as400 as $pedido) {
            $i++;

            //inicializar array para inserciones masivas
            $pedidos_por_sincronizar = $this->agregarItems($pedido);
            //sincronizar tabla intermedia con los datos de as400
            Dispatch::updateOrCreate(
                ['order_number' =>  $pedido->cbnroped, 'document_date' => $pedidos_por_sincronizar['document_date']],
                $pedidos_por_sincronizar
            );
        }


        return;
    }

    /**
     * metodo encargado de sincronizar el seguimiento de los pedidos
     */
    /*     
    public function SincronizarPedidosTackingAs400()
    {

        //fecha para busqueda en el as400
        $fecha_del_dia_as400 = date('Ymd');

        //obtener registros de la tabla pedidos del ibm
        $pedidos = Dispatch::with('tracking')
            ->select('id', 'document_date', 'branch_code', 'delivered', 'order_number', 'company_code', 'date_of_work', 'document_order_number', 'customer_id', 'social_reason')
            ->noEntregados()
            ->delDia()
            ->get();

        $i = 0;
        //iterar registros de pedidos
        foreach ($pedidos as $pedido) {



            if ($pedido->tracking) {
                //validar que no este ni en ruta ni entregado al cliente
                if ($pedido->tracking->transit_status_id == 31 || $pedido->tracking->transit_status_id == 32) {
                    echo $pedido->tracking->transit_status_id;
                    echo '          ';

                    continue;
                }
            }


            $i++;
            //obtener los datos del tracking del pedido
            $pedidos_as400 =  DB::connection('ibmi')->table('MMQ1REP')
                ->where('Q1CODCIA', $pedido->company_code)
                ->where('Q1CODSUC', $pedido->branch_code)
                ->where('Q1NROPED', $pedido->order_number)
                ->where('q1mjdt', str_replace("-", "", $pedido->date_of_work))
                ->orderBy('q1mjdt', 'desc')
                ->first();


            //obtener los datos del tracking del pedido
            $pedidos_tracking_as400 =  DB::connection('ibmi')->table('MMQ0REP')
                ->where('Q0CODCIA', $pedido->company_code)
                ->where('Q0NROPDC', $pedido->document_order_number)
                ->where('Q0DATE', str_replace("-", "", $pedido->date_of_work))
                ->get();

            //validar que exista un tracking
            if ($pedidos_as400) {

                //inicializar array para inserciones 
                $pedidos_tracking_sincronizar = $this->agregarItemsTracking($pedidos_as400);

                //crear o modificar la relacion
                $pedido_tracking = DispatchTracking::updateOrCreate(
                    ['dispatch_id' => $pedido->id],
                    $pedidos_tracking_sincronizar
                );

                //iterar los tracking
                foreach ($pedidos_tracking_as400 as $pedido_tracking_as400) {

                    //inicializar array para inserciones 
                    $pedidos_tracking_detalle_sincronizar = $this->agregarItemsTrackingDetalle($pedido_tracking_as400);

                    DispatchDetailsTracking::updateOrCreate(
                        ['dispatch_tracking_id' => $pedido_tracking->id, 'transit_status_id' => $pedido_tracking_as400->q0estado],
                        $pedidos_tracking_detalle_sincronizar
                    );

                    switch ($pedido_tracking_as400->q0estado) {
                        case "10":
                            $Est_transito = "En Ruta";
                            break;

                        case "31":
                            $Est_transito = "En Ruta";
                            break;

                        case "11":
                            $Est_transito = "Entregado";
                            break;

                        case "32":
                            $Est_transito = "Entregado";
                            break;

                        case "2":
                            $Est_transito = "Recibido";
                            break;

                        default:
                            $Est_transito = "En Preparacion";
                            break;
                    }

                    $log_mensaj = "Estimado Su pedido ($pedido->document_order_number) se encuentra con el siguiente estado: $Est_transito";

                    $mensajeEnviado = DB::table('log_mensajes')
                        ->where('cliente_id', $pedido->customer_id)
                        ->where('pedido_id', $pedido->id)
                        ->where('transito_id', $Est_transito)
                        ->where('tipo', 'WS')
                        ->first();


                    if (!$mensajeEnviado) {
                        DB::table('log_mensajes')->insert([
                            'cliente_id' => $pedido->customer_id,
                            'mensaje' => $log_mensaj,
                            'pedido_id' => $pedido->id,
                            'tipo' => "WS",
                            'transito_id' => $Est_transito
                        ]);

                        //enviar notificaciones al clientes
                        //$this->enviarMensajeWS($log_mensaj, '51957544999');

                        $data = [
                            'cliente_id' => $pedido->customer_code,
                            'cliente' => $pedido->social_reason,
                            'pedido' => $pedido->document_order_number,
                            'estado' => $log_mensaj
                        ];

                        //Mail::to('jmarquina@mym.com.pe')->send(new AlertasPedidos($data));
                    }
                }
            }
        }


        return;
    } */

    /**
     * metodo encargado de sincronizar los clientes del 
     * as400 
     */
    public function SincronizarClientesAs400()
    {

        //buscar ultimo cliente sincronizado
        $ultimo_cliente = Customer::select('date_of_job', 'job_time', 'created_at')->orderBy('created_at', 'desc')->first();

        //obtener registros de la tabla clientes del ibm
        $clientes_as400 = DB::connection('ibmi')->table('MMAKREP')->where('akfecucm', '>=', 20210101);

        //validar si ya existen clientes
        if ($ultimo_cliente) {
            $clientes_as400 = $clientes_as400->where('AKJDT', '>', str_replace("-", "", $ultimo_cliente->date_of_job));
        }

        //ejecutar consulta
        $clientes_as400 = $clientes_as400->take(10)->get();
        //var_dump($clientes_as400 );

        $listado_clientes = [];
        $i = 0;
        //iterar registros del as400 e insertarlos en pedidos
        foreach ($clientes_as400 as $cliente) {
            $i++;
            //agregamos al array para inserciones masivas
            array_push($listado_clientes, $this->agregarItemsCliente($cliente));
        }

        Customer::insert($listado_clientes);


        return;
    }

    //metodo encargado de agregar items a array de insert
    public function agregarItems($data)
    {
        $fecha = Carbon::createFromFormat('Ymd', $data->cbfecdoc, 'America/Lima');
        $fecha_formateada =  $fecha->format('Y-m-d');
        $fecha_trabajo = Carbon::createFromFormat('Ymd', $data->cbjdt, 'America/Lima');
        $fecha_formateada_trabajo  =  $fecha_trabajo->format('Y-m-d');
        //$hora_trabajo = $this->formatearHora($data->cbjtm);
        $cliente = Customer::select('id')->where('code', $data->cbcodcli)->first();

        return
            [
                'customer_code' => trim($data->cbcodcli),
                'company_code' => trim($data->cbcodcia),
                'branch_code' => trim($data->cbcodsuc),
                'document_order_number' => trim($data->cbnropdc),
                'customer_id' => $cliente ? $cliente->id : null,
                'social_reason' => trim($data->cbrazsoc),
                'number_ruc' => trim($data->cbnroruc),
                'document_date' => trim($fecha_formateada),
                'order_origin' => trim($data->cboriped),
                'tax_condition' => trim($data->cbcndtrb),
                'seller_code' => trim($data->cbcodven),
                'served_by' => trim($data->cbatnpor),
                'warehouse_code' => trim($data->cbcodalm),
                'priority_status' => trim($data->cbstspri),
                'reason_for_transfer' => trim($data->cbmtvtrs),
                'payment_method' => trim($data->cbfrmpag),
                'betalings_metode' => trim($data->cbmodpag),
                'payment_condition' => trim($data->cbcndpag),
                'currency_code' => trim($data->cbcodmon),
                'discount_customer_class' => trim($data->cbdctcls),
                'discount_cond_payment' => trim($data->cbdctcnd),
                'document_general_status' => trim($data->cbstsdgr),
                'document_type' => trim($data->cbtipdoc),
                'document_serial_number' => trim($data->cbnroser),
                'corre_document_number' => trim($data->cbnrocor),
                'imports_total' => trim($data->cbimptot),
                'discount_payment' => trim($data->cbimpdcp),
                'discount_amount_per_customer_class' => trim($data->cbimpdcc),
                'amount_taxes' => trim($data->cbimpimp),
                'order_status_document' => trim($data->cbstspdo),
                'print_status' => trim($data->cbstsimp),
                'registration_status' => trim($data->cbsts),
                'user_name        ' => trim($data->cbusr),
                'work' => trim($data->cbjob),
                'date_of_work' => $fecha_formateada_trabajo,
                'work_time' =>  $this->formatearHora($data->cbjtm),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'status' => 1
            ];
    }

    //metodo encargado de agregar items a array de insert de el tracking
    public function agregarItemsTracking($data)
    {
        //arreglo con los estados que no modificaran el mmtrack
        $estados_transito = ['11', '23', '22'];
        $fecha_formateada = null;
        $hora_salida = null;
        $fecha_formateada_llegada = null;
        $hora_llegada = null;
        $fecha_formateada_registro = null;
        $hora_registro = null;
        $fecha_formateada_movimiento = null;
        $hora_movimiento = null;


        if ($data->q1fecsal != 0) {
            $fecha = Carbon::createFromFormat('Ymd', $data->q1fecsal, 'America/Lima');
            $fecha_formateada =  $fecha->format('Y-m-d');
            $hora_salida = $this->formatearHora($data->q1horsal);
        }

        if ($data->q1feclle != 0) {
            $fecha2 = Carbon::createFromFormat('Ymd', $data->q1feclle, 'America/Lima');
            if ($fecha2 !== false) {
                $fecha_formateada_llegada =  $fecha2->format('Y-m-d');
                $hora_llegada = $this->formatearHora($data->q1horlle);
            }
        }

        if ($data->q1jdt != 0) {
            $fecha3 = Carbon::createFromFormat('Ymd', $data->q1jdt, 'America/Lima');
            if ($fecha3 !== false) {
                $fecha_formateada_registro =  $fecha3->format('Y-m-d');
                $hora_registro = $this->formatearHora($data->q1jtm);
            }
        }

        if ($data->q1mjdt != 0) {
            $fecha4 = Carbon::createFromFormat('Ymd', $data->q1mjdt, 'America/Lima');
            if ($fecha4 !== false) {
                $fecha_formateada_movimiento =  $fecha4->format('Y-m-d');
                $hora_movimiento = $this->formatearHora($data->q1jtm);
            }
        }

        //crear el arreglo a guardar
        $areglo_guardar = [
            'carrier_code' => trim($data->q1codtrn),
            'part_number' => trim($data->q1nropte),
            'department' => trim($data->q1destin),
            'guide_series_number' => trim($data->q1nrgser),
            'correlative_guide_number' => trim($data->q1nrgcor),
            'departure_date' => $fecha_formateada,
            'departure_time' => $hora_salida,
            'arrival_date' => $fecha_formateada_llegada,
            'arrival_time' => $hora_llegada,
            'packaging_group' => trim($data->q1gruemp),
            'status_office_almc' => trim($data->q1estpen),
            'observation' => trim($data->q1observ),
            'carrier_group' => trim($data->q1grutra),
            'registration_status' => trim($data->q1sts),
            'registration_user' => trim($data->q1usr),
            'workstation' => trim($data->q1job),
            'date_of_registration' => $fecha_formateada_registro,
            'check_in_time' => $hora_registro,
            'registration_scheduling' => trim($data->q1pgm),
            'user_movement' => trim($data->q1musr),
            'movement_date' => $fecha_formateada_movimiento,
            'time_movement' => $hora_movimiento,
            //'vehicles_id' => null,
            'drivers_id' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'status' => 1,
        ];

        if (!in_array(trim($data->q1estamv), $estados_transito)) {
            $areglo_guardar['transit_status_id'] = trim($data->q1estamv);
        }



        return $areglo_guardar;
    }

    //metodo encargado de agregar items a array de insert
    //de los detalles del tracking
    public function agregarItemsTrackingDetalle($data)
    {

        $fecha_formateada = null;
        $hora_salida = null;


        if ($data->q0date != 0) {
            $fecha = Carbon::createFromFormat('Ymd', $data->q0date, 'America/Lima');
            $fecha_formateada =  $fecha->format('Y-m-d');
            $hora_salida = $this->formatearHora($data->q0hora);
        }

        return [
            'personal_code' => trim($data->q0codper),
            'packaging_group' => trim($data->q0gruemp),
            'description_status' => utf8_encode(trim($data->q0observ)),
            'description_status_web' => utf8_encode(trim($data->q0observ)),
            'observations' => utf8_encode(trim($data->q0observs)),
            'registration_status' => trim($data->q0sta),
            'program' => trim($data->q0pgm),
            'username' => trim($data->q0usu),
            'work' => trim($data->q0job),
            'date_of_work' => $fecha_formateada,
            'work_time' => $hora_salida,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'status' => 1,
            'sincronizado_as400' => date('Y-m-d H:i:s'),
        ];
    }

    //metodo encargado de agregar items a array de insert
    //de los clientes
    public function agregarItemsCliente($data)
    {

        $fecha_formateada = null;
        $fecha_formateada_inscripcion = null;
        $fecha_formateada_ultima_compra = null;
        $hora = null;

        if ($data->akfecins != 0) {

            $fecha = Carbon::createFromFormat('Ymd', $data->akfecins, 'America/Lima');
            $fecha_formateada_inscripcion =  $fecha->format('Y-m-d');
        }

        if ($data->akfecucm != 0) {

            $fecha2 = Carbon::createFromFormat('Ymd', $data->akfecucm, 'America/Lima');
            $fecha_formateada_ultima_compra =  $fecha2->format('Y-m-d');
        }
        if ($data->akjdt != 0) {

            $fecha3 = Carbon::createFromFormat('Ymd', $data->akjdt, 'America/Lima');
            $fecha_formateada =  $fecha3->format('Y-m-d');
            $hora_salida = $this->formatearHora($data->akjtm);
        }

        return [
            'customer_code' => trim($data->akcodcli),
            'social_reason' => trim($data->akrazsoc),
            'tradename' => trim($data->aknomcom),
            'economic_group' => trim($data->akgrpeco),
            'document_type_id' => trim($data->aktipide),
            'document_number' => trim($data->aknroide),
            'business_turn' => trim($data->akgroneg),
            'hivenumber' => trim($data->aknroruc),
            'parental_code' => trim($data->akcodpai),
            'clientclass' => trim($data->akclsclt),
            'Typeof_company' => trim($data->aktipemp),
            'registration_date' => $fecha_formateada_inscripcion,
            'share_capital_amount' => trim($data->akimpcso),
            'last_purchase_date' => $fecha_formateada_ultima_compra,
            'tax_condition' => trim($data->akcndtrb),
            'currency_code' => trim($data->akcodmon),
            'importLimit_credit' => trim($data->akimplmt),
            'consumption_amount' => trim($data->akimpcsm),
            'sales_block' => trim($data->akblqvta),
            'credit_block' => trim($data->akblqcrd),
            'registration_status' => trim($data->aksts),
            'user_name' => trim($data->akusr),
            'job' => trim($data->akjob),
            'date_of_job' => $fecha_formateada,
            'job_time' => $hora_salida,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'status' => 1,
        ];
    }



    /**
     * metodo encargado de formatear la hora
     */
    public function formatearHora($tiemp)
    {
        $tiempo = str_pad($tiemp, 6, "0", STR_PAD_LEFT);
        $hora = substr($tiempo, 0, 2);
        $minutos = substr($tiempo, 2, 2);
        $seg = substr($tiempo, 4, 2);

        return "$hora:$minutos:$seg";
    }


    public function obtenerGuiaRemision($id)
    {
        $query = "SELECT gr.jqnroser, gr.JQNROCOR, gr.JQFECGUI, gr.JQCODSUC, gr.JQNROPDC, ped.* 
                    FROM libprddat.mmcbrep ped
                        INNER JOIN LIBPRDDAT.MMJQREP gr
                            ON ped.CBCODCIA = gr.JQCODCIA AND
                            ped.CBNROPDC = gr.JQNROPDC AND 
                            ped.CBCODSUC =gr.JQCODSUC 
                    WHERE ped.CBSTS = 'A' AND gr.JQSTS = 'A' AND ped.CBNROPDC = $id ";

        //obtener guia de remision
        $guia =  DB::connection('ibmi')->select($query);


        dd($guia);
    }


    /**
     * metodo encargado de sincronizar pedidos desde la vista
     */
    public function sincronizarAs400()
    {
        //SINCRONIZAR PEDIDOS
        $this->SincronizarPedidosAs400();

        //SINCRONIZAR TRACKING DE PEDIDOS
        $this->SincronizarPedidosTackingAs400();

        //Artisan::call("sincronizar:pedidos");

        return response()->json(["mensaje" => "actualizado"], 200);
    }

    /**
     * metodo encargado de enviar notificacion por ws
     */
    public function enviarMensajeWS($mensaje, $contact_id)
    {
        if (Config::get('services.ws_api.conf')) {

            $url = Config::get('services.ws_api.url')."/enviar-mensaje";
            echo "<br>ENVIANDO MENSAJE Whatsapp URL:".$url;
            echo "<br>MENSAJE Whatsapp:".$mensaje;
            //buscar contacto
            //validar que el pedido tenga un contacto
            if (!$contact_id) {
                return;
            }

            $contacto = CustomerContact::where('id', $contact_id)->first();

            if ($contacto) {
                # code...
                $response = Http::withoutVerifying()
                    ->withHeaders(['Cache-Control' => 'no-cache'])
                    ->withOptions(["verify" => false])
                    ->post($url, [
                        'numero' => "51$contacto->contact_phone",
                        'mensaje' => $mensaje
                    ]);

                return $response;
            }
        }else{
            echo "<br>PROBLEMAS CON LA CONFIGURACION WHATSAPP";
        }
        return;
    }

    /**
     * Actualiza contacto en pedido
     */
    public function mmtrack_actualiza_contacto_cabecera_pedidos($fila)
    {
        $registro = $fila->datos_consulta;

        $arrayWhere = array(
            ['customer_code', '=', trim($registro->cpcodcli)],
            ['customer_contact_number', '=', $registro->cpitem01],
        );
        $datos_contacto_cliente = DB::table('v_customer_contacts')->where($arrayWhere)->first();
        if ($datos_contacto_cliente) {

            $arrayWhere = array(
                ['company_code', '=', $registro->cpcodcia],
                ['branch_code', '=', $registro->cpcodsuc],
                ['document_order_number', '=', $registro->cpnropdc],
            );
            $arrayUpdate = array(
                'customer_contact_id' => $datos_contacto_cliente->customer_contact_id
            );

            return Dispatch::where($arrayWhere)->update($arrayUpdate);
        } else {
            echo " - Contacto no existe";
            return 1;
        }
    }

    /**
     * Genera un pedido nuevo o lo actualiza de acuerdo al objeto "Pedido"
     */
    public function mmtrack_escribe_actualiza_cabecera_pedidos($pedido)
    {
        $actualizar = false;
        $ped = $pedido->datos_consulta;
        $cliente =  Customer::select('id')->where('code', '=', $ped->cbcodcli)->first();
        //die(print_r($cliente));
        if (!$cliente) {
            return false;
        }

        $arrayPedido = array(
            'company_code' => $ped->cbcodcia,
            'branch_code' => trim($ped->cbcodsuc),
            'order_number' => $ped->cbnroped,
            'document_order_number' => $ped->cbnropdc,
            'social_reason' => utf8_decode(trim($ped->cbrazsoc)),
            'number_ruc' => trim($ped->cbnroruc),
            'document_date' => $ped->cbfecdoc,
            'order_origin' => trim($ped->cboriped),
            'tax_condition' => trim($ped->cbcndtrb),
            'seller_code' => trim($ped->cbcodven),
            'served_by' => trim($ped->cbatnpor),
            'warehouse_code' => trim($ped->cbcodalm),
            'priority_status' => trim($ped->cbstspri),
            'reason_for_transfer' => trim($ped->cbmtvtrs),
            'payment_method' => $ped->cbfrmpag,
            'betalings_metode' => trim($ped->cbmodpag),
            'payment_condition' => $ped->cbcndpag,
            'currency_code' => $ped->cbcodmon,
            'discount_customer_class' => trim($ped->cbdctcls),
            'discount_cond_payment' => trim($ped->cbdctcnd),
            'document_general_status' => trim($ped->cbstsdgr),
            'document_type' => trim($ped->cbtipdoc),
            'document_serial_number' => trim($ped->cbnroser),
            'corre_document_number' => trim($ped->cbnrocor),
            'imports_total' => $ped->cbimptot,
            'discount_payment' => $ped->cbimpdcp,
            'discount_amount_per_customer_class' => $ped->cbimpdcc,
            'amount_taxes' => $ped->cbimpimp,
            'order_status_document' => trim($ped->cbstspdo),
            'print_status' => trim($ped->cbstsimp),
            'registration_status' => trim($ped->cbsts),
            'user_name' => trim($ped->cbusr),
            'work' => trim($ped->cbjob),
            'date_of_work' => $ped->cbjdt,
            'work_time' => trim($ped->cbjtm),
            'created_at' => date("Y-m-d H:i:s"),
            'status' => 1,
            'customer_id' => $cliente->id,
            'delivered' => 0,
            'customer_code' => trim($ped->cbcodcli)
        );
        // echo '<pre>';
        // die(print_r($arrayPedido));
        //$fecha = Carbon::createFromFormat('Ymd', $ped->cbfecdoc, 'America/Lima');
        //$fecha_formateada =  $fecha->format('Y-m-d');
        $arrayWherePed = array(
            ['company_code', '=', $ped->cbcodcia],
            ['branch_code', '=', $ped->cbcodsuc],
            ['document_order_number', '=', $ped->cbnropdc],
        );

        switch (strtoupper(trim($pedido->sytpoper))) { //TIPO DE OPERACIÓN: INSERT, UPDATE, DELETE
            case 'INSERT':
                $actualizar = (Dispatch::updateOrCreate($arrayWherePed, $arrayPedido)) ? 1 : 0;
                break;
            case 'UPDATE':
                $actualizar = (Dispatch::where($arrayWherePed)->update($arrayPedido)) ? 1 : 0;
                break;
        }

        /*
        //TEMPORAL MIENTRAS ACTIVAN TRIGGERS EN PRODUCCION
        //BUSCAR CLIENTE-PEDIDO-CONTACTO
        echo "<br>BUSCAR CLIENTE-PEDIDO-CONTACTO";
        $arrayWhere = array(
            ['cpcodcia', '=', $ped->cbcodcia],
            ['cpcodsuc', '=', $ped->cbcodsuc],
            ['cpnropdc', '=', $ped->cbnropdc],
            ['cpcodcli', '=', $ped->cbcodcli],
            ['cpsts', '=', 'A'],
        );
        $pedido_contacto_as = DB::connection('ibmi')
            ->table('LIBPRDDAT.CPCNREP')
            ->select(['cpitem01'])
            ->where($arrayWhere)
            ->first();

        if ($pedido_contacto_as) {
            print_r($pedido_contacto_as);
            $arrayWhereCC = array(
                ['customer_id', '=', $cliente->id],
                ['customer_contact_number', '=', $pedido_contacto_as->cpitem01],
            );
            $cliente_contacto = DB::table('customer_contacts')->where($arrayWhereCC)->first();
            if (!$cliente_contacto) {
                //BUSCAR CONTACTO_CLIENTE EN AS400
                echo "<br>BUSCAR CONTACTO_CLIENTE EN AS400. CLIENTE: $ped->cbcodcli - CONTACTO:  $pedido_contacto_as->cpitem01";
                $arrayWhere = array(
                    ['pccodcli', '=', $ped->cbcodcli],
                    ['pcitem01', '=', $pedido_contacto_as->cpitem01],
                    ['pcsts', '=', 'A'],
                );
                print_r($arrayWhere);
                exit;
                $cliente_contacto_as = DB::connection('ibmi')
                    ->table('LIBPRDDAT.CCPCREP')
                    ->where($arrayWhere)
                    ->first();
                $cliente_contacto_as->datos_consulta = $cliente_contacto_as;

                //CREAR CONTACTO_CLIENTE EN BD INTERMEDIA
                echo "<br>CREAR CONTACTO_CLIENTE EN BD INTERMEDIA";
                SyncCustomer::ccpcrep_cliente_contactos($cliente_contacto_as);

                $arrayWhereCC = array(
                    ['customer_id', '=', $cliente->id],
                    ['customer_contact_number', '=', $pedido_contacto_as->cpitem01],
                );
                $cliente_contacto = DB::table('customer_contacts')->where($arrayWhereCC)->first();
            }

            //ACTUALIZAR PEDIDO-CONTACTO
            echo "<br>ACTUALIZAR PEDIDO-CONTACTO";
            return Dispatch::where($arrayWherePed)->update(['customer_contact_id' => $cliente_contacto->id]);
        }
        //FIN - TEMPORAL MIENTRAS ACTIVAN TRIGGERS EN PRODUCCION
        */

        return $actualizar;
    }

    /**
     * Genera o actualiza la tabla "pedido_detalle" dado el objeto "detalle_pedido"
     */
    public function mmtrack_escribe_actualiza_detalle_pedidos($fila)
    {

        $actualizar = true;
        $detalle = $fila->datos_consulta;
        $arrayWhere = array(
            ['company_code', '=', $detalle->cecodcia],
            ['branch_code', '=', $detalle->cecodsuc],
            ['document_order_number', '=', $detalle->cenropdc]
        );
        if ($pedido =  Dispatch::select('id')->where($arrayWhere)->first()) {

            $arrayPedidoDetalle = [
                'dispatch_id' => $pedido->id,
                'cecodcia' => $detalle->cecodcia,
                'cecodsuc' => $detalle->cecodsuc,
                'cenroped' => $detalle->cenroped,
                'cenropdc' => $detalle->cenropdc,
                'ceitem01' => $detalle->ceitem01,
                'cecodalm' => $detalle->cecodalm,
                'cecodlin' => $detalle->cecodlin,
                'cecodart' => utf8_encode(trim(strtoupper($detalle->cecodart))),
                'cecodori' => $detalle->cecodori,
                'cecodmar' => $detalle->cecodmar,
                'cedscart' => utf8_encode(trim(strtoupper($detalle->cedscart))),
                'cecandsp' => $detalle->cecandsp,
                'cecandev' => $detalle->cecandev,
                'ceimppre' => $detalle->ceimppre,
                'cestslon' => $detalle->cestslon,
                'cedctlin' => $detalle->cedctlin,
                'cedctadi' => $detalle->cedctadi,
                'ceprcimp' => $detalle->ceprcimp,
                'cestsprm' => $detalle->cestsprm,
                'cestsite' => $detalle->cestsite,
                'cests' => $detalle->cests,
                'ceusr' => $detalle->ceusr,
                'cejob' => $detalle->cejob,
                'cejdt' => $detalle->cejdt,
                'cejtm' => $detalle->cejtm
            ];

            $arrayWhere = array(
                ['cecodcia', '=', $detalle->cecodcia],
                ['cecodsuc', '=', $detalle->cecodsuc],
                ['cenropdc', '=', $detalle->cenropdc],
                ['ceitem01', '=', $detalle->ceitem01],

            );

            $actualizar = DispatchDetailMts::updateOrCreate(
                $arrayWhere,
                $arrayPedidoDetalle
            );
        }
        return $actualizar;
    }

    /**
     * Actualiza guia de remisión en tabla "cabecera_pedido"
     */
    public function mmtrack_actualiza_guia_remision_pedido($fila)
    {
        $actualizar = false;
        $ped = $fila->datos_consulta;
        $gr = $fila->datos_consulta;
        $serie = trim(strtoupper($gr->jqnroser));
        $correlativo = trim(strtoupper($gr->jqnrocor));
        if (strlen($serie . $correlativo) > 1) {
            $arrayGR = array(
                'referral_guide_number' => $serie . ' ' . $correlativo,
                'referral_guide_date' => $gr->jqfecgui
            );
            $arrayWhere = array(
                ['company_code', '=', $ped->jqcodcia],
                ['branch_code', '=', $ped->jqcodsuc],
                ['document_order_number', '=', $ped->jqnropdc],
            );
            $actualizar = (Dispatch::where($arrayWhere)->update($arrayGR)) ? 1 : 0;
            echo (' - Act: ' . $actualizar);
        }
        return 1;
    }

    /**
     * Genera-Actualiza Cabecera tracking dado el objeto"
     */
    public function mmtrack_actualiza_cabecera_tracking_pedido($fila)
    {

        $nueva_fila = $fila->datos_consulta;
        //obtener registros de la tabla pedidos del ibm
        $pedido = Dispatch::select('id')
            ->where('company_code', $nueva_fila->q1codcia)
            ->where('branch_code', $nueva_fila->q1codsuc)
            ->where('document_order_number', $nueva_fila->q1nropdc)
            ->first();

        //validar que exista el pedido
        if (!$pedido) {
            echo "NO EXISTE EL PEDIDO";
            return null;
        }


        //inicializar array para inserciones 
        $pedidos_tracking_sincronizar = $this->agregarItemsTracking($nueva_fila);

        //crear o modificar la relacion
        $pedido_tracking = DispatchTracking::updateOrCreate(
            ['dispatch_id' => $pedido->id],
            $pedidos_tracking_sincronizar
        );

        return 1;
    }

    /**
     * Genera-Actualiza Detalle tracking dado el objeto"
     */
    public function mmtrack_actualiza_detalle_tracking_pedido($fila)
    {
        $nueva_fila = $fila->datos_consulta;
        echo "<br>Inicia : mmtrack_actualiza_detalle_tracking_pedido";
        echo '<pre>';
        //print_r($fila->datos_consulta);

        //obtener registros de la tabla pedidos del ibm
        $pedido = Dispatch::with('tracking')
            ->where('company_code', $nueva_fila->q0codcia)
            ->where('branch_code', $nueva_fila->q0codsuc)
            ->where('document_order_number', $nueva_fila->q0nropdc)
            ->first();

        //validar que exista el pedido
        if (!$pedido) {
            echo "<br>PEDIDO NO ENCONTRADO";
            return null;
        }

        //obtener el trackin cabecera del pedido
        $pedido_tracking = DispatchTracking::where('dispatch_id', $pedido->id)->first();

        //validar el tracking
        if (!$pedido_tracking) {
            echo "<br>PEDIDO-TRACKING  NO ENCONTRADO";
            return null;
        }


        //inicializar array para inserciones 
        $pedidos_tracking_detalle_sincronizar = $this->agregarItemsTrackingDetalle($nueva_fila);

        //arneon 2022-04-18
        //Si existe $pedido->tracking, crear/actualizar registro en tabla DispatchDetailsTracking
        if (isset($pedido->tracking)) {
            echo "<br>Pedido tracking detalle : ".$pedido->tracking;
            DispatchDetailsTracking::updateOrCreate(
                ['dispatch_tracking_id' => $pedido->tracking->id, 'transit_status_id' => $nueva_fila->q0estado],
                $pedidos_tracking_detalle_sincronizar
            );
        }


        //validar si el estado es empaquetado 
        if ($nueva_fila->q0estado == '9') {
            DispatchTracking::where('dispatch_id', $pedido->id)->update(['is_packed' => 1]);
        }
        //validar si el estado es aprobado 
        if ($nueva_fila->q0estado == '3') {
            DispatchTracking::where('dispatch_id', $pedido->id)->update(['is_approved' => 1]);
        }
        //validar si el estado es facturado 
        if ($nueva_fila->q0estado == '5') {
            DispatchTracking::where('dispatch_id', $pedido->id)->update(['is_approved' => 1]);
        }

        $Est_transito = "";


        switch ($nueva_fila->q0estado) {
            case "10":
                $Est_transito = "En Ruta";
                break;
            case "14":
                $Est_transito = "Pedido Rechazado";
                break;

            case "31":
                $Est_transito = "En Ruta";
                break;

            case "32":
                $Est_transito = "Entregado";
                break;

            case "2":
                $Est_transito = "Recibido";
                break;

            case "9":
                $Est_transito = "Pedido Embalado y Listo para delivery";
                break;

            default:
                $Est_transito = "En Preparacion";
                break;
        }

        $enlace_web = "https://mmtrack.mym.com.pe/panel/cliente/$pedido->customer_code";
        echo "<br>Enlace web :".$enlace_web;
        //obtener el mensaje de tracking
        $transito = Transit::where('id', $nueva_fila->q0estado)->first();

        $ws_mensaje_transito = $transito ? $transito->mensaje : '';
        $ws_numero_pedido = $pedido->document_order_number;

        $log_mensaj = "*Mensaje*: $ws_mensaje_transito,  *Pedido*: $ws_numero_pedido, *Enlace*: $enlace_web";

        $mensajeEnviado = DB::table('log_mensajes')
            ->where('cliente_id', $pedido->customer_id)
            ->where('pedido_id', $pedido->id)
            ->where('transito_id', $Est_transito)
            ->where('tipo', 'WS')
            ->first();

        echo "<br>CustomerID :".$pedido->customer_id;
        echo "<br>PedidoId :".$pedido->id;
        echo "<br>TransitoID :".$Est_transito;
        if (!$mensajeEnviado) {
            if ($Est_transito != "") {
                DB::table('log_mensajes')->insert([
                    'cliente_id' => $pedido->customer_id,
                    'mensaje' => $log_mensaj,
                    'pedido_id' => $pedido->id,
                    'tipo' => "WS",
                    'transito_id' => $Est_transito
                ]);
                echo "<br>mensajeEnviado: Mensaje Whatsapp".$log_mensaj;
                //enviar notificaciones al clientes
                $this->enviarMensajeWS($log_mensaj, $pedido->customer_contact_id);

                DB::table('log_mensajes')->insert([
                    'cliente_id' => $pedido->customer_code,
                    'mensaje' => $log_mensaj,
                    'pedido_id' => $pedido->id,
                    'tipo' => "EMAIL",
                    'transito_id' => $Est_transito
                ]);
            }
        }

        echo "<br>Pedido Tracking:".$pedido_tracking;
        //validar si entrega el cliente 
        if ($pedido_tracking->carrier_code == "003151") {
            //validar que este empaquetado
            if ($pedido_tracking->is_packed == 1) {
                //validar que este aprobado
                if ($pedido_tracking->is_approved == 1) {

                    $log_mensaj = "Estimado Su pedido ($pedido->document_order_number) se encuentra listo para su recojo, para consultar su pedido en el siguiente enlace:  $enlace_web";

                    $mensajePorEnviar = DB::table('log_mensajes')
                        ->where('cliente_id', $pedido->customer_id)
                        ->where('pedido_id', $pedido->id)
                        ->where('transito_id', 'Recojo-En-Tienda')
                        ->where('tipo', 'WS')
                        ->first();

                    if (!$mensajePorEnviar) {
                        DB::table('log_mensajes')->insert([
                            'cliente_id' => $pedido->customer_id,
                            'mensaje' => $log_mensaj,
                            'pedido_id' => $pedido->id,
                            'tipo' => "WS",
                            'transito_id' => 'Recojo-En-Tienda'
                        ]);
                        echo "<br>carrier_code: Mensaje Whatsapp".$log_mensaj;
                        //enviar notificaciones al clientes
                        $this->enviarMensajeWS($log_mensaj, $pedido->customer_contact_id);

                        DB::table('log_mensajes')->insert([
                            'cliente_id' => $pedido->customer_code,
                            'mensaje' => $log_mensaj,
                            'pedido_id' => $pedido->id,
                            'tipo' => "EMAIL",
                            'transito_id' => 'Recojo-En-Tienda'
                        ]);
                    }
                }
            }
        }


        return 1;
    }
}
