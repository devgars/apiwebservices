<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

class EcommerceSyncController extends Controller
{
    public function load_veh_models()
    {
        $whereInField = 'resource_id';
        $whereInArray = array(7);
        $arraySelect = ['id', 'code', 'name', 'resource_id'];
        $array_tipos = $this->selecciona_from_tabla_where_in('gen_resource_details', $whereInField, $whereInArray, $arraySelect);

        $sql = "select obcodlin,obcodmod,obdscmod,obanomod as anio,obsecuen,obmotor,obcajac,obcoron,obcate01,obhp,obtracc,obejpst,obejdel,oborigm,obsts
        from LIBPRDDAT.MMOBREP
        WHERE OBSTS = 'A'";
        $vehiculos = DB::connection('ibmi')->select(DB::raw($sql));
        //echo '<pre>';
        //die(print_r($vehiculos));
        //die('C: ' . count($vehiculos));
        if ($vehiculos && is_array($vehiculos)) {
            foreach ($vehiculos as $vehiculo) {
                if ($linea_id = $this->busca_datos_vector_resources($vehiculo->obcodlin, 7, $array_tipos)) {
                    $arrayWhere = array(
                        ['model_code', '=', strtoupper(trim($vehiculo->obcodmod))]
                    );
                    if (!$datos_modelo = $this->selecciona_fila_from_tabla('veh_models', $arrayWhere)) {
                        $arrayInsert = array(
                            'line_id' => $linea_id,
                            'model_code' => strtoupper(trim($vehiculo->obcodmod)),
                            'model_description' => utf8_encode(strtoupper(trim($vehiculo->obdscmod))),
                            'reg_status' => 1
                        );
                        //print_r($arrayInsert);
                        $this->inserta_into_tabla('veh_models', $arrayInsert);
                        $datos_modelo = $this->selecciona_fila_from_tabla('veh_models', $arrayWhere);
                    }
                } else {
                    echo '<br>LINEA NO ENCONTRADA: ' . $vehiculo->obcodlin;
                }

                $arrayWhere = array(
                    ['model_id', '=', $datos_modelo->id],
                    ['veh_year', '=', $vehiculo->anio],
                    ['veh_hp', '=', strtoupper(trim($vehiculo->obhp))],
                    ['veh_traction', '=', strtoupper(trim($vehiculo->obtracc))],
                    //['veh_engine', '=', strtoupper(trim($vehiculo->obmotor))],
                    //['veh_gearbox', '=', strtoupper(trim($vehiculo->obcajac))],
                    //['veh_front_axle', '=', strtoupper(trim($vehiculo->obejdel))],
                    //['veh_rear_axle', '=', strtoupper(trim($vehiculo->obejpst))],
                );
                if (!$datos_vehiculo = $this->selecciona_fila_from_tabla('veh_vehicles', $arrayWhere)) {
                    $arrayInsert = array(
                        'model_id' => $datos_modelo->id,
                        'veh_year' => $vehiculo->anio,
                        'veh_hp' => strtoupper(trim($vehiculo->obhp)),
                        'veh_traction' => strtoupper(trim($vehiculo->obtracc)),
                        'veh_engine' => strtoupper(trim($vehiculo->obmotor)),
                        'veh_gearbox' => strtoupper(trim($vehiculo->obcajac)),
                        'veh_front_axle' => strtoupper(trim($vehiculo->obejdel)),
                        'veh_rear_axle' => strtoupper(trim($vehiculo->obejpst)),
                        'veh_order' => $vehiculo->obsecuen,
                        'veh_category_code' => $vehiculo->obcate01,
                        'reg_status' => 1,
                    );
                    //print_r($arrayInsert);
                    $this->inserta_into_tabla('veh_vehicles', $arrayInsert);
                }
            }
        }
    }

    public function load_part_vehicles()
    {
        ini_set('max_execution_time', 0);
        $whereInField = 'resource_id';
        $whereInArray = array(7);
        $arraySelect = ['id', 'code', 'name', 'resource_id'];
        $array_tipos = $this->selecciona_from_tabla_where_in('gen_resource_details', $whereInField, $whereInArray, $arraySelect);

        $array_modelos = $this->selecciona_from_tabla('veh_models', array(['reg_status', '=', 1]), ['id', 'model_code']);
        //echo '<pre>';
        //die(print_r($array_modelos));
        $sql = "select occodcia,oclinart,occodart,oclinmod,occodmod,ocsecuen,occodsis,occodsbs,ocaux1,ocaux2,ocaux3,ocsts
        from LIBPRDDAT.MMOCREP
        WHERE OCSTS = 'A'";
        $datos_partes_modelos = DB::connection('ibmi')->select(DB::raw($sql));
        echo '<pre>';
        //die(print_r($vehiculos));
        if ($datos_partes_modelos && is_array($datos_partes_modelos)) {
            foreach ($datos_partes_modelos as $parte_modelos) {
                $part_line_id = $this->busca_datos_vector_resources($parte_modelos->oclinart, 7, $array_tipos);
                $linea_id = $this->busca_datos_vector_resources($parte_modelos->oclinmod, 7, $array_tipos);
                $model_id = $this->busca_datos_vector_modelos(strtoupper(trim($parte_modelos->occodmod)), $array_modelos);


                if ($datos_vehiculos = $this->retorna_vehiculos_dado_linea_modelo($linea_id, strtoupper(trim($parte_modelos->occodmod)))) {
                    if (is_array($datos_vehiculos)) {
                        //die(print_r($datos_vehiculos));
                        foreach ($datos_vehiculos as $vehiculo) {
                            $part_detail_id = 0;
                            $part_id = 0;
                            if ($datos_parte = $this->retorna_parte_lineas($part_line_id, strtoupper(trim(utf8_encode($parte_modelos->occodart))))) {
                                if (is_array($datos_parte)) {
                                    foreach ($datos_parte as $parte) {
                                        //die(print_r($datos_parte));
                                        $part_detail_id = $parte->id;
                                        $part_id = $parte->part_id;
                                        //echo '<br>Model: ' . $parte_modelos->occodmod . '- part_line_id: ' . $part_line_id . ' - model_id: ' . $model_id . ' - Linea: ' . $linea_id . ' - Part: ' . $part_id . ' - PartDetailID: ' . $part_detail_id;
                                        $arrayWhere = array(
                                            ['part_detail_id', '=', $part_detail_id],
                                            ['vehicle_id', '=', $vehiculo->vehicle_id],
                                        );
                                        //print_r($arrayWhere);
                                        if (!$this->selecciona_fila_from_tabla('part_detail_vehicles', $arrayWhere)) {
                                            $arrayInsert = array(
                                                'part_detail_id' => $part_detail_id,
                                                'vehicle_id' => $vehiculo->vehicle_id,
                                                'veh_order' => 1,
                                                'reg_status' => 1
                                            );
                                            $this->inserta_into_tabla('part_detail_vehicles', $arrayInsert);
                                        }
                                    }
                                }
                            } else {
                                echo '<br>LINEA-PARTE NO EXISTE: ' . $linea_id . '-' . strtoupper(trim($parte_modelos->occodart));
                            }
                        }
                    }
                }



                /*
                $arrayWhere = array(
                    ['model_code', '=', strtoupper(trim($vehiculo->occodmod))]
                );
                if (!$datos_modelo = $this->selecciona_fila_from_tabla('veh_models', $arrayWhere)) {
                    $arrayInsert = array(
                        'line_id' => $linea_id,
                        'model_code' => strtoupper(trim($vehiculo->obcodmod)),
                        'model_description' => strtoupper(trim($vehiculo->obdscmod)),
                        'reg_status' => 1
                    );
                    $this->inserta_into_tabla('veh_models', $arrayInsert);
                    $datos_modelo = $this->selecciona_fila_from_tabla('veh_models', $arrayWhere);
                }

                $arrayWhere = array(
                    ['model_id', '=', $datos_modelo->id],
                    ['veh_year', '=', $vehiculo->anio],
                    ['veh_hp', '=', strtoupper(trim($vehiculo->obhp))],
                    ['veh_traction', '=', strtoupper(trim($vehiculo->obtracc))],
                    ['veh_engine', '=', strtoupper(trim($vehiculo->obmotor))],
                    ['veh_gearbox', '=', strtoupper(trim($vehiculo->obcajac))],
                    ['veh_front_axle', '=', strtoupper(trim($vehiculo->obejdel))],
                    ['veh_rear_axle', '=', strtoupper(trim($vehiculo->obejpst))],
                );
                if (!$datos_vehiculo = $this->selecciona_fila_from_tabla('veh_vehicles', $arrayWhere)) {
                    $arrayInsert = array(
                        'model_id' => $datos_modelo->id,
                        'veh_year' => $vehiculo->anio,
                        'veh_hp' => strtoupper(trim($vehiculo->obhp)),
                        'veh_traction' => strtoupper(trim($vehiculo->obtracc)),
                        'veh_engine' => strtoupper(trim($vehiculo->obmotor)),
                        'veh_gearbox' => strtoupper(trim($vehiculo->obcajac)),
                        'veh_front_axle' => strtoupper(trim($vehiculo->obejdel)),
                        'veh_rear_axle' => strtoupper(trim($vehiculo->obejpst)),
                        'veh_order' => $vehiculo->obsecuen,
                        'veh_category_code' => $vehiculo->obcate01,
                        'reg_status' => 1,
                    );
                    $this->inserta_into_tabla('veh_vehicles', $arrayInsert);
                }
                */
            }
        }
    }

    public function busca_datos_vector_resources($search, $resource_id, $array)
    {
        foreach ($array as $fila) {
            if ($fila->resource_id == $resource_id && $fila->code === $search) {
                return $fila->id;
            }
        }
        return null;
    }

    public function busca_datos_vector_modelos($search, $array)
    {
        foreach ($array as $fila) {
            if ($fila->model_code === $search) {
                return $fila->id;
            }
        }
        return null;
    }

    public function selecciona_from_tabla($tabla, $arrayWhere = '', $arraySelect = '')
    {
        if (empty($arraySelect)) $arraySelect = ['*'];
        if (empty($arrayWhere)) $arrayWhere = ['reg_status', '=', 1];
        return DB::table($tabla)
            ->select($arraySelect)
            ->where($arrayWhere)
            ->get()
            ->toArray();
    }

    public function selecciona_from_tabla_where_in($tabla, $whereInField, $whereInArray, $arraySelect = '', $orderBy = '')
    {
        if (empty($arraySelect)) $arraySelect = ['*'];
        if (empty($orderBy)) $orderBy = $whereInField;

        return DB::table($tabla)
            ->select($arraySelect)
            ->whereIn($whereInField, $whereInArray)
            ->orderBy($orderBy)
            ->get()
            ->toArray();
    }

    public function selecciona_fila_from_tabla($tabla, $arrayWhere)
    {
        return DB::table($tabla)
            ->where($arrayWhere)
            ->first();
    }

    public function inserta_into_tabla($tabla, $arrayInsert)
    {
        return DB::table($tabla)
            ->insertGetId($arrayInsert);
    }

    public function retorna_parte_lineas($linea_id, $part_code)
    {
        return DB::table('part_parts AS p')
            ->join('part_part_details AS pd', 'p.id', '=', 'pd.part_id')
            ->distinct()
            ->select(['pd.id', 'pd.part_id', 'pd.line_id'])
            ->where('p.code', '=', $part_code)
            ->where('pd.line_id', '=', $linea_id)
            ->get()->toArray();
        //->toSQL();
    }

    public function retorna_vehiculos_dado_linea_modelo($line_id, $model_code)
    {
        //echo '<br>' . $line_id . ' - ' . $model_code;
        return DB::table('veh_models AS mod')
            ->join('veh_vehicles AS veh', 'mod.id', '=', 'veh.model_id')
            ->select(['veh.id AS vehicle_id'])
            ->where('mod.model_code', '=', $model_code)
            ->where('mod.line_id', '=', $line_id)
            ->get()->toArray();
        //->toSQL();
    }



    public function sincronizarProductos2()
    {
        $columnas = [
            'sku',
            'item_code',
            'line_code',
            'source_code',
            'brand_code',
            "system_code",
            "subsystem_code",
            'factory_code',
            'unit',
            'name',
            "description",
            "technical_spec",
            'price',
            'sale_price',
            'stock',
            'rotation',
            "discount",
            "image",
            "item_replacecode",
            "num_of_sale",
            "model_code",
            'created_at',

        ];

        $columnas_productos = [
            'sku',
            'part_code',
            'line_code',
            'origin_code',
            'trademark_code',
            DB::raw('line_code as "system_code"'),
            DB::raw('1 as "subsystem_code"'),
            'factory_code',
            'measure_unit_code',
            'part_name',
            DB::raw('1 as "description"'),
            DB::raw('1 as "technical_spec"'),
            'min_price',
            'min_price',
            'stock',
            'rotation',
            DB::raw('0 as "discount"'),
            DB::raw('1 as "image"'),
            DB::raw('1 as "item_replacecode"'),
            DB::raw('0 as "num_of_sale"'),
            DB::raw('0 as "model_code"'),
            DB::raw('1 as "created_at"'),

        ];

        $productos = DB::connection('pgsql')
            ->table('v_partes')
            ->select($columnas_productos);

        $productos = $productos->take(10);

        //consultar
        $sql = DB::connection('ecommerce')->table('products_respaldo')->insertUsing($columnas, $productos);

        return response()->json($sql, 200);
    }

    public function sincronizarProductos()
    {
        ini_set('max_execution_time', '3000');

        $columnas_productos = [
            'sku',
            'part_code',
            'line_code',
            'origin_code',
            'trademark_code',
            'system_code',
            'subsystem_code',
            'factory_code',
            'measure_unit_code',
            'part_name',
            DB::raw('1 as "technical_spec"'),
            'min_price',
            'offer_price',
            DB::raw('(CASE WHEN stock > 0 THEN 1 ELSE 0 END) as stock'),
            'rotation',
            DB::raw('(CASE WHEN min_price = offer_price  THEN 0 ELSE 1 END) as discount'),

        ];

        $productos = DB::connection('pgsql')
            ->table('v_partes')
            ->select($columnas_productos)->orderBy('part_id', 'asc');



        echo "<pre> consultando registros";

        $productos->chunk(400, function ($busqueda) {
            $insert_masivo = [];

            foreach ($busqueda as $producto) {

                $insert = [
                    'sku' => $producto->sku,
                    'item_code' => $producto->part_code,
                    'line_code' => $producto->line_code,
                    'source_code' => $producto->origin_code,
                    'brand_code' => $producto->trademark_code,
                    "system_code" => $producto->system_code,
                    "subsystem_code" => $producto->subsystem_code,
                    'factory_code' => $producto->factory_code ? $producto->factory_code : '',
                    'unit' => $producto->measure_unit_code,
                    'name' => $producto->part_name,
                    "description" => "",
                    "technical_spec" => $producto->technical_spec,
                    'price' => $producto->min_price,
                    'sale_price' => $producto->offer_price ? $producto->offer_price : 0,
                    'stock' => $producto->stock,
                    'rotation' => $producto->rotation,
                    "discount" => $producto->offer_price ? $producto->discount : 0,
                    "image" => "",
                    "item_replacecode" => 0,
                    "num_of_sale" => 0,
                    "model_code" => "",
                    'created_at' => date("Y-m-d H:i:s"),

                ];

                array_push($insert_masivo, $insert);
            }


            DB::connection('ecommerce')->table('products_respaldo')->insert($insert_masivo);

            echo "insertando...";
        });





        /* $insert_masivo = [];
        foreach ($busqueda as $producto) {
            
            $insert = [
                'sku' => $producto->sku,
                'item_code' => $producto->part_code,
                'line_code' => $producto->line_code,
                'source_code' => $producto->origin_code,
                'brand_code' => $producto->trademark_code,
                "system_code" => $producto->system_code,
                "subsystem_code" => $producto->subsystem_code,
                'factory_code' => $producto->factory_code,
                'unit' => $producto->measure_unit_code,
                'name' => $producto->part_name,
                "description" => $producto->description,
                "technical_spec" => $producto->technical_spec,
                'price' => $producto->min_price,
                'sale_price' => $producto->min_price,
                'stock' => $producto->stock,
                'rotation' => $producto->rotation,
                "discount" => $producto->discount,
                "image" => $producto->image,
                "item_replacecode" => $producto->item_replacecode,
                "num_of_sale" => $producto->num_of_sale,
                "model_code" => $producto->model_code,
                'created_at' => date("Y-m-d H:i:s"),
                
            ];

            array_push($insert_masivo,$insert);
        }
        

        $sql = DB::connection('ecommerce')->table('products_respaldo')->insert($insert_masivo);

        return response()->json($sql,200); */
    }
}
