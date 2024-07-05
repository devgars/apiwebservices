<?php

namespace App\Http\Controllers\MMTrack;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MMTrack\Vehicles;
use App\Models\MMTrack\CarBrands;
use App\Models\MMTrack\TypeOfVehicles;
use App\Models\MMTrack\Drivers;
use App\Models\GenResourceDetail;
use DB;
use Validator;


class VehiculosController extends Controller
{
    public function index(Request $request)
    {

        $vehiculos = null;

        if ($request->mostrar_inactivos == 0) {
            //buscar vehiculos en la base de datos intermedia 
            //que esten activos
            $vehiculos = Vehicles::with('marca', 'tipo', 'conductor')->where('status', 1)->orderBy('id', 'desc')->get();
        } else {
            //buscar vehiculos en la base de datos intermedia 
            //que esten activos e inactivos
            $vehiculos = Vehicles::with('marca', 'tipo', 'conductor')->orderBy('id', 'desc')->get();
        }



        //buscar las marcas de los vehiculos
        $marcas_vehiculo = GenResourceDetail::where('resource_id', 26)->orderBy('name')->get();
        //buscar los tipos de los vehiculos
        $tipos_vehiculo = GenResourceDetail::where('resource_id', 15)->orderBy('name')->get();

        //buscar conductores
        $conductores = Drivers::where('status', 1)->orderBy('id', 'desc')->get();

        //sedes 
        $sedes =  DB::table('v_almacenes_recojo_mym')
            ->where('reg_status', 1)
            ->get();



        $response = [
            "mensaje" => "Consulta Exitosa",
            "vehiculos" => $vehiculos,
            "marcas_vehiculo" => $marcas_vehiculo,
            "tipos_vehiculo" => $tipos_vehiculo,
            "mostrar_inactivos" => $request->mostrar_inactivos,
            "conductores" => $conductores,
            "sedes" => $sedes
        ];


        return response()->json($response, 200);
    }

    /**
     * metodo encargado de guardar los vehiculos
     */
    public function guardar(Request $request)
    {

        //validando el datos necesarios
        $validacion = Validator::make($request->all(), [
            'color' => 'required',
            'description' => 'required',
            'marca' => 'required',
            'maxima_velocidad' => 'required',
            'modelo' => 'required',
            'placa' => 'required',
            'tipo' => 'required',
            'estatus' => 'required',
            'sede' => 'required',
            'capacidad_kilogramos' => 'required',
            'capacidad_volumen' => 'required',

        ]);

        //verificar si hay errores en la validacion
        if ($validacion->fails()) {
            return response()->json($validacion->errors(), 422);
        }

        //limpiar conductor de vehiculo existente
        Vehicles::where('driver_id', $request->conductor_id)->update(['driver_id' => null]);

        //guardar el vehiculo
        $vehiculo = Vehicles::updateOrCreate(
            ['id' => $request->id],

            [
                'color' => $request->color,
                'description' => $request->description,
                'car_brands_id' => $request->marca,
                'maximum_speed' => $request->maxima_velocidad,
                'model' => $request->modelo,
                'placa' => $request->placa,
                'vehicle_type_id' => $request->tipo,
                'status' => 1,
                'action_status' => $request->estatus ,
                'driver_id' => $request->conductor_id,
                'sede' => $request->sede,
                'capacidad_kilogramos' => $request->capacidad_kilogramos,
                'capacidad_volumen' => $request->capacidad_volumen,

            ]
        );

        //validar si el conductor es  distinto a null



        $response = [
            "mensaje" => "Procesado con Exito",
            "vehiculo" => $vehiculo,
        ];




        return response()->json($response, 200);
    }

    /**
     * Metodo para cambiar estado del vehiculo
     * Param: ID de pausa
     */
    public function cambiasEstatus($id)
    {
        $Vehiculo = Vehicles::findOrFail($id);
        $nuevoEstado = $Vehiculo->status == 1 ? 0 : 1;
        $Vehiculo->status = $nuevoEstado;
        $Vehiculo->save();
        return response()->json(['mensaje' => 'Estado actualizado'], 200);
    }

    public function ObtenerVehiculos($accion)
    {
        $estados = [$accion, 'PRE-ASIGNADO'];

        //buscar vehiculos en la base de datos intermedia 
        //que esten activos
        $vehiculos = Vehicles::with(['marca', 'tipo', 'despachos.details','conductor', 'despachos' =>  function ($query) {
            $query->where('status', 1)->where('type', 'Pre-Asignado')->orderBy('id', 'desc')->first();
        }])
            ->where('status', 1)
            ->where('sede', 16)
            ->whereIn('action_status', $estados)
            ->orderBy('id', 'desc')
            ->get();

        $response = [
            "mensaje" => "Consulta Exitosa1",
            "vehiculos" => $vehiculos,
            "action_status" => $estados,

        ];


        return response()->json($response, 200);
    }

    public function ObtenerVehiculosActivos()
    {

        //buscar vehiculos en la base de datos intermedia 
        //que esten activos
        $vehiculos = Vehicles::select('id', 'placa')
            ->where('status', 1)
            //->where('action_status', "!=", "En Ruta")
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($vehiculos, 200);
    }


    /**
     * Metodo para cambiar conductor del vehiculo
     * Param: ID de pausa
     */
    public function cambiarVehiculo(Request $request)
    {

        //buscar vehiculo anterior y eliminar el conductor
        Vehicles::where('id', $request->vehiculo_id_anterior)->update(['driver_id' => null]);

        $conductor = Drivers::where('user_id', $request->usuario_id)->first();
        //agregar el nuevo conductor
        Vehicles::where('id', $request->vehiculo_id)->update(['driver_id' => $conductor->id]);

        return response()->json(['mensaje' => 'Estado actualizado'], 200);
    }
}
