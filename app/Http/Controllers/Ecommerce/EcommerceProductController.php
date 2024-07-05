<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sync\Utilidades;
use Illuminate\Http\Request;
use App\Models\Ecommerce\EcommerceOrder;
use DB;
use Illuminate\Support\Facades\Validator;
use App\Models\CustomerContact;
use App\Models\Customer;
use GuzzleHttp\Exception\ClientException;
use Mockery\Exception;
use App\Traits\Api66ConTrait;
use App\Models\ProductSearchLog;

use App\Jobs\QueueGenerateOrder;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpParser\Node\Stmt\Foreach_;
use stdClass;

class EcommerceProductController extends Controller
{
    use Api66ConTrait;

    protected $job = 'WSAPI';
    protected $codCia = '10';
    protected $oriPed = 'VW';
    protected $codUser = '000293';
    protected $userId = 'ECOMMERCE';
    protected $job_as = 'WSAPI';

    public function get_product_line_applications(Request $request)
    {
        $line = $request->line;
        $part_code = $request->part_code;
        $rs = $this->return_product_line_applications($line, $part_code);
        return response()->json($rs, 200);
    }

    public function return_product_line_applications($line, $part_code)
    {
        $select = ['part_line_name as line', 'model_code', 'model_description', 'year', 'veh_hp', 'veh_traction', 'veh_engine', 'veh_gearbox', 'veh_front_axle', 'veh_rear_axle', 'veh_order'];
        return DB::table('v_parts_vechicles_applications')->select($select)
            ->where('part_code', '=', $part_code)
            ->where('part_line_code', '=', $line)
            ->get()->toArray();
    }

    public function get_products_by_params(Request $request)
    {
        //IMPLEMENTANDO VISTA
        $objeto = new \stdClass();
        $limit = ($request->limit) ? (int)$request->limit : 12;
        $offset = ($request->page) ? (($request->page - 1) * $limit) : 0;
        $line = $request->line;
        $model = str_replace('|', '/', $request->model);
        $str_year = $request->year;
        $arrayYear = explode('|', $str_year);
        $year = $arrayYear[0];
        $engine = $arrayYear[1];
        $hp = $arrayYear[2];
        $traction = $arrayYear[3];

        $str_engine = ($engine <> '*') ? " veh_engine ilike '$engine'" : '';
        $str_hp = ($hp <> '*') ? "  veh_hp ilike '$hp'" : '';
        $str_traction = ($traction <> '*') ? "  veh_traction ilike '$traction'" : '';
        $sqlWhere = '';
        $sqlWhere = $str_engine;
        $sqlWhere .= ($str_engine <> '' && $str_hp <> '') ? ' and' . $str_hp : $str_hp;
        $sqlWhere .= (($str_engine <> '' || $str_hp <> '') && $str_traction <> '') ? ' and' . $str_traction : $str_traction;
        if (!$sqlWhere) {
            return response()->json('Faltan parámetros en consulta SQL', 401);
        }

        $rs_count = DB::table('v_parts_by_vehicle_model')
            ->where('line_code', '=', $line)
            ->where('model_code', '=', $model)
            ->where('veh_year', '=', $year)
            ->whereRaw($sqlWhere)
            ->select('id')
            ->count();

        $rs = DB::table('v_parts_by_vehicle_model')
            ->where('line_code', '=', $line)
            ->where('model_code', '=', $model)
            ->where('veh_year', '=', $year)
            ->whereRaw($sqlWhere)
            ->select(['id', 'sku', 'item_code', 'line_code', 'source_code', 'brand_code', 'brand_name', 'factory_code', 'subsystem_code', 'subsystem_name', 'system_code', 'system_name', 'unit', 'name', 'description', 'rotation', 'image', 'weight', 'price', 'offer_price as sale_price', 'stock'])
            ->orderBy('weight', 'DESC')
            ->orderBy('name', 'ASC')
            ->offset($offset)
            ->limit($limit)
            ->get()->toArray();
        ///->toSql();
        //die($rs);
        $rs = (is_array($rs) && sizeof($rs) > 0) ? $rs : array();
        $objeto->page = ($offset > 0) ? floor(($offset / $limit) + 1) : 1;
        $objeto->by_page = $limit;
        $objeto->total = $rs_count;


        if (sizeof($rs) > 0) {
            foreach ($rs as $fila) {
                $fila->stock = ($fila->stock > 0) ? 1 : 0;
                $fila->sale_price = ($fila->sale_price) ? $fila->sale_price : $fila->price;
                $fila->num_of_sale = 0;
                $fila->gallery = $this->retorna_vector_galeria_imagenes($fila->id, 0);
                $fila->gallery360 = $this->retorna_vector_galeria_imagenes($fila->id, 1);
                $fila->technical_spec = '';
                $app = $this->return_product_line_applications($fila->line_code, $fila->item_code);
                $fila->applications = ($app) ? $app : '{}';
            }
        }

        $objeto->data = $rs;
        $objeto->total_pages = ceil($rs_count / $limit);
        $objeto->currency_code = "USD";
        $objeto->currency_symbol = "$";
        return response()->json($objeto, 200);
    }


    public function get_products(Request $request)
    {
        $objeto = new \stdClass();
        $limit = ($request->limit) ? $request->limit : 12;
        $offset = ($request->offset) ? $request->offset : 0;
        $customer_code = ($request->customer_code) ? $request->customer_code : null;

        $sqlWhere = $this->filterBy($request);

        if (!$sqlWhere) {
            return response()->json('Faltan parámetros en consulta SQL', 401);
        }

        $select = ['vpr.part_detail_id as id', 'vpr.technical_spec as technical_spec', 'vpr.sku', 'vpr.part_code as item_code', 'vpr.line_code', 'vpr.origin_code as source_code', 'vpr.origin_name as source_name', 'vpr.trademark_code as brand_code', 'vpr.trademark_name as brand_name', 'vpr.factory_code', 'vpr.subsystem_code', 'vpr.subsystem_name', 'vpr.system_code', 'vpr.system_name', 'vpr.measure_unit_code as unit', 'vpr.part_name', 'vpr.part_name as name', 'vpr.part_name as description', 'vpr.rotation', 'vpr.principal_image as image', 'vpr.weight', 'vpr.min_price as price', 'vpr.offer_price as sale_price', 'vpr.stock', 'vpr.product_features', 'vpr.product_remarks'];
        /*
        //Si cliente está logueado, retorna precios
        if (strlen($customer_code) == 6) {
            array_push($select, 'vpr.min_price as price');
            array_push($select, 'vpr.offer_price as sale_price');
        }
        */

        $param = mb_strtoupper(trim($request->text));
        $param_s = str_replace(' ', '%', mb_strtoupper(trim($request->text)));

        $prefijo = 'vp.';
        $sqlWhere_vp = $this->filterBy($request, $prefijo);

        $rs_count_a = DB::table('v_partes AS vp')->select($select)
            ->join('part_detail_replacements AS pr', 'vp.part_detail_id', '=', 'pr.part_detail_id')
            ->join('v_partes AS vpr', 'pr.part_detail_last_replace_id', '=', 'vpr.part_detail_id')
            ->whereRaw($sqlWhere_vp);

        $prefijo = 'vpr.';
        $sqlWhere_vpr = $this->filterBy($request, $prefijo);
        $rs_count  = DB::table('v_partes_ecommerce AS vpr')->select($select)
            ->whereRaw($sqlWhere_vpr);


        $rs_a = DB::table('v_partes AS vp')->select($select)
            ->selectRaw("case substring(vpr.part_name,0,length('" . $param . "')+1)
            when '" . $param . "' then vpr.weight*10
            else vpr.weight
            end as peso")
            ->join('part_detail_replacements AS pr', 'vp.part_detail_id', '=', 'pr.part_detail_id')
            ->join('v_partes AS vpr', 'pr.part_detail_last_replace_id', '=', 'vpr.part_detail_id')
            ->whereRaw($sqlWhere_vp);


        $rs = DB::table('v_partes_ecommerce AS vpr')->select($select)
            ->selectRaw("case substring(vpr.part_name,0,length('" . $param . "')+1)
        when '" . $param . "' then vpr.weight*10
        else vpr.weight
        end as peso")
            ->whereRaw($sqlWhere_vpr);


        if ($request->skus) {
            $rs_a = $rs_a->whereIn('vp.sku', explode(',', $request->skus));
            $rs = $rs->whereIn('vpr.sku', explode(',', $request->skus));
            //die($request->skus);
        }

        if ($request->text) {

            $rs_a = $rs_a->where(function ($query) use ($param_s, $param) {
                $query->where('vp.part_code', $param);
                $query->orWhere('vp.part_name', 'ILIKE', '%' . $param_s . '%');
                $query->orWhere('vp.factory_code', 'ILIKE', '%' . $param_s . '%');
                $query->orWhere('vp.product_features', 'ILIKE', '%' . $param_s . '%');
                $query->orWhere('vp.product_remarks', 'ILIKE', '%' . $param_s . '%');
                $query->orWhere('vp.sku', 'ILIKE', '%' . $param_s . '%');
            });

            $rs = $rs->where(function ($query) use ($param_s, $param) {
                $query->where('vpr.part_code', $param);
                $query->orWhere('vpr.part_name', 'ILIKE', '%' . $param_s . '%');
                $query->orWhere('vpr.factory_code', 'ILIKE', '%' . $param_s . '%');
                $query->orWhere('vpr.product_features', 'ILIKE', '%' . $param_s . '%');
                $query->orWhere('vpr.product_remarks', 'ILIKE', '%' . $param_s . '%');
                $query->orWhere('vpr.sku', 'ILIKE', '%' . $param_s . '%');
            });


            $rs_count_a = $rs_count_a->where(function ($query) use ($param_s, $param) {
                $query->where('vp.part_code', $param);
                $query->orWhere('vp.part_name', 'ILIKE', '%' . $param_s . '%');
                $query->orWhere('vp.factory_code', 'ILIKE', '%' . $param_s . '%');
                $query->orWhere('vp.product_features', 'ILIKE', '%' . $param_s . '%');
                $query->orWhere('vp.product_remarks', 'ILIKE', '%' . $param_s . '%');
                $query->orWhere('vp.sku', 'ILIKE', '%' . $param_s . '%');
            });

            $rs_count = $rs_count->where(function ($query) use ($param_s, $param) {
                $query->where('vpr.part_code', $param);
                $query->orWhere('vpr.part_name', 'ILIKE', '%' . $param_s . '%');
                $query->orWhere('vpr.factory_code', 'ILIKE', '%' . $param_s . '%');
                $query->orWhere('vpr.product_features', 'ILIKE', '%' . $param_s . '%');
                $query->orWhere('vpr.product_remarks', 'ILIKE', '%' . $param_s . '%');
                $query->orWhere('vpr.sku', 'ILIKE', '%' . $param_s . '%');
            });
        }

        $rs = $rs->union($rs_a);

        $rs_count = $rs_count->union($rs_count_a);

        $rs_count = $rs_count->count();

        if ($request->order == 'price') {
            $rs =  $rs->orderBy('price', $request->sortby);
            //$rs =  $rs->orderBy('offer_price', $request->sortby);
        }

        if ($request->order == 'a-z') {
            $rs =  $rs->orderBy('name', $request->sortby);
        }

        if (!$request->order) {
            $rs =  $rs->orderBy('peso', 'DESC');
        }


        $rs =  $rs->offset($offset)
            ->limit($limit)
            ->get()->toArray();

        /*
        $rs =  $rs->offset($offset)
            ->limit($limit)
            ->toSql();
        die($rs);
        */

        $objeto->page = ($offset > 0) ? floor(($offset / $limit) + 1) : 1;
        $objeto->by_page = $limit;
        $objeto->total = $rs_count;

        if (sizeof($rs) > 0) {
            $array = [
                'search_date' => date("Y-m-d"),
                'search_time' => date("H:i:s"),
                'searched_product' => $param,
                'product_found' => true,
                'customer_code' => $customer_code,
                'ip' => \Request::ip(),
                'created_at' => date("Y-m-d H:i:s")
            ];
            ProductSearchLog::create($array);
            foreach ($rs as $fila) {
                $fila->stock = ($fila->stock > 0) ? 1 : 0;
                $fila->sale_price = ($fila->sale_price) ? $fila->sale_price : $fila->price;
                $fila->num_of_sale = 0;
                $fila->gallery = $this->retorna_vector_galeria_imagenes($fila->id, 0);
                $fila->gallery360 = $this->retorna_vector_galeria_imagenes($fila->id, 1);
                $app = $this->return_product_line_applications($fila->line_code, $fila->item_code);
                $fila->applications = ($app) ? $app : '{}';

                if ($customer_code) {
                    $fila->precio_especial_cliente = $this->get_customer_special_prices($customer_code, $request->sku);
                    //$fila->sale_price = (($fila->precio_especial_cliente) && $fila->precio_especial_cliente < $fila->sale_price) ? $fila->precio_especial_cliente : $fila->sale_price;
                    $fila->sale_price = (($fila->precio_especial_cliente) && $fila->precio_especial_cliente > 0) ? $fila->precio_especial_cliente : $fila->sale_price;
                }

                //VALIDAR EL ORIGEN DEL PRODUCTO
                $fila = $this->valida_origen_parte($fila);
            }
        } else {
            $array = [
                'search_date' => date("Y-m-d"),
                'search_time' => date("H:i:s"),
                'searched_product' => $param,
                'product_found' => false,
                'customer_code' => $customer_code,
                'ip' => \Request::ip(),
                'created_at' => date("Y-m-d H:i:s")
            ];
            ProductSearchLog::create($array);
        }
        $objeto->data = $rs;
        $objeto->total_pages = ceil($rs_count / $limit);
        $objeto->currency_code = "USD";
        $objeto->currency_symbol = "$";
        return response()->json($objeto, 200);
    }

    public function get_product_discounts()
    {
        $rs = DB::table('v_partes_descuentos_ecommerce')
            ->get()->toArray();
        $rs = (is_array($rs) && sizeof($rs) > 0) ? $rs : array();

        if (sizeof($rs) > 0) {
            foreach ($rs as $fila) {
                $fila->stock = ($fila->stock > 0) ? 1 : 0;
                $fila->sale_price = ($fila->sale_price) ? $fila->sale_price : $fila->price;
                $fila->num_of_sale = 0;
                $fila->gallery = $this->retorna_vector_galeria_imagenes($fila->id, 0);
                $fila->gallery360 = $this->retorna_vector_galeria_imagenes($fila->id, 1);
                $fila->technical_spec = '';
                $fila->system_code = "";
                $fila->subsystem_code = "";
                $fila->unit = "";
                $fila->description = "";
            }
        }
        return $rs;
    }

    public function get_customer_special_prices($customer_code, $sku)
    {
        $fecha_actual = date("Ymd");
        $rs = DB::table('part_offer_group_details as pogd')->select(['pogd.offer_price'])
            ->join('part_part_details as ppd', 'pogd.part_detail_id', '=', 'ppd.id')
            ->join('part_offer_groups as pog', 'pogd.part_offer_group_id', '=', 'pog.id')
            ->join('customer_groups as cg', 'pog.company_group_id', '=', 'cg.customer_group_id')
            ->join('gen_resource_details as gr', 'cg.customer_group_id', '=', 'gr.id')
            ->join('customers as c', 'cg.customer_id', '=', 'c.id')
            ->where('c.code', $customer_code)
            ->where('ppd.sku', $sku)
            ->where('pog.end_date', '>=', $fecha_actual)
            ->where('pog.reg_status', 1)
            ->first();
        //->toSql();
        //die($rs);
        $offer_price = ($rs) ? $rs->offer_price : null;
        return $offer_price;
    }

    public function get_lines()
    {
        $rs = DB::table('gen_resource_details AS line')
            ->where('line.resource_id', '=', '25')
            ->where('line.reg_status', '=', '1')
            ->orderBy('line.order')
            ->select(['line.id', 'line.code', 'line.abrv', 'line.name AS line', 'line.description', 'line.order'])
            ->get()->toArray();
        $rs = (is_array($rs) && sizeof($rs) > 0) ? $rs : false;
        return response()->json($rs, 200);
    }

    public function get_models_by_line_code(Request $request)
    {
        $line_code = $request->line;
        $rs = DB::table('v_modelos_veh_x_linea')
            ->where('line_code', '=', $line_code)
            ->select(['id as model_id', 'model_code as modelo'])
            ->get()
            ->toArray();

        $rs = (is_array($rs) && sizeof($rs) > 0) ? $rs : array();
        return response()->json($rs, 200);
    }

    public function get_vehicles_by_line_and_model(Request $request)
    {
        $line_code = $request->line;
        $model_code = str_replace('|', '/', $request->model);

        $rs = DB::table('v_anios_x_linea_modelo_veh')
            ->where('line_code', '=', $line_code)
            ->where('model_code', '=', $model_code)
            ->select(['veh_id', 'veh_year as year', 'veh_engine as engine', 'veh_hp as hp', 'veh_traction as traction'])
            ->get()->toArray();

        $rs = (is_array($rs) && sizeof($rs) > 0) ? $rs : array();
        return response()->json($rs, 200);
    }

    public function post_quote(Request $request)
    {
        $data = $request->getContent();
        $objeto = json_decode($data);
        $cart_id = $objeto->cart_id;

        $arrayIn = array(
            'tabla' => 'post_quote',
            'mensaje' => 'CREAR COTI',
            'otro' => json_encode($objeto)
        );
        $this->inserta_into_tabla('log_migraciones', $arrayIn);

        $nro_nueva_coti = $this->create_quote_as400($objeto);
        if ($nro_nueva_coti) {
            $arrayInsert = array(
                'ecommerce_cart_id' => $cart_id,
                'json_quote' => $data,
                'tmp_quote_number' => $nro_nueva_coti,
                'as400_quote_number' => $nro_nueva_coti,
                'created_at' => date("Y-m-d H:i:s")
            );
            $this->inserta_into_tabla('ecommerce_orders', $arrayInsert);

            $response = [
                'codigo' => '1700',
                'mensajeapi' => [
                    'titulo'  => 'Cotización',
                    'codigo'  => $nro_nueva_coti,
                    'mensaje' => '* Se creó la cotización *',
                ],
                'respuesta' => 'Se creó la cotización ',
            ];
        } else {
            $response = [
                'codigo' => '1701',
                'mensajeapi' => [
                    'titulo'  => 'Cotización',
                    'codigo'  => 0,
                    'mensaje' => 'No se creó la cotización',
                ],
                'respuesta' => 'No se creó la cotización',
            ];
        }

        return response()->json($response, 200);
    }

    public function create_quote_as400($objeto)
    {
        $codCia = '10';
        $oriPed = 'VW';
        $codUser = '000293';
        $userId = 'ECOMMERCE';
        $job = 'WSAPI';
        $numeroIdentidad = (strlen($objeto->nroIdentidad) > 8) ? substr($objeto->nroIdentidad, 1, 8) : $objeto->nroIdentidad;

        //DB::beginTransaction();
        $nueva_cotizacion = Utilidades::retorna_nuevo_numero_tabla_numeradores_mmfcrep($codCia,  $objeto->codSucursal, '03');
        $arrayCabeceraCoti = array(
            'AYCODCIA' => $codCia,
            'AYCODSUC' => $objeto->codSucursal,
            'AYNROPED' => $nueva_cotizacion,
            'AYCODCLI' => $objeto->codCliente,
            'AYRAZSOC' => $objeto->razonSocial,
            'AYNRORUC' => $numeroIdentidad,
            'AYORIPED' => $oriPed,
            'AYCNDTRB' => "01",
            'AYCLSPED' => "CC",
            'AYTIPPED' => "CT",
            'AYSTSCOP' => "C",
            'AYFRMPAG' => "C",
            'AYMODPAG' => "CO",
            'AYCNDPAG' => 1,
            'AYCODMON' => $objeto->codMoneda,
            'AYCODVEN' => $codUser,
            'AYCODTLL' => 'M00000',
            'AYCODTRN' => $objeto->codTransportista,
            'AYDCTCLS' => 0,
            'AYDCTCND' => round($objeto->porcentajeDescuento, 2),
            'AYSTSDGR' => "F",
            'AYPLZMAX' => 0,
            'AYFECREG' => date("Ymd"),
            'AYFECENT' => date("His"),
            'AYATNPOR' => $userId,
            'AYSTSDES' => "S",
            'AYCODALM' => $objeto->codAlmacen,
            'AYCLSCLT' => $objeto->claseClienteId,
            'AYSTSPRI' => "S",
            'AYIMPTOT' => (float)round($objeto->impTotal, 2),
            'AYIMPDCP' => (float)round($objeto->importeDescuento, 2),
            'AYIMPDCC' => 0,
            'AYIMPIMP' => (float)round($objeto->impImpuestos, 2),
            'AYSTSPED' => "C",
            'AYSTS' => 'A',
            'AYUSR' => $userId,
            'AYJOB' => $job,
            'AYJDT' => date("Ymd"),
            'AYJTM' => date("His")
        );
        if (DB::connection('ibmi')->table('LIBPRDDAT.MMAYREP')->insert([
            $arrayCabeceraCoti
        ])) {
            //Actualizar numero de cotización en tabla mmfcrep
            $arrayWhereUp = array(
                ['FCCODCIA', '=', $codCia],
                ['FCCODSUC', '=', $objeto->codSucursal],
                ['FCCODELE', '=', '03'],
            );
            $arrayUpdate = array('FCCANACT' => $nueva_cotizacion);
            Utilidades::actualiza_tabla_numeradores_mmfcrep($arrayWhereUp, $arrayUpdate);
            //Fin - Actualizar numero de cotización en tabla mmfcrep

            //echo '<br>Generar detalle de cotización: ' . $nueva_cotizacion;
            if ($objeto->cotizaciondetalle && is_array($objeto->cotizaciondetalle)) {
                $i = 0;
                foreach ($objeto->cotizaciondetalle as $detalle) {
                    $i++;
                    $descripcion = (strlen($detalle->descripArticulo) > 8) ? substr($detalle->descripArticulo, 1, 29) : $detalle->descripArticulo;
                    $arrayInsertDetalle = array(
                        'AZCODCIA' => $codCia,
                        'AZCODSUC' => $detalle->codSucursal,
                        'AZNROPED' => $nueva_cotizacion,
                        'AZITEM01' => $i,
                        'AZCODALM' => $detalle->codAlmacen,
                        'AZCODLIN' => $detalle->codLinea,
                        'AZCODART' => $detalle->codArticulo,
                        'AZCODORI' => $detalle->codOrigen,
                        'AZCODMAR' => $detalle->codMarca,
                        'AZDSCART' => $descripcion,
                        'AZCANSOL' => $detalle->cantidadSolicitada,
                        'AZCANDSP' => $detalle->cantidadSolicitada,
                        'AZIMPPRE' => round((float)$detalle->impPrecioUnitario, 2),
                        'AZSTSLON' => "L",
                        'AZDCTLIN' => 0,
                        'AZDCTADI' => 0,
                        'AZPRCIMP' => round($detalle->porcentajeImpto, 2),
                        'AZSTSPRM' => "",
                        'AZSTS' => "A",
                        'AZUSR' => $userId,
                        'AZJOB' => $job,
                        'AZJDT' => date("Ymd"),
                        'AZJTM' => date("His")
                    );

                    if (!DB::connection('ibmi')->table('LIBPRDDAT.MMAZREP')->insert([
                        $arrayInsertDetalle
                    ])) {
                        //DB::rollBack();
                        return false;
                    }
                }
                //DB::commit();
                return $nueva_cotizacion;
            } else return false;
        } else return false;
    }

    public function post_order(Request $request)
    {
        //dd($request->getContent());
        //return response()->json($request->all(), 200);

        //$data = $request->getContent();
        //$objeto = json_decode($data);
        $objeto = json_decode($request->getContent());
        ///return response()->json($objeto, 200);

        $arrayIn = array(
            'tabla' => 'ord_orders',
            'mensaje' => 'prueba',
            'otro' => json_encode($objeto),
            'created_at' => date("Y-m-d H:i:s")
        );
        $this->inserta_into_tabla('log_migraciones', $arrayIn);

        $nro_nuevo_pedido = $this->create_order_as400($objeto);

        if ($nro_nuevo_pedido) {
            $arrayWhere = array(
                ['as400_quote_number', '=', $objeto->idCotizacion],
                ['as400_order_number', '=', null],
            );
            $arrayUpdate = array(
                'as400_order_number' => $nro_nuevo_pedido
            );
            $this->actualiza_tabla('ecommerce_orders', $arrayWhere, $arrayUpdate);

            $response = [
                'codigo' => '1700',
                'mensajeapi' => [
                    'titulo'  => 'Pedido',
                    'codigo'  => $nro_nuevo_pedido,
                    'mensaje' => '* Se creó el pedido *',
                ],
                'respuesta' => 'Se creó el pedido ',
            ];
        } else {
            $response = [
                'codigo' => '1701',
                'mensajeapi' => [
                    'titulo'  => 'Pedido',
                    'codigo'  => 0,
                    'mensaje' => 'No se creó el pedido',
                ],
                'respuesta' => 'No se creó el pedido',
            ];
        }

        return response()->json($response, 200);
        /*
        $arrayWhere = array(
            ['tmp_quote_number', 'ilike', $quote_id]
        );
        $registro = $this->selecciona_fila_from_tabla('ecommerce_orders', $arrayWhere);
        //$tmp_order_number = 'OTMP-' . $quote_id;
        $tmp_order_number = intval('999999' . $registro->ecommerce_cart_id);
        $arrayUpdate = array(
            'ecommerce_quote_number' => $quote_id,
            'json_order' => $data,
            'tmp_order_number' => $tmp_order_number,
            'created_at' => date("Y-m-d H:i:s")
        );
        $arrayWhere = array(
            ['id', '=', $registro->id]
        );
        $this->actualiza_tabla('ecommerce_orders', $arrayWhere, $arrayUpdate);

        $response = [
            'codigo' => '1700',
            'mensajeapi' => [
                'titulo'  => 'Pedido',
                'codigo'  => $tmp_order_number,
                'mensaje' => '* Se creó pedido y se le asignó un número temporal *',
            ],
            'respuesta' => 'Se creó pedido y se le asignó un número temporal',
        ];

        return response()->json($response, 200);
        */
    }

    public function obtenerCabeceraPedido($objeto)
    {
        return [
            'CBCODCIA' => $this->codCia,
            'CBCODSUC' => $objeto->codSucursal,
            'CBNROPED' => $objeto->idCotizacion,
            'CBNROPDC' => $objeto->nuevo_pedido,
            'CBCODCLI' => $objeto->idCliente,
            'CBRAZSOC' => $objeto->razonSocial,
            'CBNRORUC' => $objeto->numeroIdentidad,
            'CBFECDOC' => date("Ymd"),
            'CBORIPED' => $this->oriPed,
            'CBCNDTRB' => "01",
            'CBCODVEN' => $this->codUser,
            'CBATNPOR' => $this->userId,
            'CBCODALM' => $objeto->codAlmacen,
            'CBSTSPRI' => "S",
            'CBMTVTRS' => "",
            'CBFRMPAG' => $objeto->formaPago,
            'CBMODPAG' => $objeto->modalidad_pago,
            'CBCNDPAG' => $objeto->condicionPago,
            'CBCODMON' => $objeto->codMoneda,
            'CBDCTCLS' => 0,
            'CBDCTCND' =>  round($objeto->porcentajeDescuento, 2),
            'CBSTSDGR' => "F",
            'CBTIPDOC' => $objeto->tipo_documento,
            'CBNROSER' => "",
            'CBNROCOR' => "",
            'CBIMPTOT' =>  round($objeto->impTotal, 2),
            'CBIMPDCP' =>  round($objeto->importeDescuento, 2),
            'CBIMPDCC' => 0,
            'CBIMPIMP' =>  round($objeto->impImpuesto, 2),
            'CBSTSPDO' => "A",
            'CBSTSIMP' => "",
            'CBSTS' => "A",
            'CBUSR' => $this->userId,
            'CBJOB' => $this->job_as,
            'CBJDT' => date("Ymd"),
            'CBJTM' => date("His")
        ];
    }

    public function obtenerDetalleDePedido($objeto, $detalle, $i)
    {

        $descripcion =  (strlen($detalle->descripArticulo) > 30) ? substr($detalle->descripArticulo, 1, 30) : $detalle->descripArticulo;

        return [
            'CECODCIA' => $this->codCia,
            'CECODSUC' => $detalle->codSucursal,
            'CENROPED' => $objeto->idCotizacion,
            'CENROPDC' => $objeto->nuevo_pedido,
            'CEITEM01' => $i,
            'CECODALM' => $detalle->codAlmacen,
            'CECODLIN' => $detalle->codLinea,
            'CECODART' => $detalle->codArticulo,
            'CECODORI' => $detalle->codOrigen,
            'CECODMAR' => $detalle->codMarca,
            'CEDSCART' => $descripcion,
            'CECANDSP' => $detalle->cantidadSolicitada,
            'CECANDEV' => 0,
            'CEIMPPRE' =>  round($detalle->impPrecioUnitario, 2),
            'CESTSLON' => "L",
            'CEDCTLIN' => 0,
            'CEDCTADI' => 0,
            'CEPRCIMP' =>  round($detalle->porcentajeImpto, 2),
            'CESTSPRM' => "",
            'CESTSITE' => "",
            'CESTS' => "A",
            'CEUSR' => $this->userId,
            'CEJOB' => $this->job_as,
            'CEJDT' => date("Ymd"),
            'CEJTM' => date("His")
        ];
    }

    public function obtenerDirecciones($objeto)
    {

        $tipo_direcciones = array('01', '03', '05');
        $dir_entrega = null;
        $direcciones = [];

        for ($i = 0; $i < sizeof($tipo_direcciones); $i++) {

            if ($tipo_direcciones[$i] === '03') {

                $direccion = DB::connection('ibmi')->table('LIBPRDDAT.MMALREP')
                    ->where('ALCODCLI', $objeto->idCliente)
                    ->where('ALITEM01', $objeto->idDirecionEntrega)
                    ->first();


                if (!$direccion) {

                    $direccion = DB::connection('ibmi')->table('LIBPRDDAT.MMALREP')
                        ->where('ALCODCLI', $objeto->idCliente)
                        ->where('ALTIPDIR', $tipo_direcciones[$i])
                        ->orderBy('ALITEM01', 'DESC')
                        ->first();
                }

                $dir_entrega = $direccion;
            } else {
                $direccion = DB::connection('ibmi')->table('LIBPRDDAT.MMALREP')
                    ->where('ALCODCLI', $objeto->idCliente)
                    ->where('ALTIPDIR', $tipo_direcciones[$i])
                    ->orderBy('ALITEM01', 'DESC')
                    ->first();
            }

            if ($direccion) array_push($direcciones, $direccion);
        }

        return  $direcciones;
    }
    public function create_order_as400($objeto)
    {
        $util = new Utilidades();
        $dir_entrega = null;


        $objeto->numeroIdentidad = (strlen($objeto->nroIdentidad) > 8) ? substr($objeto->nroIdentidad, 1, 8) : $objeto->nroIdentidad;
        $objeto->modalidad_pago = ($objeto->formaPago === 'R') ? 'FA' : $objeto->modalidadPago;
        $objeto->tipo_documento = ($objeto->formaPago === 'R') ? '01' : $objeto->tipoDocumento;

        $objeto->nuevo_pedido = $util->retorna_nuevo_numero_tabla_numeradores_mmfcrep($this->codCia,  $objeto->codSucursal, '10');

        //armar el array para inser de cabecera de pedido
        $arrayCabeceraPedido = $this->obtenerCabeceraPedido($objeto);

        // insertamos la cabecera de pedido en el as 400
        $insertar_cabecera_pedido = DB::connection('ibmi')->table('LIBPRDDAT.MMCBREP')->insert([$arrayCabeceraPedido]);

        //validar si se creo el registro de cabecera
        if ($insertar_cabecera_pedido) {
            //Actualizar numero de pedido en tabla mmfcrep
            $arrayWhereUp = array(
                ['FCCODCIA', '=', $this->codCia],
                ['FCCODSUC', '=', $objeto->codSucursal],
                ['FCCODELE', '=', '10'],
            );

            $arrayUpdate = array('FCCANACT' => $objeto->nuevo_pedido);

            $util->actualiza_tabla_numeradores_mmfcrep($arrayWhereUp, $arrayUpdate);
            //Fin - Actualizar numero de pedido en tabla mmfcrep

            //ESCRIBIR DETALLE DE PEDIDO EN TABLA MMCEREP
            if ($objeto->pedidodetalle && is_array($objeto->pedidodetalle)) {
                $i = 0;
                foreach ($objeto->pedidodetalle as $detalle) {
                    $i++;

                    $arrayInsertDetalle = $this->obtenerDetalleDePedido($objeto, $detalle, $i);

                    $insert_detalle_pedido = DB::connection('ibmi')->table('LIBPRDDAT.MMCEREP')->insert([$arrayInsertDetalle]);
                }
            }
            //FIN - ESCRIBIR DETALLE DE PEDIDO EN TABLA MMCEREP

            //ENCOLAR PROCESO COMPLEMENTARIO DE GENERACIÓN DE PEDIDO
            //QueueGenerateOrder::dispatch($objeto, $codCia, $nuevo_pedido, $tipo_documento, $modalidad_pago, $numeroIdentidad, $userId, $codUser, $job_as);
            //return $nuevo_pedido;

            //SI ES A CREDITO, ESCRIBIR EN TABLA MMMLREP
            if ($objeto->formaPago === 'R') {
                $arrayInsert = array(
                    'MLCODCIA' => $this->codCia,
                    'MLCODSUC' => $objeto->codSucursal,
                    'MLNROPDC' => $objeto->nuevo_pedido,
                    'MLAPRPOR' => "",
                    'MLFECAPR' => 0,
                    'MLHORAPR' => 0,
                    'MLSTSCRD' => "E",
                    'MLSTSIMP' => "E",
                    'MLSTS' => "A",
                    'MLUSR' => $this->userId,
                    'MLJOB' => $this->job_as,
                    'MLJDT' => date("Ymd"),
                    'MLJTM' => date("His")
                );

                DB::connection('ibmi')->table('LIBPRDDAT.MMMLREP')->insert([$arrayInsert]);
            }
            //FIN - SI ES A CREDITO, ESCRIBIR EN TABLA MMMLREP

            //REGISTRAR DIRECCIONES
            $direcciones = $this->obtenerDirecciones($objeto);

            foreach ($direcciones as $direccion) {

                $direccion_completa = (strlen($direccion->aldscdir) > 30) ? substr($direccion->aldscdir, 0, 30) : $direccion->aldscdir;

                $zone_name = (strlen($direccion->aldsczdr) > 20) ? substr($direccion->aldsczdr, 0, 20) : $direccion->aldsczdr;

                $arrayInsertAs = array(
                    'CCCODCIA' => $this->codCia,
                    'CCCODSUC' => $objeto->codSucursal,
                    'CCNROPED' => $objeto->idCotizacion,
                    'CCNROPDC' => $objeto->nuevo_pedido,
                    'CCITEM01' => $direccion->alitem01,
                    'CCTIPDIR' => $direccion->altipdir,
                    'CCVIADIR' => $direccion->alviadir,
                    'CCDSCDIR' => $direccion_completa,
                    'CCNRODIR' => $direccion->alnrodir,
                    'CCNRODPT' => ($direccion->alnrodpt) ? $direccion->alnrodpt : "",
                    'CCNROPSO' => ($direccion->alnropso) ? $direccion->alnropso : "",
                    'CCNROMZA' => ($direccion->alnromza) ? $direccion->alnromza : "",
                    'CCNROLTE' => ($direccion->alnrolte) ? $direccion->alnrolte : "",
                    'CCZONDIR' => ($direccion->alzondir) ? $direccion->alzondir : "",
                    'CCDSCZDR' => $zone_name,
                    'CCDEPART' => $direccion->aldepart,
                    'CCPROVIN' => $direccion->alprovin,
                    'CCDISTRI' => $direccion->aldistri,
                    'CCPLNGEO' => $direccion->alplngeo,
                    'CCFILUBI' => $direccion->alfilubi,
                    'CCCOLUBI' => $direccion->alcolubi,
                    'CCCODPAI' => $direccion->alcodpai,
                    'CCCODCIU' => "",
                    'CCSTSPDO' => 'A',
                    'CCSTS' => 'A',
                    'CCUSR' => $this->userId,
                    'CCJOB' => $this->job_as,
                    'CCJDT' => date("Ymd"),
                    'CCJTM' => date("His")
                );

                $util->inserta_into_tabla_as400('LIBPRDDAT.MMCCREP', $arrayInsertAs);
            }
            //FIN - REGISTRAR DIRECCIONES



            //REGISTRAR EN TABLA DE SALDOS PRINCIPAL - MMEIREP
            if ($objeto->formaPago === 'R' && in_array($objeto->modalidadPago, ['FA', 'FC'])) {
                $fecha_vencimiento = $util->sumar_restar_dias_fecha(date("Ymd"), Utilidades::ECOMMERCEDIASVENCIMIENTO, 'sumar');
                $fecha_vencimiento = $fecha_vencimiento->format('Ymd');
            } else {
                $fecha_vencimiento = date("Ymd");
            }

            $arrayWhere = array(
                ['EICODCIA', '=', $this->codCia],
                ['EICODSUC', '=', $objeto->codSucursal],
                ['EICODCLI', '=', $objeto->idCliente],
                ['EITIPDOC', '=', $objeto->tipoDocumento],
                ['EINRODOC', '=', $objeto->nuevo_pedido],
            );

            if (!$util->selecciona_fila_from_tabla_db2('LIBPRDDAT.MMEIREP', $arrayWhere)) {
                $importe_total = round(((float)$objeto->impTotal + (float) $objeto->impImpuesto), 2);
                $arrayInsert = array(
                    'EICODCIA' => $this->codCia,
                    'EICODSUC' => $objeto->codSucursal,
                    'EICODCLI' => $objeto->idCliente,
                    'EITIPDOC' => $objeto->tipo_documento,
                    'EINRODOC' => $objeto->nuevo_pedido,
                    'EIFECTCM' => date("Ymd"),
                    'EIFECEMI' => date("Ymd"),
                    'EIFECVCT' => $fecha_vencimiento,
                    'EICODMON' => $objeto->codMoneda,
                    'EIIMPCCC' => $importe_total,
                    'EIIMPSLD' => $importe_total,
                    'EIFRMPAG' => $objeto->formaPago,
                    'EIMODPAG' => $objeto->modalidad_pago,
                    'EICNDPAG' => $objeto->condicionPago,
                    'EICODCBR' => $objeto->codSucursal,
                    'EICODVEN' => $this->codUser,
                    'EINROVIS' => 0,
                    'EINROREN' => 0,
                    'EICODMTV' => '',
                    'EISTSCLT' => '',
                    'EISTSABC' => '',
                    'EISTSEXT' => '',
                    'EISTSCOA' => 'C',
                    'EISTSDOC' => 'A',
                    'EISTSRCL' => '',
                    'EISTS' => 'A',
                    'EIUSR' => $this->userId,
                    'EIJOB' => $this->job_as,
                    'EIJDT' => date("Ymd"),
                    'EIJTM' => date("His"),
                    'EIMIGSAP' => ''
                );

                $util->inserta_into_tabla_as400('LIBPRDDAT.MMEIREP', $arrayInsert);
            }
            //FIN - REGISTRAR EN TABLA DE SALDOS PRINCIPAL - MMEIREP

            //AGREGAR PERSONA RECOJO
            $arrayWhere = array(
                ['PCCODCIA', '=', $this->codCia],
                ['PCCODSUC', '=', $objeto->codSucursal],
                ['PCNROPED', '=', $objeto->nuevo_pedido],
            );
            if (!$util->selecciona_fila_from_tabla_db2('LIBPRDDAT.MMPCREP', $arrayWhere)) {
                $persona_recojo = trim($objeto->personarecogepedido[0]->nombres . ' ' . $objeto->personarecogepedido[0]->nroDocumento);
                $arrayInsert = array(
                    'PCCODCIA' => $this->codCia,
                    'PCCODSUC' => $objeto->codSucursal,
                    'PCNROPED' => $objeto->nuevo_pedido,
                    'PCTXTADI' => $persona_recojo,
                    'PCSTS' => 'A',
                    'PCUSR' => $this->userId,
                    'PCJOB' => $this->job_as,
                    'PCJDT' => date("Ymd"),
                    'PCJTM' => date("His")
                );

                $util->inserta_into_tabla_as400('LIBPRDDAT.MMPCREP', $arrayInsert);
            }
            //FIN - AGREGAR PERSONA RECOJO

            //ENCABEZADO PARTE DE SALIDA
            //arneon 2022-09-21 cambio de parametro $objeto->codSucursal por $objeto->codAlmacen
            $nueva_id_parte_salida = $util->retorna_nuevo_numero_tabla_numeradores_mmfcrep($this->codCia, $objeto->codSucursal, '13');
            if ($nueva_id_parte_salida > 0) {
                $arrayInsert = array(
                    'AICODCIA' => $this->codCia,
                    'AICODSUC' => $objeto->codAlmacen,
                    'AINROPIS' => $nueva_id_parte_salida,
                    'AICODALM' => $objeto->codAlmacen,
                    'AITIPART' => 'AR',
                    'AIMTVDIS' => 'VL',
                    'AINROOCP' => 0,
                    'AICLIPRV' => $objeto->idCliente,
                    'AICODSOL' => $this->codUser,
                    'AIFECDIS' => date("Ymd"),
                    'AICODTRN' => $objeto->idTransporte,
                    'AINROPLC' => '',
                    'AIDSCREF' => 'Venta Local',
                    'AIDSCOBS' => 'Pedido',
                    'AITIPDOC' => '32',
                    'AINROREF' => $objeto->nuevo_pedido,
                    'AINROBLT' => 0,
                    'AISTSDIS' => 'S',
                    'AIATNPOR' => $this->userId,
                    'AISTS' => 'A',
                    'AIUSR' => $this->userId,
                    'AIJOB' => $this->job_as,
                    'AIJDT' => date("Ymd"),
                    'AIJTM' => date("His"),
                    'AISTSMIG' => '',
                    'AISUCVEN' => $objeto->codSucursal
                );

                $util->inserta_into_tabla_as400('LIBPRDDAT.MMAIREP', $arrayInsert);


                $arrayUpdate = array('FCCANACT' => $nueva_id_parte_salida);
                $arrayWhere = array(
                    ['FCCODCIA', '=', $this->codCia],
                    ['FCCODSUC', '=', $objeto->codSucursal],
                    ['FCCODELE', '=', '13'],
                );
                $util->actualiza_tabla_numeradores_mmfcrep($arrayWhere, $arrayUpdate);
            }
            //FIN - ENCABEZADO PARTE DE SALIDA

            //DETALLE PARTE DE SALIDA
            if ($nueva_id_parte_salida > 0) {
                foreach ($objeto->pedidodetalle as $detalle) {
                    $arrayWhere = array(
                        ['AJCODCIA', '=', $this->codCia],
                        ['AJCODSUC', '=', $detalle->codAlmacen],
                        ['AJNROPIS', '=', $nueva_id_parte_salida],
                        ['AJCODLIN', '=', $detalle->codLinea],
                        ['AJCODART', '=', $detalle->codArticulo],
                        ['AJCODORI', '=', $detalle->codOrigen],
                        ['AJCODMAR', '=', $detalle->codMarca],
                        ['AJSTS', '=', 'A']
                    );
                    if (!$util->selecciona_fila_from_tabla_db2('LIBPRDDAT.MMAJREP', $arrayWhere)) {
                        $arrayInsert = array(
                            'AJCODCIA' => $this->codCia,
                            'AJCODSUC' => $detalle->codAlmacen,
                            'AJNROPIS' => $nueva_id_parte_salida,
                            'AJCODLIN' => $detalle->codLinea,
                            'AJCODART' => $detalle->codArticulo,
                            'AJCODORI' => $detalle->codOrigen,
                            'AJCODMAR' => $detalle->codMarca,
                            'AJUMDUSO' => 'UN',
                            'AJCANNET' => $detalle->cantidadSolicitada,
                            'AJCANGOF' => $detalle->cantidadSolicitada,
                            'AJCODALM' => '',
                            'AJCODSEC' => '',
                            'AJCODEST' => '',
                            'AJSTSDIS' => 'S',
                            'AJPRICCA' => '',
                            'AJDSCPRE' => '',
                            'AJFECDIS' => date("Ymd"),
                            'AJSTS' => 'A',
                            'AJUSR' => $this->userId,
                            'AJJOB' => $this->job_as,
                            'AJJDT' => date("Ymd"),
                            'AJJTM' => date("His")
                        );
                        $util->inserta_into_tabla_as400('LIBPRDDAT.MMAJREP', $arrayInsert);

                        $util->actualiza_inventario_producto_almacen_as400($this->codCia, $objeto->idCliente, $detalle);
                    }
                }
            }
            //FIN - DETALLE PARTE DE SALIDA



            //TRACKING CABECERA MMQ1REP
            if ($nueva_id_parte_salida > 0) {
                $arrayWhere = array(
                    ['Q1STS', '=', 'A'],
                    ['Q1CODCIA', '=', $this->codCia],
                    ['Q1CODSUC', '=', $objeto->codSucursal],
                    ['Q1NROPED', '=', $objeto->idCotizacion],
                    ['Q1NROPDC', '=', $objeto->nuevo_pedido],

                );
                if (!$util->selecciona_fila_from_tabla_db2('LIBPRDDAT.MMQ1REP', $arrayWhere)) {

                    $dir_entrega = collect($direcciones)->filter(function ($value) {
                        return $value->altipdir === '03';
                    });

                    $cod_dir = $objeto->idDirecionEntrega; //str_pad($objeto->idDirecionEntrega, 2, 0, STR_PAD_LEFT);
                    $dir_ent2 = DB::connection('ibmi')->table('LIBPRDDAT.MMALREP')->where('ALITEM01', $cod_dir)->where('ALSTS', 'A')->first();
                    $dpto_entrega = ($dir_ent2) ? trim($dir_ent2->aldepart) : (($dir_entrega && is_array($dir_entrega) && sizeof($dir_entrega) > 0) ? trim($dir_entrega[0]->aldepart) : trim($direcciones[0]->aldepart));
                    $prov_entrega = ($dir_ent2) ? trim($dir_ent2->alprovin) : (($dir_entrega && is_array($dir_entrega) && sizeof($dir_entrega) > 0) ? trim($dir_entrega[0]->alprovin) : trim($direcciones[0]->alprovin));
                    //($dir_entrega && is_array($dir_entrega) && sizeof($dir_entrega) > 0) ? trim($dir_entrega[0]->alprovin) : ''

                    $objLog = new stdClass();
                    $objLog->dir_entrega = $dir_entrega;
                    $objLog->dir_ent2 = $dir_ent2;
                    $arrayIn = array(
                        'tabla' => 'LIBPRDDAT.MMQ1REP',
                        'mensaje' => 'DIRECCION DE ENTREGA',
                        'otro' => json_encode($objLog),
                        'created_at' => date("Y-m-d H:i:s")
                    );
                    $this->inserta_into_tabla('log_migraciones', $arrayIn);

                    $arrayInsert = array(
                        'Q1CODCIA' => $this->codCia,
                        'Q1CODSUC' => $objeto->codSucursal,
                        'Q1NROPED' => $objeto->idCotizacion,
                        'Q1NROPDC' => $objeto->nuevo_pedido,
                        'Q1NROPTE' => $nueva_id_parte_salida,
                        'Q1CODCLI' => $objeto->idCliente,
                        'Q1RAZSOC' => $objeto->razonSocial,
                        'Q1NVORUC' => $objeto->nroIdentidad,
                        'Q1DESTIN' => $dpto_entrega,
                        'Q1CODTRN' => $objeto->idTransporte,
                        'Q1ESTAMV' => '02',
                        'Q1STS' => 'A',
                        'Q1USR' => $this->userId,
                        'Q1JOB' => $prov_entrega,
                        'Q1JDT' => date("Ymd"),
                        'Q1JTM' => date("His"),
                        'Q1PGM' => $this->job_as,
                        'Q1MUSR' => $this->userId,
                        'Q1MJOB' => $this->job_as,
                        'Q1MJDT' => date("Ymd"),
                        'Q1MJTM' => date("His"),
                        'Q1MPGM' => $this->job_as
                    );
                    $util->inserta_into_tabla_as400('LIBPRDDAT.MMQ1REP', $arrayInsert);
                }
            }
            //FIN - TRACKING CABECERA MMQ1REP


            //TRACKING DETALLE MMQ0REP
            $arrayInsert = array(
                'Q0CODCIA' => $this->codCia,
                'Q0CODSUC' => $objeto->codSucursal,
                'Q0NROPED' => $objeto->idCotizacion,
                'Q0NROPDC' => $objeto->nuevo_pedido,
                'Q0ESTADO' => '02',
                'Q0OBSERV' => 'Pedido Generado',
                'Q0STA' => 'A',
                'Q0PGM' => $this->job_as,
                'Q0USU' => $this->userId,
                'Q0JOB' => $this->job_as,
                'Q0DATE' => date("Ymd"),
                'Q0HORA' => date("His")
            );

            $util->inserta_into_tabla_as400('LIBPRDDAT.MMQ0REP', $arrayInsert);
            //return false;

            //FIN - TRACKING DETALLE MMQ0REP


            //DB::commit();
            return $objeto->nuevo_pedido;
        } else         return false;
    }

    public function retorna_estatus_pedido(Request $request)
    {
        $codigo_cliente = $request->clientCode;
        $nro_cotizacion = $request->quoteCode;
        $nro_pedido = $request->orderCode;
        $estado = $request->orderStatus;
        //die($codigo_cliente . ' - ' . $nro_cotizacion . ' - ' . $nro_pedido . ' - ' . $estado);
        return '01';
    }


    public function inserta_into_tabla($tabla, $arrayInsert)
    {
        return DB::table($tabla)
            ->insertGetId($arrayInsert);
    }

    public function actualiza_tabla($tabla, $arrayWhere, $arrayUpdate)
    {
        return DB::table($tabla)
            ->where($arrayWhere)
            ->update($arrayUpdate);
    }

    public function selecciona_fila_from_tabla($tabla, $arrayWhere)
    {
        return DB::table($tabla)
            ->where($arrayWhere)
            ->first();
    }

    public function selecciona_from_tabla($tabla, $arrayWhere)
    {
        return DB::table($tabla)
            ->where($arrayWhere)
            ->get()
            ->toArray();
    }

    public function crear_cotizacion_pedido_as400()
    {
        set_time_limit(3000);
        echo "Fecha: " . date("Y-m-d H:i:s");
        $arrayWhere = array(
            ['as400_sync', '=', null]
        );
        $registros = $this->selecciona_from_tabla('ecommerce_orders', $arrayWhere);
        if ($registros && is_array($registros)) {
            foreach ($registros as $registro) {
                $numero_cotizacion_as = 0;
                $numero_pedido_as = 0;

                if (strlen($registro->as400_quote_number) == 0) {
                    $data = json_decode($registro->json_quote);
                    $nro_nueva_coti = $this->create_quote_as400($data);
                    die('COTIZACIÓN (' . $nro_nueva_coti . ') CREADA');
                }
                /*
                if (strlen($registro->as400_order_number) == 0 && $registro->as400_quote_number > 0) {
                    $data = json_decode($registro->json_order);
                    $nro_nuevo_pedido = $this->create_order_as400($data);
                    die('PEDIDO (' . $nro_nuevo_pedido . ') CREADO');
                }
                die('ERROR CREANDO COTIZACIÓN');
                */

                /*
                if (strlen($registro->as400_quote_number) == 0) {
                    $data = json_decode($registro->json_quote);
                    $obj = new \stdClass;
                    $obj->codTransportista = $data->codTransportista;
                    $obj->nroIdentidad = $data->nroIdentidad;
                    $obj->codCliente = $data->codCliente;
                    $obj->claseClienteId = $data->claseClienteId;
                    $obj->razonSocial = $data->razonSocial;
                    $obj->codAlmacen = $data->codAlmacen;
                    $obj->codSucursal = $data->codSucursal;
                    $obj->codMoneda = $data->codMoneda;
                    $obj->importeDescuento = $data->importeDescuento;
                    $obj->porcentajeDescuento = $data->porcentajeDescuento;
                    $obj->impImpuestos = $data->impImpuestos;
                    $obj->impTotal = $data->impTotal;
                    $obj->cotizaciondetalle = $data->cotizaciondetalle;

                    $response = $this->dataPost('cotizaciones/add', $obj);
                    if ($response->codigo == '1700') {
                        $numero_cotizacion_as = $response->mensajeapi[0]->codigo;
                        echo "<br>Nro Coti AS400: $numero_cotizacion_as";
                        $arrayUpdate = array(
                            'as400_quote_number' => ($numero_cotizacion_as > 0) ? $numero_cotizacion_as : null,
                        );
                        $this->actualiza_tabla('ecommerce_orders', array(['id', '=', $registro->id]), $arrayUpdate);

                        // *** ACTUALIZAR TABLA DE COTIZACIONES DB ECOMMERCE *** //
                        if ($numero_cotizacion_as > 0) {
                            $arrayUpdateEC = array('quote_code' => $numero_cotizacion_as);
                            $arrayWhereEC = array(
                                ['cart_id', '=', $registro->ecommerce_cart_id]
                            );
                            DB::connection('mysql')
                                ->table('quotes')
                                ->where($arrayWhereEC)
                                ->update($arrayUpdateEC);
                        }
                        // *** FIN ACTUALIZAR TABLA DE COTIZACIONES DB ECOMMERCE *** //
                    } else {
                        "<br>ERROR: LA COTIZACIÓN TEMPORAL $registro->tmp_quote_number NO PUDO SER CREADA EN EL AS400";
                        echo "<br>$response->mensaje";
                    }
                } else $numero_cotizacion_as = $registro->as400_quote_number;
                */


                if (strlen($registro->as400_order_number) == 0 && $numero_cotizacion_as > 0) {
                    echo "<br>$registro->as400_order_number - $numero_cotizacion_as";
                    $data = json_decode($registro->json_order);
                    //echo "<br>$registro->json_order";
                    if ($data) {
                        $data->idCotizacion = intval($numero_cotizacion_as);
                        foreach ($data->pedidodetalle as $detalle) {
                            $detalle->idCotizacion = intval($numero_cotizacion_as);
                        }
                        if ($response = $this->dataPost('pedidos/add', $data)) {
                            if ($response->codigo == '1700') {
                                $numero_pedido_as = $response->mensajeapi[0]->codigo;
                                echo "<br>Nro Pedido AS400: $numero_pedido_as";

                                if ($numero_cotizacion_as > 0 || $numero_pedido_as > 0) {
                                    $arrayUpdate = array(
                                        'as400_order_number' => ($numero_pedido_as > 0) ? $numero_pedido_as : null,
                                        'as400_sync' => ($numero_cotizacion_as > 0 && $numero_pedido_as > 0) ? date("Y-m-d H:i:s") : null
                                    );
                                    $this->actualiza_tabla('ecommerce_orders', array(['id', '=', $registro->id]), $arrayUpdate);

                                    // *** ACTUALIZAR TABLA DE PEDIDOS DB ECOMMERCE *** //
                                    $arrayUpdateEC = array(
                                        'quote_code' => $numero_cotizacion_as,
                                        'order_code' => $numero_pedido_as
                                    );
                                    $arrayWhereEC = array(
                                        ['quote_code', '=', $registro->tmp_quote_number]
                                    );
                                    DB::connection('mysql')
                                        ->table('orders')
                                        ->where($arrayWhereEC)
                                        ->update($arrayUpdateEC);
                                    // *** FIN ACTUALIZAR TABLA DE PEDIDOS DB ECOMMERCE *** //
                                }
                            } else {
                                "<br>ERROR: EL PEDIDO TEMPORAL $registro->tmp_order_number NO PUDO SER CREADO EN EL AS400";
                                echo "<br>$response->mensaje";
                                echo '<pre>';
                                print_r($data);
                                print_r($registro);
                            }
                        }
                    }
                }
            }
        }
        //$this->redirecciona('https://192.168.1.196:444/api/ecommerce/orders/postQuoteOrderAs', 15);
    }


    public function redirecciona($url = '', $tiempo = 25)
    {
        if (!empty($url)) {
            echo '<br>Por favor espere un momento...';
            sleep($tiempo);
            echo '<script type="text/javascript">';
            echo 'const url_redirect = "' . $url . '";';
            echo 'window.location.href = url_redirect;';
            echo '</script>';
            echo '<noscript>';
            echo '<meta http-equiv="refresh" content="0;url="+url_redirect />';
            echo '</noscript>';
        }
    }

    public function get_product_brands()
    {
        $rs = DB::table('part_trademarks')
            ->orderBy('name')
            ->select(['id', 'code', 'name'])
            ->distinct()
            ->get()->toArray();
        $rs = (is_array($rs) && sizeof($rs) > 0) ? $rs : array();
        return response()->json($rs, 200);
    }

    /**
     * validar existencia de productos
     */
    public function showExistenceProducts($sku, $qty)
    {
        $sku = str_replace('|', '/', $sku);
        ///die('Cantidad' . $qty);
        $vista_partes = DB::table('v_partes')
            ->select('stock', 'min_price', 'part_name', 'offer_price')
            ->where('sku', '=', $sku)
            ->first();

        if ($vista_partes) {
            # code...
            //verificar si tiene descuento
            $descuento = $vista_partes->offer_price ? true : false;
            $precio =  $descuento ? $vista_partes->offer_price : $vista_partes->min_price;

            if ($vista_partes->stock >= $qty) {
                $resultado = [
                    'stock' => $vista_partes ? $vista_partes->stock : 0,
                    'sale_price' =>  $precio,
                    'price' =>  $vista_partes->min_price,
                    'descuento' => $descuento,
                    'name' =>  $vista_partes ? $vista_partes->part_name : '',
                    'especificacionesTecnicas' => '',
                    'model_code' => [],
                ];
            } else {
                $resultado = [
                    'stock' =>  0,
                    'sale_price' =>  $precio,
                    'price' =>  $vista_partes->min_price,
                    'descuento' => $descuento,
                    'name' =>  $vista_partes ? $vista_partes->part_name : '',
                    'especificacionesTecnicas' => '',
                    'model_code' => [],
                ];
            }


            return response()->json($resultado);
        }

        $resultado = [
            'stock' => 0,
            'descuento' => false,
        ];

        return response()->json($resultado);
    }

    /*
    Busca existencia de productos en un almacén dado
    Entrada: Vector de productos a revisar existencia x almacén, codigo de almacén
    */
    public function get_products_stock_by_warehouse(Request $request)
    {
        $warehouse_code = $request->codAlmacen;
        $stockalmacendetalle = $request->stockalmacendetalle;
        foreach ($stockalmacendetalle as $part_wh) {
            $sku = $part_wh['codLinea'] . $part_wh['codOrigen'] . $part_wh['codMarca'] . $part_wh['codArticulo'];
            $array_skus[] = $sku;
            $part_wh['sku'] = $sku;
        }

        $warehouse_parts = DB::table('v_partes_por_almacen')
            ->select(['sku', 'stock'])
            ->where('warehouse_code', $warehouse_code)
            ->whereIn('sku', $array_skus)
            ->get()->toArray();

        $filtered_collection = array();
        foreach ($warehouse_parts as $part) {
            $filtered_collection = collect($stockalmacendetalle)->filter(function ($item) use ($part) {
                $sku = ($item['codLinea'] . $item['codOrigen'] . $item['codMarca'] . $item['codArticulo']);
                return ($sku === $part->sku && $part->stock >= $item['cantidadSolicitada']);
            });
        }

        if (sizeof($stockalmacendetalle) == sizeof($filtered_collection)) {
            $response = [
                'codigo' => '1700',
                'mensajeapi' => [
                    'titulo'  => 'Stock de Partes',
                    'stock'  => true,
                    'mensaje' => 'Stock de Partes',
                ],
                'respuesta' => 'Stock de Partes'
            ];
        } else {
            $response = [
                'codigo' => '1701',
                'mensajeapi' => [
                    'titulo'  => 'Stock de Partes',
                    'stock'  => false,
                    'mensaje' => 'No hay stock de las partes solicitadas',
                ],
                'respuesta' => 'No hay stock de las partes solicitadas'
            ];
        }

        return response()->json($response, 200);
    }

    /**
     * obtener tipos de direcciones
     */
    public function getAddressTypes()
    {
        $tipos_direcciones =    DB::table('v_tipos_direccion')
            ->select('code as codElemento', 'abrv as descripAbreviatura', 'name as descripLarga')
            ->where('reg_status', 1)->get();


        return response()->json($tipos_direcciones);
    }


    /**
     * obtener  los almacenes 
     */
    public function getAlmacenes()
    {

        $select = [
            "address as sucursalDireccion",
            "code as sucursalId",
            "name as sucursalNombre",
            "phone_number as sucursalTelefono",
        ];

        $respuesta =    DB::table('v_almacenes_ecommerce')->select($select)->where('reg_status', 1)->get();

        return response()->json($respuesta);
    }
    /**
     * obtener  los almacenes 
     */
    public function getSucursales()
    {
        $select = [
            "code as sucursalId",
            "name as sucursalNombre",
            "phone_number as sucursalTelefono",
        ];

        $respuesta =    DB::table('v_sucursales_ecommerce')->select($select)->selectRaw("CONCAT(address, ' - DISTRITO ', dist_name) as \"sucursalDireccion\"")->where('reg_status', 1)->get();

        return response()->json($respuesta);
    }


    /**
     * obtener  los metodos de pago del cliente 
     * parametro cliente_code 
     */
    public function getCustomerPaymentMethods()
    {

        $select = [
            "address as sucursalDireccion",
            "code as sucursalId",
            "name as sucursalNombre",
            "phone_number as sucursalTelefono",
        ];

        $respuesta =    DB::table('v_customer_payment_methods')->select($select)->where('reg_status', 1)->get();

        return response()->json($respuesta);
    }

    public function postOrderPayment(Request $request)
    {
        $response = new \stdClass();
        $response->codigo = 1701;
        $mensajeapi = [
            'codigo' => '1701',
            'datos' => $request->all()
        ];
        $response->mensajeapi = $mensajeapi;
        return response()->json($response, 200);
    }

    /*
    public function post_deposit_payment(Request $request)
    {
        $response = new \stdClass();
        $response->codigo = 1700;
        $mensajeapi = [
            'titulo' => 'Depósito realizado',
            'codigo' => '0',
            'datos' => $request->all()
        ];
        $response->mensajeapi = $mensajeapi;
        return response()->json($response, 200);
    }
    */

    public function post_deposit_payment(Request $request)
    {
        //FALTA POR TERMINAR 
        $rules = [
            'bancoId' => [
                'required',
                //Rule::in(['02', '09', '011']),
            ],
            'idCliente' => 'required',
            'ctaBco' => 'required',
            'monedaId' => 'required',
            'nroOprcn' => 'required',
            'importe' => 'required',
        ];
        $messages = [
            'required' => 'El campo :attribute es obligatorio'
        ];
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }


        $arrayDeposito = array(
            'company_code' => ($request->codCia) ? $request->codCia : '10',
            'subsidiary_code' => '01',
            'customer_code' => $request->idCliente,
            'bank_code' => $request->bancoId,
            'bank_account' => $request->ctaBco,
            'currency_code' => $request->monedaId,
            'operation_number' => $request->nroOprcn,
            'amount' => $request->importe,
            'deposit_status' => 'P',
            'seller_code' => $this->codUser,
            'user_code' => $this->userId,
            'job' => $this->job
        );
        //die(print_r($arrayDeposito));

        $response = new \stdClass();

        $utilidades = new Utilidades();

        if ($utilidades->regitra_deposito_bancario((object)$arrayDeposito)) {
            $response->codigo = 1700;
            $mensajeapi = [
                'titulo' => 'Depósito realizado',
                'codigo' => '0',
            ];
        } else {
            $response->codigo = 1701;
            $mensajeapi = [
                'titulo' => 'Error registrando depósito',
                'codigo' => '0',
                'datos' => $request->all()
            ];
        }
        $response->mensajeapi = $mensajeapi;
        return response()->json($response, 200);
    }

    /**
     * metodo encargado de guardar colaboradores 
     */
    public function addContact(Request $request)
    {

        //buscar cliente por correo
        $cliente = Customer::where('code', $request->idCliente)->first();

        //validar existencia de cliente 
        if (!$cliente) {
            $response = [
                'codigo' => '1701',
                'mensajeapi' => [
                    'titulo'  => 'Contacto',
                    'codigo'  => 0,
                    'mensaje' => '*No Se creó el contacto *',
                ],
                'respuesta' => 'El cliente no existe',
            ];

            return response()->json($response, 200);
        }

        //buscar existencia de correo
        $existe_correo = CustomerContact::where('contact_email', $request->correoElectronico)
            ->where('customer_id', $cliente->id)
            ->first();
        //validar existencia de correo 
        if ($existe_correo) {
            $response = [
                'codigo' => '1701',
                'mensajeapi' => [
                    'titulo'  => 'Contacto',
                    'codigo'  => $existe_correo->id,
                    'mensaje' => '*No Se creó el contacto *',
                ],
                'respuesta' => 'El correo ya esta en uso',
            ];

            return response()->json($response, 200);
        }

        try {
            DB::beginTransaction();
            $nombre_contacto = substr("$request->primerNombre $request->segundoNombre $request->primerApellido $request->segundpApellido", 1, 80);

            //consultar ultimo contacto
            $contacto_item = DB::connection('ibmi')->table('LIBPRDDAT.CCPCREP')->where('PCCODCLI', $cliente->code)->max('PCITEM01') + 1;

            //crear el contacto
            $contacto = CustomerContact::create([
                'customer_id' => $cliente->id,
                'work_position_id' => 0,
                'contact_name' => $nombre_contacto,
                'contact_phone' => $request->telefononro1,
                'contact_email' => $request->correoElectronico,
                'reg_status' => 1,
                'identification_type_id' => $request->identityType,
                'identification_number' => $request->numeroDocumento,
                'customer_contact_number' => $contacto_item

            ]);


            //agregar al as400 
            DB::connection('ibmi')->table('LIBPRDDAT.CCPCREP')
                ->insert([
                    'PCCODCIA' => '10',
                    'PCCODCLI' => "$cliente->code",
                    'PCITEM01' => "$contacto_item",
                    'PCPRAPLL' => "$request->primerApellido",
                    'PCSGAPLL' => "$request->segundpApellido",
                    'PCPRNOMB' => "$request->primerNombre",
                    'PCSGNOMB' => "$request->segundoNombre",
                    'PCDOCIDE' => "$request->numeroDocumento",
                    'PCCODCAR' => "$request->cargo",
                    'PCCORREO' => "$request->correoElectronico",
                    'PCTELEF1' => $request->telefononro1,
                    'PCSTS' => 'A',
                    'PCUSR' => 'ECOMMERCE',
                    'PCJDT' => "" . date('Ymd'),
                    'PCJTM' => "" . date('His'),
                    'PCPGM' => ''
                ]);

            $response = [
                'codigo' => '1700',
                'mensajeapi' => [
                    'titulo'  => 'Contacto',
                    'codigo'  => $contacto->id,
                    'mensaje' => '* Se creó Contacto *',
                ],
                'respuesta' => 'Se creó Contacto ',
            ];

            DB::commit();

            return response()->json($response, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['mensaje' => $e->getMessage()], 502);
        }
    }

    public function deleteContact($idcliente, $idContacto)
    {
        //buscar existencia del contacto
        $existe_contacto = CustomerContact::where('id', $idContacto)->first();

        if (!$existe_contacto) {
            $response = [
                'codigo' => '1701',
                'mensajeapi' => [
                    'titulo'  => 'Contacto',
                    'codigo'  => $existe_contacto->id,
                    'mensaje' => '*No existe el contacto *',
                ],
                'respuesta' => 'El existe el contacto',
            ];

            return response()->json($response, 200);
        }

        //eliminado logico
        $existe_contacto->reg_status = 0;
        $existe_contacto->save();

        $response = [
            'codigo' => '1700',
            'mensajeapi' => [
                'titulo'  => 'Contacto',
                'codigo'  => $existe_contacto->id,
                'mensaje' => '* Se elimino Contacto *',
            ],
            'respuesta' => 'Se elimino Contacto ',
        ];

        return response()->json($response, 200);
    }


    public function verifyDocument($document_number, $document_type)
    {
        //variables para el select
        $select = ["id", "document_number as ruc", "name_social_reason as trade_name", "name_social_reason as business_name"];
        $select_addres = [
            "customer_id", "dpto_code as department_code", "prov_code as province_code", "dist_code as district_code",
            "tipo_direccion_id as road_type_code", "number as address_number", "direccion_completa as address",
            "road_name"
        ];

        //obtener cliente con su ubicacion
        $cliente = Customer::select($select)
            ->with(['direcciones' => function ($query) use ($select_addres) {
                $query->select($select_addres);
                $query->take(1);
            }])
            //->where('document_type_id', 6)
            ->where('document_number', $document_number)
            ->first();


        //validar si existe el cliente                    
        if (!$cliente) {

            return response()->json(["cliente" => (object) [],], 200);
        }



        //agregar ubicacion al objeto del cliente
        $cliente->business_address = count($cliente->direcciones) > 0 ? $cliente->direcciones[0]->address : '';
        $cliente->telephone = "";
        $cliente->addresses =   $cliente->direcciones ? $cliente->direcciones : '';



        return response()->json(["cliente" => $cliente,], 200);
    }

    public function get_products_x_trademark(Request $request)
    {
        $select = [
            'factory_code',
        ];
        //die(print_r($select));
        $rs = DB::connection('pgsql')->table('v_partes_ecommerce')->distinct()->select($select)->where('reg_status', 1)->where('trademark_code', trim($request->trademark_code))->get()->toArray();
        $response = new \stdClass();
        $response->cantidad = sizeof($rs);
        $response->data = $rs;
        return response()->json($response);
    }


    public function get_products_by_params_refactored(Request $request)
    {
        $objeto = new \stdClass();
        $limit = ($request->limit) ? (int)$request->limit : 12;
        $by_page = ($request->limit) ? $request->limit : 12;
        $page = ($request->offset) ? $request->offset : 1;
        $offset = ($page * $by_page) - $by_page;

        $line = $request->line;
        $brand = $request->brand;
        $system = $request->system;
        $subsystem = $request->subsystem;
        $searched_product = $request->searched_product;


        $model = str_replace('|', '/', $request->model);
        $str_year = $request->year;
        $arrayYear = explode('|', $str_year);
        $year = $arrayYear[0];
        $engine = $arrayYear[1];
        $hp = $arrayYear[2];
        $traction = $arrayYear[3];

        $str_engine = ($engine <> '*') ? " veh_engine ilike '$engine'" : '';
        $str_hp = ($hp <> '*') ? "  veh_hp ilike '$hp'" : '';
        $str_traction = ($traction <> '*') ? "  veh_traction ilike '$traction'" : '';
        $sqlWhere = '';
        $sqlWhere = $str_engine;
        $sqlWhere .= ($str_engine <> '' && $str_hp <> '') ? ' and' . $str_hp : $str_hp;
        $sqlWhere .= (($str_engine <> '' || $str_hp <> '') && $str_traction <> '') ? ' and' . $str_traction : $str_traction;
        if (!$sqlWhere) {
            return response()->json('Faltan parámetros en consulta SQL', 401);
        }
    }

    public function get_products_by_part_code_part_name_factory_code(Request $request) //$param, $customer_code = null
    {
        $response = new \stdClass();

        $by_page = ($request->limit) ? $request->limit : 12;
        $page = ($request->offset) ? $request->offset : 1;
        $param = trim(strtoupper($request->param));
        $param_s = str_replace(' ', '%', $param);
        $offset = ($page * $by_page) - $by_page;
        $customer_code = ($request->customer_code) ? $request->customer_code : null;
        $brand_code = $request->brand;

        $select = ['vpr.part_detail_id as id', 'vpr.sku', 'vpr.part_code as item_code', 'vpr.line_code', 'vpr.origin_code as source_code', 'vpr.origin_name as source_name', 'vpr.trademark_code as brand_code', 'vpr.trademark_name as brand_name', 'vpr.factory_code', 'vpr.subsystem_code', 'vpr.subsystem_name', 'vpr.system_code', 'vpr.system_name', 'vpr.measure_unit_code as unit', 'vpr.part_name as name', 'vpr.rotation', 'vpr.principal_image as image', 'vpr.stock', 'vpr.weight'];
        if (strlen($customer_code) == 6) {
            array_push($select, 'vpr.min_price as price');
            array_push($select, 'vpr.offer_price as sale_price');
        }


        $total = DB::table('v_partes AS vp')->select($select)
            ->join('part_detail_replacements AS pr', 'vp.part_detail_id', '=', 'pr.part_detail_id')
            ->join('part_part_details as ppd', 'pr.part_detail_last_replace_id', '=', 'ppd.id')
            ->join('v_partes AS vpr', 'ppd.part_id', '=', 'vpr.part_id')
            ->where('vp.min_price', '>', 0)
            ->where('vp.part_code', 'ilike', '%' . $param . '%')
            ->orWhere('vp.part_name', 'ilike', '%' . $param_s . '%')
            ->orWhere('vp.factory_code', 'ilike', '%' . $param_s . '%')
            ->orWhere('vp.product_features', 'ilike', '%' . $param_s . '%')
            ->orWhere('vp.product_remarks', 'ilike', '%' . $param_s . '%')
            ->orWhere('vp.sku', 'ilike', '%' . $param . '%');

        $total_registros = DB::table('v_partes_ecommerce AS vpr')->select($select)
            ->where('vpr.part_code', $param)
            ->where('vpr.min_price', '>', 0)
            ->where('vpr.part_code', 'ilike', '%' . $param_s . '%')
            ->orWhere('vpr.part_name', 'ilike', '%' . $param_s . '%')
            ->orWhere('vpr.factory_code', 'ilike', '%' . $param_s . '%')
            ->orWhere('vpr.product_features', 'ilike', '%' . $param_s . '%')
            ->orWhere('vpr.product_remarks', 'ilike', '%' . $param_s . '%')
            ->orWhere('vpr.sku', 'ilike', '%' . $param_s . '%')
            ->union($total)
            ->get()->count();


        $parte = DB::table('v_partes AS vp')->select($select)
            ->selectRaw("case substring(vpr.part_name,0,length('" . $param . "')+1)
            when '" . $param . "' then vpr.weight*10
            else vpr.weight
            end as peso")
            ->join('part_detail_replacements AS pr', 'vp.part_detail_id', '=', 'pr.part_detail_id')
            ->join('part_part_details as ppd', 'pr.part_detail_last_replace_id', '=', 'ppd.id')
            ->join('v_partes AS vpr', 'ppd.part_id', '=', 'vpr.part_id')
            ->where('vp.min_price', '>', 0)
            ->where('vp.part_code', 'ilike', '%' . $param_s . '%')
            ->orWhere('vp.part_name', 'ilike', '%' . $param_s . '%')
            ->orWhere('vp.factory_code', 'ilike', '%' . $param_s . '%')
            ->orWhere('vp.product_features', 'ilike', '%' . $param_s . '%')
            ->orWhere('vp.product_remarks', 'ilike', '%' . $param_s . '%')
            ->orWhere('vp.sku', 'ilike', '%' . $param_s . '%');


        $partes = DB::table('v_partes_ecommerce AS vpr')->select($select)
            ->selectRaw("case substring(vpr.part_name,0,length('" . $param . "')+1)
            when '" . $param . "' then vpr.weight*10
            else vpr.weight
            end as peso")
            ->where('vpr.part_code', $param)
            ->where('vpr.min_price', '>', 0)
            ->where('vpr.part_code', 'ilike', '%' . $param_s . '%')
            ->orWhere('vpr.part_name', 'ilike', '%' . $param_s . '%')
            ->orWhere('vpr.factory_code', 'ilike', '%' . $param_s . '%')
            ->orWhere('vpr.product_features', 'ilike', '%' . $param_s . '%')
            ->orWhere('vpr.product_remarks', 'ilike', '%' . $param_s . '%')
            ->orWhere('vpr.sku', 'ilike', '%' . $param_s . '%')
            ->union($parte)
            ->offset($offset)
            ->limit($by_page)
            ->orderBy('peso', 'DESC')
            ->get()->toArray();
        //->toSql();
        //die(print_r($partes));

        if (sizeof($partes) > 0) {
            $array = [
                'search_date' => date("Y-m-d"),
                'search_time' => date("H:i:s"),
                'searched_product' => $param,
                'product_found' => true,
                'customer_code' => $customer_code,
                'ip' => \Request::ip(),
                'created_at' => date("Y-m-d H:i:s")
            ];
            ProductSearchLog::create($array);

            foreach ($partes as $producto) {
                $producto->gallery = $this->retorna_vector_galeria_imagenes($producto->id, 0);
                $producto->gallery360 = $this->retorna_vector_galeria_imagenes($producto->id, 1);
                $producto->technical_spec = '';
                $producto->num_of_sale = 0;
                $producto->stock = ($producto->stock > 0) ? 1 : 0;

                //SI EL CLIENTE EXISTE, VALIDA EL PRECIO EN OFERTA
                if (property_exists($producto, 'sale_price')) {
                    $producto->sale_price = ($producto->sale_price <> null) ? $producto->sale_price : $producto->price;
                }

                //VALIDAR EL ORIGEN DEL PRODUCTO
                $producto = $this->valida_origen_parte($producto);
            }

            $response->total = $total_registros;
            $response->data = $partes;
        } else {
            $array = [
                'search_date' => date("Y-m-d"),
                'search_time' => date("H:i:s"),
                'searched_product' => $param,
                'product_found' => false,
                'customer_code' => $customer_code,
                'ip' => \Request::ip(),
                'created_at' => date("Y-m-d H:i:s")
            ];
            ProductSearchLog::create($array);

            $response->total = 0;
            $response->data = array();
        }
        return response()->json($response);
    }

    public function generate_report_not_found_products(Request $request)
    {
        $rules = [
            'from_date' => 'required|date',
            'to_date' => 'required|date',
        ];
        $messages = [
            'required' => 'El campo :attribute es obligatorio'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        $from_date = $request->from_date;
        $to_date = $request->to_date;

        $select = ['search_date', 'searched_product', 'customer_code'];
        $products = DB::table('product_search_logs')->select($select)->where('product_found', 0)->whereBetween('search_date', [$from_date, $to_date])->get()->toArray();


        //** GENERAR ARCHIVO EXCEL **//
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // encabezados
        $header = [
            'A4' => 'FECHA',
            'B4' => 'PRODUCTO BUSCADO',
            'C4' => 'CÓD. CLIENTE',
        ];
        foreach ($header as $key => $value) {
            $sheet->setCellValue($key, $value);
        }

        $i = 5;
        foreach ($products as $product) {
            $sheet->setCellValue("A$i", $product->search_date);
            $sheet->setCellValue("B$i", $product->searched_product);
            $sheet->setCellValue("C$i", $product->customer_code);
            $i++;
        }

        $logo = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
        $logo->setName('Logo');
        $logo->setDescription('Logo');
        $logo->setPath(base_path() . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "imagenes" . DIRECTORY_SEPARATOR . 'logo.png'); /* put your path and image here */
        $logo->setCoordinates('A1');
        $logo->setWidth(60);
        $logo->setWorksheet($spreadsheet->getActiveSheet());

        $sheet->setCellValue('B1', 'REPORTE DE PRODUCTOS NO ENCONTRADOS POR CLIENTES ECOMMERCE');
        $sheet->setCellValue('B2', 'FECHA DESDE: ' . $from_date . ' HASTA: ' . $to_date);
        $sheet->getStyle('B1:B3')->getFont()->setBold(true);
        $sheet->getStyle('B1:B3')->getFont()->setSize(14);

        $sheet->getStyle('A4:C4')->getFont()->setBold(true);
        $sheet->getStyle('A4:C4')->getFont()->setSize(12);

        // auto ancho
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }

        $filename = "REPORTE_PRODUCTOS_NO_ENCONTRADOS-" . $from_date . "-" . $to_date . ".xls";
        $filePath = base_path() . DIRECTORY_SEPARATOR . "storage" . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "xls" . DIRECTORY_SEPARATOR . $filename;
        $writer = new Xls($spreadsheet);

        try {
            $writer->save($filePath);
        } catch (Exception $e) {
            exit($e->getMessage());
        }
    }

    private function filterBy($request, $prefijo = '')
    {
        $where = $prefijo . 'min_price > 0';
        $line = " and " . $prefijo . "line_code IN('" . str_replace(",", "','", $request->line_code) . "')";
        $brand_code = $prefijo . "trademark_code IN('" . str_replace(",", "','", $request->brand_code) . "')";

        if ($request->line_code) {
            $where .= $line;
        } else {
            if (!$request->skus) {
                $where .= "and " . $prefijo . "line_code = '01'";
            }
        }

        if ($request->source_code) {
            $where = ($where != '') ? $where . ' AND ' : $where;
            $where .= $prefijo . "origin_code = $request->source_code";
        }
        if ($request->brand_code != '') {
            $where = ($where != '') ? $where . ' AND ' : $where;
            $where .= $brand_code;
        }
        if ($request->category_code) {
            $where = ($where != '') ? $where . ' AND ' : $where;
            $where .= $prefijo . "system_code = '$request->category_code'";
        }
        if ($request->subcategory_code) {
            $where = ($where != '') ? $where . ' AND ' : $where;
            $where .= $prefijo . "subsystem_code = '$request->subcategory_code'";
        }
        if ($request->model_code) {
            $where = ($where != '') ? $where . ' AND ' : $where;
            $where .= $prefijo . "model_code LIKE '%$request->model_code%'";
        }
        if ($request->year) {
            $where = ($where != '') ? $where . ' AND ' : $where;
            $where .= $prefijo . "year LIKE '%$request->year%'";
        }
        if ($request->item_code) {
            $where = $prefijo . "item_code = '$request->item_code' OR item_replacecode = '$request->item_code'";
        }
        if ($request->sku) {
            $where = $prefijo . "sku = '$request->sku'";
        }


        return $where;
    }

    public function retorna_vector_galeria_imagenes($part_detail_id, $is_360 = 0)
    {
        $vector_img = array();
        $imagenes = DB::table('part_detail_images')->select(['image'])->where('part_detail_id', $part_detail_id)->where('is_360', $is_360)->get()->toArray();
        foreach ($imagenes as $imagen) {
            array_push($vector_img, $imagen->image);
        }
        return $vector_img;
    }

    public function valida_origen_parte($datos_parte)
    {
        $arrayMarcas = ['001', '645', '646', '647', '649', '650', '651', '652', '653', '655', '656', '657', '658', '659', '660', '661', '662', '663', '880', '219', '815', '861', '889', '862'];

        if ($datos_parte->source_code === '01' &&  !in_array($datos_parte->brand_code, $arrayMarcas)) {
            $datos_parte->source_code_original = $datos_parte->source_code;
            $datos_parte->source_name_original = $datos_parte->source_name;

            //FALTA VALIDAR SI LA MARCA PERTENECE A LA LINEA, EN ESE CASO ES ORIGINAL 
            $datos_parte->source_code = '02';
            $datos_parte->source_name = 'IMPORTADO';

            return $datos_parte;
        }

        $arrayMarcas = ['001', '645', '646', '647', '649', '650', '651', '652', '653', '655', '656', '657', '658', '659', '660', '661', '662', '663', '880', '219', '815', '861', '889', '862'];

        if ($datos_parte->source_code !== '01' &&  in_array($datos_parte->brand_code, $arrayMarcas)) {
            $datos_parte->source_code_original = $datos_parte->source_code;
            $datos_parte->source_name_original = $datos_parte->source_name;

            $datos_parte->source_code = '01';
            $datos_parte->source_name = 'ORIGINAL';

            return $datos_parte;
        }

        $datos_parte->source_code_original = $datos_parte->source_code;
        $datos_parte->source_name_original = $datos_parte->source_name;
        return $datos_parte;
    }
}
