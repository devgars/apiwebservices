<?php

namespace App\Http\Controllers\MMTrack;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use App\Models\MMTrack\OrderDelivery;
use Illuminate\Support\Facades\Http;
use App\Models\MMTrack\OrderDeliveryDetail;
use App\Models\GenResourceDetail;
use App\Models\CustomerContact;
use App\Models\MMTrack\Dispatch;
use App\Models\MMTrack\DispatchDetailsTracking;
use App\Models\MMTrack\DispatchTracking;
use App\Models\MMTrack\Vehicles;
use App\Models\MMTrack\Transit;
use App\Models\MMTrack\Drivers;
use App\Models\MMTrack\AgencyBranches;
use Illuminate\Support\Facades\Mail;
use Image;
use App\Mail\AlertasPedidos;
use Illuminate\Support\Str;
use Config;
use DB;


class OrderDeliveryController extends Controller
{

    /**
     * metodo encargado de guardar  
     */
    public function guardar(Request $request)
    {
        
        //validando el datos necesarios
        $validacion = Validator::make($request->all(), [
            'vehiculo_id' => 'required',
            'tipo_despacho' => 'required',
            'envios_lima' => 'required_without:envio_provincias',
            'envio_provincias' => 'required_without:envios_lima',
            
        ]);

        //validar si es una preasignacion
        if ($request->preasignado!="false") {
            //eliminar despacho para crear uno nuevo
            OrderDelivery::where('id',$request->preasignado)->delete();
        }

        //verificar si hay errores en la validacion
        if ($validacion->fails()) {
            return response()->json($validacion->errors(), 422);
        }
        
        
        DB::transaction(function () use($request) {
            
            //guardar el registro
            $OrderDelivery = OrderDelivery::Create([   
                'vehiculo_id' => $request->vehiculo_id,
                'type' => $request->tipo_despacho,
                'status' => 1,
            ]);

            //validar si el despacho es inmediato
            if ($request->tipo_despacho=="Inmediato") {
                //cambiar el estado del vehiculo a vehiculo en ruta
                Vehicles::where('id',$request->vehiculo_id)->update(['action_status' => 'En Ruta']);
            }else{
                //cambiar el estado del vehiculo a vehiculo Preasignado
                Vehicles::where('id',$request->vehiculo_id)->update(['action_status' => 'PRE-ASIGNADO']); 
            }

            if ($request->has('envios_lima')) {
                //guardar los detalles que van a lima
                foreach ($request->envios_lima as $envio) {
                    //obtener el id del cliente 
                    $despacho = Dispatch::where('id',$envio)->first();

                    $eliminarPreasignado = null;
                    //validar si es una preasignacion
                    if ($request->preasignado!="false") {
                        $eliminarPreasignado = OrderDeliveryDetail::where('dispatch', $envio)->where('order_delivery_id',$request->preasignado)->first();
                        //eliminar despacho para crear uno nuevo
                        //OrderDelivery::where('id',$request->preasignado)->delete();
                        OrderDeliveryDetail::where('dispatch', $envio)->where('order_delivery_id',$request->preasignado)->delete();
                    }


                    //guardar items
                    OrderDeliveryDetail::Create([   
                        'order_delivery_id' => $OrderDelivery->id,
                        'dispatch' => $envio,
                        'customer_id' => $despacho->customer_id,
                        'shipping_type' => 'Lima',
                        'status_order' => 'Despachado',
                        'status' => 1,
                        'peso' => $eliminarPreasignado ? $eliminarPreasignado->peso : null,
                        'bultos' => $eliminarPreasignado ? $eliminarPreasignado->bultos : null,
                    ]);

                    //agregar cambio al tracking
                    //en movilidad
                    $despacho_tracking = DispatchTracking::where('dispatch_id',$envio)->first();
                    //validar si el despacho es inmediato
                    if ($request->tipo_despacho=="Inmediato") {

                        $despacho_tracking->transit_status_id = 18;
                    }

                    $despacho_tracking->vehicles_id = $request->vehiculo_id;
                    $despacho_tracking->save();

                    //crear el detalle del tracking en movilidad
                    DispatchDetailsTracking::Create([
                        'dispatch_tracking_id' =>  $despacho_tracking->id,
                        'transit_status_id' => 18,
                        'description_status' => 'En Movilidad',
                        'description_status_web' => 'En Preparacion',
                        'observations' => '',
                        'registration_status' => 'A',
                        'program' => 'MMTRACK',
                        'username' => 'SISTEMAS' ,
                        'date_of_work' => date('Y-m-d'),
                        'work_time' => date('H:i:s'),
                        'status' => 1,
                    ]);

                } 
            } 
        
            //validar si existen
            if ($request->has('envio_provincias')) {
                //guardar los detalles que van a provincia
                foreach ($request->envio_provincias as $envio) {
                    //obtener el id del cliente 
                    $despacho = Dispatch::where('id',$envio)->first();
                    
                    $eliminarPreasignado = null;
                    //validar si es una preasignacion
                    if ($request->preasignado!="false") {
                        $eliminarPreasignado = OrderDeliveryDetail::where('dispatch', $envio)->where('order_delivery_id',$request->preasignado)->first();
                        //eliminar despacho para crear uno nuevo
                        //OrderDelivery::where('id',$request->preasignado)->delete();
                        OrderDeliveryDetail::where('dispatch', $envio)->where('order_delivery_id',$request->preasignado)->delete();
                    }

                    //guardar items
                    OrderDeliveryDetail::Create([   
                        'order_delivery_id' => $OrderDelivery->id,
                        'dispatch' => $envio,
                        'customer_id' => $despacho->customer_id,
                        'shipping_type' => 'Provincia',
                        'status_order' => 'Despachado',
                        'status' => 1,
                        'peso' => $eliminarPreasignado ? $eliminarPreasignado->peso : null,
                        'bultos' => $eliminarPreasignado ? $eliminarPreasignado->bultos : null,
                    ]);

                    //agregar cambio al tracking
                    //en movilidad
                    $despacho_tracking = DispatchTracking::where('dispatch_id',$envio)->first();
                    //validar si el despacho es inmediato
                    if ($request->tipo_despacho=="Inmediato") {

                        $despacho_tracking->transit_status_id = 18;
                    }
                    $despacho_tracking->vehicles_id = $request->vehiculo_id;
                    $despacho_tracking->save();

                    //crear el detalle del tracking en movilidad
                    DispatchDetailsTracking::Create([
                        'dispatch_tracking_id' =>  $despacho_tracking->id,
                        'transit_status_id' => 18,
                        'observations' => '',
                        'registration_status' => 'A',
                        'description_status' => 'En Movilidad',
                        'description_status_web' => 'En Preparacion',
                        'program' => 'MMTRACK',
                        'username' => 'SISTEMAS' ,
                        'date_of_work' => date('Y-m-d'),
                        'work_time' => date('H:i:s'),
                        'status' => 1,
                    ]);
                }
            }


        
        });
 


        $response = [
            "mensaje" => "Procesado con Exito",
            "vehiculo" =>'$vehiculo',
        ];

        
       

        return response()->json($response, 200);


    }

    //dar inicio a un despacho preasignado
    public function iniciarDespacho(Request $request)
    {
        //validando el datos necesarios
        $validacion = Validator::make($request->all(), [
            'vehiculo_id' => 'required',
        ]);
 
        //verificar si hay errores en la validacion
        if ($validacion->fails()) {
            return response()->json($validacion->errors(), 422);
        }
        
        DB::transaction(function () use($request) {
            //guardar el registro
            OrderDelivery::where('vehiculo_id',$request->vehiculo_id)
                                            ->where('type', 'Pre-Asignado')
                                            ->where('status', 1)
                                            ->update(['type' => 'Inmediato']);

            //cambiar el estado del vehiculo a vehiculo en ruta
            Vehicles::where('id',$request->vehiculo_id)->update(['action_status' => 'En Ruta']);
       
        });

    }


    public function obtenerDespachosPorConductor($vehiculo_id)
    {
        $columnas = [
            'dispatch.document_order_number',
            'dispatch.customer_code',
            'order_delivery.id',
            'order_delivery.type',
            'order_delivery.status',
            'order_delivery_detail.order_delivery_id',
            'order_delivery_detail.id as order_delivery_detail_id',
            'order_delivery_detail.dispatch',
            'order_delivery_detail.shipping_type',
            'order_delivery_detail.status_order',
            'order_delivery_detail.customer_id',
            'order_delivery_detail.peso',
            'order_delivery_detail.bultos',
            'customers.name_social_reason',
            'vehicles.placa',
            'dispatch_tracking.carrier_code',
            'dispatch_tracking.department',
            'dispatch_tracking.carrier_code_secondary',
            'order_delivery.assistant_id',
        ];

        $despachos_mym = OrderDelivery::select($columnas)
                            ->join('order_delivery_detail','order_delivery.id','order_delivery_detail.order_delivery_id' )
                            ->join('customers','customers.id','order_delivery_detail.customer_id' )
                            ->join('vehicles','vehicles.id','order_delivery.vehiculo_id' )
                            ->join('dispatch','dispatch.id','order_delivery_detail.dispatch' )
                            ->join('dispatch_tracking','dispatch_tracking.dispatch_id','order_delivery_detail.dispatch' )
                            ->where('order_delivery.vehiculo_id',$vehiculo_id)
                            ->where('order_delivery.status',1)
                            ->where('dispatch_tracking.carrier_code',"003174")
                            ->where('order_delivery_detail.status_order','!=','Entregado')
                            ->where('order_delivery_detail.assistant',0)
                            ->get();    

        $despachos_agencias = OrderDelivery::select($columnas)
                            ->join('order_delivery_detail','order_delivery.id','order_delivery_detail.order_delivery_id' )
                            ->join('customers','customers.id','order_delivery_detail.customer_id' )
                            ->join('vehicles','vehicles.id','order_delivery.vehiculo_id' )
                            ->join('dispatch','dispatch.id','order_delivery_detail.dispatch' )
                            ->join('dispatch_tracking','dispatch_tracking.dispatch_id','order_delivery_detail.dispatch' )
                            ->where('order_delivery.vehiculo_id',$vehiculo_id)
                            ->where('order_delivery.status',1)
                            ->where('dispatch_tracking.carrier_code',"!=","003151")
                            ->where('dispatch_tracking.carrier_code',"!=","003174")
                            ->where('order_delivery_detail.status_order','!=','Entregado')
                            ->where('order_delivery_detail.assistant',0)
                            ->get();

        //iterar todos los pedidos de las agencias
        foreach ($despachos_agencias as $despacho_agencia) {
            $despacho_agencia->sucursal = null;
            $despacho_agencia->carrier_code_nuevo =  $despacho_agencia->carrier_code;

            //buscar si ese departamento tiene una sucursal
            $sucursales = AgencyBranches::with('departementos:id,code')
                                        ->where('carrier_code',$despacho_agencia->carrier_code)
                                        ->get();
            
            foreach ($sucursales as $sucursal) {
                //validar si existe sucursal
                if ($sucursal) {

                    //validar que exista el departamento
                    $filtered = $sucursal->departementos->filter(function ($value) use ($despacho_agencia) {
                        return $value->code == $despacho_agencia->department;
                    });

                    if (count($filtered)>0) {
                        
                        $despacho_agencia->carrier_code_nuevo = $despacho_agencia->carrier_code."-".$filtered[0]->id;
                        $despacho_agencia->sucursal = $sucursal->name;
                        
                    }


                } 
            }

        }
        

        //agrupar los registros por clientes
        $despachos_mym = $despachos_mym->groupBy('customer_id');
        //agrupar por agencia
        $despachos_agencias = $despachos_agencias->groupBy('carrier_code_nuevo');
        

        //unir collecciones
        $despachos =  $despachos_mym->concat($despachos_agencias);

        //return response()->json($despachos_mym, 200);
 
        foreach ($despachos as $despacho) {

            //CONSULTAR DIRECCION
            $direccion = DB::table('v_direcciones_entrega')
                            ->select('direccion_completa as direccion_envio','contact_phone as telefono')
                            ->where('customer_id',$despacho[0]->customer_id)
                            ->first();

 
            $direccion_envio = $direccion ? $direccion->direccion_envio : '';
            $agencia_envio = "";
            $agencia_envio_2 = "";
             
            //validar si la agencia tiene sucursal
            $agencia = GenResourceDetail::where('resource_id',23)->where('code',$despacho[0]->carrier_code)->first();
            $agencia2 = GenResourceDetail::where('resource_id',23)->where('code',$despacho[0]->carrier_code_secondary)->first();
            
            if ($agencia) {
                $agencia_envio = trim($agencia->name);
            }            
            if ($agencia2) {
                $agencia_envio_2 = trim($agencia2->name);
            }
            
            $despacho[0]->direccion_envio = $direccion_envio;
            $despacho[0]->direccion_envio = $direccion_envio;
            $despacho[0]->agencia_envio = $agencia_envio;
            $despacho[0]->agencia_envio_2 = $agencia_envio_2;
            $despacho[0]->telefono = $direccion ? $direccion->telefono : '';

            //validar si es mym o agencia
            if ($despacho[0]->carrier_code=="003174") {
                $despacho[0]->titulo = $despacho[0]->name_social_reason;
            }else{
                //validar si tiene sucursal
                $despacho[0]->titulo = $despacho[0]->agencia_envio;
            }
        }


        $agencias = GenResourceDetail::where('resource_id',23)->get();
        $vehiculo = Vehicles::select('placa','id')->where('id',$vehiculo_id)->first();
        
        $response = [
            "mensaje" => "Procesado con Exito",
            "despachos" =>$despachos,
            "agencias" => $agencias,
            "vehiculo" => $vehiculo
        ];

        return response()->json($response, 200);

    
    }

    public function obtenerRegistrosAyudante($usuario_id)
    {
        $columnas = [
            'dispatch.document_order_number',
            'dispatch.customer_code',
            'order_delivery.id',
            'order_delivery.type',
            'order_delivery.status',
            'order_delivery_detail.order_delivery_id',
            'order_delivery_detail.id as order_delivery_detail_id',
            'order_delivery_detail.dispatch',
            'order_delivery_detail.shipping_type',
            'order_delivery_detail.status_order',
            'order_delivery_detail.customer_id',
            'order_delivery_detail.peso',
            'order_delivery_detail.bultos',
            'customers.name_social_reason',
            'vehicles.placa',
            'dispatch_tracking.carrier_code',
            'dispatch_tracking.carrier_code_secondary',
            'order_delivery.assistant_id',
            'order_delivery_detail.assistant_date',
        ];

        $despachos_mym = OrderDelivery::select($columnas)
                            ->join('order_delivery_detail','order_delivery.id','order_delivery_detail.order_delivery_id' )
                            ->join('customers','customers.id','order_delivery_detail.customer_id' )
                            ->join('vehicles','vehicles.id','order_delivery.vehiculo_id' )
                            ->join('dispatch','dispatch.id','order_delivery_detail.dispatch' )
                            ->join('dispatch_tracking','dispatch_tracking.dispatch_id','order_delivery_detail.dispatch' )
                            ->where('order_delivery.assistant_id',$usuario_id)
                            ->where('order_delivery.status',1)
                            ->where('dispatch_tracking.carrier_code',"003174")
                            ->where('order_delivery_detail.status_order','!=','Entregado')
                            ->where('order_delivery_detail.assistant',1)
                            ->orderBy('order_delivery_detail.assistant_date','asc')
                            ->get();    

        $despachos_agencias = OrderDelivery::select($columnas)
                            ->join('order_delivery_detail','order_delivery.id','order_delivery_detail.order_delivery_id' )
                            ->join('customers','customers.id','order_delivery_detail.customer_id' )
                            ->join('vehicles','vehicles.id','order_delivery.vehiculo_id' )
                            ->join('dispatch','dispatch.id','order_delivery_detail.dispatch' )
                            ->join('dispatch_tracking','dispatch_tracking.dispatch_id','order_delivery_detail.dispatch' )
                            ->where('order_delivery.assistant_id',$usuario_id)
                            ->where('order_delivery.status',1)
                            ->where('dispatch_tracking.carrier_code',"!=","003151")
                            ->where('dispatch_tracking.carrier_code',"!=","003174")
                            ->where('order_delivery_detail.status_order','!=','Entregado')
                            ->where('order_delivery_detail.assistant',1)
                            ->orderBy('order_delivery_detail.assistant_date','asc')
                            ->get();

        //agrupar los registros por clientes
        $despachos_mym = $despachos_mym->groupBy('customer_id');
        //agrupar por agencia
        $despachos_agencias = $despachos_agencias->groupBy('carrier_code');
        
        //unir collecciones
        $despachos =  $despachos_mym->concat($despachos_agencias);

        //return response()->json($despachos_mym, 200);
 
        foreach ($despachos as $despacho) {

            //CONSULTAR DIRECCION
            $direccion = DB::table('v_direcciones_entrega')
                            ->select('direccion_completa as direccion_envio','contact_phone as telefono')
                            ->where('customer_id',$despacho[0]->customer_id)
                            ->first();

 
            $direccion_envio = $direccion ? $direccion->direccion_envio : '';
            $agencia_envio = "";
            $agencia_envio_2 = "";
             
            $agencia = GenResourceDetail::where('resource_id',23)->where('code',$despacho[0]->carrier_code)->first();
            $agencia2 = GenResourceDetail::where('resource_id',23)->where('code',$despacho[0]->carrier_code_secondary)->first();
            
            if ($agencia) {
                $agencia_envio = trim($agencia->name);
            }            
            if ($agencia2) {
                $agencia_envio_2 = trim($agencia2->name);
            }
            
            $despacho[0]->direccion_envio = $direccion_envio;
            $despacho[0]->agencia_envio = $agencia_envio;
            $despacho[0]->agencia_envio_2 = $agencia_envio_2;
            $despacho[0]->telefono = $direccion ? $direccion->telefono : '';

            //validar si es mym o agencia
            if ($despacho[0]->carrier_code=="003174") {
                $despacho[0]->titulo = $despacho[0]->name_social_reason;
            }else{
                $despacho[0]->titulo = $despacho[0]->agencia_envio;
            }
        }


        $agencias = GenResourceDetail::where('resource_id',23)->get();
        $vehiculo = null;
        
        $response = [
            "mensaje" => "Procesado con Exito",
            "despachos" =>$despachos,
            "agencias" => $agencias,
            "vehiculo" => $vehiculo
        ];

        return response()->json($response, 200);

    
    }    
    /**
     * Metodo para cambiar estado del envio
     * Param: ID de envio
     */
    public function cambiasEstatus(Request $request)
    {
        $modificaciones = [];
        $modificaciones_tracking = 0;
        $modificaciones_tracking_text = "";

        switch ($request->estatus) {
            case 'En Ruta':
                $modificaciones = ['status_order' => $request->estatus, 'route_start_date'=>date('Y-m-d H:i:s') ];
                $modificaciones_tracking = 31;
                $modificaciones_tracking_text = "En Ruta";

                break;

            case 'Entregado':
                $modificaciones = ['status_order' => $request->estatus, 'deliver_date'=>date('Y-m-d H:i:s') ];
                $modificaciones_tracking = 32;
                $modificaciones_tracking_text = "Entregado al Cliente";

                break;
                    
            case 'Punto de Destino':
                $modificaciones = ['status_order' => $request->estatus, 'arrival_date'=>date('Y-m-d H:i:s') ];
                $modificaciones_tracking_text = "En Punto de Destino";

                break;
            default:
                # code...
                break;
        }

        OrderDeliveryDetail::where('order_delivery_id',$request->order_delivery_id)
                            ->whereIn('dispatch',$request->pedidos)
                            ->update($modificaciones);

        //validar si existe cambio al tracking
        if ($modificaciones_tracking>0) {
            
            for ($i=0;  $i<=count($request->pedidos)-1 ; $i++) { 
                
                $pedigo_modificado = DispatchTracking::where('dispatch_id',$request->pedidos[$i])->first();
                $pedigo_modificado->transit_status_id = $modificaciones_tracking;
                $pedigo_modificado->save();

                //crear el detalle del tracking en movilidad
                DispatchDetailsTracking::Create([
                    'dispatch_tracking_id' =>  $pedigo_modificado->id,
                    'transit_status_id' => $modificaciones_tracking,
                    'observations' => '',
                    'description_status' => $modificaciones_tracking_text,
                    'description_status_web' => $modificaciones_tracking_text,
                    'registration_status' => 'A',
                    'program' => 'MMTRACK',
                    'username' => 'SISTEMAS' ,
                    'date_of_work' => date('Y-m-d'),
                    'work_time' => date('H:i:s'),
                    'status' => 1,
                ]);

                 
                $datos_pedido = Dispatch::select('document_order_number','customer_code','social_reason','customer_contact_id')->where('id',$request->pedidos[$i])->first();
                
                $enlace_web = "https://mmtrack.mym.com.pe/panel/cliente/$datos_pedido->customer_code"; 
                $ws_numero_pedido = $datos_pedido->document_order_number;

                //obtener el mensaje de tracking
                $transito = Transit::where('id',$modificaciones_tracking)->first();

                $ws_mensaje_transito = $transito ? $transito->mensaje : '';
                
                $log_mensaj = "*Mensaje*: $ws_mensaje_transito,  *Pedido*: $ws_numero_pedido, *Enlace*: $enlace_web";
                //enviar notificaciones al clientes
                $this->enviarMensajeWS($log_mensaj, $datos_pedido->customer_contact_id);
                
                
                $data = ['cliente_id' => $datos_pedido->customer_code,
                'cliente' => $datos_pedido->social_reason,
                'pedido' => $datos_pedido->document_order_number,
                'estado'=>$log_mensaj];

                //Mail::to('jmarquina@mym.com.pe')->send(new AlertasPedidos($data)); 


              

            }

        }
 

        return response()->json(['mensaje' => 'Actualizado'], 200);
    }
        
    /**
     * Metodo para cambiar o registra el peso
     * Param: ID de envio
     */
    public function registrarPeso(Request $request)
    {
            
        foreach ($request->clientes as $registro) {
            $registro = json_decode($registro);
             
            $variable = "peso-pedido-".$registro->cliente;
            $variable2 = "bulto-pedido-".$registro->cliente;

            if ($request->get($variable)!=null) {
                OrderDeliveryDetail::whereIn('dispatch',$request->pedidos)
                    ->update([ "peso" => $request->get($variable),"bultos" => $request->get($variable2)]);
            } 
        }
         
        return response()->json(['mensaje' => 'Actualizado'], 200);
    }
    /**
     * Metodo para cancelar estado del envio
     * Param: ID de envio
     */
    public function cancelarEnvio(Request $request)
    {
        
        $modificaciones = ['status_order' => 'Despachado',"observacion" => $request->observacion ];

        OrderDeliveryDetail::where('order_delivery_id',$request->order_delivery_id)
                            //->where('customer_id',$request->customer_id)
                            ->whereIn('dispatch',$request->pedidos)
                            ->update($modificaciones);
            
        for ($i=0;  $i<=count($request->pedidos)-1 ; $i++) { 
                
            $pedigo_modificado = DispatchTracking::where('dispatch_id',$request->pedidos[$i])->first();
            $pedigo_modificado->transit_status_id = 18;
            $pedigo_modificado->save();

                //crear el detalle del tracking en movilidad
                DispatchDetailsTracking::where('dispatch_tracking_id',$pedigo_modificado->id)
                                        ->where('transit_status_id',31)
                                        ->delete();
 
                //obtener el vehiculo asociado
                $vehiculo = Vehicles::where('id',$pedigo_modificado->vehicles_id)->first(); 
                
                //obtener el conductor
                $conductor = Drivers::where('id',$vehiculo->driver_id)->first(); 
                
                //obtener el despacho
                $obtener_despacho = Dispatch::where('id',$request->pedidos[$i])->first(); 
                $obtener_despacho->observaciones = $obtener_despacho->observaciones." <li>".$conductor->names." Cancelo Por: ".$request->observacion." <li>";
                $obtener_despacho->save();

                $log_mensaj="El envio a Su Pedido  $obtener_despacho->document_order_number ha sido *Cancelado* por el siguiente motivo: El conductor cancela envio por *$request->observacion*";
                //enviar mensaje de cancelacion
                $this->enviarMensajeWS($log_mensaj, $obtener_despacho->customer_contact_id);

        }

        
 

        return response()->json(['mensaje' => 'Actualizado'], 200);
    }
    /**
     * Metodo para cambiar Agencia del envio
     * Param: ID de envio
     */
    public function cambiarAgencia(Request $request)
    {
        
        $obtenerUltimaAgencia = DispatchTracking::select('carrier_code')->whereIn('dispatch_id',$request->pedidos)->first();
        
        if ($obtenerUltimaAgencia) {
             
            //cambiar la segunda agencia
            $obtener_despacho = DispatchTracking::whereIn('dispatch_id',$request->pedidos)->update(["carrier_code_secondary" => $obtenerUltimaAgencia->carrier_code,"carrier_code" => $request->agencia]); 

            $agencia = GenResourceDetail::where('resource_id',23)->where('code',$request->agencia)->first();
            $agencia_envio = "";     
            
            if ($agencia) {
                $agencia_envio = trim($agencia->name);
            }

            $log_mensaj="El envio a Su Pedido ha sido *Cambiado a la siguiente agencia *$agencia_envio*";
            //enviar mensaje de cancelacion
            //$this->enviarMensajeWS($log_mensaj, '51957544999');


            return response()->json(['mensaje' => 'Actualizado'], 200);
        }

        return response()->json(['mensaje' => 'No se Encontro Primera Agencia'], 200);

    }
    
 
    public function cerrarEnvio(Request $request)
    { 
 
        //validando el datos necesarios
        $validacion = Validator::make($request->all(), [
            'order_delivery_id' => 'required',
            'dispatch' => 'required',
            'customer_id' => 'required',
            'tipo_entrega' => 'required',
        ]);

        //verificar si hay errores en la validacion
        if ($validacion->fails()) {
            return response()->json($validacion->errors(), 422);
        }

        //obtener los pedidos del cliente despachado
        $envios = OrderDeliveryDetail::where('order_delivery_id',$request->order_delivery_id)
                                        ->whereIn('dispatch', explode(",",$request->dispatch))
                                        ->where('status_order','Punto de Destino')
                                        ->get();

        //crear arreglo para validar cada imagend e pedido
        $arreglo_validar_imagenes = [];

        DB::beginTransaction();

        try {
           
            //iterar los pedidos del cliente y validar las imagenes
            foreach ($envios as $envio) {
 
                //cerrar el pedido como entregado
                OrderDeliveryDetail::where('order_delivery_id',$request->order_delivery_id)
                    //->where('customer_id',$request->customer_id) 
                    ->where('dispatch',$envio->dispatch)
                    ->update([  'tipo_entrega' =>$request->tipo_entrega, 
                                'status_order' => 'Entregado',
                                ]);
                    
                $pedigo_modificado = DispatchTracking::where('dispatch_id',$envio->dispatch)->first();
                $pedigo_modificado->transit_status_id = 32;
                $pedigo_modificado->save();
                
                $datos_pedido = Dispatch::select('document_order_number','customer_code','social_reason','customer_contact_id')->where('id',$envio->dispatch)->first();
                
                $enlace_web = "https://mmtrack.mym.com.pe/panel/cliente/$datos_pedido->customer_code"; 
                
                $ws_numero_pedido = $datos_pedido->document_order_number;

                //obtener el mensaje de tracking
                $transito = Transit::where('id',32)->first();

                $ws_mensaje_transito = $transito ? $transito->mensaje : '';
                
                $log_mensaj = "*Mensaje*: $ws_mensaje_transito,  *Pedido*: $ws_numero_pedido, *Enlace*: $enlace_web";

                //enviar notificaciones al clientes
                $this->enviarMensajeWS($log_mensaj, $datos_pedido->customer_contact_id);
    
                $data = ['cliente_id' => $datos_pedido->customer_code,
                'cliente' => $datos_pedido->social_reason,
                'pedido' => $datos_pedido->document_order_number,
                'estado'=>$log_mensaj];


                //crear el detalle del tracking en movilidad
                DispatchDetailsTracking::Create([
                    'dispatch_tracking_id' =>  $pedigo_modificado->id,
                    'transit_status_id' => 32,
                    'observations' => '',
                    'registration_status' => 'A',
                    'program' => 'MMTRACK',
                    'username' => 'SISTEMAS' ,
                    'description_status' => 'Entregado al Cliente',
                    'description_status_web' => 'Entregado al Cliente',
                    'date_of_work' => date('Y-m-d'),
                    'work_time' => date('H:i:s'),
                    'status' => 1,
                ]);
              

                //validar si aun existen pedidos por entregar de ese despacho
                $contar_despachos = OrderDeliveryDetail::where('order_delivery_id',$request->order_delivery_id)
                                                        ->where('status_order','!=','Entregado')
                                                        ->count();

                if ($contar_despachos==0) {

                    $order = OrderDelivery::where('id',$request->order_delivery_id)->first();
                    $order->status = 3;
                    $order->save();

                    //cambiar el estado del vehiculo a vehiculo en ruta
                    Vehicles::where('id',$order->vehiculo_id)->update(['action_status' => 'Disponible']);

                }
                    
            } 

            DB::commit();
            return response()->json(['mensaje' => 'Actualizado'], 200);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['mensaje' => $e->getMessage()], 502);

        }
        
 
         
 
    }


    /**
     * metodo encargado de enviar notificacion por ws
    */
    public function enviarMensajeWS($mensaje, $contact_id)
    {
        if (Config::get('services.ws_api.conf')) {
            $url = Config::get('services.ws_api.url')."/enviar-mensaje";
        
            //buscar contacto
            //validar que el pedido tenga un contacto
            if (!$contact_id) {
            return;
            }

            $contacto = CustomerContact::where('id',$contact_id)->first();

            if ($contacto) {
                    # code...
                    $response = Http::withoutVerifying()
                                    ->withHeaders(['Cache-Control' => 'no-cache'])
                                    ->withOptions(["verify"=>false])
                                    ->post($url,[   'numero' =>"51$contacto->contact_phone",
                                    'mensaje' =>$mensaje
                                ]);
                
                return $response;
            }
        }
        
        return;

        
    }

    public function enviarMensajeWSPrueba()
    {
        $mensaje = 'prueba';
        $numero_cliente = '51934356241';

        $url = Config::get('services.ws_api.url')."/enviar-mensaje";
         
        echo $url;

        $response = Http::withoutVerifying()
                        ->withHeaders(['Cache-Control' => 'no-cache'])
                        ->withOptions(["verify"=>false,'timeout' => 5,   'connect_timeout' => 5])
                        ->post($url,[   'numero' =>$numero_cliente ,
                        'mensaje' =>$mensaje
                    ]);


        return $response;
        
    }

    /**
     * metodo encargado de hacer un trasbordo
     */
    public function registrarTransbordo(Request $request)
    {
         
        //validando el datos necesarios
        $validacion = Validator::make($request->all(), [
            'vehiculo_id' => 'required',
            'pedido_id' => 'required',
        ]);

        //verificar si hay errores en la validacion
        if ($validacion->fails()) {
            return response()->json($validacion->errors(), 422);
        }

        //buscar order delivery
        $detalle = OrderDeliveryDetail::where('dispatch',$request->pedido_id)->orderBy('id','desc')->first();
        
        //cambiar el vehiculo al que se trasborda 
        DispatchTracking::where('dispatch_id',$request->pedido_id)->update(['vehicles_id'=>$request->vehiculo_id]);
        
        //obtener el delivery
        $delivery = OrderDelivery::where('id',$detalle->order_delivery_id)->first();

        //buscar si el vehiculo tiene un delivery asignado
        $delivery_asignado = OrderDelivery::where('vehiculo_id',$request->vehiculo_id)->where('status', 1)->orderBy('id','desc')->first();


        //validar si no existe un delyvery asignado 
        if (!$delivery_asignado) {
            //guardar el registro de delivery
            $delivery_asignado = OrderDelivery::Create([   
                'vehiculo_id' => $request->vehiculo_id,
                'type' => 'Inmediato',
                'status' => 1,
            ]);
        }

        //crear detalle
        OrderDeliveryDetail::Create([   
            'order_delivery_id' => $delivery_asignado->id,
            'dispatch' => $request->pedido_id,
            'customer_id' => $detalle->customer_id,
            'shipping_type' => $detalle->shipping_type,
            'status_order' => 'Despachado',
            'status' => 1,
        ]);
        
        //eliminarlo del registro antiguo
        OrderDeliveryDetail::where('id',$detalle->id)->delete();
        
        //validar si aun existen pedidos por entregar de ese despacho
        $contar_despachos = OrderDeliveryDetail::where('order_delivery_id',$detalle->order_delivery_id)
            ->where('status_order','!=','Entregado')
            ->count();
        
        
        if ($contar_despachos==0) {

            $order = OrderDelivery::where('id',$detalle->order_delivery_id)->first();
            $order->status = 3;
            $order->save();

            //cambiar el estado del vehiculo a vehiculo en ruta
            Vehicles::where('id',$order->vehiculo_id)->update(['action_status' => 'Disponible']);

        }

        //cambiar el estado del vehiculo a vehiculo en ruta
        Vehicles::where('id',$request->vehiculo_id)->update(['action_status' => 'En Ruta']);
        
        return response()->json(['mensaje' => 'Actualizado', "detalle" => $detalle, "contar_despachos" => $contar_despachos], 200);
    }  
    
    /**
    * metodo encargado de hacer un trasbordo
    */
    public function registrarDescarga(Request $request)
    {
            
        //validando el datos necesarios
        $validacion = Validator::make($request->all(), [
            'pedido_id' => 'required',
            'vehiculo_id' => 'required',
        ]);
        
        //verificar si hay errores en la validacion
        if ($validacion->fails()) {
            return response()->json($validacion->errors(), 422);
        }

        //buscar order delivery
        $detalle = OrderDeliveryDetail::where('dispatch',$request->pedido_id)->orderBy('id','desc')->first();
        
        //validar que exista un detalle
        if (!$detalle) {
            return response()->json(['mensaje' => 'Ya no Se encuentra el Registro', "detalle" => $detalle], 200);
        }
        //dejar el despacho sin vehiculo
        DispatchTracking::where('dispatch_id',$request->pedido_id)->update(['vehicles_id'=>null]);
        
        //obtener el delivery
        $delivery = OrderDelivery::where('id',$detalle->order_delivery_id)->first();

        //buscar si el vehiculo tiene un delivery asignado
        $delivery_asignado = OrderDelivery::where('vehiculo_id',$request->vehiculo_id)->where('status', 1)->orderBy('id','desc')->first();
        
        //eliminarlo del registro antiguo
        OrderDeliveryDetail::where('id',$detalle->id)->delete();
        
        //validar si aun existen pedidos por entregar de ese despacho
        $contar_despachos = OrderDeliveryDetail::where('order_delivery_id',$detalle->order_delivery_id)
            ->where('status_order','!=','Entregado')
            ->count();
        
        
        if ($contar_despachos==0) {

            $order = OrderDelivery::where('id',$detalle->order_delivery_id)->first();
            $order->status = 3;
            $order->save();

            //cambiar el estado del vehiculo a vehiculo en ruta
            Vehicles::where('id',$order->vehiculo_id)->update(['action_status' => 'Disponible']);

        }


        return response()->json(['mensaje' => 'Actualizado', "detalle" => $detalle, "contar_despachos" => $contar_despachos], 200);
    } 

    /**
    * metodo encargado de hacer un trasbordo
    */
    public function registrarDescargaMasiva(Request $request)
    {
            
        //validando el datos necesarios
        $validacion = Validator::make($request->all(), [
            'pedidos' => 'required',
            'vehiculo_id' => 'required',
        ]);
        
        //verificar si hay errores en la validacion
        if ($validacion->fails()) {
            return response()->json($validacion->errors(), 422);
        }

        foreach ($request->pedidos as $pedido_id) {
            //buscar order delivery
            $detalle = OrderDeliveryDetail::where('dispatch',$pedido_id)->where('status_order','Despachado')->orderBy('id','desc')->first();
            
            //validar que exista un detalle
            if ($detalle) {
                //dejar el despacho sin vehiculo
                DispatchTracking::where('dispatch_id',$pedido_id)->update(['vehicles_id'=>null]);
                
                //obtener el delivery
                $delivery = OrderDelivery::where('id',$detalle->order_delivery_id)->first();
                
                //buscar si el vehiculo tiene un delivery asignado
                $delivery_asignado = OrderDelivery::where('vehiculo_id',$request->vehiculo_id)->where('status', 1)->orderBy('id','desc')->first();
            
                //eliminarlo del registro antiguo
                OrderDeliveryDetail::where('id',$detalle->id)->delete();
            
                //validar si aun existen pedidos por entregar de ese despacho
                $contar_despachos = OrderDeliveryDetail::where('order_delivery_id',$detalle->order_delivery_id)
                    ->where('status_order','!=','Entregado')
                    ->count();
            
            
                if ($contar_despachos==0) {
                    
                    $order = OrderDelivery::where('id',$detalle->order_delivery_id)->first();
                    $order->status = 3;
                    $order->save();
                    
                    //cambiar el estado del vehiculo a vehiculo en ruta
                    Vehicles::where('id',$order->vehiculo_id)->update(['action_status' => 'Disponible']);
                    
                }
            }
            
        }

        return response()->json(['mensaje' => 'Actualizado', "detalle" => $detalle, "contar_despachos" => $contar_despachos], 200);
    }

    public function obtenerFotos($id)
    {
        
        $fotos = OrderDeliveryDetail::select('imagen','imagen_bultos')->where('id',$id)->first();
       
        //$foto_peso = substr($fotos->image_64, 0,5)=='data:' ? $fotos->image_64 : "data:image/jpeg;base64,$fotos->image_64";
        //$foto_bulto = substr($fotos->image_64_bultos, 0,5)=='data:' ? $fotos->image_64_bultos : "data:image/jpeg;base64,$fotos->image_64_bultos";
       
        $foto_peso = "192.168.1.190/$fotos->imagen";
        $foto_bulto = "192.168.1.190/$fotos->imagen_bultos";

        if (!$foto_peso) {
            $foto_peso = substr($fotos->image_64, 0,5)=='data:' ? $fotos->image_64 : "data:image/jpeg;base64,$fotos->image_64";
            $foto_bulto = substr($fotos->image_64_bultos, 0,5)=='data:' ? $fotos->image_64_bultos : "data:image/jpeg;base64,$fotos->image_64_bultos";
        }
       

        $imagenes = [
            [
                "original" => $foto_peso,
                "thumbnail" => $foto_peso,
            ],
            [
                "original" => $foto_bulto,
                "thumbnail" => $foto_bulto,
            ]
        ];

        
        return response()->json(['mensaje' => 'Actualizado', "imagenes" =>$imagenes], 200);


    }

    /**
     * metodo encargado de guardar las imagenes en segundo plano
     */
    public function guardarImagen(Request $request)
    {
        $pedidos = OrderDeliveryDetail::select('id','dispatch')
                                        ->where('order_delivery_id',$request->order_id)
                                        ->where('customer_id',$request->customer_id)->get();

        foreach ($pedidos as $pedido) {

            $imagen = $request->file('imagen');
            $extencion = $imagen->guessExtension();
            $tipo = $request->tipo;

            $nombre_carpeta = "mmtrack/cliente$request->customer_id";   
            $nombre_imagen = "pedido$pedido->dispatch-imagen$tipo.$extencion";   

            //crear directorio
            Storage::makeDirectory($nombre_carpeta);

            //ruta de la imagen
            $ruta = storage_path()."/app/$nombre_carpeta/$nombre_imagen";

            //guardar la imagen con 1200 pixeles
            Image::make($imagen)
            ->resize(1200, null, function ($constraint) {
            $constraint->aspectRatio();
            })->save($ruta);

            if ($tipo == 'peso') {
                OrderDeliveryDetail::where('id',$pedido->id)->update(['imagen' => $ruta]);
            }else{
                OrderDeliveryDetail::where('id',$pedido->id)->update(['imagen_bultos' => $ruta]);
            }
        }

        return response()->json(['mensaje' => 'Actualizado'], 200);
    }
 
}
