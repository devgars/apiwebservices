<?php

namespace App\Http\Controllers\Sync;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\PartDetailWarehouse;
use App\Models\PartPartDetail;
use App\Models\PartTrademark;
use App\Http\Controllers\Sync\Utilidades;

class SyncProductWarehouses extends Controller
{
    protected $array_codigos_almacenes_ecommerce = ['01', '02', '03', '04', '05', '06', '07', '22'];

    public function mmetrep_productos_almacen($fila)
    {
        echo '<pre>';
        print_r($fila);
        $list_price = floatval($fila->datos_consulta->etimplis);
        $min_price = floatval($fila->datos_consulta->etimppmi);
        $replacement_cost = floatval($fila->datos_consulta->etimpcrp);
        $min_price_factor = floatval($fila->datos_consulta->etfacpmi);
        $max_price_factor = floatval($fila->datos_consulta->etfacpma);
        $min_profit_rate = floatval($fila->datos_consulta->etprcumi);
        $max_profit_rate = floatval($fila->datos_consulta->etprcuma);
        $init_qty = floatval($fila->datos_consulta->etstkini);
        $in_qty = floatval($fila->datos_consulta->etcaning);
        $out_qty = floatval($fila->datos_consulta->etcansld);
        $in_warehouse_stock = ($init_qty + $in_qty - $out_qty);

        $line_code = $fila->datos_consulta->etcodlin;
        $origin_code = $fila->datos_consulta->etcodori;
        $trademark_code = $fila->datos_consulta->etcodmar;
        $code = str_replace(' ', '%', trim(strtoupper(utf8_encode($fila->datos_consulta->etcodart))));

        //BUSCAR LINEA-ORIGEN-MARCA PARA GENERAR SKU
        $util = new Utilidades();
        $datos_linea = $util->selecciona_fila_from_tabla('gen_resource_details', array(['resource_id', '=', 7], ['code', '=', $fila->datos_consulta->etcodlin]));
        $datos_origen = $util->selecciona_fila_from_tabla('gen_resource_details', array(['resource_id', '=', 6], ['code', '=', $fila->datos_consulta->etcodori]));
        if (!$datos_marca = $util->selecciona_fila_from_tabla('part_trademarks', array(['code', '=', $fila->datos_consulta->etcodmar]))) {
            echo "<br>NO EXISTE Marca: " . $fila->datos_consulta->etcodmar;
            //exit;
        }


        $factory_code = utf8_encode(strtoupper(trim($fila->datos_consulta->etcodfab)));
        $currency_code = '02';
        //$type_id_array = array(3, 4);
        $arrayWhere = array(
            ['type_id', '=', 3],
            ['code', '=', $fila->datos_consulta->etcodsuc],
        );

        if (!$datos_almacen = $util->selecciona_fila_from_tabla('establishments', $arrayWhere)) {
            $arrayWhere = array(
                ['type_id', '=', 4],
                ['code', '=', $fila->datos_consulta->etcodsuc],
            );
            if (!$datos_almacen = $util->selecciona_fila_from_tabla('establishments', $arrayWhere)) {
                echo '<br>Almacen no existe.';
                echo '<pre>';
                die(print_r($fila));
            }
        }

        //VERIFICA SI PRODUCTO EXISTE, SINO, LO CREA
        $sku =  $line_code . $origin_code  . $trademark_code . $code;
        echo "<br>SKU: $sku";
        $arrayWherePD = array(
            ['sku', 'like', $sku],
        );

        if (!$part_detail = $util->selecciona_fila_from_tabla('part_part_details', $arrayWherePD)) {
            //EXISTE EN part_parts?
            $arrayWherePP = array(
                ['code', 'like', $code],
            );
            if (!$part = $util->selecciona_fila_from_tabla('part_parts', $arrayWherePP)) {
                $code = trim(strtoupper($fila->datos_consulta->etcodart));
                echo ("<br>DEBE CREARSE EL PRODUCTO: $code");


                if (!$part_detail = $util->crear_producto_dado_linea_origen_marca_codigo($line_code, $origin_code, $trademark_code, $code)) {
                    echo ("<br>ERROR: EL CÓDIGO DE PRODUCTO NO CREADO");
                    return 0;
                }
            } else {
                if (!$datos_linea || !$datos_origen || !$datos_marca || !$part) {
                    echo '<br>FALTAN DATOS PARA CREAR EL PRODUCTO EN  TABLA part_details';
                    echo '<pre>';
                    echo '<BR>LNEA';
                    print_r($datos_linea);
                    echo '<BR>ORIGEN';
                    print_r($datos_origen);
                    echo '<BR>MARCA';
                    print_r($datos_marca);
                    echo '<BR>PARTE';
                    print_r($part);
                    return false;
                }
                $nuevo_sku = $datos_linea->code . '' . $datos_origen->code . '' . $datos_marca->code . '' . $part->code;
                //ESCRIBIR REGISTRO EN TABLA part_part_details
                $arrayInsertPPD = array(
                    'part_id' => $part->id,
                    'line_id' => $datos_linea->id,
                    'origin_id' => $datos_origen->id,
                    'trademark_id' => $datos_marca->id,
                    'sku' => $nuevo_sku,
                    'reg_status' => 1,
                    'factory_code' => $factory_code,
                    'list_price' => $list_price,
                    'currency_code' => $currency_code,
                    'min_price' => $min_price,
                    'replacement_cost' => $replacement_cost,
                    'min_price_factor' => $min_price_factor,
                    'max_price_factor' => $max_price_factor,
                    'min_profit_rate' => $min_profit_rate,
                    'max_profit_rate' => $max_profit_rate,
                );
                $arrayWherePD = array(
                    ['part_id', '=', $part->id],
                    ['line_id', '=', $datos_linea->id],
                    ['origin_id', '=', $datos_origen->id],
                    ['trademark_id', '=', $datos_marca->id],
                );

                PartPartDetail::updateOrCreate(
                    $arrayWherePD,
                    $arrayInsertPPD
                );
                $part_detail = $util->selecciona_fila_from_tabla('part_part_details', $arrayWherePD);
            }
        } else {

            if (in_array($datos_almacen->code, $this->array_codigos_almacenes_ecommerce)) {

                //ACTUALIZAR PRECIO EN PART_PART_DETAILS
                $arrayWhere = array(['id', '=', $part_detail->id]);
                $arrayInsert = array(
                    'list_price' => $list_price,
                    'min_price' => $min_price,
                    'updated_at' => date("Y-m-d H:i:s")
                );

                PartPartDetail::updateOrCreate(
                    $arrayWhere,
                    $arrayInsert
                );
            }
        }

        if (!$part_detail && $datos_almacen) {
            echo "<br>ERROR: ARTÍCULO NO CREADO";
            return 0;
        }
        //ESCRIBIR EN TABLA part_detail_warehouses
        $arrayWherePDW = array(
            ['part_detail_id', '=', $part_detail->id],
            ['warehouse_id', '=', $datos_almacen->id],
        );
        $arrayInsertPDW = array(
            'part_detail_id' => $part_detail->id,
            'warehouse_id' => $datos_almacen->id,
            'init_qty' => $init_qty,
            'in_qty' => $in_qty,
            'out_qty' => $out_qty,
            'in_warehouse_stock' => $in_warehouse_stock,
            'created_at' => date("Y-m-d H:i:s"),
            'reg_status' => 1
        );

        if (PartDetailWarehouse::updateOrCreate(
            $arrayWherePDW,
            $arrayInsertPDW
        )) {
            echo '<br>REGISTRO AGREGADO!...';
            return 1;
        }
    }


    public function mmeyrep_marcas($marca)
    {
        $arrayWhere = [
            ['code', '=', strtoupper(trim($marca->eycodmar))]
        ];
        $arrayInsert = [
            'code' => utf8_encode(strtoupper(trim($marca->eycodmar))),
            'abrv' => utf8_encode(strtoupper(trim($marca->eydscabr))),
            'short_name' => utf8_encode(strtoupper(trim($marca->eydsccor))),
            'name' => utf8_encode(strtoupper(trim($marca->eydsclar))),
            'reg_status' => ($marca->eysts === 'I') ? 0 : 1
        ];
        PartTrademark::updateOrCreate(
            $arrayWhere,
            $arrayInsert
        );
        return 1;
    }
}
