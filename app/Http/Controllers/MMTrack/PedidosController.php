<?php

namespace App\Http\Controllers\MMTrack;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MMTrack\Dispatch;
use App\Models\MMTrack\DispatchTracking;
use App\Models\Customer;
use App\Models\GenResourceDetail;
use App\Models\MMTrack\Vehicles;
use App\Models\MMTrack\DriverAssistant;
use App\Models\CustomerContact;
use DB;

class PedidosController extends Controller
{
    /**
     * metodo encargado de listar todos los pedidos por vendedor
     */
    public function pedidos(Request $request)
    {
        $columnas_pedido = ['id', 'social_reason', 'customer_id', 'document_order_number', 'social_reason', 'date_of_work', 'served_by', 'registration_status','referral_guide_number','customer_contact_id'];
        //buscar pedidos 
        $pedidos = Dispatch::select($columnas_pedido)
            ->with([
                'tracking:id,dispatch_id,transit_status_id,vehicles_id,carrier_code',
                'tracking.details.transit',
                'details',
                'calificacion',
                'tracking.transit:id,description_web',
                'client:id,document_type_id,code,document_number',
                'client.document:id,resource_id,abrv',
                'detail:id,customer_id,tipo_entrega,dispatch,order_delivery_id,peso,bultos',
                'tracking.vehiculo:id,placa,driver_id',
                'tracking.vehiculo.conductor',
                'tracking.details' => function ($query) {
                    
                    $query->validos();
                    $query->orderBy('id', 'asc');
                }
            ]);

        $estados = [];
        $estatus_registro = ["A"];

        //validar estado del pedido
        switch ($request->estado_pedido) {
            case 'recibido':
                $estados = [2];
                break;

            case 'en_ruta':
                $estados = [31];

                break;
            case 'en_preparacion':

                $estados = [3, 4, 5, 6, 7, 8, 18];
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

            case 'rechazado':

                $estados = [14, 3, 4, 5, 6, 7, 8, 9, 32, 31, 2, 18];
                $estatus_registro = ["I"];
                break;
            default:

                $estados = [14, 3, 4, 5, 6, 7, 8, 9, 32, 31, 2, 18,33,11];
                $estatus_registro = ["I", "A"];
                break;
        }



        $pedidos = $pedidos->whereHas('tracking', function ($query) use ($estados) {
            $query->whereIn('transit_status_id', $estados);
        });

        //validar numero de pedido
        if ($request->numero_pedido != '') {
            $pedidos = $pedidos->where('document_order_number', $request->numero_pedido);
        }
        //validar codigo de cliente
        if ($request->codigo_cliente != '') {
            $pedidos = $pedidos->where('customer_code', $request->codigo_cliente);
        }
        //validar fecha
        if ($request->fecha != '') {
            $pedidos = $pedidos->where('date_of_work', $request->fecha);
        }

        //validar codigo de as400
        if ($request->codigo_as400 != '') {
            $codigo_as400 = strtoupper(trim($request->codigo_as400));
            $pedidos = $pedidos->where('served_by', 'like', '%' . $codigo_as400 . '%');
        }




        $pedidos = $pedidos->whereIn('registration_status', $estatus_registro)->where('warehouse_code', '22')->orderBy('created_at', 'desc')->paginate(10);
      
        //iterar pedidos
        foreach ($pedidos->items() as $pedido_item) {
            # buscar transportista
            $transportista = GenResourceDetail::where('resource_id', 23)->where('code', $pedido_item->tracking->carrier_code)->first();
            $transportista2 = null;

            if ($pedido_item->tracking->carrier_code_secondary) {
                $transportista2 = GenResourceDetail::where('resource_id', 23)->where('code', $pedido_item->tracking->carrier_code_secondary)->first();
            }

            //CONSULTAR DIRECCION
            $direccion = DB::table('v_direcciones_entrega')
                ->select('direccion_completa as direccion_envio')
                ->where('customer_id', $pedido_item->customer_id)
                ->first();


            $direccion_envio = $direccion ? $direccion->direccion_envio : '';

            $contacto = CustomerContact::where('id',$pedido_item->customer_contact_id)->first();

            $pedido_item->contacto_nombre =  $contacto ? $contacto->contact_name : '';
            $pedido_item->contacto_numero = $contacto ? $contacto->contact_phone : '';

            $pedido_item->transportista = "";
            $pedido_item->transportista2 = "";

            $pedido_item->direccion_envio = $direccion_envio;

            if ($transportista) {
                $pedido_item->transportista = $transportista->name;
            }

            if ($transportista2) {
                $pedido_item->transportista2 = $transportista2->name;
            }
        }


        $response = [
            "mensaje" => "Consulta Exitosa",
            "pedidos" => $pedidos,
        ];


        return response()->json($response, 200);
    }

    /**
     * metodo encargado de listar todos los vehiculos con sus pedidos
     */
    public function vehiculosConPedidos(Request $request)
    {
        //buscar vehiculos 
        $vehiculos = Vehicles::whereHas('despachos.details', function ($query) use ($request) {
                                //validar codigo de cliente
                                if ($request->fecha != '') {
                                    $query->whereDate('created_at', $request->fecha);
                                }
                                }) 
        ->with('marca', 'tipo', 'conductor', 'despachos.details.pedido:id,document_order_number,customer_code,created_at')
        ->with(['despachos.details' => function ($query) use ($request) {
            $query->select('id','order_delivery_id','dispatch','status_order','created_at');
            if ($request->fecha != '') {
                $query->whereDate('created_at', $request->fecha);
            }
        }])
        ->where('status', 1);
        
        //validar estado 
        if ($request->estado_pedido != '' && $request->estado_pedido != "todos") {
            $vehiculos = $vehiculos->where('action_status', $request->estado_pedido);
        }
        //validar placa
        if ($request->numero_pedido != "") {
            $vehiculos = $vehiculos->where('placa', $request->numero_pedido);
        }

        $vehiculos = $vehiculos->orderBy('id', 'desc')->paginate(20);

        //iterar registros
        foreach ($vehiculos->items() as $vehiculo) {

            $suma_pedidos = 0;
            $suma_pedidos_entregados = 0;
            $suma_pedidos_en_ruta = 0;


            $cargar_pedidos = [];

            foreach ($vehiculo->despachos as $despacho) {
                $suma_pedidos = $suma_pedidos + (count($despacho->details));
                $suma_pedidos_entregados = $suma_pedidos_entregados + (count($despacho->details->where('status_order', 'Entregado')));
                $suma_pedidos_en_ruta = $suma_pedidos_en_ruta + (count($despacho->details->where('status_order', 'En Ruta'))) + (count($despacho->details->where('status_order', 'Punto de Destino')));


                if (count($despacho->details) > 0) {
                    foreach ($despacho->details as $detail) {
                        array_push($cargar_pedidos, $detail);
                    }
                }
            }

            $vehiculo->pedidos = $cargar_pedidos;

            $vehiculo->total_pedidos = $suma_pedidos;
            $vehiculo->total_pedidos_entregados = $suma_pedidos_entregados;
            $vehiculo->total_pedidos_en_ruta = $suma_pedidos_en_ruta;
            $vehiculo->total_pedidos_pendientes = $suma_pedidos - $suma_pedidos_entregados - $suma_pedidos_en_ruta;
        }

        $response = [
            "mensaje" => "Consulta Exitosa",
            "pedidos" => $vehiculos,
        ];


        return response()->json($response, 200);
    }
    /**
     * metodo encargado de listar todos los vehiculos con sus pedidos
     */
    public function ayudantesConPedidos(Request $request)
    {

        //buscar despachos por asistentes 
        $asistentes = DriverAssistant::whereHas('despachos.details', function ($query) use ($request) {
                                                    $query->where('assistant','!=',0);
                                                    //validar codigo de cliente
                                                    if ($request->fecha != '') {
                                                        $query->whereDate('created_at', $request->fecha);
                                                    }
                                                })
                                        ->with([
                                                'despachos.details' => function ($query) use ($request) {
                                                    $query->select('id','order_delivery_id','dispatch','status_order','created_at');
                                                    $query->where('assistant','!=',0);
                                                    if ($request->fecha != '') {
                                                        $query->whereDate('created_at', $request->fecha);
                                                    }
                                                },
                                                'despachos.details.pedido:id,document_order_number,customer_code,created_at',
                                                'despachos.details.pedido.tracking:id,dispatch_id,vehicles_id',
                                                'despachos.details.pedido.tracking.vehiculo:id,placa,driver_id',
                                                'despachos.details.pedido.tracking.vehiculo.conductor:id,names,surnames'

                                        ])
                                        ->where('status',1)->paginate(20);
        //iterar asistentes
        foreach ($asistentes->items() as $asistente) {
            $suma_pedidos = 0;
            $suma_pedidos_entregados = 0;
            $suma_pedidos_en_ruta = 0;
            $cargar_pedidos = [];

            foreach ($asistente->despachos as $despacho) {
                $suma_pedidos = $suma_pedidos + (count($despacho->details));
                $suma_pedidos_entregados = $suma_pedidos_entregados + (count($despacho->details->where('status_order', 'Entregado')));
                $suma_pedidos_en_ruta = $suma_pedidos_en_ruta + (count($despacho->details->where('status_order', 'En Ruta'))) + (count($despacho->details->where('status_order', 'Punto de Destino')));


                if (count($despacho->details) > 0) {
                    foreach ($despacho->details as $detail) {
                        array_push($cargar_pedidos, $detail);
                    }
                }
            }
            
            $asistente->pedidos = $cargar_pedidos;

            $asistente->total_pedidos = $suma_pedidos;
            $asistente->total_pedidos_entregados = $suma_pedidos_entregados;
            $asistente->total_pedidos_en_ruta = $suma_pedidos_en_ruta;
            $asistente->total_pedidos_pendientes = $suma_pedidos - $suma_pedidos_entregados - $suma_pedidos_en_ruta;
        }
      

        $response = [
            "mensaje" => "Consulta Exitosa",
            "pedidos" => $asistentes,
        ];

        return response()->json($response, 200);
         
       
    }

    /**
     * metodo encargado de listar los pedidos empaquetados
     */
    public function pedidosEmpaquetados(Request $request)
    {
        //buscar pedidos empaquetados para lima
        $buscar_pedidos_lima = Dispatch::whereHas('tracking', function ($query) {
                $query->whereNull('vehicles_id');
                $query->where('is_packed', 1);
                $query->where('is_approved', 1);
                $query->whereIn('department', ["15", "07"]);
                $query->where('carrier_code', '003174');
                $query->where('transit_status_id', '!=', '11');
            })
            ->with('tracking.details.transit', 'tracking.transit', 'client.direcciones')
            ->where('registration_status', 'A')
            ->where('warehouse_code', '22')
            ->whereDate('created_at','>', '2022-10-27')
            ->orderBy('document_date', 'asc')
            ->get();  

        //buscar pedidos empaquetados para provincia
        $buscar_pedidos_provincia = Dispatch::whereHas('tracking', function ($query) {
                        $query->whereNull('vehicles_id');
                        $query->where('is_packed', 1);
                        $query->where('is_approved', 1);
                        //$query->where('department','!=', "15");
                        //$query->where('department','!=', "07");
                        $query->where('carrier_code', '!=', '003151');
                        $query->where('carrier_code', '!=', '003174');
                        $query->where('transit_status_id', '!=', '11');
            })
            ->with('tracking.details.transit', 'tracking.transit', 'client.direcciones')
            ->where('registration_status', 'A')
            ->where('warehouse_code', '22')
            ->whereDate('created_at','>', '2022-10-27')
            ->orderBy('document_date', 'asc')
            ->get();


        //obtener los clientes lima
        $pedidos_agrupados_lima = $buscar_pedidos_lima->groupBy('customer_id')->toArray();
        $clientes_lima = array_keys($pedidos_agrupados_lima);

        //obtener los clientes provincia
        $pedidos_agrupados_provincia = $buscar_pedidos_provincia->groupBy('tracking.carrier_code')->toArray();
        $clientes_provincia = array_keys($pedidos_agrupados_provincia);

        $pedidos_lima = [];
        $pedidos_Provincia = [];

        //ajustar al select de clientes en lima
        foreach ($clientes_lima as $cliente_id) {

            //agregar el nombre del cliente
            $label_cliente = $pedidos_agrupados_lima[$cliente_id][0]["client"]["name_social_reason"];

            //agregar las opciones
            $opciones = [];


            //iterar los pedidos del cliente
            foreach ($pedidos_agrupados_lima[$cliente_id] as $registro) {

                array_push($opciones, [
                    "value" => $registro["id"],
                    "label" => $registro["customer_code"] . " PED " . $registro["document_order_number"] . " FEC " . $this->formatearFecha($registro["document_date"])
                ]);
            }
            //crear el arreglo en el formato correcto
            $nuevo_arreglo = [
                "label" => $label_cliente,
                "options" => $opciones
            ];

            array_push($pedidos_lima, $nuevo_arreglo);
        }

        //ajustar al select de clientes en provincia
        foreach ($clientes_provincia as $cliente_id) {

            $agencia = GenResourceDetail::where('resource_id', 23)->where('code', $cliente_id)->first();
            //agregar el nombre del cliente
            //$label_cliente = $agencia ? trim($agencia->name) : $pedidos_agrupados_provincia[$cliente_id][0]["client"]["direcciones"][0]["region"];
            $label_cliente = $agencia ? trim($agencia->name) : '';

            //agregar las opciones
            $opciones = [];

            //iterar los pedidos del cliente
            foreach ($pedidos_agrupados_provincia[$cliente_id] as $registro) {

                array_push($opciones, [
                    "value" => $registro["id"],
                    "label" => $registro["customer_code"] . " Ped " . $registro["document_order_number"] ." Fec ". $this->formatearFecha($registro["document_date"])
                ]);
            }
            //crear el arreglo en el formato correcto
            $nuevo_arreglo = [
                "label" => $label_cliente,
                "options" => $opciones
            ];

            array_push($pedidos_Provincia, $nuevo_arreglo);
        }



        $response = [
            "mensaje" => "Consulta Exitosa",
            "pedidos_lima" => $pedidos_lima,
            "pedidos_provincia" => $pedidos_Provincia,
        ];


        return response()->json($response, 200);
    }


    public function formatearFecha($fecha)
    {
        $recortar = explode('-',$fecha);

        $fecha_formateada =  "$recortar[2]-$recortar[1]-$recortar[0]";
        
        return $fecha_formateada;
    }

        /**
     * metodo encargado de listar todos los pedidos por vendedor
     */
    public function obtenerDatosPedido(Request $request)
    {
        $columnas_pedido = ['id', 'social_reason', 'customer_id', 'document_order_number', 'social_reason', 'date_of_work', 'served_by', 'registration_status','referral_guide_number','customer_contact_id'];
        //buscar pedidos 
        $pedido = Dispatch::select($columnas_pedido)
            ->with([
                'tracking:id,dispatch_id,transit_status_id,vehicles_id',
                'tracking.details.transit',
                'details',
                'calificacion',
                'tracking.transit:id,description_web',
                'client:id,document_type_id,code,document_number',
                'client.document:id,resource_id,abrv',
                'detail:id,customer_id,tipo_entrega,dispatch,order_delivery_id,peso,bultos',
                'tracking.details' => function ($query) {
                    
                    $query->validos();
                    $query->orderBy('id', 'asc');
                }
            ])
            ->where('id',$request->id)->first();
    

        return response()->json($pedido, 200);
    }
}
