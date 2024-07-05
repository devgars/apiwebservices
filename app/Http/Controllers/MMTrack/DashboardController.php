<?php

namespace App\Http\Controllers\MMTrack;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MMTrack\Dispatch;
use App\Models\MMTrack\DispatchTracking;
use App\Models\MMTrack\Vehicles;
use Carbon\Carbon;
use DB;
use DateTime;
class DashboardController extends Controller
{
    /**
     * metodo encargado de proporcionar la data para el Dashboard
    */
    public function Dashboard(Request $request)
    {
        $fecha = $request->fecha ? $request->fecha : date('Y-m-d');
        $sede = $request->sede; 
        //total de pedidos
        $pedidos_totales = Dispatch::where('date_of_work',$fecha)
                                    ->join('dispatch_tracking', 'dispatch_tracking.dispatch_id', 'dispatch.id')
                                    ->where('dispatch.status', 1)
                                    ->where('dispatch.registration_status', 'A');
        
        //pedidos por delivery
        $pedidos_totales_delivery = Dispatch::join('dispatch_tracking', 'dispatch_tracking.dispatch_id', 'dispatch.id')
                                                ->where('dispatch.date_of_work',$fecha)
                                                ->where('dispatch_tracking.carrier_code','!=','003151')
                                                ->where('dispatch.registration_status', 'A')
                                                ->where('dispatch.status', 1);

        //pedidos por recojo en tienda
        $pedidos_totales_recojo_cliente = Dispatch::join('dispatch_tracking', 'dispatch_tracking.dispatch_id', 'dispatch.id')
                                                    ->where('dispatch.date_of_work',$fecha)                                            
                                                    ->where('dispatch_tracking.carrier_code','003151')
                                                    ->where('dispatch.registration_status', 'A')
                                                    ->where('dispatch.status', 1);

        //buscar pedidos entregados en el dia
        $pedidos_entregados =  Dispatch::join('dispatch_tracking', 'dispatch_tracking.dispatch_id', 'dispatch.id')
                                        ->where('dispatch.date_of_work',$fecha)
                                        ->where('dispatch_tracking.transit_status_id',32)
                                        ->where('dispatch.registration_status', 'A')
                                        ->where('dispatch.status', 1);

         
        //buscar vehiculos disponibles
        $vehiculos_disponibles = Vehicles::where('status', 1)->disponibles()->count();

        //buscar vehiculos totales
        $vehiculos_totales = Vehicles::where('status', 1)->count();

        //buscar vehiculos en ruta
        $vehiculos_en_ruta = Vehicles::where('status', 1)->enRuta()->count();

        //buscar vehiculos preasignados
        $vehiculos_preasignados = Vehicles::where('status', 1)->preasignados()->count();

        //query para consultar fecha de entrega contra fecha de pedido
        $query = "select dispatch.id as id, dispatch.date_of_work as fecha_pedido,
                    (select date_of_work from dispatch_details_tracking where dispatch_tracking_id = dispatch_tracking.id limit 1 ) as fecha_entrega
                    from dispatch inner join dispatch_tracking on dispatch_tracking.dispatch_id = dispatch.id 
                    where dispatch.status = 1 and dispatch.registration_status = 'A' and dispatch_tracking.transit_status_id = 32 and dispatch.date_of_work = '$fecha'";
            
        //validar si existe filtro de sede
        if ($sede!='Todos') {
            $pedidos_totales = $pedidos_totales->where('warehouse_code',$sede);  
            $pedidos_entregados = $pedidos_entregados->where('warehouse_code',$sede); 
            $pedidos_totales_recojo_cliente = $pedidos_totales_recojo_cliente->where('warehouse_code',$sede); 
            $pedidos_totales_delivery = $pedidos_totales_delivery->where('warehouse_code',$sede);    
            
            $query .= "and warehouse_code = '$sede'"; 
        }  

        $pedidos_totales = $pedidos_totales->count();  
        $pedidos_entregados = $pedidos_entregados->count(); 
        $pedidos_totales_recojo_cliente = $pedidos_totales_recojo_cliente->count(); 
        $pedidos_totales_delivery = $pedidos_totales_delivery->count();              

        $listados_pedidos =  DB::select($query);
        $suma_de_dias = 0;

        foreach ($listados_pedidos as $pedido ) {
            $firstDate  = new DateTime($pedido->fecha_pedido);
            $secondDate = new DateTime($pedido->fecha_entrega);
            $intvl = $firstDate->diff($secondDate)->days; 
            $suma_de_dias = $suma_de_dias + $intvl; 
        }
        
        $promedio_entregas = 0;
        //validar si el dia han entregado pedidos
        if (count($listados_pedidos)>0) {
            //promedo de entregas en dias
            $promedio_entregas = $suma_de_dias / count($listados_pedidos);
        }

        $kpis =[];

        if ($pedidos_totales>0) {
            $on_time = 100;
            if ($pedidos_totales_delivery>0) {
                $on_time = round(($pedidos_entregados * 100 / $pedidos_totales_delivery),2);
            }
            $porcentaje_delivery = round(($pedidos_totales_delivery * 100 / $pedidos_totales),2);
            $porcentaje_tienda = round(($pedidos_totales_recojo_cliente * 100 / $pedidos_totales),2);
            //obtener conteo de pedidos
            $kpis =   [
                [
                    "id" => 1,
                    "titulo" => "Pedidos por Delivery",
                    "porcentaje" => round($porcentaje_delivery,2)."%",
                    "total" => "$pedidos_totales_delivery de $pedidos_totales",
                    "color" => "info",
                ],[
                    "id" => 1,
                    "titulo" => "Recojo en Tienda",
                    "total" => "$pedidos_totales_recojo_cliente de $pedidos_totales",
                    "porcentaje" => round($porcentaje_tienda,2)."%",
                    "color" => "success",
                ],[
                    "id" => 1,
                    "titulo" => "Entrega a tiempo",
                    "total" => "$pedidos_entregados de $pedidos_totales_delivery",
                    "porcentaje" => round($on_time,2)."%",
                    "color" => $on_time>50 ? "success":"danger",
                ],[
                    "id" => 1,
                    "titulo" => "Promedio De Entrega",
                    "porcentaje" => round($promedio_entregas,2)." Dias",
                    "total" => $pedidos_entregados,
                    "color" => "primary",
                ]
            ]; 
        }
            
        $conteo_vehiculos =     [
            [
                "titulo" => "Vehiculos Disponibles",
                "total" => "$vehiculos_disponibles de $vehiculos_totales",
                "color" => "success",
                "filtro" => "En Taller"  

            ],
            [
                "titulo" => "Vehiculos Pre-Asignados",
                "total" => $vehiculos_preasignados,
                "color" => "info",
                "filtro" => "Pre Asignados"  

            ],
            [
                "titulo" => "Vehiculos En Ruta",
                "total" => $vehiculos_en_ruta,
                "color" => "warning",
                "filtro" => "En Ruta"  


            ]
 
             
        ];

        //sedes 
        $sedes =  DB::table('v_almacenes_all')->select('warehouse_code','warehouse_name')
            ->where('reg_status', 1)
            ->get();

        $response = [
            "kpis" => $kpis ,
            "conteo_vehiculos" => $conteo_vehiculos,
            "listados_pedidos" => $listados_pedidos,
            "sedes" => $sedes

        ];

        return response()->json($response, 200);
    }

    /**
     * metodo encargado de mostrar vehiculos 
     * segun el tipo de consulta 
     * Disponibles
     * En ruta 
     * Preasignados
     */
    public function obtenerVehiculos($tipo)
    {
        //buscar vehiculos en la base de datos intermedia 
        $vehiculos = Vehicles::select('id','placa','driver_id')->with('conductor:id,names,surnames')->where('status', 1);
       
        //que esten En Taller
        if ($tipo == "En Taller") {
            $vehiculos = $vehiculos->enTaller();
        }       
        //que esten Pre Asignados"
        if ($tipo == "Pre Asignados") {
            $vehiculos = $vehiculos->preasignados();
        }       
        //que esten En Ruta
        if ($tipo == "En Ruta") {
            $vehiculos = $vehiculos->enRuta();
        }

        $vehiculos = $vehiculos->orderBy('id', 'desc') ->get();
    

        return response()->json($vehiculos, 200);
    }

    public function obtenerChartPedidos($fecha, $sede)
    {
        $hasta = Carbon::createFromFormat('Y-m-d', $fecha);
        //restar 5 dias
        $desde  =  $hasta->subDays(10)->format('Y-m-d');

        
        $query_recibidos = "SUM(CASE WHEN dispatch_tracking.transit_status_id = 2 THEN 1 ELSE 0 END) AS recibidos";
        $query_totales = "count(*) AS totales";
        $query_en_preparacion = "SUM(CASE WHEN dispatch_tracking.transit_status_id in (3,4,5,6,7,8,9,18) THEN 1 ELSE 0 END) AS en_preparacion";
        $query_entregados = "SUM(CASE WHEN dispatch_tracking.transit_status_id = 32 THEN 1 ELSE 0 END) AS entregados";

        $registros = Dispatch::select(DB::raw('DATE(dispatch.date_of_work) as dia'),DB::raw($query_recibidos),DB::raw($query_totales),DB::raw($query_en_preparacion),DB::raw($query_entregados))
                                ->join('dispatch_tracking', 'dispatch_tracking.dispatch_id', 'dispatch.id')                            
                                ->whereDate('dispatch.date_of_work','>=',$desde)
                                ->whereDate('dispatch.date_of_work','<=', $fecha)
                                ->where('dispatch.status', 1)
                                ->where('dispatch.registration_status', 'A')
                                ->where('dispatch_tracking.carrier_code','!=','003151');

        //validar si existe filtro de almacen
        if ($sede !='Todos') {
            $registros = $registros->where('dispatch.warehouse_code',$sede);
        }

        $registros = $registros->orderBy('dispatch.date_of_work')->groupBy('dia')->get();

        //armar datos para la grafica de pedidos
        $labels = []; 
        $data_recibidos = []; 
        $data_totales = []; 
        $data_entregados = []; 
        $data_en_preparacion = []; 
        
        foreach ($registros as $registro) {
            //agregar labels
            array_push($labels, substr($registro->dia,5));
            //agregar totales del dia
            array_push($data_totales,$registro->totales);
            //agregar recibidos del dia
            array_push($data_recibidos,$registro->recibidos);
            //agregar entregados del dia
            array_push($data_entregados,$registro->entregados);
            //agregar en preparacion del dia
            array_push($data_en_preparacion,$registro->en_preparacion);
        }

        //crear arreglo para respuesta
        $respuesta_pedidos = [
            'labels' =>$labels,
            'totales' =>$data_totales,
            'recibidos' =>$data_recibidos,
            'entregados' =>$data_entregados,
            'en_preparacion' =>$data_en_preparacion,
        ];

        //consulta para pedidos por grupo
        $query_lima = "SUM(CASE WHEN dispatch_tracking.carrier_code = '003174' THEN 1 ELSE 0 END) AS lima";
        $query_provincia = "SUM(CASE WHEN dispatch_tracking.carrier_code != '003151' and  dispatch_tracking.carrier_code != '003174'  THEN 1 ELSE 0 END) AS provincia";
        $query_cliente = "SUM(CASE WHEN dispatch_tracking.carrier_code = '003151' THEN 1 ELSE 0 END) AS cliente";

        $registros_grupo = Dispatch::select(DB::raw('DATE(dispatch.date_of_work) as dia'),DB::raw($query_cliente),DB::raw($query_provincia),DB::raw($query_lima))
                                    ->join('dispatch_tracking', 'dispatch_tracking.dispatch_id', 'dispatch.id')                            
                                    ->whereDate('dispatch.date_of_work','>=',$desde)
                                    ->whereDate('dispatch.date_of_work','<=', $fecha)
                                    ->where('dispatch.registration_status', 'A')
                                    ->where('dispatch.status', 1);
                                    

        //validar si existe filtro de almacen
        if ($sede) {
            $registros_grupo = $registros_grupo->where('dispatch.warehouse_code',$sede);
        }

        $registros_grupo = $registros_grupo->orderBy('dispatch.date_of_work')->groupBy('dia')->get();  

        $labels_por_grupo =[];
        $data_totales_lima =[];
        $data_totales_provincia =[];
        $data_totales_cliente =[];

        foreach ($registros_grupo as $grupo) {

            //agregar labels
            array_push($labels_por_grupo, substr($grupo->dia,5));
            //agregar pedidos de lima por dia
            array_push($data_totales_lima,$grupo->lima);
            //agregar pedidos de provincia por dia
            array_push($data_totales_provincia,$grupo->provincia);
            //agregar pedidos de cliente por dia
            array_push($data_totales_cliente,$grupo->cliente);

        }

        $respuesta_pedidos_por_grupo = [
            'labels' =>$labels_por_grupo,
            'lima' =>$data_totales_lima,
            'provincia' =>$data_totales_provincia,
            'cliente' =>$data_totales_cliente,
        ];


        return response()->json(['pedidos' =>$respuesta_pedidos, 'prdidos_por_grupo' =>$respuesta_pedidos_por_grupo], 200);

         
    }

    public function gestionPedidosPorAlmacen($fecha,$sede)
    {
        
        $registros = Dispatch::select(DB::raw('count(*) as total'),'warehouse_code')
                                    ->whereDate('date_of_work',$fecha)
                                    ->where('status', 1);
        
        //validar si existe filtro de almacen
        if ($sede != 'Todos') {
            $registros = $registros->where('warehouse_code',$sede);
        }

        $registros = $registros->groupBy('warehouse_code')
                                    ->get();  
        $registros_totales = Dispatch::whereDate('date_of_work',$fecha)->where('status', 1)->count();                         
        $labels = []; 
        $total = []; 
        
        foreach ($registros as $registro) {
            //buscar nombre del almacen
            $almacen = DB::table('v_almacenes_all')->select('warehouse_name')->where('warehouse_code', $registro->warehouse_code)->first();
            //agregar labels
            array_push($labels, $almacen ? trim($almacen->warehouse_name)."\n" : '');
            //agregar total de pedidos por ese almacen
            if ($registros_totales>0) {
                array_push($total, $registro->total * 100 / $registros_totales);
            }
        }

        $respuesta = [
            'labels' =>$labels,
            'total' =>$total,
        ];


        return response()->json($respuesta, 200);

    }


    public function obtenerPedidosFiltrados($fecha, $tipo,$sede)
    {

        //filtrar segun sea el tipo 
        $estados =[];
        //validar estado del pedido
        switch ($tipo) {
            case 'Recibidos':
                $estados = [2];
                break;

            case 'En Preparacion':

                $estados = [3, 4, 5, 6, 7, 8, 9, 18];
                break;

            case 'Entregados':

                $estados = [32];
                break;

            default:

                $estados = [14, 3, 4, 5, 6, 7, 8, 9, 32, 31, 2, 18];
                break;
        }

        
        //buscar pedidos
        $pedidos = Dispatch::select('dispatch.id','dispatch.warehouse_code','dispatch.document_order_number','dispatch.customer_code','dispatch.social_reason')
                            ->join('dispatch_tracking', 'dispatch_tracking.dispatch_id', 'dispatch.id')
                            ->whereDate('date_of_work',$fecha)
                            ->whereIn('dispatch_tracking.transit_status_id',$estados)
                            ->where('dispatch_tracking.carrier_code','!=','003151')
                            ->where('dispatch.status', 1)
                            ->where('dispatch.registration_status', 'A');
                                    //validar si existe filtro de almacen
        if ($sede !='Todos') {
            $pedidos = $pedidos->where('dispatch.warehouse_code',$sede);
        }

 
        $pedidos = $pedidos->orderBy('dispatch.customer_code','desc')->get();  
 

        return response()->json($pedidos, 200);

    }
}
