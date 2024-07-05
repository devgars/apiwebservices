<?php

namespace App\Http\Controllers\Sync;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

class SyncOrderController extends Controller
{
    public function escribir_actualizar_cabecera_pedido($fila)
    {
        $util = new Utilidades();
        $registro = $fila->datos_consulta;
        echo '<pre>';
        print_r($registro);
        $datos_almacen = DB::table('v_almacenes_all')->where('company_code', $registro->cbcodcia)->where('warehouse_code', $registro->cbcodalm)->first();
        $datos_sucursal = DB::table('v_sucursales_all')->where('company_code', $registro->cbcodcia)->where('subsidiary_code', $registro->cbcodsuc)->first();
        $seller = DB::table('v_users_by_companies')->select('user_user_id')->where('company_id', $datos_sucursal->company_id)->where('operator_code', trim($registro->cbcodven))->first();
        $user = DB::table('v_users_by_companies')->select('user_user_id')->where('company_id', $datos_sucursal->company_id)->where('user_code', trim($registro->cbusr))->first();
        /*
            Empresas de envío   -> 23
            Formas de pago  -> 31
            Modalidades de pago -> 32
            Condiciones de pago -> 33
            Documentos SUNAT      -> 38
            Origen de pedidos  -> 44
            */
        $payment_type_id = DB::table('gen_resource_details')->select('id')->where('resource_id', 31)->where('code', $registro->cbfrmpag)->first()->id;
        $modalidad_pago = DB::table('gen_resource_details')->select('id')->where('resource_id', 32)->where('code', $registro->cbmodpag)->first()->id;
        print_r($modalidad_pago);
        $payment_condition_id = DB::table('gen_resource_details')->select('id')->where('resource_id', 33)->where('code', $registro->cbcndpag)->first()->id;
        $origin_id = DB::table('gen_resource_details')->select('id')->where('resource_id', 44)->where('code', $registro->cboriped)->first()->id;
        $document_type_id = DB::table('gen_resource_details')->select('id')->where('resource_id', 38)->where('code', $registro->cbtipdoc)->first()->id;
        $track_as = DB::connection('ibmi')->table('LIBPRDDAT.MMQ1REP')->select('q1codtrn')->where('q1codcia', $registro->cbcodcia)->where('q1codsuc', $registro->cbcodsuc)->where('q1nropdc', $registro->cbnropdc)->first();
        print_r($track_as);

        switch ($track_as->q1codtrn) {
                //delivery_type_id: 504 -> Entrega M&M , 503 -> Recojo Cliente, 505 -> Empresa de envíos
            case '003174':
                $delivery_type_id = 504; //Entrega M&M 
                $carrier_id = null;
                break;
            case '003151':
                $delivery_type_id = 503; //RECOJO CLIENTE
                $carrier_id = null;
                break;
            case '003174':
                $delivery_type_id = 505; //Empresa de envíos
                $carrier_id = DB::table('gen_resource_details')->select('id')->where('resource_id', 23)->where('code', $track_as->q1codtrn)->first()->id;
                break;

            default:
                $delivery_type_id = 504;
                $carrier_id = null;
                break;
        }

        echo "<br>delivery_type_id: $delivery_type_id - carrier_id: $carrier_id";

        $datos_cliente = DB::table('customers')->where('code', trim($registro->cbcodcli))->first();
        if (!$datos_cliente) {
            echo ('<br>Cliente no encontrado ' . $registro->cbcodcli . ' -> Llamar a función para crear clientes');
            $registro->datos_consulta = $this->selecciona_fila_from_tabla_db2('LIBPRDDAT.MMAKREP', array(['AKCODCLI', '=', $registro->cbcodcli]));
            $CustomerController = new SyncCustomer;
            $CustomerController->mmakrep_maestro_clientes($registro);
            if (!$datos_cliente = DB::table('customers')->where('code', trim($registro->cbcodcli))->first()) {
                die(' -> CLIENTE NO FUE REGISTRADO');
            }
        }
        $order_time = ($registro->cbjtm && $registro->cbjtm > 0) ? $registro->cbjtm : 1;

        $modalidad_pago = DB::table('gen_resource_details')->where('resource_id', 33)->where('code', $registro->cbcndpag)->first();
        if ($registro->cbimptot > 0) {
            $porcentaje_igv = round((float)round($registro->cbimpimp, 2) * 100 / (float) round($registro->cbimptot, 2), 2);
            $total = round(((float)$registro->cbimptot + (float)$registro->cbimpimp), 2);
            //$subtotal = round((float)$registro->cbimptot, 2);
        } else {
            $porcentaje_igv = 0;
            $total = round(((float)$registro->cbimptot + (float)$registro->cbimpimp), 2);
            //$subtotal = 0;
        }

        $arrayInsert = array(
            'company_id' => $datos_sucursal->company_id,
            'subsidiary_id' => $datos_sucursal->subsidiary_id,
            'warehouse_id' => $datos_almacen->warehouse_id,
            'customer_id' => $datos_cliente->id,
            'document_type_id' => $document_type_id,
            'origin_id' => ($origin_id) ? $origin_id : null,
            'order_number' => $registro->cbnropdc,
            'order_date' => $registro->cbfecdoc,
            'order_time' => $order_time,
            'seller_id' => ($seller) ? $seller->user_user_id : null,
            'attended_by_user_id' => ($user) ? $user->user_user_id : null,
            'currency_id' => ($registro->cbcodmon === '02') ? 391 : 390,
            'payment_type_id' => $payment_type_id,
            'payment_condition_id' => $payment_condition_id,
            'credit_days' => 9999,
            'delivery_type_id' => $delivery_type_id,
            'carrier_id' => $carrier_id,
            'customer_class_discount_rate' => $registro->cbdctcls,
            'customer_class_total_discount' => $registro->cbimpdcc,
            'payment_type_discount_rate' => $registro->cbdctcnd,
            'payment_type_total_discount' => $registro->cbimpdcp,
            'global_discount' => 0,
            'subtotal' => $registro->cbimptot,
            'igv_tax' => $porcentaje_igv,
            'total_tax' => $registro->cbimpimp,
            'total' => $total,
            'user_id' => ($user) ? $user->user_user_id : null,
            'reg_doc_status' => $registro->cbstsdgr,
            'reg_order_doc_status' => $registro->cbstspdo,
            'reg_status' => ($registro->cbsts === 'A') ? 1 : 0,
        );
        $arrayWhere = array(
            ['company_id', '=', $datos_sucursal->company_id],
            ['subsidiary_id', '=', $datos_sucursal],
            ['order_number', '=', $registro->cbnropdc],
        );
        (print_r($arrayInsert));
        print_r($registro);
        exit;
        /*
        $json_pedido = OrdOrder::updateOrCreate(
            $arrayWhere,
            $arrayInsert
        );*/
    }

    public function escribir_actualizar_detalle_pedido($fila)
    {
        $util = new Utilidades();
        $registro = $fila->datos_consulta;
        echo '<pre>';
        print_r($registro);
    }
}
