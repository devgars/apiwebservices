<?php

namespace App\Http\Controllers\MMTrack;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\VehiculosExport;
use Maatwebsite\Excel\Facades\Excel;
use DB;
use PhpParser\ErrorHandler\Collecting;

class ReportesController extends Controller
{

    public function pedidos(Request $request)
    {
        
        //filtros
        $fecha_desde   =  $request->fecha_desde ? $request->fecha_desde : date('Y-m-d');
        $fecha_hasta   =   $request->fecha_hasta ? $request->fecha_hasta : date('Y-m-d');
        $numero_pedido =  $request->numero_pedido;
        $codigo_cliente = $request->codigo_cliente;
       
        //buscar pedidos 
        $pedidos = DB::table('v_reporte_pedidos')
                    ->whereBetween('fecha_documento',[$fecha_desde,$fecha_hasta]);
                    
        //filtro de numero de pedidos
        if($numero_pedido){
            $pedidos = $pedidos->where('numero_pedido',$numero_pedido);
        }   

        //filtro de codigo de cliente
        if($codigo_cliente){
            $pedidos = $pedidos->where('codigo_cliente',$codigo_cliente);
        }

        $pedidos = $pedidos->orderBy('fecha_documento', 'desc')->paginate(10);
        

        //iterar pedidos
        foreach ($pedidos->items() as $pedido_item) {
            
            //agregar detalles de tracking al pedido
            //iterar los detalles del tracking
            foreach (json_decode($pedido_item->detalles) as $detalle) {
                # code...
                $this->agregarDetalleTracking($pedido_item,$detalle);
            }
            
            $pedido_item->detalles = null;
            
        }
 

        return response()->json($pedidos, 200);
    }
    public function pedidosExcel(Request $request)
    {
        //filtros
        $fecha_desde   =  $request->fecha_desde ? $request->fecha_desde : date('Y-m-d');
        $fecha_hasta   =   $request->fecha_hasta ? $request->fecha_hasta : date('Y-m-d');
        $numero_pedido =  $request->numero_pedido;
        $codigo_cliente = $request->codigo_cliente;
       
        //buscar pedidos 
        $pedidos = DB::table('v_reporte_pedidos')
                    ->whereBetween('fecha_documento',[$fecha_desde,$fecha_hasta]);
                    
        //filtro de numero de pedidos
        if($numero_pedido){
            $pedidos = $pedidos->where('numero_pedido',$numero_pedido);
        }   

        //filtro de codigo de cliente
        if($codigo_cliente){
            $pedidos = $pedidos->where('codigo_cliente',$codigo_cliente);
        }

        $pedidos = $pedidos->orderBy('fecha_documento', 'desc')->take(700)->get();
        
        //iterar pedidos
        foreach ($pedidos as $pedido_item) {
            
            //agregar detalles de tracking al pedido
            //VALIDAR DETALLES
            if ($pedido_item->detalles) {
                
                //iterar los detalles del tracking
                foreach (json_decode($pedido_item->detalles) as $detalle) {
                    # code...
                    $this->agregarDetalleTracking($pedido_item,$detalle);
                }
                
            }
            $pedido_item->detalles = null;
            
        }

        return $pedidos;
    }

    public function agregarDetalleTracking($pedido_item, $detalle)
    {
  
        //agregar pedido recibido 
        if ($detalle->transito_id==2) {
            $pedido_item->pedido_recibido = true;
            $pedido_item->pedido_recibido_codigo = $detalle->transito_id;
            $pedido_item->pedido_recibido_descripcion = $detalle->descripcion;
            $pedido_item->pedido_recibido_fecha = $detalle->fecha;
            $pedido_item->pedido_recibido_hora = $detalle->hora;
            $pedido_item->pedido_recibido_usuario = $detalle->usuario;
        }
        //agregar pedido aprobado 
        if ($detalle->transito_id==3) {
            $pedido_item->pedido_aprobado = true;
            $pedido_item->pedido_aprobado_codigo = $detalle->transito_id;
            $pedido_item->pedido_aprobado_descripcion = $detalle->descripcion;
            $pedido_item->pedido_aprobado_fecha = $detalle->fecha;
            $pedido_item->pedido_aprobado_hora = $detalle->hora;
            $pedido_item->pedido_aprobado_usuario = $detalle->usuario;
        }
        //agregar pedido sacador 
        if ($detalle->transito_id==6) {
            $pedido_item->pedido_sacador = true;
            $pedido_item->pedido_sacador_codigo = $detalle->transito_id;
            $pedido_item->pedido_sacador_descripcion = $detalle->descripcion;
            $pedido_item->pedido_sacador_fecha = $detalle->fecha;
            $pedido_item->pedido_sacador_hora = $detalle->hora;
            $pedido_item->pedido_sacador_usuario = $detalle->usuario;
        }
        //agregar pedido empaquetado 
        if ($detalle->transito_id==9) {
            $pedido_item->pedido_empaquetado = true;
            $pedido_item->pedido_empaquetado_codigo = $detalle->transito_id;
            $pedido_item->pedido_empaquetado_descripcion = $detalle->descripcion;
            $pedido_item->pedido_empaquetado_fecha = $detalle->fecha;
            $pedido_item->pedido_empaquetado_hora = $detalle->hora;
            $pedido_item->pedido_empaquetado_usuario = $detalle->usuario;
        }
        //agregar pedido guia 
        if ($detalle->transito_id==4) {
            $pedido_item->pedido_guia = true;
            $pedido_item->pedido_guia_codigo = $detalle->transito_id;
            $pedido_item->pedido_guia_descripcion = $detalle->descripcion;
            $pedido_item->pedido_guia_fecha = $detalle->fecha;
            $pedido_item->pedido_guia_hora = $detalle->hora;
            $pedido_item->pedido_guia_usuario = $detalle->usuario;
        }
        //agregar pedido facturado 
        if ($detalle->transito_id==5) {
            $pedido_item->pedido_facturado = true;
            $pedido_item->pedido_facturado_codigo = $detalle->transito_id;
            $pedido_item->pedido_facturado_descripcion = $detalle->descripcion;
            $pedido_item->pedido_facturado_fecha = $detalle->fecha;
            $pedido_item->pedido_facturado_hora = $detalle->hora;
            $pedido_item->pedido_facturado_usuario = $detalle->usuario;
        }
        //agregar pedido en_ruta 
        if ($detalle->transito_id==31) {
            $pedido_item->pedido_en_ruta = true;
            $pedido_item->pedido_en_ruta_codigo = $detalle->transito_id;
            $pedido_item->pedido_en_ruta_descripcion = $detalle->descripcion;
            $pedido_item->pedido_en_ruta_fecha = $detalle->fecha;
            $pedido_item->pedido_en_ruta_hora = $detalle->hora;
            $pedido_item->pedido_en_ruta_usuario = $detalle->usuario;
        }
        //agregar pedido entregado 
        if ($detalle->transito_id==32) {
            $pedido_item->pedido_entregado = true;
            $pedido_item->pedido_entregado_codigo = $detalle->transito_id;
            $pedido_item->pedido_entregado_descripcion = $detalle->descripcion;
            $pedido_item->pedido_entregado_fecha = $detalle->fecha;
            $pedido_item->pedido_entregado_hora = $detalle->hora;
            $pedido_item->pedido_entregado_usuario = $detalle->usuario;
        }
            
         
       
    }

    public function PedidosExportarExcel(Request $request)
    {
        //filtros
        $pedidos = $this->pedidosExcel($request);

        $itens = $this->itensExcelPedidos($pedidos);

        return Excel::download(new VehiculosExport($itens), 'pedidos.xlsx');
    }

    public function itensExcelPedidos($pedidos)
    {
        $respuesta =  [];

        foreach ($pedidos as $pedido) {
             
            array_push($respuesta,[
                "codigo_sucursal" => $pedido->codigo_sucursal,
                "nombre_sucursal" => $pedido->nombre_sucursal,
                "codigo_almacen" => $pedido->codigo_almacen,
                "nombre_almacen" => $pedido->nombre_almacen,
                "origen" => $pedido->origen,
                "numero_orden" => $pedido->numero_orden,
                "numero_pedido" => $pedido->numero_pedido,
                "codigo_usuario" => $pedido->codigo_usuario,
                "vendedor" => $pedido->vendedor,
                "codigo_cliente" => $pedido->codigo_cliente,
                "rason_social" => $pedido->rason_social,
                "numero_documento" => $pedido->numero_documento,
                "codigo_transportista" => $pedido->codigo_transportista,
                "transportista" => $pedido->transportista,
                "forma_pago" => $pedido->forma_pago,
                "metodo_paga" => $pedido->metodo_paga,
                "condicion_pago" => $pedido->condicion_pago,
                "tipo_documento" => $pedido->tipo_documento,
                "nombre_documento" => $pedido->nombre_documento,
                "fecha_documento" => $pedido->fecha_documento,
                "hora_documento" => $pedido->hora_documento,
                "pedido_recibido" => isset($pedido->pedido_recibido) ? 'SI' : 'NO',
                "pedido_recibido_codigo" => isset($pedido->pedido_recibido) ? $pedido->pedido_recibido_codigo : '',
                "pedido_recibido_descripcio" => isset($pedido->pedido_recibido) ? $pedido->pedido_recibido_descripcion : '',
                "pedido_recibido_fecha" => isset($pedido->pedido_recibido) ? $pedido->pedido_recibido_fecha : '',
                "pedido_recibido_hora" => isset($pedido->pedido_recibido) ? $pedido->pedido_recibido_hora : '',
                "pedido_recibido_usuario" =>isset($pedido->pedido_recibido) ?  $pedido->pedido_recibido_usuario : '',
                "pedido_aprobado" => isset($pedido->pedido_aprobado) ? 'SI' : 'NO',
                "pedido_aprobado_codigo" => isset($pedido->pedido_aprobado) ? $pedido->pedido_aprobado_codigo : '',
                "pedido_aprobado_descripcio" => isset($pedido->pedido_aprobado) ? $pedido->pedido_aprobado_descripcion : '',
                "pedido_aprobado_fecha" => isset($pedido->pedido_aprobado) ? $pedido->pedido_aprobado_fecha : '',
                "pedido_aprobado_hora" => isset($pedido->pedido_aprobado) ? $pedido->pedido_aprobado_hora : '',
                "pedido_aprobado_usuario" => isset($pedido->pedido_aprobado) ? $pedido->pedido_aprobado_usuario : '',
                "pedido_sacador" => isset($pedido->pedido_sacador) ? 'SI' : 'NO',
                "pedido_sacador_codigo" => isset($pedido->pedido_sacador) ? $pedido->pedido_sacador_codigo : '',
                "pedido_sacador_descripcion" => isset($pedido->pedido_sacador) ? $pedido->pedido_sacador_descripcion : '',
                "pedido_sacador_fecha" => isset($pedido->pedido_sacador) ? $pedido->pedido_sacador_fecha : '',
                "pedido_sacador_hora" => isset($pedido->pedido_sacador) ? $pedido->pedido_sacador_hora : '',
                "pedido_sacador_usuario" => isset($pedido->pedido_sacador) ? $pedido->pedido_sacador_usuario : '',
                "pedido_empaquetado" => isset($pedido->pedido_empaquetado) ? 'SI' : 'NO',
                "pedido_empaquetado_codigo" => isset($pedido->pedido_empaquetado) ?  $pedido->pedido_empaquetado_codigo: '',
                "pedido_empaquetado_descrip" => isset($pedido->pedido_empaquetado) ?  $pedido->pedido_empaquetado_descripcion: '',
                "pedido_empaquetado_fecha" => isset($pedido->pedido_empaquetado) ?  $pedido->pedido_empaquetado_fecha: '',
                "pedido_empaquetado_hora" => isset($pedido->pedido_empaquetado) ?  $pedido->pedido_empaquetado_hora: '',
                "pedido_empaquetado_usuario" => isset($pedido->pedido_empaquetado) ?  $pedido->pedido_empaquetado_usuario: '',
                "pedido_guia" => isset($pedido->pedido_guia) ? 'SI' : 'NO',
                "pedido_guia_codigo" => isset($pedido->pedido_guia) ? $pedido->pedido_guia_codigo : '',
                "pedido_guia_descripcion" => isset($pedido->pedido_guia) ? $pedido->pedido_guia_descripcion : '',
                "pedido_guia_fecha" => isset($pedido->pedido_guia) ? $pedido->pedido_guia_fecha : '',
                "pedido_guia_hora" => isset($pedido->pedido_guia) ? $pedido->pedido_guia_hora : '',
                "pedido_guia_usuario" => isset($pedido->pedido_guia) ? $pedido->pedido_guia_usuario : '',
                "pedido_facturado" => isset($pedido->pedido_facturado) ? 'SI' : 'NO',
                "pedido_facturado_codigo" =>  isset($pedido->pedido_facturado) ? $pedido->pedido_facturado_codigo : '',
                "pedido_facturado_descripci" =>  isset($pedido->pedido_facturado) ? $pedido->pedido_facturado_descripcion : '',
                "pedido_facturado_fecha" =>  isset($pedido->pedido_facturado) ? $pedido->pedido_facturado_fecha : '',
                "pedido_facturado_hora" =>  isset($pedido->pedido_facturado) ? $pedido->pedido_facturado_hora : '',
                "pedido_facturado_usuario" =>  isset($pedido->pedido_facturado) ? $pedido->pedido_facturado_usuario : '',
                "pedido_en_ruta" => isset($pedido->pedido_en_ruta) ? 'SI' : 'NO',
                "pedido_en_ruta_codigo" => isset($pedido->pedido_en_ruta) ?  $pedido->pedido_en_ruta_codigo : '',
                "pedido_en_ruta_descripcion" => isset($pedido->pedido_en_ruta) ?  $pedido->pedido_en_ruta_descripcion : '',
                "pedido_en_ruta_fecha" => isset($pedido->pedido_en_ruta) ?  $pedido->pedido_en_ruta_fecha : '',
                "pedido_en_ruta_hora" => isset($pedido->pedido_en_ruta) ?  $pedido->pedido_en_ruta_hora : '',
                "pedido_en_ruta_usuario" => isset($pedido->pedido_en_ruta) ?  $pedido->pedido_en_ruta_usuario : '',
                "pedido_entregado" => isset($pedido->pedido_entregado) ? 'SI' : 'NO',
                "pedido_entregado_codigo" => isset($pedido->pedido_entregado) ? $pedido->pedido_entregado_codigo : '',
                "pedido_entregado_descripci" => isset($pedido->pedido_entregado) ? $pedido->pedido_entregado_descripcion : '',
                "pedido_entregado_fecha" => isset($pedido->pedido_entregado) ? $pedido->pedido_entregado_fecha : '',
                "pedido_entregado_hora" => isset($pedido->pedido_entregado) ? $pedido->pedido_entregado_hora : '',
                "pedido_entregado_usuario" => isset($pedido->pedido_entregado) ? $pedido->pedido_entregado_usuario : '',
            ]);
        }


        return collect($respuesta);
    }
    
 
}
