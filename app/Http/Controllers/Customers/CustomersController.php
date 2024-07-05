<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Vistas\VCustomers;
use App\Models\Vistas\VCustomerPaymentMethods;
use App\Models\Vistas\Direcciones;
use Illuminate\Support\Facades\Route;

class CustomersController extends Controller
{
    public function get_customer_by_code(Request $request)
    {
        try {
            $userId = (request()->user()->id) ? request()->user()->id : 0;
            if (!$datos_cliente = VCustomers::select(['customer_code AS idCliente', 'company_type_code AS tipoEmpresa', 'document_type_code AS tipoDocIdentida', 'document_number AS nroDocIdentida', 'name_social_reason AS razonSocial', 'client_class AS claseClienteId'])
                ->where('customer_code', $request->idCliente)->first()) {
                $response = new \stdClass;
                $response->mensaje = 'Código de cliente (' . $request->idCliente . ') no existe';

                $datos_request = new \stdClass;
                $datos_request->idCliente = $request->idCliente;
                $datos_request->userId = $userId;
                $datos_request->all = $request->all();

                $arrayLogInsert = array(
                    'method' => 'get_customer_by_code',
                    'system' => 'Ecommerce',
                    'user' => $userId,
                    'json_request_values' => json_encode($datos_request),
                    'json_response_values' => json_encode($response),
                    'created_at' => date("Y-m-d H:i:s")
                );
                DB::table('api_logs')->insertGetId($arrayLogInsert);
                return response()->json($response, 400);
            }
            $datos_cliente->clientemodalidadpago = VCustomerPaymentMethods::select(['customer_code AS idCliente', 'way_to_pay_code AS formaPago', 'payment_modality_code AS modalidadPago', 'payment_condition_code AS condicionPago'])
                ->where('customer_id', $datos_cliente->customer_id)->get();
            $datos_cliente->clientedireccion = Direcciones::select(['address_order AS item', 'customer_code AS idCliente', 'number AS nroDireccion', 'dist_code AS distrito', 'prov_code AS provincia', 'dpto_code AS departamento', 'floor AS nroPiso', 'block AS nroManzana', 'allotment AS nroLote', 'zone_type_id AS zonaDireccion', 'tipo_zona AS descripZonaDirecc', 'tipo_direccion_id', 'tipo_direccion', 'road_name AS descripDireccion'])
                ->where('customer_id', $datos_cliente->customer_id)->get();
            $datos_cliente->clientecontacto = array();

            $datos_request = new \stdClass;
            $datos_request->idCliente = $request->idCliente;
            $datos_request->userId = $userId;
            $datos_request->all = $request->all();

            $arrayLogInsert = array(
                'method' => 'get_customer_by_code',
                'system' => 'Ecommerce',
                'user' => $userId,
                'json_request_values' => json_encode($datos_request),
                'json_response_values' => json_encode($datos_cliente),
                'created_at' => date("Y-m-d H:i:s")
            );
            DB::table('api_logs')->insertGetId($arrayLogInsert);
            return response()->json($datos_cliente, 200);
        } catch (\Exception $e) {
            return response()->json($e, 500);
        }
    }

    public function get_customer_by_identification(Request $request)
    {
        try {
            $numero_identificacion = $request->identificationNumber;
            $userId = (request()->user()) ? request()->user()->id : 0;
            $select = ['customer_code', 'document_type_code', 'document_number', 'name_social_reason'];

            if (!$datos_cliente = VCustomers::select($select)
                ->where('document_number', $numero_identificacion)->first()) {
                $response = new \stdClass;
                $response->mensaje = 'Cliente (' . $numero_identificacion . ') no existe';

                $datos_request = [
                    'document_number' => $numero_identificacion,
                    'userId' => $userId,
                    'all' => $request->all()
                ];

                $arrayLogInsert = array(
                    'method' => 'get_customer_by_identification',
                    'system' => '',
                    'user' => $userId,
                    'json_request_values' => json_encode($datos_request),
                    'json_response_values' => json_encode($response),
                    'created_at' => date("Y-m-d H:i:s")
                );
                DB::table('api_logs')->insertGetId($arrayLogInsert);
                return response()->json($response, 400);
            }
            /* $datos_cliente->clientemodalidadpago = VCustomerPaymentMethods::select(['customer_code AS idCliente', 'company_type_code AS tipoEmpresa', 'document_type_code AS tipoDocIdentida', 'document_number AS nroDocIdentida', 'name_social_reason AS razonSocial', 'client_class AS claseClienteId'])
                ->where('customer_id', $datos_cliente->customer_id)->get();
            $datos_cliente->clientedireccion = Direcciones::select(['address_order AS item', 'customer_code AS idCliente', 'number AS nroDireccion', 'dist_code AS distrito', 'prov_code AS provincia', 'dpto_code AS departamento', 'floor AS nroPiso', 'block AS nroManzana', 'allotment AS nroLote', 'zone_type_id AS zonaDireccion', 'tipo_zona AS descripZonaDirecc', 'tipo_direccion_id', 'tipo_direccion', 'road_name AS descripDireccion'])
                ->where('customer_code', $datos_cliente->customer_id)->get();
            $datos_cliente->clientecontacto = array();
            */
            $datos_request = [
                'document_number' => $numero_identificacion,
                'userId' => $userId,
                'all' => $request->all()
            ];

            $arrayLogInsert = array(
                'method' => 'get_customer_by_identification',
                'system' => '',
                'user' => $userId,
                'json_request_values' => json_encode($datos_request),
                'json_response_values' => json_encode($datos_cliente),
                'created_at' => date("Y-m-d H:i:s")
            );
            DB::table('api_logs')->insert($arrayLogInsert);
            return response()->json($datos_cliente, 200);
        } catch (\Exception $e) {
            return response()->json($e, 500);
        }
    }

    public function get_customers()
    {
        $rs = DB::table('v_customers')->select(['customer_code', 'document_number', 'name_social_reason', 'reg_status'])->get()->toArray();
        return response()->json($rs, 200);
    }

    public function get_customer_by_name(Request $request)
    {
        try {
            //$userId = (request()->user()->id) ? request()->user()->id : 0;
            $userId = 0;
            // die("OK");
            $cliente =  strtoupper(trim($request->customer_name));
            if (!$datos_cliente = VCustomers::select(['customer_code', 'document_type_code', 'document_number', 'name_social_reason', 'client_class'])
                ->where('name_social_reason', 'ilike', '%' . $cliente . '%')->orderBy('name_social_reason')->get()->toArray()) {
                $response = new \stdClass;
                $response->mensaje = 'Cliente (' . $request->customer_name . ') no existe';

                $datos_request = new \stdClass;
                $datos_request->customer_name = $request->customer_name;
                $datos_request->userId = $userId;
                $datos_request->all = $request->all();

                $arrayLogInsert = array(
                    'method' => 'get_customer_by_name',
                    'system' => 'Ecommerce',
                    'user' => $userId,
                    'json_request_values' => json_encode($datos_request),
                    'json_response_values' => json_encode($response),
                    'created_at' => date("Y-m-d H:i:s")
                );
                DB::table('api_logs')->insertGetId($arrayLogInsert);
                return response()->json($response, 400);
            }

            die(print_r($datos_cliente));
            /*
            $datos_cliente->clientemodalidadpago = VCustomerPaymentMethods::select(['customer_code AS idCliente', 'way_to_pay_code AS formaPago', 'payment_modality_code AS modalidadPago', 'payment_condition_code AS condicionPago'])
                ->where('customer_id', $datos_cliente->customer_id)->get();
            $datos_cliente->clientedireccion = Direcciones::select(['address_order AS item', 'customer_code AS idCliente', 'number AS nroDireccion', 'dist_code AS distrito', 'prov_code AS provincia', 'dpto_code AS departamento', 'floor AS nroPiso', 'block AS nroManzana', 'allotment AS nroLote', 'zone_type_id AS zonaDireccion', 'tipo_zona AS descripZonaDirecc', 'tipo_direccion_id', 'tipo_direccion', 'road_name AS descripDireccion'])
                ->where('customer_id', $datos_cliente->customer_id)->get();
            $datos_cliente->clientecontacto = array();
            */

            $datos_request = new \stdClass;
            $datos_request->customer_name = $request->customer_name;
            $datos_request->userId = $userId;
            $datos_request->all = $request->all();

            $arrayLogInsert = array(
                'method' => 'get_customer_by_name',
                'system' => 'Ecommerce',
                'user' => $userId,
                'json_request_values' => json_encode($datos_request),
                'json_response_values' => json_encode($datos_cliente),
                'created_at' => date("Y-m-d H:i:s")
            );
            DB::table('api_logs')->insertGetId($arrayLogInsert);
            return response()->json($datos_cliente, 200);
        } catch (\Exception $e) {
            return response()->json($e, 500);
        }
    }
}
