<?php

namespace App\Http\Controllers\Sync;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

use App\Http\Controllers\MMTrack\ServicesController;
use App\Http\Controllers\Sync\SyncProductWarehouses;
use App\Http\Controllers\Sync\SyncCustomer;
use App\Http\Controllers\Sync\SyncOrderController;
use App\Http\Controllers\Sync\SyncProductOffers;
use App\Http\Controllers\Sync\BanksSync;
use App\Models\CurrencyExchangeRate;
use Carbon\Carbon;
use App\Models\AsSync;
use App\Models\Customer;

class SyncController extends Controller
{
    public function sync(Request $request)
    {
        switch ($request->param) {
            case '1':
                $str_tablas = "'MMCBREP','MMQ1REP','MMCEREP','MMJQREP'";
                break;
            case '2':
                $str_tablas = "'MMETREP'";
                break;
            case '3':
                $str_tablas = "'MMQ0REP'";
                break;
            case 'CLI':
                $str_tablas = "'MMAKREP','MMALREP','CCPCREP','CPCNREP'";
                break;

            default:
                $str_tablas = $request->param;
                break;
        }
        //$str_tablas = (strlen($request->param) > 6) ? $request->param : '';
        echo "<br>Fecha: " . date("Y-m-d H:i:s");
        $registros = $this->leer_db2_sync($str_tablas);

        if ($registros && is_array($registros)) {
            echo "<br>Cantidad de Registros nuevos: " . sizeof($registros);

            $i = 0;
            foreach ($registros as $registro) {
                //Guardar en tabla AS_sync de BD Intermedia
                $vector_as_sync_bd_intermedia = [
                    'sytabla' => trim($registro->sytabla),
                    'sql' => utf8_encode(trim($registro->sycadsql)),
                    'usuario' => trim($registro->syusuari),
                    'fecha_generado' => $registro->syfechac,
                    'hora_generado' => $registro->syhorac,
                    'tipo_operacion' => $registro->sytpoper,
                    'created_at' => date("Y-m-d H:i:s"),
                ];
                AsSync::create($vector_as_sync_bd_intermedia);

                $i++;
                $actualizar = false;
                $sql = trim(str_replace(';', '', $registro->sycadsql));
                $registro->datos_consulta = $this->consulta_tabla_db2($sql);

                if ($registro->datos_consulta) {
                    echo "<br>Registro: " . $i . ' - TABLA: ' . trim($registro->sytabla) . ' - ACCIÓN: ' . trim($registro->sytpoper);
                    $hora_actualizacion_registro = date("His");
                    switch (trim($registro->sytabla)) {
                        case 'CCDDFCNEG': //BANCOS-FACTURAS NEGOCIABLES
                            echo "<br>PROCESAR BANCOS-FACTURAS NEGOCIABLES";
                            //$actualizar = BanksSync::ccddfcneg_facturas_consolidadas($registro);
                            break;
                        case 'MMCBREP': //CABECERA PEDIDOS
                            echo "<br>ACTUALIZANDO CABECERA PEDIDOS: ";
                            $actualizar = ServicesController::mmtrack_escribe_actualiza_cabecera_pedidos($registro);
                            //$actualizar = SyncOrderController::escribir_actualizar_cabecera_pedido($registro);
                            //exit;
                            break;
                        case 'CPCNREP': //PEDIDO - CONTACTO
                            echo '<br>Cliente - Pedido - Contacto: ' . $registro->datos_consulta->cpcodcli . ' --- ' . $registro->datos_consulta->cpnropdc . ' --- ' . $registro->datos_consulta->cpitem01 . '<br>';
                            $actualizar = ServicesController::mmtrack_actualiza_contacto_cabecera_pedidos($registro);
                            //$CustomerController = new SyncCustomer;
                            //$actualizar = $CustomerController->ccpcrep_cliente_contactos($registro);
                            break;
                        case 'MMCEREP': //DETALLE PEDIDOS
                            echo "<br>ACTUALIZANDO DETALLE PEDIDOS: ";
                            $actualizar = ServicesController::mmtrack_escribe_actualiza_detalle_pedidos($registro);
                            //$actualizar = SyncOrderController::escribir_actualizar_detalle_pedido($registro);
                            break;
                        case 'MMJQREP': //GUIAS DE REMISIÓN
                            echo "<br>ACTUALIZANDO GUIAS DE REMISIÓN: ";
                            $actualizar = ServicesController::mmtrack_actualiza_guia_remision_pedido($registro);
                            break;
                        case 'MMQ1REP': //CABECERA TRACKING PEDIDOS
                            echo "<br>ACTUALIZANDO TRACKING PEDIDOS: ";
                            $ServicesController = new ServicesController;
                            $actualizar = $ServicesController->mmtrack_actualiza_cabecera_tracking_pedido($registro);
                            break;
                        case 'MMQ0REP': //DETALLE TRACKING PEDIDOS
                            echo "<br>ACTUALIZANDO DETALLE TRACKING PEDIDOS: ";
                            echo "<br>ESTADO DEL REGISTRO MMQ0REP: ".$registro->datos_consulta->q0estado;
                            //echo '<pre>';
                            //print_r($registro);
                            if (strlen(trim($registro->datos_consulta->q0estado)) > 0) {
                                $ServicesController = new ServicesController;
                                $actualizar = $ServicesController->mmtrack_actualiza_detalle_tracking_pedido($registro);
                            }
                            break;

                        case 'MMBAREP': //DIRECCIONES PEDIDOS
                            /*
                            if (strtoupper(trim($registro->sytpoper)) === 'DELETE') {
                                echo '<pre>';
                                print_r($registro);
                                //exit;
                            }
                            */
                            # code...
                            //$actualizar = 0;
                            break;
                        case 'MMETREP': //PRODUCTOS-ALMACENES
                            if ($registro->datos_consulta->etsts === 'I') {
                                echo '<br>PRODUCTO INACTIVO (' . trim(strtoupper($registro->datos_consulta->etcodart)) . ')';
                                $actualizar = 1;
                                $hora_actualizacion_registro = 0;
                            } else {
                                $producto_almacen = new SyncProductWarehouses;
                                $actualizar = $producto_almacen->mmetrep_productos_almacen($registro);
                            }
                            break;
                        case 'MMAKREP': //MAESTRO CLIENTES
                            echo '<br>Código Cliente: ' . $registro->datos_consulta->akcodcli . '<br>';
                            //$CustomerController = new SyncCustomer;
                            $actualizar = SyncCustomer::mmakrep_maestro_clientes($registro);
                            break;
                        case 'MMALREP': //CLIENTE - DIRECCIONES
                            echo '<br>Código Cliente - Dirección: ' . $registro->datos_consulta->alcodcli . ' --- ' . $registro->datos_consulta->alitem01 . '<br>';
                            $CustomerController = new SyncCustomer;
                            $actualizar = $CustomerController->mmalrep_cliente_direcciones($registro);
                            break;

                        case 'CCPCREP': //CLIENTE - CONTACTOS
                            echo '<br>Código Cliente - Contacto: ' . $registro->datos_consulta->pccodcli . ' --- ' . $registro->datos_consulta->pcitem01 . '<br>';
                            $CustomerController = new SyncCustomer;
                            $actualizar = $CustomerController->ccpcrep_cliente_contactos($registro);
                            break;

                        case 'MMAHREP': //PROVEEDORES
                            $actualizar = SyncCustomer::mmahrep_proveedores($registro);
                            break;

                        case 'MMSCREP': //OFERTAS
                            $actualizar = SyncProductOffers::mmscrep_ofertas($registro->datos_consulta);
                            break;

                        case 'MMSDREP': //OFERTAS
                            $actualizar = SyncProductOffers::mmsdrep_productos_oferta($registro->datos_consulta);
                            break;

                        case 'MMVGREP': //GRUPOS EMPRESAS
                            $actualizar = SyncProductOffers::mmvgrep_grupos_empresas($registro->datos_consulta);
                            break;

                        case 'MMVDREP': //CLIENTES-GRUPOS
                            $actualizar = SyncProductOffers::mmvdrep_clientes_grupos_empresas($registro->datos_consulta);
                            break;

                        case 'MMDDREP': //CLIENTES-GRUPOS
                            $actualizar = SyncProductOffers::mmddrep_clientes_grupos_empresas($registro->datos_consulta);
                            break;

                        case 'MMEYREP': //MARCAS
                            echo "<br>MARCA";
                            $actualizar = SyncProductWarehouses::mmeyrep_marcas($registro->datos_consulta);
                            break;

                        case 'MMARREP': //ACTUALIZAR FORMAS DE PAGO CLIENTES
                            echo "<br>ACTUALIZAR FORMAS DE PAGO";
                            $actualizar = SyncCustomer::mmarrep_forma_pago_cliente($registro->datos_consulta);
                            break;
                    }
                } else {
                    echo "<br>SQL: $registro->sycadsql";
                    $hora_actualizacion_registro = 0;
                    $actualizar = 1;
                }

                //$actualizar = 1;
                if ($actualizar) {
                    echo '<br>actualizar registro en DB2';
                    $fecha_actualizacion_registro = date("Ymd");
                    echo ' --- Fecha: ' . $fecha_actualizacion_registro . ' ' . $hora_actualizacion_registro;
                    $arrayUpdate = array(
                        'SYFMMWEB' => $fecha_actualizacion_registro,
                        'SYHMMWEB' => $hora_actualizacion_registro
                    );
                    $arrayWhere = array(
                        ['SYTABLA', '=', $registro->sytabla],
                        ['SYCADSQL', '=', $registro->sycadsql],
                        ['SYUSUARI', '=', $registro->syusuari],
                        ['SYFECHAC', '=', $registro->syfechac],
                        ['SYHORAC', '=', $registro->syhorac],
                        ['SYFMMWEB', '=', $registro->syfmmweb]
                    );
                    $this->actualiza_tabla_db2('LIBPRDDAT.AS_SYNC', $arrayWhere, $arrayUpdate);
                } else {
                    echo "no se actualizo $actualizar";
                }
            }
        }

        $this->redirecciona();
        //$this->redirecciona(env('APP_URL_SYNC_REDIRECT') . '/api/sync/sync/' . $request->param);
    }

    public function redirecciona($url = '', $tiempo = 10)
    {
        die('<br> ' . date("Y-m-d H:i:s") . ' Fin de proceso....');
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

    public function leer_db2_sync($str_tablas = '')
    {
        $limite = env('SYNC_SELECT_MAX', 350);
        //'MMBAREP','CCDDFCNEG'
        $str_tablas = ($str_tablas <> '') ? $str_tablas : "'MMCBREP', 'MMCEREP', 'MMQ1REP', 'MMQ0REP', 'MMJQREP','MMETREP','MMAKREP','MMALREP'";
        $sql = "SELECT * FROM LIBPRDDAT.AS_SYNC WHERE SYFMMWEB=0 and SYTABLA IN($str_tablas) and SYFECHAC >= 20220722 ORDER BY SYFECHAC ASC, SYHORAC ASC  LIMIT $limite";
        //die($sql);
        $registros = DB::connection('ibmi')
            ->select(DB::raw($sql));
        return $registros;
    }

    public function consulta_tabla_db2($sql)
    {
        $registros = DB::connection('ibmi')
            ->select(DB::raw($sql));
        if (is_array($registros) && sizeof($registros) > 0) return $registros[0];
        else return false;
    }

    public function actualiza_tabla_db2($tabla_db2, $arrayWhere, $arrayUpdate)
    {
        return DB::connection('ibmi')
            ->table($tabla_db2)
            ->where($arrayWhere)
            ->update($arrayUpdate);
    }

    public function sincronizar_tipo_cambio_mym()
    {
        $rs = DB::connection('ibmi')
            ->table('LIBPRDDAT.MMDKREP')
            ->where('DKSTS', '=', 'A')
            ->where('DKCODMON', '=', '02')
            ->where('DKCLSTCM', '=', '03')
            ->where('DKFECTCM', '<=', date("Ymd"))
            ->orderBy('DKFECTCM', 'DESC')
            ->first();

        if ($rs) {
            $arrayWhere = array(
                ['reg_date', '=',  $rs->dkfectcm],
                ['currency_code', '=',  $rs->dkcodmon],
            );
            $arrayInsert = array(
                'reg_date' => $rs->dkfectcm,
                'currency_code' => $rs->dkcodmon,
                'mym_buying_price' => $rs->dkimpcmp,
                'mym_selling_price' => $rs->dkimpvta,
                'mym_average_price' => $rs->dkimpprm,
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s"),
                'reg_status' => ($rs->dksts === 'A' ? 1 : 0)
            );
            CurrencyExchangeRate::updateOrCreate(
                $arrayWhere,
                $arrayInsert
            );
            $str = date("Y-m-d H:i:s") . " - FC:  {$rs->dkfectcm} - Moneda: {$rs->dkcodmon} - Precio Venta M&M: {$rs->dkimpvta}";
            echo $str;
        }
    }

    public function mantenimiento_tabla_as_sync()
    {
        $qty_dias = 15;
        $fecha = Carbon::createFromFormat("Ymd", date("Ymd"), 'America/Lima');
        $fecha->subDay($qty_dias);
        $deleted = DB::connection('ibmi')->table('LIBPRDDAT.AS_SYNC')->where('SYFECHAC', '<=', $fecha->format('Ymd'))->delete();
        echo ("Registros eliminados menores a la fecha ({$fecha->format('Ymd')}): $deleted");
    }
}
