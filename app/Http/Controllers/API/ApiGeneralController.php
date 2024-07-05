<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sync\Utilidades;
use App\Models\PartPartDetail;
use Illuminate\Http\Request;
use DB;

use App\Models\PartDetailImage;

class ApiGeneralController extends Controller
{
    public function get_identification_types()
    {
        $rs = DB::table('v_identificacion_types')
            ->get();
        return response()->json($rs, 200);
    }

    public function comparar_tablas_mmeirep_ccaplbco()
    {
        $registros_mmeirep = DB::connection('ibmi')
            ->table('LIBPRDDAT.MMEIREP')
            ->where('EICODCIA', '10')
            ->where('EISTS', 'A')
            ->where('EIIMPSLD', '>', 0)
            ->whereIn('EITIPDOC', array('01', '03'))
            ->get()->toArray();
        echo "<br>Cantidad de registros: " . sizeof($registros_mmeirep);
        $no_existe = 0;
        foreach ($registros_mmeirep as $registro) {
            $arrayWhere = array(
                ['abcodcia', '=', $registro->eicodcia],
                ['abcodsuc', '=', $registro->eicodsuc],
                ['abtipdoc', '=', $registro->eitipdoc],
                ['abnrodoc', '=', $registro->einrodoc],
            );
            if ($reg_aplbco = DB::connection('ibmi')->table('LIBPRDDAT.CCAPLBCO')->where($arrayWhere)->first()) {
                $str_log = "<br> CIA: $registro->eicodcia - SUC: $registro->eicodsuc - TD: $registro->eitipdoc - NroDoc: $registro->einrodoc";
                if ($registro->eicodcli !== $reg_aplbco->abcodcli) $str_log .= '<br>CLIENTE NO COINCIDE: MMEIREP: (' . $registro->eicodcli . ') - CCAPLBCO: (' . $reg_aplbco->abcodcli . ')';
                if ($registro->eifecemi <> $reg_aplbco->abfecemi) $str_log .= '<br>FECHA EMISIÓN NO COINCIDE: MMEIREP: (' . $registro->eifecemi . ') - CCAPLBCO: (' . $reg_aplbco->abfecemi . ')';
                if ($registro->eiimpccc <> $reg_aplbco->abimpccc) $str_log .= '<br>MONTO TOTAL NO COINCIDE: MMEIREP: (' . $registro->eiimpccc . ') - CCAPLBCO: (' . $reg_aplbco->abimpccc . ')';
                if ($registro->eiimpsld <> $reg_aplbco->abimpsld) $str_log .= '<br>SALDO NO COINCIDE: MMEIREP: (' . $registro->eiimpsld . ') - CCAPLBCO: (' . $reg_aplbco->abimpsld . ')';
                if ($registro->eifrmpag !== $reg_aplbco->abfrmpag) $str_log .= '<br>FORMA DE PAGO NO COINCIDE: MMEIREP: (' . $registro->eifrmpag . ') - CCAPLBCO: (' . $reg_aplbco->abfrmpag . ')';
                if (strlen($str_log > 50)) echo $str_log . '<br>';
            } else {
                $no_existe++;
                echo '<pre>';
                echo '<br>REGISTRO NO EXISTE EN CCAPLBCO: CIA->' . $registro->eicodcia . ' - SUC->' . $registro->eicodsuc . ' - NRODOC->' . $registro->einrodoc . '<br>';
                print_r($registro);
                $sql = "insert into LIBPRDDAT.CCAPLBCO (ABCODCIA,ABCODSUC,ABCODCLI,ABTIPDOC,ABNRODOC,ABFECTCM,ABFECEMI,ABFECVCT,ABCODMON,ABIMPCCC,ABIMPSLD,ABFRMPAG,ABMODPAG,ABCNDPAG,ABCODCBR,ABCODVEN,ABSTS,ABUSR,ABJOB,ABJDT,ABJTM)
                (SELECT EICODCIA, EICODSUC, EICODCLI, EITIPDOC, EINRODOC, EIFECTCM, EIFECEMI, EIFECVCT, EICODMON, EIIMPCCC, EIIMPSLD, EIFRMPAG, EIMODPAG, EICNDPAG, EICODCBR, EICODVEN, EISTS, EIUSR, EIJOB, EIJDT, EIJTM
                FROM LIBPRDDAT.MMEIREP WHERE EICODCIA='" . $registro->eicodcia . "' AND EICODSUC='" . $registro->eicodsuc . "' AND EITIPDOC='" . $registro->eitipdoc . "' AND EINRODOC=" . $registro->einrodoc . ")";
                //DB::connection('ibmi')->insert($sql);
                echo ($sql);
            }
        }
        if ($no_existe > 0) echo "<br>Registros que no existen en tabla CCAPLBCO: " . $no_existe;
    }

    public function comparar_tablas_mmeirep_ccaplbco_inactivos()
    {
        $registros = DB::connection('ibmi')
            ->table('LIBPRDDAT.MMEIREP')
            ->select(['MMEIREP.*'])
            ->join('LIBPRDDAT.CCAPLBCO', function ($join) {
                $join->on('EICODCIA', '=', 'ABCODCIA');
                $join->on('EICODSUC', '=', 'ABCODSUC');
                $join->on('EICODCLI', '=', 'ABCODCLI');
                $join->on('EITIPDOC', '=', 'ABTIPDOC');
                $join->on('EINRODOC', '=', 'ABNRODOC');
                $join->on('EISTS', '<>', 'ABSTS');
            })
            ->where('EICODCIA', '10')
            ->where('EISTS', 'I')
            ->where('EIIMPSLD', '>', 0)
            ->whereIn('EITIPDOC', array('01', '03'))
            ->get()->toArray();
        //->toSql();
        //die($registros);
        if (sizeof($registros) > 0) {
            echo "<br>Cantidad: " . sizeof($registros);
            exit;
            foreach ($registros as $registro) {
                $arrayWhere = [
                    ['ABCODCIA', '=', $registro->eicodcia],
                    ['ABCODSUC', '=', $registro->eicodsuc],
                    ['ABCODCLI', '=', $registro->eicodcli],
                    ['ABTIPDOC', '=', $registro->eitipdoc],
                    ['ABNRODOC', '=', $registro->einrodoc],
                ];
                $arrayUpdate = [
                    'ABSTS' => 'I',
                    'RUPDATE' => 1
                ];
                DB::connection('ibmi')->table('LIBPRDDAT.CCAPLBCO')
                    ->where($arrayWhere)
                    ->update($arrayUpdate);
            }
        }
    }

    public function sync_tablas_mmeirep_ccaplbco(Request $request)
    {
        ini_set('max_execution_time', '3000');

        switch ($request->paso) {
            case '1':
                echo "<br>INACTIVAR REGISTROS DIFERENTES";
                $this->inactivar_registros_diferentes();
                //return redirect('sync_reg_sald/2');
                break;

            case '2':
                echo "<br>ACTUALIZAR SALDOS DIFERENTES";
                $this->actualizar_saldo_registros_diferentes();
                break;

            default:
                echo "<br>Error en paso seleccionado";
                break;
        }
    }

    public function inactivar_registros_diferentes()
    {
        $registros = DB::connection('ibmi')->table('LIBPRDDAT.MMEIREP AS sal')
            ->join('LIBPRDDAT.CCAPLBCO AS aux', function ($join) {
                $join->on('sal.EICODCIA', '=', 'aux.ABCODCIA');
                $join->on('sal.EICODSUC', '=', 'aux.ABCODSUC');
                $join->on('sal.EICODCLI', '=', 'aux.ABCODCLI');
                $join->on('sal.EITIPDOC', '=', 'aux.ABTIPDOC');
                $join->on('sal.EINRODOC', '=', 'aux.ABNRODOC');
            })
            ->get()->toArray();

        echo "<br>Cantidad: " . sizeof($registros);
        $i = 0;
        foreach ($registros as $registro) {

            $inactivar_registro_aux = false;

            $diff_sts = false;
            $diff_saldo = false;
            $diff_eistsrcl = false;
            $mensaje = '';

            if ($registro->eists === 'I' && $registro->absts === 'A') {
                $diff_sts = true;
                $mensaje .= '<BR>DIFERENCIA EN ESTADO DE REGISTRO';
            }

            if ($registro->eiimpsld == 0 && $registro->abimpsld > 0) {
                $diff_saldo = true;
                $mensaje .= '<BR>DIFERENCIA EN SALDO DE DOCUMENTO';
            }

            if ($registro->eistsrcl === 'S' && $registro->absts === 'A') {
                $diff_eistsrcl = true;
                $mensaje .= '<BR>CAMPO eistsrcl = "S"';
            }

            if ($diff_sts || $diff_saldo  || $diff_eistsrcl) {
                $inactivar_registro_aux = true;
            }

            if ($inactivar_registro_aux) {
                $i++;
                echo "<br>($i) -> $registro->abcodcia - $registro->abcodsuc - $registro->abcodcli - $registro->abtipdoc - $registro->abnrodoc";
                echo $mensaje;

                DB::connection('ibmi')->table('LIBPRDDAT.CCAPLBCO')
                    ->where('ABCODCIA', $registro->abcodcia)
                    ->where('ABCODSUC', $registro->abcodsuc)
                    ->where('ABCODCLI', $registro->abcodcli)
                    ->where('ABTIPDOC', $registro->abtipdoc)
                    ->where('ABNRODOC', $registro->abnrodoc)
                    ->update(['ABSTS' => 'I', 'RUPDATE' => 1]);
                echo "<br> -> Registro Actualizado";
                //echo '<pre>';
                //print_r($registro);
                //exit;
            }
        }
    }

    public function actualizar_saldo_registros_diferentes()
    {
        $registros = DB::connection('ibmi')->table('LIBPRDDAT.MMEIREP AS sal')
            ->join('LIBPRDDAT.CCAPLBCO AS aux', function ($join) {
                $join->on('sal.EICODCIA', '=', 'aux.ABCODCIA');
                $join->on('sal.EICODSUC', '=', 'aux.ABCODSUC');
                $join->on('sal.EICODCLI', '=', 'aux.ABCODCLI');
                $join->on('sal.EITIPDOC', '=', 'aux.ABTIPDOC');
                $join->on('sal.EINRODOC', '=', 'aux.ABNRODOC');
                $join->on('sal.EISTS', '=', "'A'");
            })
            ->get()->toArray();

        echo "<br>Cantidad: " . sizeof($registros);
        $i = 0;
        foreach ($registros as $registro) {

            if ($registro->eists === 'A' && $registro->eiimpsld > 0 && $registro->eiimpsld <> $registro->abimpsld) {
                $i++;
                echo "<br>($i) -> $registro->abcodcia - $registro->abcodsuc - $registro->abcodcli - $registro->abtipdoc - $registro->abnrodoc";
                DB::connection('ibmi')->table('LIBPRDDAT.CCAPLBCO')
                    ->where('ABCODCIA', $registro->abcodcia)
                    ->where('ABCODSUC', $registro->abcodsuc)
                    ->where('ABCODCLI', $registro->abcodcli)
                    ->where('ABTIPDOC', $registro->abtipdoc)
                    ->where('ABNRODOC', $registro->abnrodoc)
                    ->update(['ABIMPSLD' => $registro->eiimpsld, 'ABSTS' => 'A', 'RUPDATE' => 1]);
                echo " - Saldo Actualizado";
                //echo '<pre>';
                //print_r($registro);
                //exit;
            }
        }
    }

    public function actualiza_galeria_imagenes_productos(Request $request)
    {
        ini_set('max_execution_time', '3000');
        $util = new Utilidades();
        $offset = ($request->offset) ? intval($request->offset) : 0;
        $limit = 15000;

        $direccion_base = 'https://api.mym.com.pe:9443/images/prod/?f=';

        $select = ['ppd.id', 'sku', 'principal_image'];

        $cantidad = DB::table('part_part_details AS ppd')->select($select)
            ->leftJoin('part_detail_images AS ppdimg', 'ppd.id', '=', 'ppdimg.part_detail_id')
            ->whereRaw('ppdimg.id IS null')
            ->whereRaw("(principal_image is null and length(principal_image) <> 0)")
            ->count();
        echo "<br>Cantidad: de registros: $cantidad";
        // exit;

        $registros = DB::table('part_part_details AS ppd')->select($select)
            ->leftJoin('part_detail_images AS ppdimg', 'ppd.id', '=', 'ppdimg.part_detail_id')
            ->whereRaw('ppdimg.id IS null')
            ->whereRaw("(principal_image is not null and length(principal_image) <> 0)")
            ->orderBy('sku')
            ->limit($limit)->offset($offset)
            ->get()->toArray();
        //->toSql();
        //die($registros);

        echo '<pre>';
        $cont = 0;
        foreach ($registros as $fila) {
            $cont++;
            //ELIMINAR REGISTROS ACTUALES
            //$deleted = DB::table('part_detail_images')->where('part_detail_id', $fila->id)->delete();
            //echo "<br>($cont) SKU: $fila->sku - Imagenes eliminadas: $deleted ";
            //$vector_galeria = [];
            for ($i = 0; $i < 10; $i++) {
                $part_code = str_replace('/', '-', substr($fila->sku, 7, strlen($fila->sku)));
                $imagen = substr($fila->sku, 0, 2) . ',' . substr($fila->sku, 2, 2) . ',' . substr($fila->sku, 4, 3) . ',';
                $imagen .= $part_code . '-000' . $i;
                $existe_imagen = $this->verifica_imagen_existe($imagen);
                echo "<br>($i) SKU: $fila->sku - Imagen: $imagen";
                echo ' Existe imagen?: ->   ';
                echo ($existe_imagen) ? 'SI' : 'NO';
                if ($existe_imagen) {
                    $imagen2 = $direccion_base . $imagen . '.jpg';
                    $fecha_hora = date("Y-m-d H:i:s");

                    $arrayWhere = [
                        ['part_detail_id', '=', $fila->id],
                        ['image', '=', $imagen2]
                    ];

                    $arrayInsert = [
                        'part_detail_id' => $fila->id,
                        'image' => $imagen2,
                        'created_at' => $fecha_hora,
                        'updated_at' => $fecha_hora
                    ];

                    PartDetailImage::updateOrCreate(
                        $arrayWhere,
                        $arrayInsert
                    );
                    echo "<br>Producto-Imagen asociado";
                }
            }
            echo "<br>";
            print_r($fila);
            //print_r($vector_galeria);
        }
    }

    public function actualiza_imagen_principal_productos(Request $request)
    {
        ini_set('max_execution_time', '3000');
        //$util = new Utilidades();
        $offset = ($request->offset) ? intval($request->offset) : 0;
        $limit = 10000;

        $direccion_base = 'https://api.mym.com.pe:9443/images/prod/?f=';

        $select = ['id', 'sku', 'principal_image'];
        $cantidad = DB::table('part_part_details')->select($select)
            ->whereRaw("(principal_image is null or length(principal_image) = 0)")
            ->count();
        $registros = DB::table('part_part_details')->select($select)
            ->whereRaw("(principal_image is null or length(principal_image) = 0)")
            ->limit($limit)->offset($offset)
            ->get()->toArray();
        //->toSql();
        echo "<br>Página (" . ($offset > 0) ? ceil($offset / $limit) : 1 . ")   - Fecha: " . date("Y-m-d H:i:s");
        echo "<br>Cantidad: $cantidad - Offset: $offset - Limit: $limit<br>";
        //die($registros);
        //echo '<pre>';a
        echo "<br>Cantidad de registros: $cantidad";
        $i = 0;
        $encontradas = 0;
        foreach ($registros as $fila) {
            $str = '';
            $i++;
            $part_code = str_replace('/', '-', substr($fila->sku, 7, strlen($fila->sku)));
            $principal_image = substr($fila->sku, 0, 2) . ',' . substr($fila->sku, 2, 2) . ',' . substr($fila->sku, 4, 3) . ',';
            $principal_image .= $part_code . '-0001';
            $existe_imagen = $this->verifica_imagen_existe($principal_image);
            $str .= "<br>($i) SKU: $fila->sku - Imagen: $principal_image";
            $str .= ' Existe imagen?: ->   ';
            $str .= ($existe_imagen) ? 'SI' : 'NO';

            if ($existe_imagen) {
                $encontradas++;
                $imagen =  $direccion_base . $principal_image . '.jpg';
                $str .= "<br>Imagen: $imagen";
                $fecha_hora = date("Y-m-d H:i:s");

                PartPartDetail::where('sku', $fila->sku)
                    ->update(['principal_image' => $imagen, 'updated_at' => $fecha_hora]);

                echo $str;
            }
        }
        $n_offset = (int)$offset + (int)$limit - $encontradas;
        if ($n_offset < $cantidad) {
            echo '<a href="/act_principal_image/' . $n_offset . '">Siguiente</a>';
            return redirect('/act_principal_image/' . $n_offset);
        } else echo '<br>Fin de registro de Partes';

        return redirect('act_g_images');
    }

    public function get_partes_imagen_principal_incorrecta()
    {
        ini_set('max_execution_time', '3000');

        $direccion_base = 'https://api.mym.com.pe:9443/images/prod/?f=';
        $sql = " select line_code, origin_code, trademark_code, part_code, sku, replace(principal_image,'%2C',','), concat('https://api.mym.com.pe:9443/images/prod/?f=',line_code,',',origin_code,',',trademark_code,',',replace(part_code,'/','-'),'-0001.jpg') imagen_correcta
        from v_partes
        where (principal_image is not null 
        and principal_image <>'')
        and replace(principal_image,'%2C',',') <> concat('https://api.mym.com.pe:9443/images/prod/?f=',line_code,',',origin_code,',',trademark_code,',',replace(part_code,'/','-'),'-0001.jpg')
       ";
        $registros = DB::select(DB::raw($sql));
        if (is_array($registros) && sizeof($registros) > 0) {
            echo '<br>Registros: ' . sizeof($registros);
            foreach ($registros as $registro) {
                $fecha_hora = date("Y-m-d H:i:s");
                echo '<pre>';
                print_r($registro);
                $part_code = str_replace('/', '-', $registro->part_code);
                $principal_image = $registro->line_code . ',' . $registro->origin_code . ',' . $registro->trademark_code . ',' . $part_code . '-0001';
                $existe_imagen = $this->verifica_imagen_existe($principal_image);
                if ($existe_imagen) {
                    $imagen =  $direccion_base . $principal_image . '.jpg';
                    echo "<br>OK SKU: $registro->sku - Imagen Correcta: $imagen";

                    PartPartDetail::where('sku', $registro->sku)
                        ->update(['principal_image' => $imagen, 'updated_at' => $fecha_hora]);
                } else {
                    echo "<br>SKU $registro->sku NO TIENE IMAGEN ($principal_image)";

                    PartPartDetail::where('sku', $registro->sku)
                        ->update(['principal_image' => null, 'updated_at' => $fecha_hora]);
                }
            }
        }
    }

    public function verifica_imagen_existe($f)
    {
        $url = "http://192.168.1.66/prod_img/?json=1&f=" . urlencode($f);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $result = curl_exec($ch);
        if (0 !== curl_errno($ch)) {
            return false;
        }
        curl_close($ch);
        $resp = json_decode($result, true);
        if ($resp === null || ($resp['error'] ?? null)) {
            return false;
        }
        return true;
    }
}
