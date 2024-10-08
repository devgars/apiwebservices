<?php
//namespace Carbon;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CustomerController;
use App\Http\Controllers\Customers\CustomersController;
use App\Http\Controllers\MMTrack\VehiculosController;
use App\Http\Controllers\MMTrack\DriverAssistantController;
use App\Http\Controllers\MMTrack\ServicesController;
use App\Http\Controllers\MMTrack\OrderDeliveryController;
use App\Http\Controllers\MMTrack\ConductoresController;
use App\Http\Controllers\MMTrack\ClientesController;
use App\Http\Controllers\MMTrack\PedidosController;
use App\Http\Controllers\MMTrack\ReportesController;
use App\Http\Controllers\MMTrack\MMTrackAuthController;
use App\Http\Controllers\MMTrack\UsuarioController;
use App\Http\Controllers\MMTrack\DashboardController;
use App\Http\Controllers\MMTrack\AgenciaController;
use App\Http\Controllers\Ecommerce\EcommerceProductController;
use App\Http\Controllers\Ecommerce\EcommerceSyncController;
use App\Http\Controllers\Ecommerce\EcommerceCustomerController;
use App\Http\Controllers\Warehouse\WarehouseController;
use App\Http\Controllers\Sync\SyncController;
use App\Http\Controllers\MMTrack\ConfiguracionesController;
use App\Http\Controllers\PostVenta\ComentTrackingController;
use App\Http\Controllers\PostVenta\CustumersContactController;
use App\Http\Controllers\PostVenta\FacturasController;
use App\Http\Controllers\PostVenta\ProducDetailRequestController;
use App\Http\Controllers\PostVenta\ProveedorController;
use App\Http\Controllers\PostVenta\PvCustumersController;
use App\Http\Controllers\PostVenta\RequestController;
use App\Http\Controllers\PostVenta\ResourcesController;
use App\Http\Controllers\PostVenta\TrackingRequestController;
use App\Http\Controllers\PostVenta\UsersController;
use App\Http\Controllers\API\ApiGeneralController;
use Carbon\Carbon;
use App\Exports\VehiculosExport;
use App\Http\Controllers\Resources\GeneralController;
use App\Http\Controllers\vimAPI\ProductosController;
use App\Http\Controllers\vimAPI\SyncVimController;
use App\Http\Controllers\ClientJTController;
use App\Http\Controllers\FabricacionController;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//$ambiente = env('APP_ENV');
//die($ambiente);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


//Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/userEval',  [AuthController::class, 'user_validate'])->middleware('auth:sanctum')->middleware('auth:sanctum');

Route::post('/CustomerDebtInquiries', [CustomerController::class, 'customer_debt_inquiries'])->middleware('auth:sanctum');
Route::post('/CustomerPayments', [CustomerController::class, 'customer_payment_process'])->middleware('auth:sanctum');
Route::post('/BankReturnRequest', [CustomerController::class, 'bank_return_request'])->middleware('auth:sanctum');
//extorno prueba marco
Route::post('/ASReturnDocumentRequest', [CustomerController::class, 'as400_return_request']);
Route::get('/ValidateAmountSld/{id}', [CustomerController::class, 'customer_debt_inquiries_validate']);
Route::get('/CustomerDebtsList', [CustomerController::class, 'customer_debts_list']); //->middleware('auth:sanctum');


// **  APIREST ECOMMERCE  ** //
Route::get('/ecommerce/getLines', [EcommerceProductController::class, 'get_lines'])->middleware('auth:sanctum');
Route::get('/ecommerce/getModelsByLine/{line}', [EcommerceProductController::class, 'get_models_by_line_code'])->middleware('auth:sanctum');
Route::get('/ecommerce/getVehiclesByLineModel/{line}/{model}', [EcommerceProductController::class, 'get_vehicles_by_line_and_model']); //->middleware('auth:sanctum');
Route::get('/ecommerce/getProductLines/{line}/{part_code}', [EcommerceProductController::class, 'get_product_line_applications']); //->middleware('auth:sanctum');
Route::get('/ecommerce/getProductsByParams/{line}/{model}/{year}/{page}', [EcommerceProductController::class, 'get_products_by_params'])->middleware('auth:sanctum');
//Route::get('/ecommerce/getProducts', [EcommerceProductController::class, 'get_products'])->middleware('auth:sanctum');
Route::get('/ecommerce/products/getDiscountedProducts', [EcommerceProductController::class, 'get_product_discounts'])->middleware('auth:sanctum');
Route::get('/ecommerce/getProductsByTrademark/{trademark_code}', [EcommerceProductController::class, 'get_products_x_trademark']); //->middleware('auth:sanctum');
Route::post('/ecommerce/getProductsByParam', [EcommerceProductController::class, 'get_products_by_part_code_part_name_factory_code']); //->middleware('auth:sanctum');
Route::post('/ecommerce/getProductsNotFound', [EcommerceProductController::class, 'generate_report_not_found_products']); //->middleware('auth:sanctum');
Route::post('/ecommerce/getProductsByParamsR', [EcommerceProductController::class, 'get_products_by_params_refactored']); //->middleware('auth:sanctum');

//ruta para validar existencia de productos
Route::get('/ecommerce/showExistenceProductsByParams/{sku}/{qty}', [EcommerceProductController::class, 'showExistenceProducts']); //->middleware('auth:sanctum');
Route::post('/ecommerce/getProductsStockByWarehouse', [EcommerceProductController::class, 'get_products_stock_by_warehouse']); //->middleware('auth:sanctum');
Route::get('/ecommerce/address-types', [EcommerceProductController::class, 'getAddressTypes'])->middleware('auth:sanctum');

Route::post('/ecommerce/quotes/postQuote', [EcommerceProductController::class, 'post_quote']); //->middleware('auth:sanctum');
Route::post('/ecommerce/orders/postOrder', [EcommerceProductController::class, 'post_order']); //->middleware('auth:sanctum');
Route::get('/ecommerce/orders/getOrderStatus/{clientCode}/{quoteCode}/{orderCode}/{orderStatus}', [EcommerceProductController::class, 'retorna_estatus_pedido']); //->middleware('auth:sanctum');
//Route::post('/ecommerce/pagos/add', [EcommerceProductController::class, 'postOrderPayment']); //->middleware('auth:sanctum');

Route::post('/ecommerce/payments/postDepositPayment', [EcommerceProductController::class, 'post_deposit_payment']); //->middleware('auth:sanctum');

Route::post('/ecommerce/contact/add', [EcommerceProductController::class, 'addContact']); //->middleware('auth:sanctum');
Route::get('/ecommerce/contact/delete/{id}/{id2}', [EcommerceProductController::class, 'deleteContact']); //->middleware('auth:sanctum');
Route::get('/ecommerce/client/verifyDocument/{id}/{id2}', [EcommerceProductController::class, 'verifyDocument']); //->middleware('auth:sanctum');


Route::get('/ecommerce/getVehModels', [EcommerceSyncController::class, 'load_veh_models']); //->middleware('auth:sanctum');
Route::get('/ecommerce/getPartVehicles', [EcommerceSyncController::class, 'load_part_vehicles']); //->middleware('auth:sanctum');
Route::get('/ecommerce/sincronizarProductos', [EcommerceSyncController::class, 'sincronizarProductos']); //->middleware('auth:sanctum');

Route::post('/ecommerce/customer/postCustomerAddress', [EcommerceCustomerController::class, 'ecommerce_add_customer_address']); //->middleware('auth:sanctum');
Route::get('/ecommerce/customer/getPaymentMethods', [EcommerceCustomerController::class, 'get_customer_payment_method']); //->middleware('auth:sanctum');
Route::post('/ecommerce/customer/add', [EcommerceCustomerController::class, 'customerAdd']); //->middleware('auth:sanctum');
Route::get('/ecommerce/customer/getAccountStatus/{customerCode}', [EcommerceCustomerController::class, 'get_customer_account_status']); //->middleware('auth:sanctum');
Route::get('/ecommerce/customer/getAddresses/{codigo}/{business_id}', [EcommerceCustomerController::class, 'get_customer_addresses']); //->middleware('auth:sanctum');
Route::get('/ecommerce/customer/getAddress/{address_code}/{codigo}', [EcommerceCustomerController::class, 'get_customer_address']); //->middleware('auth:sanctum');


Route::post('/ecommerce/transport-agencies/add', [EcommerceCustomerController::class, 'transport_agencies_add']); //->middleware('auth:sanctum');

Route::get('/ecommerce/almacenes', [EcommerceProductController::class, 'getAlmacenes']);
Route::get('/ecommerce/sucursales', [EcommerceProductController::class, 'getSucursales']);

Route::post('/ecommerce/customer/postCustomerAddress2', [EcommerceCustomerController::class, 'ecommerce_add_customer_address_as400']);

Route::post('/ecommerce/customer/postCustomerAs', [EcommerceCustomerController::class, 'post_customer_as400']); //->middleware('auth:sanctum');

Route::get('/ecommerce/bancos/{codCia}/{extraInfo?}', [EcommerceCustomerController::class, 'get_bank_accounts']);
//Conexión a API java
Route::get('/ecommerce/orders/postQuoteOrderAs', [EcommerceProductController::class, 'crear_cotizacion_pedido_as400']); //->middleware('auth:sanctum');
// ** FIN  APIREST ECOMMERCE  ** //

// ** APIREST ALMACEN POLO ** //
//Route::get('/warehouse/getInventory', [WarehouseController::class, 'consignment_warehouse_inventory'])->middleware('auth:sanctum');
Route::get('/warehouse/getInventoryPolo', [WarehouseController::class, 'consignment_warehouse_inventory']); //->middleware('auth:sanctum');
///{company_code}/{customer_code}/{warehouse_code}
Route::get('/warehouse/sendInventoryPolo', [WarehouseController::class, 'enviar_correo_inventario_polo']);
// ** APIREST ALMACEN POLO ** //

// ** SINCRONIZACIÓN ** //
Route::get('/sync/sync/{param}', [SyncController::class, 'sync']);
Route::get('/sync/mantSync', [SyncController::class, 'mantenimiento_tabla_as_sync']);
// ** FIN SINCRONIZACIÓN ** //

// ** VISOR DE IMPORTACIONES ** //
Route::post('/general/getProviders', [GeneralController::class, 'get_providers_as400']);
Route::get('/general/getCountries', [GeneralController::class, 'get_countries']);
// ** FIN VISOR DE IMPORTACIONES ** //

// ** SYNC TIPO CAMBIO MYM ** //
Route::get('/sync/getExchangeRate', [SyncController::class, 'sincronizar_tipo_cambio_mym']);
Route::get('/ecommerce/getActualExchangeRate', [EcommerceCustomerController::class, 'get_actual_exchange_rate']);
// ** FIN SYNC TIPO CAMBIO MYM ** //

// ** API CLIENTES ** //
Route::get('/ecommerce/getIdentificationTypes', [EcommerceCustomerController::class, 'get_identification_types']); //->middleware('auth:sanctum');
Route::get('/ecommerce/getEmployments', [EcommerceCustomerController::class, 'get_employments']); //->middleware('auth:sanctum');
Route::get('/customers/getCustomerByCode/{idCliente}', [CustomersController::class, 'get_customer_by_code'])->middleware('auth:sanctum');
Route::get('/customers/getCustomerByIdentification/{identificationNumber}', [CustomersController::class, 'get_customer_by_identification']); //->middleware('auth:sanctum');
Route::get('/customers/getCustomers', [CustomersController::class, 'get_customers']); //->middleware('auth:sanctum');
Route::post('/customers/getCustomerByName', [CustomersController::class, 'get_customer_by_name']); //->middleware('auth:sanctum');

Route::post('/customers/postCustomerContact', [CustomersController::class, 'put_customer_contact'])->middleware('auth:sanctum');
// ** FIN API CLIENTES ** //

// ** MARCAS ** //
Route::get('/ecommerce/getTrademarks', [EcommerceProductController::class, 'get_product_brands']); //->middleware('auth:sanctum');
// ** MARCAS ** //

// ** ACTUALIZA IMAGENES ** //
Route::get('/ecommerce/getImagesToUpdate', [ApiGeneralController::class, 'actualiza_imagen_principal_productos']); //->middleware('auth:sanctum');
// ** ACTUALIZA IMAGENES ** //

// Grupo de rutas para usuarios autenticados por el guard [web] | default
Route::group(['middleware' => ['cors']], function () {

    //rutas para manejar servicios de el MM Track
    Route::get('/mmtrack/cliente/pedidos', [ServicesController::class, 'Dispatchs']);
    Route::get('/mmtrack/cliente/pedidos/calificar-pedido', [ServicesController::class, 'calificar']);

    //ruta para obtener los vehiculos registrados
    Route::get('/mmtrack/vehiculos/obtenerVehiculos', [VehiculosController::class, 'index']);
    //ruta para guardar vehiculos
    Route::get('/mmtrack/vehiculos/registrarVehiculo', [VehiculosController::class, 'guardar']);
    Route::get('/mmtrack/vehiculo/cambiar-vehiculo', [VehiculosController::class, 'cambiarVehiculo']);

    //ruta para obtener los ayudantes registrados
    Route::get('/mmtrack/ayudantes/obtener-ayudantes', [DriverAssistantController::class, 'index']);
    //ruta para guardar vehiculos
    Route::get('/mmtrack/ayudantes/registrar-ayudante', [DriverAssistantController::class, 'guardar']);
    //eliminar o activar ayudantes
    Route::get('/mmtrack/ayudantes/cambiar-estatus-ayudante/{id}', [DriverAssistantController::class, 'cambiarEstatus']);
    //eliminar o activar ayudantes
    Route::get('/mmtrack/ayudantes/trasbordar-ayudante/{id}/{id2}', [DriverAssistantController::class, 'trasbordoAyudante']);
    Route::get('/mmtrack/ayudantes/posponer-entrega', [DriverAssistantController::class, 'posponerEntrega']);
    //obtener nombre y id de ayudantes activos
    Route::get('/mmtrack/ayudantes/obtener-ayudantes-activos', [DriverAssistantController::class, 'ayudantesActivos']);


    //ruta para cambiar-estatus vehiculos
    Route::get('/mmtrack/vehiculos/cambiar-estatus-vehiculo/{id}', [VehiculosController::class, 'cambiasEstatus']);
    //ruta para obtener los vehiculos registrados
    Route::get('/mmtrack/vehiculos/obtenerVehiculos/{id}', [VehiculosController::class, 'ObtenerVehiculos']);
    Route::get('/mmtrack/vehiculos/obtenerVehiculosActivos', [VehiculosController::class, 'ObtenerVehiculosActivos']);

    //ruta para guardar vehiculos
    Route::get('/mmtrack/conductores/registrarConductor', [ConductoresController::class, 'guardar']);
    //ruta para obtener los conductores registrados
    Route::get('/mmtrack/conductores/obtenerConductores', [ConductoresController::class, 'index']);
    //ruta para obtener los usuarios registrados
    Route::get('/mmtrack/usuarios/obtenerUsuarios', [UsuarioController::class, 'index']);

    //ruta para obtener las agencias
    Route::get('/mmtrack/agencias/obtenerAgencias', [AgenciaController::class, 'index']);
    Route::get('mmtrack/agencias/crear-sucursal', [AgenciaController::class, 'guardarSucursal']);
    //eliminar sucursal
    Route::get('/mmtrack/agencias/eliminar-sucursal/{id}', [AgenciaController::class, 'eliminar']);

    //ruta para cambiar-estatus conductores
    Route::get('/mmtrack/conductores/cambiar-estatus-conductor/{id}', [ConductoresController::class, 'cambiasEstatus']);

    //ruta para obtener guia de remision
    Route::get('/mmtrack/servicios/obtener-guia-remision/{id}', [ServicesController::class, 'obtenerGuiaRemision']);

    //ruta para guardar despachos
    Route::get('/mmtrack/despachos/registrarDespacho', [OrderDeliveryController::class, 'guardar']);
    Route::get('/mmtrack/despachos/enviarMensajeWSPrueba', [OrderDeliveryController::class, 'enviarMensajeWSPrueba']);



    //ruta para iniciar despacho
    Route::get('/mmtrack/despachos/iniciar-despacho', [OrderDeliveryController::class, 'iniciarDespacho']);
    //ruta para guardar despachos
    Route::get('/mmtrack/despachos/registrarTransbordo', [OrderDeliveryController::class, 'registrarTransbordo']);

    //ruta para guardar descarga
    Route::get('/mmtrack/despachos/registrarDescarga', [OrderDeliveryController::class, 'registrarDescarga']);
    Route::get('/mmtrack/despachos/registrarDescargaMasiva', [OrderDeliveryController::class, 'registrarDescargaMasiva']);
    //ruta para obtener despachos por conductor
    Route::get('/mmtrack/despachos/obtenerDespachosPorConductor/{id}', [OrderDeliveryController::class, 'obtenerDespachosPorConductor']);
    Route::get('/mmtrack/despachos/obtenerDespachosPorAyudante/{id}', [OrderDeliveryController::class, 'obtenerRegistrosAyudante']);

    //ruta para cambiar el estado de los envios
    Route::get('/mmtrack/despachos/cambiar-estado-envio', [OrderDeliveryController::class, 'cambiasEstatus']);
    //ruta para cargar el peso de los pedidos
    Route::get('/mmtrack/despachos/registrar-peso', [OrderDeliveryController::class, 'registrarPeso']);
    //ruta para cancelar el estado de los envios
    Route::get('/mmtrack/despachos/cancelar-envio', [OrderDeliveryController::class, 'cancelarEnvio']);
    //ruta para cancelar el estado de los envios
    Route::get('/mmtrack/despachos/cambiar-agencia', [OrderDeliveryController::class, 'cambiarAgencia']);

    //ruta para cerrar los envios
    Route::post('/mmtrack/despachos/cerrar-envio', [OrderDeliveryController::class, 'cerrarEnvio']);

    Route::get('/mmtrack/despachos/obtener-fotos/{id}', [OrderDeliveryController::class, 'obtenerFotos']);

    Route::post('/mmtrack/despachos/guardar-imagen/', [OrderDeliveryController::class, 'guardarImagen']);

    //ruta para obtener pedidos
    Route::get('/mmtrack/pedidos/obtenerpedidos', [PedidosController::class, 'pedidos']);
    //ruta para obtener pedido por id
    Route::get('/mmtrack/pedidos/obtener-datos-pedido', [PedidosController::class, 'obtenerDatosPedido']);


    //ruta para obtener vehiculos con pedidos asignados
    Route::get('/mmtrack/pedidos/vehiculos-con-pedidos', [PedidosController::class, 'vehiculosConPedidos']);

    //ruta para obtener ayudantes con pedidos asignados
    Route::get('/mmtrack/pedidos/ayudantes-con-pedidos', [PedidosController::class, 'ayudantesConPedidos']);

    //ruta para obtener pedidos empaquetados
    Route::get('/mmtrack/pedidos/obtenerPedidos/empaquetados', [PedidosController::class, 'pedidosEmpaquetados']);

    //ruta para obtener clientes
    Route::get('/mmtrack/clientes/obtenerClientes', [ClientesController::class, 'index']);

    //clientes para select
    Route::get('/mmtrack/clientes/obtenerClientesSelect', [ClientesController::class, 'clientesSelect']);

    //clientes para select
    Route::get('/mmtrack/inicio/pedidos/dashboard', [DashboardController::class, 'Dashboard']);
    Route::get('/mmtrack/inicio/pedidos/dashboard/obtenerVehiculos/{tipo}', [DashboardController::class, 'obtenerVehiculos']);
    Route::get('/mmtrack/inicio/pedidos/dashboard/obtenerChartPedidos/{fecha}/{sede}', [DashboardController::class, 'obtenerChartPedidos']);
    Route::get('/mmtrack/inicio/pedidos/dashboard/obtenerChartPedidosPorAlmacen/{fecha}/{sede}', [DashboardController::class, 'gestionPedidosPorAlmacen']);
    Route::get('/mmtrack/inicio/pedidos/dashboard/obtenerPedidosFiltrados/{fecha}/{tipo}/{sede}', [DashboardController::class, 'obtenerPedidosFiltrados']);

    //ruta para sincroniar pedidos del as400
    Route::get('/mmtrack/sincronizar/as400', [ServicesController::class, 'sincronizarAs400']);

    //ruta para obtener configuracion de transito
    Route::get('/mmtrack/configuracion/tracking', [ConfiguracionesController::class, 'index']);

    //ruta para obtener configuracion de transito
    Route::get('/mmtrack/configuracion/tracking/registrar', [ConfiguracionesController::class, 'guardar']);
    //ruta para cambiar estatus del transito
    Route::get('/mmtrack/configuracion/tracking/cambiar-estatus/{id}', [ConfiguracionesController::class, 'cambiasEstatus']);
    //ruta para obtener mensajes de transito
    Route::get('/mmtrack/configuracion/mensajes', [ConfiguracionesController::class, 'obtenerMensajes']);

    //ruta para reporte de pedidos
    Route::get('/mmtrack/reportes/pedidos', [ReportesController::class, 'Pedidos']);

});


//ruta para iniciar sesion desde mmtrack
Route::post('mmtrack/login', [MMTrackAuthController::class, 'login']);
Route::get('mmtrack/usuario/crear-usuario', [MMTrackAuthController::class, 'guardarUsuario']);
//ruta para cambiar de clave
Route::get('/mmtrack/usuario/cambiar-clave', [MMTrackAuthController::class, 'cambiarClave']);


Route::group([

    'middleware' => 'api',
    'prefix' => 'auth'

], function ($router) {

    Route::post('mmtrack/logout', [MMTrackAuthController::class, 'logout']);
    Route::post('mmtrack/refresh', [MMTrackAuthController::class, 'refresh']);
    Route::post('mmtrack/me', [MMTrackAuthController::class, 'me']);
});


Route::post('/postventa/users/login-mym', [UsersController::class, 'loginMym']);
Route::post('/postventa/users/login-token-mym', [UsersController::class, 'loginMymValidate']);
Route::post('/postventa/users/login', [UsersController::class, 'Login']);
Route::post('/postventa/users/verify_token', [UsersController::class, 'verifyToken']);
Route::post('/postventa/users/verify_company_by_email', [UsersController::class, 'verifyCompanyByUser']);
Route::get('/postventa/users', [UsersController::class, 'index']);
Route::post('/postventa/resources', [ResourcesController::class, 'getResources']);
Route::post('/postventa/resources/bol-fac', [ResourcesController::class, 'getResourcesFacBol']);
Route::post('/postventa/resources/categoria-by-ts', [ResourcesController::class, 'getCategoiraByTS']);
Route::post('/postventa/request/create', [RequestController::class, 'create']);
Route::post('/postventa/customer/by-document', [PvCustumersController::class, 'customerByDocument']);
Route::post('/postventa/contact/create', [CustumersContactController::class, 'create']);
Route::post('/postventa/contact/contact-by-customer', [CustumersContactController::class, 'index']);
Route::get('/postventa/request/pdf', [RequestController::class, 'TextSolicitudesPdf']);
Route::get('/postventa/request/index/{id}', [RequestController::class, 'getByRequest']);
Route::get('/postventa/request/email', [RequestController::class, 'sendMailable']);
Route::post('/postventa/request/by-request', [RequestController::class, 'getByIdRequestForTracking']);
Route::post('/postventa/coment/create', [ComentTrackingController::class, 'create']);
Route::post('/postventa/coment/by-request', [ComentTrackingController::class, 'getByIdRequest']);
Route::post('/postventa/request/upd-request-id', [RequestController::class, 'editStateRquest']);
Route::post('/postventa/product-detail/by-request', [ProducDetailRequestController::class, 'index']);
Route::post('/postventa/users/by-roles', [UsersController::class, 'byIdRoles']);
Route::post('/postventa/tracking-request/create', [TrackingRequestController::class, 'createRechazado']);
Route::post('/postventa/product-detail/upd-procede-state', [ProducDetailRequestController::class, 'updateDetailProductNP']);
Route::post('/postventa/product-detail/files-by-product-detail', [ProducDetailRequestController::class, 'filesByDetailProduct']);
Route::post('/postventa/request/upd-proveedor', [ProducDetailRequestController::class, 'stateCerradoProveedor']);
Route::post('/postventa/facturas/by-number', [FacturasController::class, 'index']);
Route::post('/postventa/facturas/guia-remision', [FacturasController::class, 'getGuiaRemision']);
Route::post('/postventa/request/send-email', [RequestController::class, 'sendEmailPersonalize']);
Route::post('/postventa/request/send-email-solution', [RequestController::class, 'sendMailSolucionado']);
Route::get('/postventa/request/send-email', [RequestController::class, 'sendMailSolucionado']);
Route::post('/postventa/proveedor/proveedor-by-request', [ProveedorController::class, 'index']);
Route::post('/postventa/product-detail/oc-by-company-id', [ProducDetailRequestController::class, 'ocByCompanyId']);
Route::post('/postventa/request/alert', [RequestController::class, 'index']);
Route::put('/postventa/request/alert-active', [RequestController::class, 'alertActive']);
Route::post('/postventa/request/atenttions', [RequestController::class, 'getAtenttion']);
Route::post('/postventa/request/excel', [RequestController::class, 'exportExcell']);
Route::post('/postventa/request/excel-generador', [RequestController::class, 'exportGeneradoExcel']);
Route::post('/postventa/product-detail/upd-oc', [ProducDetailRequestController::class, 'updOc']);
Route::post('/postventa/request/nc-by-factura', [RequestController::class, 'getNCByFactura']);
Route::post('/postventa/request/year-by-line-by-model', [RequestController::class, 'getYearByLineByModel']);
Route::post('/postventa/request/request-by-fecha', [RequestController::class, 'getRequestByFecha']);
Route::post('/postventa/request/pie-request-by-fecha', [RequestController::class, 'getPieRequestByFecha']);
Route::post('/postventa/request/bar-request-estado-by-fecha', [RequestController::class, 'getBarRequestEstadoByFecha']);
Route::post('/postventa/request/grid-request-vendedor-by-fecha', [RequestController::class, 'getGridRequestVendedorByFecha']);
Route::post('/postventa/request/donuts-request-motivo-by-fecha', [RequestController::class, 'getDonutsRequestMotivoByFecha']);
Route::post('/postventa/resources/create-marca', [ResourcesController::class, 'createMarca']);
Route::get('/postventa/request/generator/pdf/{id}', [RequestController::class, 'getPdfGeneratoSolicitudes']);
Route::post('/postventa/resources/get-estado-quejas-admin', [ResourcesController::class, 'getEstadoQuejasAdmin']);
Route::post('/postventa/request/upd-motivo', [RequestController::class, 'updmotivo']);
Route::post('/postventa/users/by-tracking-roles', [UsersController::class, 'byIdRolesPoventaTracking']);
Route::post('/postventa/request/upload', [RequestController::class, 'uploadFile']);
Route::post('/postventa/request/up-upload', [RequestController::class, 'SubuploadFileDes']);
Route::post('/postventa/resources/getLines', [ResourcesController::class, 'get_lines']);
Route::get('/postventa/resources/getModelsByLine/{line}', [ResourcesController::class, 'get_models_by_line_code']);
Route::get('/postventa/request/informe-tecnico/pdf/{id}', [RequestController::class, 'getPdfInformeTecnico']);
Route::post('/postventa/request/dasboard-excel', [RequestController::class, 'exportDasboardExcell']);
Route::post('/postventa/request/upd-request-qa-id', [RequestController::class, 'editStateQARquest']);
Route::post('/postventa/product-detail/upd-procede-revision-state', [ProducDetailRequestController::class, 'updateDetailRevisionProductNP']);
Route::get('/ecommerce/getProducts', [EcommerceProductController::class, 'get_products']);
Route::get('/postventa/request/informe-tecnico/producto/pdf/{id}', [RequestController::class, 'getByProductoPdfInformeTecnico']);


//ruta para exportar a excel el reporte de pedidos
Route::get('/mmtrack/reportes/pedidos-exportar/excel', [ReportesController::class, 'PedidosExportarExcel']);
Route::post('/postventa/users/permiso-user', [UsersController::class, 'userPermission']);
Route::post('/postventa/request/donuts-request-producto-by-fecha', [RequestController::class, 'getGridRequestByProducto']);
Route::post('/postventa/request/donuts-request-proveedor-by-fecha', [RequestController::class, 'getGridRequestByProveedor']);
Route::post('/postventa/request/list-vendedor-by-fecha', [RequestController::class, 'byCodVendedorByFecha']);
Route::post('/postventa/customer/request-by-document', [PvCustumersController::class, 'customerRequestByDocument']);
Route::post('/postventa/request/request-file', [RequestController::class, 'filesRequest']);
Route::post('/postventa/resources/resources-by-parent', [ResourcesController::class, 'getResourcesByParentDetail']);
Route::post('/postventa/request/total-request-by-user', [RequestController::class, 'totalRequestByUser']);
Route::post('/postventa/request/donuts-request-marca-by-fecha', [RequestController::class, 'getGridRequestByMarca']);
Route::post('/postventa/request/donuts-request-procede-no-by-fecha', [RequestController::class, 'getDonutByProcedeNot']);
Route::post('/postventa/request/report-txt-resguardo', [RequestController::class, 'resguardoFileTxt']);
Route::post('/postventa/request/report-txt-lectura', [RequestController::class, 'lecturaFileTxt']);
Route::get('/formatpdf', [RequestController::class, 'funcionOpen']);
Route::get('/postventa/request/reenvio-mail-proveedor', [RequestController::class, 'sendReenvioMailProveedor']);

//WEB SERVICE JTOCAS - VIM
//Trae Productos de la DB: LIBPRDDAT y la tabla B2BPROD del DB2
Route::get('/vimAPI/producto', [ProductosController::class, 'getProducto']);
//Trae consultas almacenadas de la DB: LIBPRDDAT y la tabla AS_SYNC del DB2
Route::get('/vimAPI/dataqueryclient', [SyncVimController::class, 'getQueriesStoragedInDB2CLIENTMIGRATION']);
//INSERTAR A TRAVES DEL POSTMAN USANDO EL METODO POST Y LA DATA EN FORMATO JSON
Route::post('/vimAPI/insertardatosjson', [SyncVimController::class, 'insertarDatos']);
//ACTUALMENTE ESTA FUNCIONANDO PARA LEER DATA DEL DB2 E INSERTAR EN LA TABLA AS_SYNC DE POSTGRESQL
// Route::post('/vimAPI/synctable/{param}', [SyncVimController::class, 'syncvim']); //FUNCIONANDO, EN USO
Route::get('/vimAPI/synctable/{param}', [SyncVimController::class, 'syncvim']); //FUNCIONANDO, EN USO
//PROBANDO QUE HAY 2 MANERAS DE CONECTARNOS A UNA TABLA EN POSTGRESQL
Route::get('/vimAPI/getclient', [ClientJTController::class, 'ObtenerDatosCliente']);
//INSERTAR A TRAVES DEL POSTMAN USANDO EL METODO POST Y LA DATA EN FORMATO JSON (TABLA clientes)
Route::post('/vimAPI/insertarclientejson', [ClientJTController::class, 'insertarCliente']);
//FIN WEB SERVICE VIM

Route::get('/vimAPI/list-sku', [ProductosController::class, 'getSku']);
Route::get('/vimAPI/repuesto/{sku}', [ProductosController::class, 'getRepuesto']);
Route::get('/vimAPI/marca', [ProductosController::class, 'getMarca']);
Route::get('/vimAPI/linea', [ProductosController::class, 'getLinea']);
Route::get('/vimAPI/origen', [ProductosController::class, 'getOrigen']);
Route::get('/vimAPI/consulta-repuesto/{texto}', [ProductosController::class, 'getConsultaRepuesto']);
