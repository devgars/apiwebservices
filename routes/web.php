<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\testController;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\SyncBankController;
use App\Http\Controllers\ImportFromDB2;
use App\Http\Controllers\MMTrack\ServicesController;
use App\Http\Controllers\MMTrack\UtilidadesController;
use App\Http\Controllers\PostVenta\RequestController;
use App\Http\Controllers\PostVenta\ResourcesController;
use App\Http\Controllers\PostVenta\UsersController;
use Illuminate\Support\Facades\Http;
use App\Mail\AlertasPedidos;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Cajas\CajasController;
use App\Http\Controllers\API\ApiGeneralController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::get('test_db', [testController::class, 'test_db']);
Route::get('sync', [SyncController::class, 'sincronizar_db2_con_interface']);
Route::get('sync_track_db2', [SyncController::class, 'sincronizar_tracking_a_db2']);
Route::get('sync_v2', [SyncBankController::class, 'execute_job_sync_bank']);
Route::get('imp_gen', [ImportFromDB2::class, 'import_generics']);
Route::get('imp_ubigeo', [ImportFromDB2::class, 'import_ubigeo']);
Route::get('imp_clientes/{offset}',  [ImportFromDB2::class, 'import_clientes']);
Route::get('imp_clientes_fp/{offset}',  [ImportFromDB2::class, 'actualiza_formas_pago_clientes']);
Route::get('corrige_clientes',  [ImportFromDB2::class, 'corrige_clientes']);
Route::get('imp_marcas_prod',  [ImportFromDB2::class, 'importar_marcas_de_productos']);
Route::get('imp_productos/{offset}',  [ImportFromDB2::class, 'importar_partes']);
Route::get('imp_productos_detalles/{offset}',  [ImportFromDB2::class, 'importar_partes_detalles']);
Route::get('imp_productos_ofertas/{offset}',  [ImportFromDB2::class, 'importar_productos_en_oferta']);
Route::get('act_productos_detalles/{offset}',  [ImportFromDB2::class, 'actualizar_partes_detalles']);
Route::get('act_productos_detalles_precio/{offset}',  [ImportFromDB2::class, 'actualizar_precio_partes_detalles']);
Route::get('act_rotacion_productos/{offset}',  [ImportFromDB2::class, 'actualiza_rotacion_productos']);
Route::get('act_und_medida_productos/{offset}',  [ImportFromDB2::class, 'actualiza_und_medida_productos']);
Route::get('act_sub_sistema_productos/{offset}',  [ImportFromDB2::class, 'actualiza_subsistema_productos']);
Route::get('act_img_productos_mysql/{offset}',  [ImportFromDB2::class, 'actualiza_imagen_principal_productos']);
Route::get('imp_productos_alm/{offset}',  [ImportFromDB2::class, 'importar_productos_almacenes']);
Route::get('imp_productos_alm_as/{codigo_almacen}/{offset}',  [ImportFromDB2::class, 'importar_productos_almacen_as400']);
Route::get('imp_grupos_empresas/{offset}',  [ImportFromDB2::class, 'importa_grupos_empresas']);
Route::get('imp_ofertas_grupos_empresas/{offset}',  [ImportFromDB2::class, 'importar_productos_en_oferta_grupo_empresa']);
Route::get('imp_cliente_grupos/{offset}',  [ImportFromDB2::class, 'importa_cliente_grupo_empresa']);
Route::get('imp_sistemas',  [ImportFromDB2::class, 'importar_sistemas']);
Route::get('imp_usuarios',  [ImportFromDB2::class, 'importar_usuarios']);
Route::get('imp_sublineas',  [ImportFromDB2::class, 'importar_sublineas']);
Route::get('imp_blacklist',  [ImportFromDB2::class, 'importar_lista_negra_clientes']);
Route::get('imp_pedidos/{offset}/{fromDate?}',  [ImportFromDB2::class, 'importar_pedidos']);
Route::get('imp_facbol/{offset}',  [ImportFromDB2::class, 'importar_fac_bol_fiscales']);
Route::get('imp_nc_fiscales/{offset}',  [ImportFromDB2::class, 'importar_nc_fiscales']);
Route::get('imp_ocs/{offset}',  [ImportFromDB2::class, 'importar_ordenes_de_compra']);
Route::get('imp_grs/{offset}',  [ImportFromDB2::class, 'importar_guias_de_remision']);
Route::get('imp_ncs/{offset}',  [ImportFromDB2::class, 'importar_notas_de_credito']);
Route::get('imp_bancos',  [ImportFromDB2::class, 'migrar_bancos']);
Route::get('act_sku',  [ImportFromDB2::class, 'actualiza_skus']);
Route::get('imp_reemp',  [ImportFromDB2::class, 'importar_reemplazos_de_partes']);
Route::get('imp_rec',  [ImportFromDB2::class, 'importa_gen_resources_and_details_dev_to_prod']);
Route::get('imp_cli_con',  [ImportFromDB2::class, 'migrar_contactos_clientes']);
Route::get('comp_tbls',  [ApiGeneralController::class, 'comparar_tablas_mmeirep_ccaplbco']);
Route::get('comp_tbls_i',  [ApiGeneralController::class, 'comparar_tablas_mmeirep_ccaplbco_inactivos']);
Route::get('sync_reg_sald/{paso}',  [ApiGeneralController::class, 'sync_tablas_mmeirep_ccaplbco']);
Route::get('add_col_line_parts/{offset}',  [ImportFromDB2::class, 'llenar_columna_line_id_en_part_parts']);
Route::get('act_principal_image/{offset}',  [ApiGeneralController::class, 'actualiza_imagen_principal_productos']);
Route::get('act_p_images',  [ApiGeneralController::class, 'get_partes_imagen_principal_incorrecta']);
Route::get('act_g_images',  [ApiGeneralController::class, 'actualiza_galeria_imagenes_productos']);
Route::get('sync_cust_adds/{offset}',  [ImportFromDB2::class, 'sync_customer_addresses']);

Route::get('reporte_cajas/{cia}/{suc}/{fecha}',  [CajasController::class, 'generar_pdf_caja_cerrada']);


Route::get('/', function () {
    return  view('welcome');
});


Route::get('test', function () {

    $data = [
        'cliente_id' => '089945',
        'pedido' => '12345',
        'estado' => 'Pedido en Ruta',
        'cliente' => 'Kevin Duran'
    ];


    //return  view('mails/alertasPedido',compact('data'));


    Mail::to('kevinduran223@gmail.com')->send(new AlertasPedidos($data));
});


Route::get('test-ws', function () {

    if (Config::get('services.ws_api.conf')) {

        // $url = Config::get('services.ws_api.url') . "/enviar-mensaje";
        $url = "http://localhost:3000/enviar-mensaje";

        $response = Http::withoutVerifying()
            ->withHeaders(['Cache-Control' => 'no-cache'])
            ->withOptions(["verify" => false])
            ->post($url, [
                'numero' => '51996070170',
                'mensaje' => 'mensaje de prueba marco'
            ]);

        return $response;
    }

    return 1;
});




Route::get('/mmtrack/sincronizar/pedidos', [ServicesController::class, 'SincronizarPedidosAs400']);
Route::get('/mmtrack/sincronizar/pedidos1', [ServicesController::class, 'SincronizarPedidosTackingAs400']);
Route::get('/mmtrack/sincronizar/clientes', [ServicesController::class, 'SincronizarClientesAs400']);





Route::get('edwin', function () {
    //return 'hola estes mi tutorial';
    $data = DB::table('users')
        ->select("*")->get();
    //$user =  User::all();
    return json_encode($data);
});


Route::get('/mmtrack/utilidades/convertir-imagenes', [UtilidadesController::class, 'convertirImagen64PorPng']);
Route::get('/admin/rol', [testController::class, 'administrarRoles']);
Route::get('/admin/rol', [testController::class, 'administrarRoles']);
