<?php

namespace App\Http\Controllers\PostVenta;

use App\Exports\RequestDasboardExport;
use App\Exports\RequestExport;
use App\Exports\RequestGeneradorExport;
use App\Exports\UsersExport;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Rules\ValidationMYM;
use Illuminate\Support\Facades\Config;
use App\Repository\FunctionsRepository;
use PDF;
use App\Mail\SolicitudMailable;
use App\Mail\ProveedorMailable;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\PostVenta\ComentTrackingController;
use App\Http\Controllers\PostVenta\TrackingStateController;
use GuzzleHttp\Promise\Create;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
class RequestController extends Controller
{   
    protected $functionsReposirory;
    public function __construct(FunctionsRepository $functionsReposirory)
    {
        $this->functionsReposirory = $functionsReposirory;
    }
    public function index(Request $request){
        $dataResources=DB::table('gen_resource_details')
        ->select(
            "id"
        )->where('resource_id', 42)
        ->where('code', '06')
        ->skip(0)
        ->take(1)
        ->get();
        $id_state = 0;
        if(count($dataResources)>0){
            $id_state =$dataResources[0]->id;
        }
        $data=DB::table('poventa_request as t1')
        ->leftJoin('customers as t2', 't1.id_client', '=', 't2.id')
        ->leftJoin('gen_resource_details as t3', 't1.type_document', '=', 't3.id')
        ->leftJoin('gen_resource_details as t4', 't1.type_request', '=', 't4.id')
        ->leftJoin('gen_resource_details as t5', 't1.category', '=', 't5.id')
        ->leftJoin('gen_resource_details as t6', 't1.id_state', '=', 't6.id')
        ->select( 
            "t1.id",
            "t1.id as idRequest",
            "t1.num_request",
            DB::raw("concat(t1.num_request, ' - ', t2.name_social_reason) as title"),
            DB::raw("to_char(t1.date_reg,'DD/MM/YYYY') as date_reg"),
            "t1.id_client",
            "t2.name_social_reason",
            "t1.fac_nom_vendedor",
            "t3.description as tipo_doc",
            "t4.description as tipo_sol",
            "t5.description as categoria",
            "t6.description as estado",
            "t1.alert_date as date",
        )  
        ->where('t1.alert_state',1 )
        ->where('t1.alert_id_user',$request->id_user )
        //->whereNotIn('t1.id_state', [1524])//desarrollo
        //->whereNotIn('t1.id_state', [1891])//produccion
        ->whereNotIn('t1.id_state', [$id_state])//produccion
        ->orderBy('id', 'asc')
        ->get();
        $datosCalendar  =array();
        foreach ($data as $item){
            $color = '';
            if($item->estado==='GENERADO'){
                $color = '#9191a5';
            }
            if($item->estado==='ANULADO'){
                $color = '#7239ea';
            }
            if($item->estado==='EN PROCESO'){
                $color = '#e5b407';
            }
            if($item->estado==='PENDIENTE'){
                $color = '#36a76a';
            }
            if($item->estado==='RECHAZADO'){
                $color = '#d9214e';
            }
            if($item->estado==='CERRADO'){
                $color = '#0e98e6';
            }
            $datosCalendar[]=array(
                "id"=>$item->id,
                "idRequest"=>$item->idRequest,  
                "num_request"=>$item->num_request,
                "title"=>$item->title,
                "date_reg"=>$item->date_reg,
                "date"=>$item->date,
                "color"=>$color, 
                "id_client"=>$item->id_client,
                "name_social_reason"=>$item->name_social_reason,
                "fac_nom_vendedor"=>$item->fac_nom_vendedor,
                "tipo_doc"=>$item->tipo_doc,
                "tipo_sol"=>$item->tipo_sol,
                "categoria"=>$item->categoria,
                "estado"=>$item->estado,
            );
        }
        return response()->json($datosCalendar, 200);
    }
   
    public function create(Request $request){
        $req=$request->input();
        $idContacto = $req['IdContact'];
        $devolucion = $req['devolucion'];
        $dataProduct = json_decode($req['data_product'], true);
        $questionRequest = json_decode($req['question_request'], true);
        if($idContacto===null){
            $idContacto=0;
        }
        $required = 'required';
        $requiredTipoTRabajo = [$required,function ($attribute, $value, $fail) {if ($value == 0) {$fail('Seleccione Tipo de Trabajo');}}];
        if($req['isCheckedInstall']==='false'){
            $required = '';
            $requiredTipoTRabajo=''; 
        }
        $dateNowFpdf=date('m-d-H-i-s');
        $dateNow =date('Y-m-d H:i:s');
        $dateNowf2 =date('d/m/Y');
        $dateNowh2 =date('H:i');
        $requiredQuestion=[];
        if(count($questionRequest)===0){
            $requiredQuestion= [function ($attribute, $value, $fail) {if ($value ==='[]') {$fail('Seleccione una pregunta');}}];
        }
        $rules=[ 
            "TypeRequest"=> ['required','numeric',function ($attribute, $value, $fail) {if ($value == 0) {$fail('Seleccione tipo de Solicitud');}}],
            "IdClient"=>  ['required', 'numeric', function ($attribute, $value, $fail) {if ($value == 0) {$fail('Seleccione cliente');}}],
            //"IdContact"=>  ['required', 'numeric',function ($attribute, $value, $fail) {if ($value == 0) {$fail('Seleccione un contacto');}}],
            "Category"=>  ['required','numeric', function ($attribute, $value, $fail) {if ($value == 0) {$fail('Seleccione una categoria');}}],
            "type_document"=>  ['required','numeric', function ($attribute, $value, $fail) {if ($value == 0) {$fail('Seleccione tipo documento');}}],
            "serie"=>  ['required'],
            "NumFact"=>  ['required'],
            "Idresponsable"=> 'required',
            //"brand_veh"=>  $required,
            //"model_Veh"=>  $required,
            //"year_veh"=>  $required,
            //"plate_veh"=>  $required,
            //"engine_veh"=>  $required,
            //"type_use_machinery"=> $requiredTipoTRabajo,
            //"type_flaw"=>  ['required', function ($attribute, $value, $fail) {if ($value == 0) {$fail('Seleccione Tipo Defecto');}}],
            //"question_request"=>  $requiredQuestion,
            //"detail_request"=>  'required', 
        ];
        $validator = Validator::make($request->all(),$rules);
        if ($validator->fails()){
            return response()->json( ["estado"=>false, "data"=>$validator->errors(),"message"=>'warning','alert'=>0], 200);
        }else{
            $validProductDetalle = false;
            //if($req['TypeRequest'] == 1488 || $req['TypeRequest'] == 1490){//desarollo
            //if($req['TypeRequest'] == 1818 || $req['TypeRequest'] == 1816){//producción
            if($req['tiposolText']==='QUEJA' || $req['tiposolText']==='G. ADMIN'){ //producción
                $validProductDetalle = true;
            }else{
                if (count($dataProduct)>0){
                    $validProductDetalle = true;
                }
            }
            if ($validProductDetalle){
                $unit_rec = 0;
                $unit_ven = 0;
                $arrayPartDetail=array(); 
                $rulesProducto=[];
                foreach ($dataProduct as $v){
                    $unidad_ven = $v["unidad_ven"];
                    $unidad_rec = $v["unidad_rec"];
                    $detail_init = $v["detail_init"];

                    $line_id_veh = $v["line_id_veh"];
                    $line_code_veh = $v["line_code_veh"];
                    $brand_veh = $v["brand_veh"];
                    $model_id_veh = $v["model_id_veh"];
                    $model_code_veh = $v["model_code_veh"];
                    $model_Veh = $v["model_Veh"];
                    $year_veh = $v["year_veh"];
                    $plate_veh = $v["plate_veh"];
                    $engine_veh = $v["engine_veh"];
                    $type_usemachinery = $v["type_use_machinery"];
                    $idseinstalo = $v["idseinstalo"];
                    $codigo = $v["codigo"];
                    $marca = $v["marca"];
                    $question_req = $v["question_req"];
                    if($unidad_rec>0){
                        $unit_rec++;
                    }
                    if($unidad_rec<=$unidad_ven){
                        $unit_ven++; 
                    }
                    $arrayPartDetail[]=($v["part_detail_id"]); 

                    if($unidad_rec<=0){
                        $rulesProducto["unidad_rec"] = 'Ingrese la cantidad reclamado [Código: '.$codigo.']';
                    }
                    if($unidad_rec>$unidad_ven){
                        $rulesProducto["unidad_rec"] = 'El campo Unidad reclamda no debe ser mayor a la unidad de venta [Código: '.$codigo.']';
                    }
                    if(count($question_req)<=0){
                        $rulesProducto["question_req"] = 'Seleccione al menos un tipo de defecto [Código: '.$codigo.']';
                    }
                    if ($detail_init===""){
                        $rulesProducto["detail_init"] = 'El campo detalle de producto es obligatorio [Código: '.$codigo.']';
                    }    
                    if($idseinstalo){     
                        if($brand_veh===""){
                            $rulesProducto["brand_veh"] = 'Seleccione un marca [Código: '.$codigo.']';
                        }
                        if($model_Veh===""){
                            $rulesProducto["model_Veh"] = 'Seleccione un modelo [Código: '.$codigo.']';
                        }
                        if($year_veh===""){
                            $rulesProducto["year_veh"] = 'Seleccione año  [Código: '.$codigo.']';
                        }
                        if($plate_veh===""){
                            $rulesProducto["plate_veh"] = 'El campo placa es obligatorio [Código: '.$codigo.']';
                        }
                        if($engine_veh ===""){
                            $rulesProducto["engine_veh"] = 'El campo modelo de motor es obligatorio [Código: '.$codigo.']';
                        }
                        if($type_usemachinery ==="0" || $type_usemachinery ===""){ 
                            $rulesProducto["type_usemachinery"] = 'El campo tipo de trabajo que realiza [Código: '.$codigo.']';
                        } 
                    }
                }
                if(count($rulesProducto)>0){
                    return response()->json( ["estado"=>false, "data"=>$rulesProducto,"message"=>'warning','alert'=>0], 200);
                }else{
                    /*if($unit_rec==count($dataProduct)){
                        if($unit_ven==count($dataProduct)){
                            //this code
                        }else{
                            return response()->json( ["estado"=>false, "data"=>array("nume"=>["El campo Unidad reclamda no debe ser mayor a la unidad de venta"]),"message"=>'warning'], 200);
                        }
                    }else{
                        return response()->json( ["estado"=>false, "data"=>array("nume"=>["Ingrese la cantidad reclamado"]),"message"=>'warning', 'alert'=>0], 200);
                    }
                    */
                    $dataResources=DB::table('gen_resource_details')
                    ->select(
                        "id"
                    )->where('resource_id', 42)
                    ->where('code', '01')
                    ->skip(0)
                    ->take(1)
                    ->get();
                    $idstate_row = 0;
                    if(count($dataResources)>0){
                        $idstate_row =$dataResources[0]->id;
                    }
                    $request_count =DB::table('poventa_request')
                    ->select(DB::raw('count(*) as req_count')) 
                    ->first();
                    $request_id_count =DB::table('poventa_request')
                    ->select(DB::raw('id +1 as id_sum')) 
                    ->orderBy('id', 'desc')
                    ->first();
                    $num_request =('R0'.$request_count->req_count).'1'.'-'.date('Y');
                    if($request_count->req_count>0){
                        $num_request ='R00'.($request_id_count->id_sum).'-'.date('Y');
                    }
                    $nameFilePDF = $num_request.'-'.$dateNowFpdf.'.pdf';
                    $type_use_machinery = $req['type_use_machinery']==='false' ? 0:$req['type_use_machinery'];
                    $type_flaw = $req['type_flaw']==='false' ? 0:$req['type_flaw'];
                    $data=array();
                    $data['num_request'] = $num_request;
                    $data['type_request' ] =  $req['TypeRequest'];
                    $data['category' ] =  $req['Category'];
                    $data['id_client' ] =  $req['IdClient'];
                    $data['type_document' ] =  $req['type_document'];
                    $data['serie'] = 0 ;
                    $data['serie_name'] = $req['serie'];
                    $data['id_contact' ] =  $req['IdContact'];
                    //$data['id_contact' ] =  0;
                    $data['fac_fiscal_document_id' ] =  $req['fac_fiscal_document_id'];
                    $data['num_fact' ] =  $req['NumFact'];
                    $data['id_responsable' ] =  $req['Idresponsable'];
                    $data['id_state'] =  $idstate_row;
                    $data['line_id'] =  $req['line_id'];
                    $data['line_code' ] =  $req['line_code'];
                    $data['brand_veh' ] =  $req['brand_veh'];
                    $data['model_id' ] =  $req['model_id'];
                    $data['model_code' ] =  $req['model_code'];
                    $data['model_Veh' ] =  $req['model_Veh'];
                    $data['year_veh' ] =  $req['year_veh'];
                    $data['plate_veh' ] =  $req['plate_veh'];
                    $data['engine_veh' ] =  $req['engine_veh'];
                    $data['type_use_machinery' ] =  $type_use_machinery;
                    $data['type_flaw' ] =   $type_flaw;
                    $data['detail_request' ] =  $req['detail_request'];
                    $data['state' ] =  1;
                    $data['date_reg' ] =  $dateNow;
                    $data['hour_reg' ] =  date('H:i:s');
                    $data['filenamepdf' ] = $nameFilePDF;   
                    $data['fac_order_id' ] = $req['fac_order_id'];
                    $data['fac_nom_vendedor' ] = $req['fac_nom_vendedor'];
                    $data['fac_date_emision' ] = date_format(date_create_from_format('Ymd', $req['fac_date_emision']), 'Y-m-d');
                    $data['fac_suc' ] = $req['fac_suc'];
                    $data['fac_alm' ] = $req['fac_alm'];
                    $data['fac_dir_cli' ] = $req['customer_address']; 
                    $data['fac_guia_remision' ] = $req['fac_guia_remision'];
                    $data['id_user_reg' ] = $req['Idresponsable'];
                    $data['devolucion' ] = $devolucion ;
                    $result=DB::table('poventa_request')->insertGetId($data);
                    if($result>0){
                        /***NOTIFICACION AL TERCER MENSAJE */
                        $rsAlert=DB::table('poventa_request as t1')
                        ->leftJoin('poventa_produc_detail_request as t2', 't1.id', '=', 't2.id_request')
                        ->select( 
                            't2.code', 
                            't1.date_reg',
                            't2.brand',
                            't2.description',
                            't2.unit_ven',
                            't2.unit_rec'
                        )
                        ->whereIn('t2.part_detail_id', $arrayPartDetail)
                        ->orderBy('t1.date_reg', 'desc')
                        ->get();
                        $arrUnique=array();
                        foreach($rsAlert as $val){
                            $arrUnique[]=array(
                                'code'=>$val->code,
                                'description'=>$val->description
                            );
                        }
                        $arrUniqueAll =array_unique($arrUnique, SORT_REGULAR);
                        $arraySendProduct = array();
                        $codeProductoAlert='';
                        $codeArrayProducto=array();
                        foreach($arrUniqueAll as $val){ 
                            $counter = 0;     
                            $unitven = array();
                            $unit_vent = 0;
                            $unit_rec = 0;
                            foreach($rsAlert as $val1){
                                if($val1->code===$val['code']){
                                    $fechaActual = date("Y-m-d H:i:s");
                                    $FechaSeisMonth = date("Y-m-d H:i:s",strtotime($fechaActual."- 6 months")); 
                                    if($FechaSeisMonth<=$val1->date_reg){   
                                        $unitven[]=array(
                                            "unit_rec" => $val1->unit_rec,
                                            "unit_ven" => $val1->unit_ven,
                                            "date_reg" => $val1->date_reg,
                                        );
                                        $unit_vent+=$val1->unit_ven;
                                        $unit_rec+=$val1->unit_rec;
                                    }
                                }
                            }
                            $porcentajeTotal = ($unit_rec/$unit_vent)*100;
                            if ($porcentajeTotal>=30){
                                $arraySendProduct[]=array(
                                    'code'=>$val['code'],
                                    'description'=>$val['description'],
                                    'unitvenTotal'=>$unit_vent,
                                    'unitrecTotal'=>$unit_rec,
                                    'porcentajeTotal'=> $porcentajeTotal,
                                    "unitven"=>$unitven,
                                );   
                                $codeArrayProducto[]= ($val['code']);
                                $codeProductoAlert.=$val['code'].' - '.$val['description']. ', ';
                            }        
                        }
                        //***** ****************************/
                        $prodReqDet= array();  
                        $questionReq= array();               
                        foreach ($dataProduct as $itm){
                            $prodReqDet['id_request' ] =  $result;
                            $prodReqDet['num_fac' ] =  $req['NumFact'];
                            $prodReqDet['code' ] =  $itm["codigo"];
                            $prodReqDet['part_detail_id' ] =  $itm["part_detail_id"];
                            $prodReqDet['sku' ] =  $itm["sku"];
                            $prodReqDet['factory_code' ] =  $itm["factory_code"];
                            $prodReqDet['brand' ] =  $itm["marca"];
                            $prodReqDet['description' ] =  $itm["decripcion"];
                            $prodReqDet['unit_ven' ] =  $itm["unidad_ven"];
                            $prodReqDet['unit_rec' ] =  $itm["unidad_rec"];
                            $prodReqDet['state' ] =  1;
                            $prodReqDet['date_reg' ] =  $dateNow;
                            $prodReqDet['item_price' ] =  $itm["item_price"];
                            $prodReqDet['origin_code' ] =  $itm["origin_code"];
                            $prodReqDet['line_code' ] =  $itm["line_code"];
                            $prodReqDet['order_id' ] =  $itm["order_id"];
                            $prodReqDet['state_proveedor' ] =  1;
                            $prodReqDet['detail_init' ] =  $itm["detail_init"];
                            $question_req = $itm["question_req"];
                            $files_req = $itm["files_req"];
                            $prodReqDet['line_id_veh' ] =  $itm["line_id_veh"];
                            $prodReqDet['line_code_veh' ] =  $itm["line_code_veh"];
                            $prodReqDet['brand_veh' ] =  $itm["brand_veh"];
                            $prodReqDet['model_id_veh' ] =  $itm["model_id_veh"];
                            $prodReqDet['model_code_veh' ] =  $itm["model_code_veh"];
                            $prodReqDet['model_Veh' ] =  $itm["model_Veh"];
                            $prodReqDet['year_veh' ] =  $itm["year_veh"];
                            $prodReqDet['plate_veh' ] =  $itm["plate_veh"];
                            $prodReqDet['engine_veh' ] =  $itm["engine_veh"];
                            $prodReqDet['type_use_machinery' ] =  $itm["type_use_machinery"];

                            $rsDetailPrd=DB::table('poventa_produc_detail_request')->insertGetId($prodReqDet);
                            foreach ($question_req as $itm1){
                                $questionReq['id_product_detail_request'] = $rsDetailPrd;
                                $questionReq['id_request' ] =  $result;
                                $questionReq['id_question' ] =  $itm1["id_question"];
                                $questionReq['response' ] =  $itm1["response"];
                                $questionReq['state' ] =  1;
                                $questionReq['created_at' ] = date('Y-m-d H:i:s');                                    
                                $rsQuestion=DB::table('poventa_question_request')->insertGetId($questionReq);
                            } 
                            foreach ($files_req as $valor1){ 
                                $daFile['id_product_detail_request' ] = $rsDetailPrd;    
                                $daFile['type_file' ] = $valor1["type_file"];    
                                $daFile['id_request' ] = $result;    
                                $daFile['id_user' ] =$req['Idresponsable'];    
                                $daFile['name_file' ] = $valor1["name_file"];    
                                $daFile['name_file_encrypt' ] ='';  
                                $daFile['description' ] = $valor1["description"];  
                                $daFile['name_icon_file' ] =$valor1["name_icon_file"];    
                                $daFile['date_reg' ] = date('Y-m-d H:i:s');
                                $daFile['status_type' ] = 1;
                                $rsFile=DB::table('poventa_tracking_file')->insertGetId($daFile);
                            }           
                        }                           
                        $rsRequest=$this->getByRequest($result);
                        if(count($rsRequest)>0){
                            $dataDetail=DB::table('poventa_produc_detail_request as t1')
                            ->leftJoin('gen_resource_details as t2', 't1.status_product', '=', 't2.id')
                            ->leftJoin('gen_resource_details as t3', 't1.id_motivo', '=', 't3.id')
                            ->select(
                                't1.code',
                                't1.brand',
                                't1.unit_rec',
                                't1.unit_proc',
                                "t2.description as estado_producto",
                                "t3.description as motivo",
                                't1.line_code',
                                't1.factory_code',
                                't1.description',
                                't1.item_price'
                            )
                            ->where('t1.id_request',$result)
                            ->orderBy('t1.id', 'asc')
                            ->get();
                            $destianatariolerta=[  
                                'elonazco@mym.com.pe',
                                'sreynaga@mym.com.pe',
                                //'hhuanca@mym.com.pe',
                            ];
                            $rsDataSolicitud =  array( 
                                'Type'=>'byid',
                                'datadetial'=>$dataDetail,
                                "NumRquest"=>$num_request,
                                'contact_name'=>$rsRequest[0]->contact_name,
                                "NameClient"=>$rsRequest[0]->name_social_reason,
                                "contact_phone"=>$rsRequest[0]->contact_phone,
                                "Domicilio"=>'',
                                "Domicilio_contact"=>$rsRequest[0]->address_contact,
                                "Document_contact"=>$rsRequest[0]->document_contact,
                                "Document"=>$rsRequest[0]->document_number,
                                "Email"=>'',
                                "Detalle"=>$rsRequest[0]->detail_request,
                                "TypeRequest"=>$rsRequest[0]->description,
                                'Day'=> $this->functionsReposirory->NameDate($dateNow,'d'),
                                'Month'=> $this->functionsReposirory->NameDate($dateNow,'n_m'),
                                'Year'=> $this->functionsReposirory->NameDate($dateNow,'Y'),
                                'FilePDF'=>'files/reclamos/'.$nameFilePDF,
                                'estado_des'=>'GENERADO',
                                'maildestinatarioalert'=>$destianatariolerta,
                                'productos'=>$arraySendProduct
                            );
                            $trakState =  new TrackingStateController();
                            $rsInsSta  =$trakState->create($result, $req['Idresponsable'],$idstate_row);
                            $this->SolicitudesPdf($rsDataSolicitud, $nameFilePDF);
                            $resultData = [ 
                                "id"=>$result,
                                "filenameRoutes"=>'/files/reclamos/'.$nameFilePDF,
                                "fechaRegtxt"=>$num_request .' el '. $dateNowf2.' a las '.now()->isoFormat('hh:mm A'),
                                'num_request'=>$num_request
                            ];
                            $destinatario1 =[];
                            if($devolucion===1){
                                $destinatario1 =[
                                    //"gflores@mym.com.pe"
                                    'sreynaga@mym.com.pe',
                                    'elonazco@mym.com.pe'
                                ];
                            }
                            $emailcontact =$rsRequest[0]->contact_email;
                            $emailcontactValid ='elonazco@mym.com.pe';
                            if(filter_var($emailcontact, FILTER_VALIDATE_EMAIL)){
                                $emailcontactValid =$rsRequest[0]->contact_email;
                            }
                            $destinatario2 = [
                                'elonazco@mym.com.pe',
                                'sreynaga@mym.com.pe',
                                //$emailcontactValid,
                                /*'gflores@mym.com.pe',
                                'sreynaga@mym.com.pe',
                                'mperez@mym.com.pe',
                                'hhuanca@mym.com.pe'
                                */
                            ];
                            $destinatario= array_merge($destinatario1,$destinatario2);
                            $correo =  new SolicitudMailable($rsDataSolicitud);
                            Mail::to($destinatario)->send($correo);
                            /***************/
                            $alertState =0;
                            if(count($arraySendProduct)>0){
                                $data_mail = Mail::send('mails.alertaproducto', ["rsSend"=>$rsDataSolicitud], function($message) use ($rsDataSolicitud) {
                                    $message->to($rsDataSolicitud['maildestinatarioalert'])->subject($rsDataSolicitud['NumRquest'].' - La cantidad reclamada es superior al promedio vendido en los últimos 6 meses.')
                                    ->attach(public_path($rsDataSolicitud['FilePDF']));
                                },true);
                                $alertState =1;
                            }
                            /***************/
                            return response()->json(["estado"=>true, "data"=>$resultData,"message"=>'success', 'alert'=>$alertState, 'productAlert'=> '['.substr($codeProductoAlert, 0, -2).']', 'codeArrayProducto'=>$codeArrayProducto ], 200);
                        }else{
                            return response()->json(["estado"=>false, "data"=>$result,"message"=>'error'], 200);
                        }
                    }else{
                        return response()->json(["estado"=>false, "data"=>$result, "message"=>'error'], 200);
                    }
                }
            }else{
                return response()->json( ["estado"=>false, "data"=>array("nume"=>["Seleccion el detalle de factura"]),"message"=>'warning','alert'=>0], 200);
            }
        }
    }
    public function senEmailByAlmacen(){
        $arrayMail = array(
            array(
                "code_almacen"=>'1',
                "mail"=>'esandoval@mym.com.pe'
            ),
            array(
                "code_almacen"=>'3',
                "mail"=>'rvelasquez@mym.com.pe'
            ),
            array(
                "code_almacen"=>'4',
                "mail"=>'lchavez@mym.com.pe'
            ),
            array(
                "code_almacen"=>'5',
                "mail"=>'avargas@mym.com.pe'
            ),
            array(
                "code_almacen"=>'6',
                "mail"=>'ybasurto@mym.com.pe'
            ),
            array(
                "code_almacen"=>'7',
                "mail"=>'fniquen@mym.com.pe'
            ),
            array(
                "code_almacen"=>'22',
                "mail"=>'htacza@mym.com.pe'
            )
        );
        foreach($arrayMail  as $val){
            echo $val["mail"].' ';
        }
    }
    public function getByRequest($id){
        $data=DB::table('poventa_request as t1')
        ->leftJoin('customers as t2', 't1.id_client','=','t2.id')
        ->leftJoin('gen_resource_details as t3', 't1.type_request', '=', 't3.id')
        ->leftJoin('customer_contacts as t4', 't4.id', '=', 't1.id_contact')
        ->leftJoin('gen_resource_details as t5', 't1.id_state', '=', 't5.id')
        ->leftJoin('gen_resource_details as t6', 't1.category', '=', 't6.id')
        ->leftJoin('gen_resource_details as t7', 't1.id_motivo', '=', 't7.id')
        ->leftJoin('gen_resource_details as t8', 't1.serie', '=', 't8.id')
        ->select(
            't1.id',
            't1.num_request',
            't1.detail_request',
            't2.name_social_reason', 
            't2.code as code_cli', 
            't2.document_number',
            't3.id as id_tipo_reclamo', 
            't3.description',
            't4.contact_name',
            't4.contact_email',
            't1.filenamepdf',
            't4.contact_phone',
            't4.identification_number as document_contact',
            't4.contact_address as address_contact',
            't1.date_reg',
            't5.description as estado_des',
            't6.description as categoria',
            't7.description as motivo',
            't1.num_fact as num_comprobante',
            DB::raw("to_char(t1.fac_date_emision,'DD/MM/YYYY') as fac_date_emision"),
            DB::raw("to_char(t1.date_reg,'DD/MM/YYYY') as fecha_reg"),
            't1.fac_fiscal_document_id',
            't1.serie_name as serie_description',
        )->where('t1.id', $id)
        ->get();
        //return response()->json($data, 200);
        return $data; 
    }
    public function sendEmailPersonalize(Request $request){
        $rules=[
            "id"=>  ['required'],
            "correo"=>  ['required', 'email']
        ];
        $validator = Validator::make($request->all(),$rules);
        if ($validator->fails()){
            return response()->json( ["estado"=>false, "data"=>$validator->errors(),"message"=>'warning'], 200);
        }else{
            $rsRequest=$this->getByRequest($request->id);
            $nameEmail =$request->correo;
            if(count($rsRequest)>0){
                $rsDataSolicitud =  array( 
                    "NumRquest"=> $rsRequest[0]->num_request,
                    "NameClient"=>$rsRequest[0]->name_social_reason,
                    "Domicilio"=>'',
                    "Document"=>$rsRequest[0]->document_number,
                    "Email"=>'',
                    "Domicilio_contact"=>$rsRequest[0]->address_contact,
                    "Document_contact"=>$rsRequest[0]->document_number,
                    "Detalle"=>$rsRequest[0]->detail_request,
                    "TypeRequest"=>$rsRequest[0]->description,
                    'Day'=> '',
                    'Month'=>'',
                    'Year'=> '',
                    'FilePDF'=>'files/reclamos/'.$rsRequest[0]->filenamepdf
                );
                $destinatario = [ 
                    'elonazco@mym.com.pe',
                    'aorozco@mym.com.pe', 
                ];
                $correo =  new SolicitudMailable($rsDataSolicitud);
                Mail::to($nameEmail)->send($correo);
                return response()->json(["estado"=>true, "data"=>1,"message"=>'success'], 200);
            }else{
                return response()->json(["estado"=>false, "data"=>2,"message"=>'error'], 200);
            }
        }
    }
    public function getByIdRequestForTracking(Request $request){
        $data=DB::table('poventa_request as t1')
        ->leftJoin('customers as t2', 't1.id_client','=','t2.id')
        ->leftJoin('gen_resource_details as t3', 't1.type_request', '=', 't3.id')
        ->leftJoin('customer_contacts as t4', 't4.id', '=', 't1.id_contact')
        ->leftJoin('gen_resource_details as t5', 't5.id', '=', 't1.category')
        ->leftJoin('gen_resource_details as t6', 't6.id', '=', 't1.id_state')
        ->leftJoin('users as t7', 't7.id', '=', 't1.id_user_responsable')
        ->leftJoin('users as t9', 't9.id', '=', 't1.id_responsable')
        ->leftJoin('gen_resource_details as t10', 't10.id', '=', 't1.accion_correctiva_cli')
        ->leftJoin('gen_resource_details as t11', 't11.id', '=', 't1.id_motivo')
        ->select(
            't1.id', 
            't1.detail_request',
            't1.type_request as id_type_request',
            't1.category as id_category',
            't1.num_request',
            't1.num_fact',
            DB::raw("to_char(t1.date_reg,'DD/MM/YYYY') as date_reg"),
            DB::raw("to_char(t1.date_reg,'HH12:MI AM') as hour_reg"),
            't2.name_social_reason', 
            't2.document_number',
            't3.id as id_tipo_reclamo', 
            't3.description as type_request',
            't4.contact_name',
            't4.contact_email',
            't5.description as categoria',
            't6.description as estado',
            't6.description as estado_desc',
            't1.id_state',
            't7.name as name_user', 
            't9.name as name_user_gen',
            DB::raw("(DATE_PART('day', current_timestamp::timestamp - t1.date_reg::timestamp)) as ditas_diff"),
            DB::raw("(DATE_PART('day', current_timestamp::timestamp - t1.date_upd::timestamp)) as dias_reabrir_diff"),
            't1.num_nc_cli',
            't10.description as accion_correctiva_cli',
            't1.fac_nom_vendedor',
            DB::raw("to_char(t1.fac_date_emision,'DD/MM/YYYY') as fac_date_emision"),
            't1.fac_dir_cli',
            't1.fac_guia_remision',
            't1.fac_suc',
            't1.fac_alm',
            't1.alert_state',
            't11.description as motivo',
        )->where('t1.id', $request->id)
        ->skip(0)
        ->take(1)
        ->get();
        $requestDetail = array();
        foreach($data  as $val){
            $listDataToken = [
                $val->id,
                $val->num_request
            ]; 
            $keyurl = base64_encode(implode('|', $listDataToken)); 
            $requestDetail[]=array(
                "id" =>$val->id,
                "detail_request" =>$val->detail_request,
                "id_type_request" =>$val->id_type_request,
                "id_category" =>$val->id_category,
                "num_request" =>$val->num_request,
                "num_fact" =>$val->num_fact,
                "date_reg" =>$val->date_reg,
                "hour_reg" =>$val->hour_reg,
                "name_social_reason" =>$val->name_social_reason,
                "document_number" =>$val->document_number,
                "id_tipo_reclamo" =>$val->id_tipo_reclamo,
                "type_request" =>$val->type_request,
                "contact_name" =>$val->contact_name,
                "contact_email" =>$val->contact_email,
                "categoria" =>$val->categoria,
                "estado" =>$val->estado,
                "estado_desc" =>$val->estado_desc,
                "id_state" =>$val->id_state,
                "name_user" =>$val->name_user,
                "name_user_gen" =>$val->name_user_gen,
                "ditas_diff" =>$val->ditas_diff,
                "dias_reabrir_diff" =>$val->dias_reabrir_diff,
                "num_nc_cli" =>$val->num_nc_cli,
                "accion_correctiva_cli" =>$val->accion_correctiva_cli,
                "fac_nom_vendedor" =>$val->fac_nom_vendedor,
                "fac_date_emision" =>$val->fac_date_emision,
                "fac_dir_cli" =>$val->fac_dir_cli,
                "fac_guia_remision" =>$val->fac_guia_remision,
                "fac_suc" =>$val->fac_suc,
                "fac_alm" =>$val->fac_alm,
                "alert_state" =>$val->alert_state,
                "motivo" =>$val->motivo,
                "id_enconde" =>$keyurl
            );
        }
        return response()->json($requestDetail, 200);
    }
    public function SolicitudesPdf($rsDataSolicitud=[],$nameFile=''){
        $namePDF = $nameFile;
        $pdf = PDF::loadview('pdf.hojareclamacion', ['rsDataSolicitud'=>$rsDataSolicitud]);
        //$pdf=PDF::loadHTML('<h1>Test</h1>');
        $pdf->setPaper('letter', 'portrait');
        $pdf->render();
        //return $pdf->stream($namePDF); 
        file_put_contents('files/reclamos/'.$namePDF, $pdf->output());
    }
    public function TextSolicitudesPdf(){
        $rsDataSolicitud =  array( 
            'Type'=>'',
            "NumRquest"=>'',
            "NameClient"=>'', 
            'contact_name'=>'Contact Sabrina',
            "contact_phone"=>'9348938',
            "Domicilio"=>'Direcion av',
            "Document"=>'',
            "Domicilio_contact"=>'',
            "Document_contact"=>'',
            "Email"=>'',
            "Detalle"=>'',
            "TypeRequest"=>'reclamos ',
            'Day'=> $this->functionsReposirory->NameDate(date('Y-m-d H:i:s'),'d'),
            'Month'=> $this->functionsReposirory->NameDate(date('Y-m-d H:i:s'),'n_m'),
            'Year'=> $this->functionsReposirory->NameDate(date('Y-m-d H:i:s'),'Y'),
            'estado_des'=>''
        );
        $namePDF = 'Test-0-'.date('Y-m-d-h-i-s').'.pdf';
        $pdf = PDF::loadview('pdf.hojareclamacion', ['rsDataSolicitud'=>$rsDataSolicitud]);
        //$pdf=PDF::loadHTML('<h1>Test</h1>');
        $pdf->setPaper('letter', 'portrait');
        $pdf->render();
        //file_put_contents('files/reclamos/'.$namePDF, $pdf->output());
        return $pdf->stream($namePDF);
        //$pdf->download($namePDF);
    }
    public function getPdfGeneratoSolicitudes(Request $request){
        $prRoute =$request->id;
        $refDecode = base64_decode($prRoute);
        $refExplo = explode("|", $refDecode);
        $rsRequest=$this->getByRequest($refExplo[0]);
        $dataDetail=DB::table('poventa_produc_detail_request as t1')
        ->leftJoin('gen_resource_details as t2', 't1.status_product', '=', 't2.id')
        ->leftJoin('gen_resource_details as t3', 't1.id_motivo', '=', 't3.id')
        ->select(
            't1.code',
            't1.brand',
            't1.unit_rec',
            't1.unit_proc',
            "t2.description as estado_producto",
            "t3.description as motivo",
            't1.line_code',
            't1.factory_code',
            't1.description',
            't1.item_price'
        )
        ->where('t1.id_request',$refExplo[0] )
        ->orderBy('t1.id', 'asc')
        ->get();
        if(count($rsRequest)>0){
            $dateNow =$rsRequest[0]->date_reg;
            $rsDataSolicitud =  array( 
                'Type'=>'byid',
                'datadetial'=>$dataDetail,
                "NumRquest"=>$rsRequest[0]->num_request,
                'contact_name'=>$rsRequest[0]->contact_name,
                "NameClient"=>$rsRequest[0]->name_social_reason,
                "contact_phone"=>$rsRequest[0]->contact_phone,
                "Domicilio"=>'',
                "Domicilio_contact"=>$rsRequest[0]->address_contact,
                "Document_contact"=>$rsRequest[0]->document_contact,
                "Document"=>$rsRequest[0]->document_number,
                "Email"=>'',
                "Detalle"=>$rsRequest[0]->detail_request,
                "TypeRequest"=>$rsRequest[0]->description,
                'Day'=> $this->functionsReposirory->NameDate($dateNow,'d'),
                'Month'=> $this->functionsReposirory->NameDate($dateNow,'n_m'),
                'Year'=> $this->functionsReposirory->NameDate($dateNow,'Y'),
                "estado_des"=>$rsRequest[0]->estado_des
            );
            $namePDF = 'Test-0-'.date('Y-m-d-h-i-s').'.pdf';
            $pdf = PDF::loadview('pdf.hojareclamacion', ['rsDataSolicitud'=>$rsDataSolicitud]);
            $pdf->setPaper('letter', 'portrait');
            $pdf->render();
            return $pdf->stream($namePDF);
        }else{
            return view('pdf.filewhithout');
        }
    }
    public function sendMailable(){ 
        $datos = [
            "prov_ord_compra" => '34535353',
            "prov_cod_prod" => '34545343',
            "prov_subject" => 'Nota de Crédito',
            "prov_solution_proveedor" => 'NC',
            'prov_num_nc'=> '34543535',
            "prov_type_money_nc"  => 'soles',
            "prov_date_nc" => '19/05/2021',
            "prov_importe_nc" => '100.00',
            "prov_num_fac" => '3543535',
            "prov_date_fac" => '19/05/2021',
            "prov_monto_desc" => '100',
            "prov_porcentaje_desc"  => '10'
        ];
        $correo =  new ProveedorMailable($datos);
        Mail::to('elonazco@mym.com.pe')->send($correo);
        return 'Correo se envio exitoso ';
        
    }
    public function editStateRquest(Request $request){
        $rules=[]; //1522 en proceso 
        $code_estado = $request->code_estado;
        $dataResources=DB::table('gen_resource_details')
        ->select(
            "id",
            'description'
        )->where('resource_id', 42)
        ->where('code', $code_estado)
        ->skip(0)
        ->take(1)
        ->get();
        if(count($dataResources)>0){
            $idstate =$dataResources[0]->id;
            $description_state=$dataResources[0]->description;
            if($description_state==='CERRADO'){//produccion  123
                $rules=[
                    "accion_correctiva_cli"=>  ['required', 'numeric',function ($attribute, $value, $fail) {if ($value == 0) {$fail('Seleccione acción correctiva');}}],
                    "num_nc_cli"=>  ['required'],
                ];
            }
            $validator = Validator::make($request->all(),$rules);
            if ($validator->fails()){
                return response()->json( ["estado"=>false, "data"=>$validator->errors(),"message"=>'warning'], 200);
            }else{
                $datos["id_state"]  = $idstate;//$request->id_state;
                $datos["date_upd"]  =  date('Y-m-d H:i:s');
                $datos["hour_upd"]  =  date('H:i:s');
                $datos["id_user_upd"]  =  $request->id_user_upd; 
                if($description_state==='EN PROCESO'){ 
                    $datos["id_user_responsable"]  =  $request->id_user_responsable;
                    $datos["id_responsable"]  =  $request->id_user_responsable;
                }            
                $datos["num_nc_cli"]  =  $request->num_nc_cli;
                $datos["nc_fiscal_document_id"]  =  $request->nc_fiscal_document_id;
                $datos["accion_correctiva_cli"]  =  $request->accion_correctiva_cli;
                $rs = DB::table('poventa_request')->where('id','=', $request->id)->update($datos);
                if ($rs>0){
                    $trakState =  new TrackingStateController();
                    $rsInsSta  =$trakState->create($request->id,$request->id_user_upd,$idstate);
                    return response()->json(["estado"=>true, "data"=>$rs, "message"=>'success'], 200);
                }else{
                    return response()->json(["estado"=>false, "data"=>$rs, "message"=>'error'], 200);
                }
            }
        }else{
            return response()->json(["estado"=>false, "data"=>'No hay resultados posibles', "message"=>'error'], 200);
        }
    }
    public function editStateQARquest(Request $request){
        $rules=[]; //1522 en proceso 
        $code_estado = $request->code_estado;
        $id_type_request =  $request->id_type_request;
        $dataResources=DB::table('gen_resource_details')
        ->select(
            "id",
            'description'
        )->where('resource_id', 42)
        ->where('code', $code_estado)
        ->skip(0)
        ->take(1)
        ->get();
        if(count($dataResources)>0){
            $idstate =$dataResources[0]->id;
            $description_state=$dataResources[0]->description;
            if($description_state!=='EN PROCESO'){//produccion  123
                $rules=[
                    "id_motivo"=>  ['required', 'numeric',function ($attribute, $value, $fail) {if ($value == 0) {$fail('Seleccione un motivo');}}],
                ];
            }
            $validator = Validator::make($request->all(),$rules);
            if ($validator->fails()){
                return response()->json( ["estado"=>false, "data"=>$validator->errors(),"message"=>'warning'], 200);
            }else{
                $datos["id_state"]  = $idstate;//$request->id_state;
                $datos["date_upd"]  =  date('Y-m-d H:i:s');
                $datos["hour_upd"]  =  date('H:i:s');
                $datos["id_user_upd"]  =  $request->id_user_upd; 
                //if($request->id_state==1522){desarrollo
                //if($request->id_state===1889){ 
                if($description_state==='EN PROCESO'){ 
                    $datos["id_user_responsable"]  =  $request->id_user_responsable;
                    $datos["id_responsable"]  =  $request->id_user_responsable;
                }         
                $datos["id_motivo"]  =  $request->id_motivo;
                $rs = DB::table('poventa_request')->where('id','=', $request->id)->update($datos);
                if ($rs>0){
                    $trakState =  new TrackingStateController();
                    $rsInsSta  =$trakState->create($request->id,$request->id_user_upd,$idstate);
                    return response()->json(["estado"=>true, "data"=>$rs, "message"=>'success'], 200);
                }else{
                    return response()->json(["estado"=>false, "data"=>$rs, "message"=>'error'], 200);
                }
            }
        }else{
            return response()->json(["estado"=>false, "data"=>'No hay resultados posibles', "message"=>'error'], 200);
        }
    }
    public function sendMailSolucionado(Request $request){
        //$rsRequest=$this->getByRequest($request->id_request);
        $idrequest=$request->id_request;
        //$idrequest=47;
        $rsInforRequest=$this->getByRequest($idrequest);
        if(count($rsInforRequest)>0){
            $rsFiles=DB::table('poventa_tracking_file as t1')
            ->select(
                "t1.id",
                "t1.name_file",
                DB::raw("concat('/storage/mymfiles/', t1.name_file ) as nombre_archivo"),
                't1.description',
                't1.name_icon_file',
                't1.id_product_detail_request',
                't1.type_file',
                't1.Adicional'
            )->where('id_request', $idrequest)
            ->where('status', 1)
            ->where('t1.status_type', 2)
            ->orderBy('type_file', 'asc')
            ->get();
            $counter =0;
            $arrayColum = array();
            $arrayFinalFiles = array();
            foreach ($rsFiles as $val){
                $counter++;
                $varparam = 0;
                if($counter>=1 && $counter<3){
                    $varparam =1;
                }else if ($counter>=3 && $counter<5){
                    $varparam =2;
                }else if ($counter>=5 && $counter<7){
                    $varparam =3;
                }else if ($counter>=7 && $counter<9){
                    $varparam =4;
                }else if ($counter>=9 && $counter<11){
                    $varparam =5;
                }
                $arrayColum[]=array(
                    'GrouBy'=>$varparam
                );
                $arrayColumaa[]=array(
                    "id" => $val->id,
                    "name_file"=>$val->name_file,
                    "nombre_archivo"=>$val->nombre_archivo,
                    'description'=>$val->description,
                    'name_icon_file'=>$val->name_icon_file,
                    'id_product_detail_request'=>$val->id_product_detail_request,
                    'type_file'=>$val->type_file,
                    'Adicional'=>$val->Adicional,
                    'GrouBy'=>$varparam
                );
            }
            $arrGroup =array_unique($arrayColum, SORT_REGULAR);
            foreach ($arrGroup as $val){
                $group = $val["GrouBy"];
                $arrayatydd= array();
                foreach ($arrayColumaa as $val1){
                    if ($val1["GrouBy"] ===$val["GrouBy"]){
                        $arrayatydd[]=array(
                            "id" => $val1["id"],
                            "name_file"=>$val1["name_file"],
                            "nombre_archivo"=>$val1["nombre_archivo"],
                            'description'=>$val1["description"],
                            'name_icon_file'=>$val1["name_icon_file"],
                            'id_product_detail_request'=>$val1["id_product_detail_request"],
                            'type_file'=>$val1["type_file"],
                            'Adicional'=>$val1["Adicional"],
                            'GrouBy'=>$group
                        );
                    }
                }
                $arrayFinalFiles[]=array(
                    'GrouBy'=>$group,
                    'dato'=>$arrayatydd
                );
            }
            $dataDetail=DB::table('poventa_produc_detail_request as t1')
            ->leftJoin('gen_resource_details as t2', 't1.status_product', '=', 't2.id')
            ->leftJoin('gen_resource_details as t3', 't1.id_motivo', '=', 't3.id')
            ->Join('gen_resource_details as line', 't1.line_code', '=', 'line.code')
            ->leftJoin('gen_resource_details as t4', 't1.recover_discard', '=', 't4.id')
            ->select(
                't1.code',
                't1.brand',
                't1.unit_rec',
                't1.unit_ven',
                't1.origin_code',
                't1.line_code',
                't1.unit_proc',
                't1.description as descripcion_prod',
                "t2.description as estado_producto",
                "t3.description as motivo",
                "t1.detail as detalle_producto",
                't1.subjet',
                't1.detail',
                't1.factory_code',
                "t1.cause_failure",
                "t1.recommendations",
                't1.item_price',
                "t1.id",
                't1.detail_init',
                'line.description as line_description',
                't4.description as condicion_descripcion',
                't1.detail_recover_discard',
                't1.conclusion_detail',
                't1.evidence',
                't1.id_request'
            )
            ->where('t1.id_request', $idrequest)
            ->where('t2.resource_id', 45)
            ->where('t2.code', '01')
            ->where('line.resource_id', 25)
            ->where('line.reg_status', 1)
            ->orderBy('t1.id', 'asc')
            ->get();
            $dataProveedorDetail=DB::table('poventa_produc_detail_request as t1')
            ->leftJoin('gen_resource_details as t2', 't1.status_product', '=', 't2.id')
            ->leftJoin('gen_resource_details as t3', 't1.id_motivo', '=', 't3.id')
            ->Join('gen_resource_details as line', 't1.line_code', '=', 'line.code')
            ->leftJoin('gen_resource_details as t4', 't1.recover_discard', '=', 't4.id')
            ->leftJoin('purchase_orders as oc', 't1.oc_id', '=', 'oc.id')
            ->select(
                't1.code',
                't1.brand',
                't1.unit_rec',
                't1.unit_ven',
                't1.origin_code',
                't1.line_code',
                't1.unit_proc',
                't1.description as descripcion_prod',
                "t2.description as estado_producto",
                "t3.description as motivo",
                "t1.detail as detalle_producto",
                't1.subjet',
                't1.detail',
                't1.factory_code',
                "t1.cause_failure",
                "t1.recommendations",
                't1.item_price',
                "t1.id",
                't1.detail_init',
                'line.description as line_description',
                't4.description as condicion_descripcion',
                't1.detail_recover_discard',
                't1.conclusion_detail',
                't1.evidence',
                'oc.purchase_description',
                't1.id_request'
            )
            ->where('t1.id_request', $idrequest)
            ->where('t2.resource_id', 45)
            ->where('t2.code', '01')
            ->where('t3.resource_id', '43')
            ->where('t3.code', '13')
            ->where('line.resource_id', 25)
            ->where('line.reg_status', 1)
            ->orderBy('t1.id', 'asc')
            ->get();
            /**********************PROVEEDOR********************/
            $arrayPdfFilesProveedor = array();
            foreach ($dataProveedorDetail as $val){ 
                $namePDFProv = $val->id.' - Informe-tecnico-proveedor-'.date('Y-m-d-h-i-s').'.pdf';
                $fileNameProveedor = 'files/reclamos/'.$namePDFProv;
                $rsDataProveedor =  array( 
                    'NumRquest'=>$rsInforRequest[0]->num_request,
                    'DayNow'=>date('d/m/Y'),
                    'NameClient'=>$rsInforRequest[0]->name_social_reason,
                    'Document'=>$rsInforRequest[0]->document_number,
                    'code_cli'=>$rsInforRequest[0]->code_cli,
                    'serie_description'=>$rsInforRequest[0]->serie_description,
                    'fac_date_emision'=>$rsInforRequest[0]->fac_date_emision,
                    'arrayFinalFiles'=>$arrayFinalFiles,
                    "num_comprobante"=>$rsInforRequest[0]->num_comprobante,
                    'code' =>$val->code,
                    'brand'=>$val->brand,
                    'unit_rec'=>$val->unit_rec,
                    'unit_ven'=>$val->unit_ven,
                    'origin_code'=>$val->origin_code,
                    'line_code'=>$val->line_code,
                    'unit_proc'=>$val->unit_proc,
                    'descripcion_prod'=>$val->descripcion_prod,
                    "estado_producto"=>$val->estado_producto,
                    "motivo"=>$val->motivo,
                    "detalle_producto"=>$val->detalle_producto,
                    'subjet'=>$val->subjet,
                    'detail'=>$val->detail,
                    'factory_code'=>$val->factory_code,
                    "cause_failure"=>$val->cause_failure,
                    "recommendations"=>$val->recommendations,
                    'item_price'=>$val->item_price,
                    "id"=>$val->id,
                    'detail_init'=>$val->detail_init,
                    'line_description'=>$val->line_description,
                    'condicion_descripcion'=>$val->condicion_descripcion,
                    'detail_recover_discard'=>$val->detail_recover_discard,
                    'conclusion_detail'=>$val->conclusion_detail,
                    'evidence'=>$val->evidence
                );
                $provpdf = PDF::loadview('pdf.informeproductoproveedor', ['rsDataInforme'=>$rsDataProveedor]);
                $provpdf->setPaper('letter', 'portrait');
                $provpdf->render();
                file_put_contents($fileNameProveedor, $provpdf->output());
                $arrayPdfFilesProveedor[]=($fileNameProveedor);
                //$ResponseFile = file_exists($fileNameProveedor) ? unlink($fileNameProveedor):'pdf no eliminado';
            }
            /************************ALMACEN*******************/
            $arrayPdfFilesAlmacen = array();
            foreach ($dataDetail as $val){ 
                $namePDFAlm = $val->id.' - Informe-tecnico-'.date('Y-m-d-h-i-s').'.pdf';
                $fileNameAlmacen = 'files/reclamos/'.$namePDFAlm;
                $rsDataAlmacen =  array( 
                    'NumRquest'=>$rsInforRequest[0]->num_request,
                    'DayNow'=>date('d/m/Y'),
                    'NameClient'=>$rsInforRequest[0]->name_social_reason,
                    'Document'=>$rsInforRequest[0]->document_number,
                    'code_cli'=>$rsInforRequest[0]->code_cli,
                    'serie_description'=>$rsInforRequest[0]->serie_description,
                    'fac_date_emision'=>$rsInforRequest[0]->fac_date_emision,
                    'arrayFinalFiles'=>$arrayFinalFiles,
                    "num_comprobante"=>$rsInforRequest[0]->num_comprobante,
                    'code' =>$val->code,
                    'brand'=>$val->brand,
                    'unit_rec'=>$val->unit_rec,
                    'unit_ven'=>$val->unit_ven,
                    'origin_code'=>$val->origin_code,
                    'line_code'=>$val->line_code,
                    'unit_proc'=>$val->unit_proc,
                    'descripcion_prod'=>$val->descripcion_prod,
                    "estado_producto"=>$val->estado_producto,
                    "motivo"=>$val->motivo,
                    "detalle_producto"=>$val->detalle_producto,
                    'subjet'=>$val->subjet,
                    'detail'=>$val->detail,
                    'factory_code'=>$val->factory_code,
                    "cause_failure"=>$val->cause_failure,
                    "recommendations"=>$val->recommendations,
                    'item_price'=>$val->item_price,
                    "id"=>$val->id,
                    'detail_init'=>$val->detail_init,
                    'line_description'=>$val->line_description,
                    'condicion_descripcion'=>$val->condicion_descripcion,
                    'detail_recover_discard'=>$val->detail_recover_discard,
                    'conclusion_detail'=>$val->conclusion_detail,
                    'evidence'=>$val->evidence
                );
                $pdf = PDF::loadview('pdf.informetecnicoproductomail', ['rsDataInforme'=>$rsDataAlmacen]);
                $pdf->setPaper('letter', 'portrait');
                $pdf->render();
                file_put_contents($fileNameAlmacen, $pdf->output());
                $arrayPdfFilesAlmacen[]=($fileNameAlmacen);
            }
            /*******************************************/
            $totalventa = 0;
            $totalsum = 0;
            foreach($dataDetail as $item){
                $totalventa = ($item->item_price * $item->unit_proc) + (($item->item_price * $item->unit_proc) * 0.18 )  ;
                $totalsum += $totalventa;
            }
            $destinatario1 = [];
            if($totalsum>500){
                $destinatario1 = [
                    //'sponce@mym.com.pe';
                    'sreynaga@mym.com.pe',
                    'elonazco@mym.com.pe',
                ];
            }
            $destinatario2=[  
                'elonazco@mym.com.pe',
                'sreynaga@mym.com.pe',
                /*'sreynaga@mym.com.pe',
                'gflores@mym.com.pe',
                'hhuanca@mym.com.pe',*/
                //'esandoval@mym.com.pe',
                //'rvelasquez@mym.com.pe',
                //'lchavez@mym.com.pe',
                //'avargas@mym.com.pe',
                //'ybasurto@mym.com.pe',
                //'fniquen@mym.com.pe'
            ];
            $destinatario= array_merge($destinatario1,$destinatario2);
            $namePDF = $rsInforRequest[0]->num_request.' - Informe-tecnico-'.date('Y-m-d-h-i-s').'.pdf';
            $fileName = 'files/reclamos/'.$namePDF;

            $namePDFProv = $rsInforRequest[0]->num_request.' - Informe-tecnico-proveedor-'.date('Y-m-d-h-i-s').'.pdf';
            $fileNameProveedor = 'files/reclamos/'.$namePDFProv;
            
            $rsFactBol=DB::table('v_fac_bol_cab')
            ->select(
                'numero_interno'
            )
            ->where('fiscal_document_id', $rsInforRequest[0]->fac_fiscal_document_id)
            ->skip(0)
            ->take(1)
            ->get();
            $numero_pedido = '';
            if(count($rsFactBol)>0){
                $numero_pedido  = $rsFactBol[0]->numero_interno;
            }     
            $dateNow =$rsInforRequest[0]->date_reg;
            $destinatarioproveedor = [
                'sreynaga@mym.com.pe',
                'elonazco@mym.com.pe'
            ];
            $rsDataSolicitud =  array( 
                'Type'=>'byid',
                'datadetial'=>$dataDetail,
                "NumRquest"=>$rsInforRequest[0]->num_request,
                'contact_name'=>$rsInforRequest[0]->contact_name,
                "NameClient"=>$rsInforRequest[0]->name_social_reason,
                "contact_phone"=>$rsInforRequest[0]->contact_phone,
                "Domicilio"=>'',
                "Domicilio_contact"=>$rsInforRequest[0]->address_contact,
                "Document_contact"=>$rsInforRequest[0]->document_contact,
                "Document"=>$rsInforRequest[0]->document_number,
                "Email"=>'',
                "Detalle"=>$rsInforRequest[0]->detail_request,
                "TypeRequest"=>$rsInforRequest[0]->description,
                'Day'=> $this->functionsReposirory->NameDate($dateNow,'d'),
                'Month'=> $this->functionsReposirory->NameDate($dateNow,'n_m'),
                'Year'=> $this->functionsReposirory->NameDate($dateNow,'Y'),
                "estado_des"=>$rsInforRequest[0]->estado_des,
                "fac_date_emision"=>$rsInforRequest[0]->fac_date_emision,
                "fecha_reg"=>$rsInforRequest[0]->fecha_reg, 
                "dataFiles"=>$rsFiles,
                'arrayFinalFiles'=>$arrayFinalFiles,
                "num_comprobante"=>$rsInforRequest[0]->num_comprobante,
                "serie_description"=>$rsInforRequest[0]->serie_description,
                'FilePDF'=>'files/reclamos/'.$rsInforRequest[0]->filenamepdf,
                'name'=>'esandoval@mym.com.pe',
                "categoria"=> $rsInforRequest[0]->categoria,
                'productos'=>$dataDetail,
                "code_cli"=> $rsInforRequest[0]->code_cli,
                "num_pedido"=>  $numero_pedido,
                "motivo"=> $rsInforRequest[0]->motivo,
                "detail_request"=> $rsInforRequest[0]->detail_request,
                "maildestinatario"=> $destinatario ,
                "maildestinatarioproveedor"=> $destinatarioproveedor ,
                'filenameinformetec'=>$fileName,
                'totalsum_nc'=> number_format($totalsum, 2, '.', ','),
                'dataProveedorDetail'=>$dataProveedorDetail,
                'fileNameProveedor'=>[$fileNameProveedor, $fileNameProveedor],
                'NumeroFacProveedor'=>'00548458',
                'DayNow'=>date('d/m/Y'),
                'arrayPdfFilesProveedor'=>$arrayPdfFilesProveedor,
                'arrayPdfFilesAlmacen'=>$arrayPdfFilesAlmacen,
                'id'=>$rsInforRequest[0]->id
            );
            if(count($dataDetail)>0){
                $data_mail = Mail::send('mails.solucionadomail', ["rsSend"=>$rsDataSolicitud], function($message) use ($rsDataSolicitud) {
                    $message->to($rsDataSolicitud['maildestinatario'])
                    ->subject($rsDataSolicitud['NumRquest'].' - Notificación de creación de la NC al cliente');
                    $attachmentsAl = $rsDataSolicitud['arrayPdfFilesAlmacen'];
                    foreach ($attachmentsAl as $filePath) {
                        $message->attach($filePath);
                    }
                },true);
                //$ResponseFile = file_exists($fileName) ? unlink($fileName):'pdf no eliminado';
            }
            if(count($dataProveedorDetail)>0){
                $proveedor_mail = Mail::send('mails.proveedorprocedente', ["rsSend"=>$rsDataSolicitud], function($message) use ($rsDataSolicitud) {
                    $message->to($rsDataSolicitud['maildestinatarioproveedor'])
                    ->subject('Proveedor - Notificación de los productos procedentes');
                    //->attach(public_path($rsDataSolicitud['fileNameProveedor']));
                    $attachments = $rsDataSolicitud['arrayPdfFilesProveedor'];
                    foreach ($attachments as $filePath) {
                        $message->attach($filePath);
                    }
                },true);
            }
            return response()->json(["estado"=>true], 200);
        }else{
            return response()->json(["estado"=>false], 200);
        }
    }

    public function alertActive(Request $request){
        $rules=[
            "alert_date"=>  ['required','date']
        ];
        $validator = Validator::make($request->all(),$rules);
        if ($validator->fails()){
            return response()->json( ["estado"=>false, "data"=>$validator->errors(),"message"=>'warning'], 200);
        }else{
            $datos["alert_state"]  = $request->alert_state;
            $datos["alert_id_user"]  = $request->alert_id_user;
            $datos["alert_date"]  =$request->alert_date;
            $datos["alert_description"]  = $request->alert_description;
            $datos["alert_date_reg"]  =  date('Y-m-d H:i:s');
            $rs = DB::table('poventa_request')->where('id','=', $request->id)->update($datos);
            if ($rs>0){
                return response()->json(["estado"=>true, "data"=>$rs, "message"=>'success'], 200);
            }else{
                return response()->json(["estado"=>false, "data"=>$rs, "message"=>'error'], 200);
            }
        }
    }
    public function stateCerradoRequest(Request $request){
        //$trakState =  new TrackingStateController();
        //$rsInsSta  =$trakState->create($request->id,$request->id_user_upd,$request->id_state);   
    }
    public function uploadFile(Request $request){
        //if($request->hasFile('avatar')){
            //$files = $request->file('avatar')->store("public/mymfiles");// otra forma simple de subir archivos
            $filesarr = $request->file('avatar');
            $id_request = $request->id_request;
            $ar = array();
            foreach( $filesarr as $val){
                $date = date('y-m-d-H-i-s');
                $nameFile = Str::slug( $date .'-'.$val->getClientOriginalName()).'.'.$val->getClientOriginalExtension();
                if(Storage::putFileAs('/public/mymfiles/',$val, $nameFile )){
                    //echo $nameFile.'<br>';
                    $arrayImges[]=array("nombre" =>$nameFile);
                }
            }
            return response()->json(["datos22"=>$filesarr, "datos"=>$ar, "message"=>$id_request], 200);
        //}
    }

    public function SubuploadFileDes(Request $request){
        $max_size = (int)ini_get("upload_max_filesize") * 10240;
        $id_request = $request->id_request;
        $files = $request->file("avatar");
        $arrayImges = array();
        foreach( $files as $val){
            $date = date('y-m-d-H-i-s');
            $nameFile = Str::slug( $date .'-'.$val->getClientOriginalName()).'.'.$val->getClientOriginalExtension();
            if(Storage::putFileAs('/public/mymfiles/',$val, $nameFile )){
                //echo $nameFile.'<br>';
                $arrayImges[]=array("nombre" =>$nameFile);
            }
        }
        return redirect('http://localhost:3011/reactreclamos/tracking/'.$id_request);
        //return response()->json(["estado"=>true, "datos"=>$files, "message"=>'success'], 200);
        //dd($files);
    }
    public function getAtenttion(Request $request){ 
        $iduser = $request->id_user==='all' ? '':$request->id_user;
        $codeiProd = $request->codigo_prod;
        $filterCode='%'.$iduser.'%';
        $columnCode='t1.id_responsable';
        if(strlen($codeiProd)>0){
            $filterCode='%'.$codeiProd.'%';
            $columnCode='t7.code';
        }
        $data=DB::table('poventa_request as t1')
        ->leftJoin('customers as t2', 't1.id_client', '=', 't2.id')
        ->leftJoin('gen_resource_details as t3', 't1.type_document', '=', 't3.id')
        ->leftJoin('gen_resource_details as t4', 't1.type_request', '=', 't4.id')
        ->leftJoin('gen_resource_details as t5', 't1.category', '=', 't5.id')
        ->leftJoin('gen_resource_details as t6', 't1.id_state', '=', 't6.id')
        ->leftJoin('poventa_produc_detail_request as t7', 't1.id', '=', 't7.id_request')
        ->select(
            "t1.id",
            "t1.num_request",
            DB::raw("to_char(t1.date_reg,'DD/MM/YYYY') as date_reg"),
            "t1.id_client",
            "t2.name_social_reason",
            "t1.fac_nom_vendedor",
            "t3.description as tipo_doc",
            "t4.description as tipo_sol",
            "t5.description as categoria",
            "t6.description as estado",
            "t1.id_responsable as id_responsable",
            "t1.id_user_responsable as id_user_responsable"
        )
        ->where('t1.state',1 )
        ->where('t1.type_request','like', '%'.$request->type_request.'%')
        ->where('t1.category','like', '%'.$request->category.'%')
        ->where('t1.id_client','like', '%'.$request->id_client.'%')
        ->where('t1.id_state','like', '%'.$request->id_state.'%')
        ->where('t1.type_document','like', '%'.$request->type_document.'%')
        ->where('t1.num_request','like', '%'.$request->num_request.'%')
        ->whereBetween('t1.date_reg', [$request->date_reg_ini.' 00:00:00', $request->date_reg_fin.' 23:59:59'])
        ->where('t1.id_responsable','like', '%'.$iduser.'%')
        ->where($columnCode,'like', $filterCode)
        ->where('t1.fac_nom_vendedor','like', '%'.$request->fac_nom_vendedor.'%')
        ->orderBy('id', 'asc')
        ->distinct()
        ->get();
        $detailDatos=DB::table('poventa_produc_detail_request as t1')
        ->select(
            "t1.id",
            "t1.id_request",
            "t1.unit_ven",
            "t1.unit_rec",
            't1.costo_proveedor',
            't1.unit_proc',
            't1.item_price'
        )
        ->get();
        $request = array();
        foreach($data as $val){
            $listDataToken = [
                $val->id,
                $val->num_request
            ]; 
            $keyurl = base64_encode(implode('|', $listDataToken));
            $totalventa = 0;
            $totalsum = 0;
            foreach($detailDatos as $item){
                if($item->id_request == $val->id){
                    $totalventa = ($item->item_price * $item->unit_proc) + (($item->item_price * $item->unit_proc) * 0.18 )  ;
                    //unidad_reclamada * $item->unit_ven 
                    $totalsum += $totalventa;
                }
            }
            $request[]=array(
                "id" =>$val->id,
                "num_request"=>$val->num_request,
                "date_reg" =>$val->date_reg,
                "id_client" =>$val->id_client,
                "name_social_reason" =>$val->name_social_reason,
                "fac_nom_vendedor" =>$val->fac_nom_vendedor,
                "tipo_doc" =>$val->tipo_doc,
                "tipo_sol" =>$val->tipo_sol,
                "categoria" =>$val->categoria,
                "estado" =>$val->estado,
                "id_enconde" =>$keyurl,
                "costoventa" => number_format($totalsum, 2, '.', ',') ,
                'id_user_responsable'=>$val->id_user_responsable,
                'id_responsable'=>$val->id_responsable
            );
        }
        return response()->json($request, 200);
    }

    public function exportExcell(Request $request) 
    {   
        $iduser = $request->id_user==='all' ? '':$request->id_user;
        $users = [
            "type_request"=>$request->type_request,
            "category"=>$request->category,
            "id_client"=>$request->id_client,
            "id_state"=>$request->id_state,
            "type_document"=>$request->type_document,
            "num_request"=>$request->num_request,
            "date_reg_ini"=>$request->date_reg_ini,
            "date_reg_fin"=>$request->date_reg_fin,
            "id_user"=>$iduser,
            "codigo_prod"=>$request->codigo_prod,
            'fac_nom_vendedor'=>$request->fac_nom_vendedor,
        ];
        $requestexpor = new RequestExport($users);
        return Excel::download($requestexpor, 'request.xlsx');
    }

    public function exportGeneradoExcel(Request $request) 
    {   
        $data = $request->data;
        $this->array_sort_by($data, 'order', SORT_ASC);
        $users = [
            "date_reg_ini"=>$request->date_reg_ini,
            "date_reg_fin"=>$request->date_reg_fin,
            "data"=>$data
        ];
        $requestexpor = new RequestGeneradorExport($users);
        $consulta = 'reporte-generador-'.$request->id_user;
        $fileNameRuta = "files/reclamos/".$consulta.".json";
        //$ResponseFile = file_exists($fileNameRuta) ? unlink($fileNameRuta):'pdf no eliminado';
        $fp = fopen($fileNameRuta,"w+"); 
        $miarchivo=fopen($consulta.'.json','w');
        if($fp == false) { 
            die("Ocurrió un error al crear resguardo"); 
        }
        fwrite($fp, json_encode($data));
        fclose($fp);
        return Excel::download($requestexpor, 'request.xlsx');
    }

    function array_sort_by(&$arrIni, $col, $order = SORT_ASC){
        $arrAux = array();
        foreach ($arrIni as $key=> $row)
        {
            $arrAux[$key] = is_object($row) ? $arrAux[$key] = $row->$col : $row[$col];
            $arrAux[$key] = strtolower($arrAux[$key]);
        }
        array_multisort($arrAux, $order, $arrIni);
    }
    public function getNCByFactura(Request $request){
        $data=DB::table('v_ncs_x_fac_bol as t1')
        ->join('poventa_request as t2', 't1.fac_fiscal_document_id', 't2.fac_fiscal_document_id')
        ->select(
            't1.fac_fiscal_document_id', 
            't1.nc_fiscal_document_id', 
            't1.nc_fiscal_serie', 
            't1.nc_correlative_fiscal_number'
        )->where('t1.fac_company_id', 1)
        ->skip(0)
        ->take(10)
        ->get();
        return response()->json($data, 200);
    }
    public function getYearByLineByModel(Request $request){
        $data=DB::table('v_anios_x_linea_modelo_veh_2')
        ->select('*'
        )
        ->where('model_code', $request->model)
        ->where('line_code', $request->line)
        ->get();
        return response()->json($data, 200);
    }
    public function getRequestByFecha(Request $request){
        $data=DB::table('poventa_request as t1')
        ->join('gen_resource_details as t2', 't1.type_request', 't2.id')
        ->join('gen_resource_details as t3', 't1.category', 't3.id')
        ->select(
            't1.type_request', 
            't1.category', 
            't2.description as tipo_solicitud', 
            't3.description as categoria',
            DB::raw("count(t2.id) as total_tipo_sol"),
        )
        ->whereBetween('t1.date_reg', [$request->date_reg_ini.' 00:00:00', $request->date_reg_fin.' 23:59:59'])
        ->groupBy('t2.description', 't3.description', 't1.type_request', 't1.category')
        ->get();
        return response()->json($data, 200);
    }
    public function getPieRequestByFecha(Request $request){
        $data=DB::table('poventa_request as t1')
        ->join('gen_resource_details as t2', 't1.type_request', 't2.id')
        ->select(
            't2.description as name',
            DB::raw("count(t2.id) as y"),
        )
        ->whereBetween('t1.date_reg', [$request->date_reg_ini.' 00:00:00', $request->date_reg_fin.' 23:59:59'])
        ->groupBy('t2.description')
        ->get();
        return response()->json($data, 200);
    }
    public function getBarRequestEstadoByFecha(Request $request){
        $data=DB::table('poventa_request as t1')
        ->join('gen_resource_details as t2', 't1.id_state', 't2.id')
        ->select(
            't2.description as estado',
            DB::raw("count(t2.id) as total_estado")
        )
        ->whereBetween('t1.date_reg', [$request->date_reg_ini.' 00:00:00', $request->date_reg_fin.' 23:59:59'])
        ->where('t1.type_request', $request->type_request)
        ->where('t1.category', $request->category)
        ->groupBy('t2.description')
        ->get();
        $datoscategories = array();
        $datoshich = array();
        foreach($data as $item){
            $datoscategories[]=(
                $item->estado
            );
            $datoshich[]=(
                $item->total_estado
            );
        }
        return response()->json(['categories'=>$datoscategories, 'data'=>$datoshich], 200);
    }
    public function getGridRequestVendedorByFecha(Request $request){
        $data=DB::table('poventa_request as t1')
        ->select(
            't1.fac_nom_vendedor as cod_vendedor',
            DB::raw("count(t1.fac_nom_vendedor) as total_vendedor")
        )
        ->whereBetween('t1.date_reg', [$request->date_reg_ini.' 00:00:00', $request->date_reg_fin.' 23:59:59'])
        ->where('t1.type_request', $request->type_request)
        ->where('t1.category', $request->category)
        ->groupBy('t1.fac_nom_vendedor')
        ->get();
        $datosVend = array();
        $totalVen = 0;
        //$dfdfd = lo que tengo / total * 100 
        foreach($data as $item){
            $totalVen+=$item->total_vendedor;
        }
        foreach($data as $item){
            $total_porcentaje = ($item->total_vendedor / $totalVen) *100;
            $datosVend[]=array( 
                "cod_vendedor"=>$item->cod_vendedor,
                "total_vendedor"=>$item->total_vendedor,
                "total_porcentaje"=>$total_porcentaje,
            ); 
        }   
        return response()->json(['data'=>$datosVend], 200);
    }
    public function getDonutsRequestMotivoByFecha(Request $request){
        //if( $request->type_request==1488 || $request->type_request==1490){//desarrollo
        //if($request->type_request== 1818 || $request->type_request == 1816){//producción
        if($request->tipo_solicitud==='G. ADMIN' || $request->tipo_solicitud ==='QUEJA'){//producción
            $data=DB::table('poventa_request as t1')
            ->join('gen_resource_details as t3', 't1.id_motivo', 't3.id')
            ->select(
                't3.description as motivo',
                DB::raw("count(t1.id_motivo) as total_motivo")
            )
            ->whereBetween('t1.date_reg', [$request->date_reg_ini.' 00:00:00', $request->date_reg_fin.' 23:59:59'])
            ->where('t1.type_request', $request->type_request)
            ->where('t1.category', $request->category)
            ->groupBy('t3.description')
            ->get();
            $datosVend = array();
            foreach($data as $item){
                $datosVend[]=([
                    $item->motivo, $item->total_motivo ]
                );
            }
            return response()->json(['data'=>$datosVend], 200);
        }else{ 
            $data=DB::table('poventa_request as t1')
            ->join('poventa_produc_detail_request as t2', 't1.id', 't2.id_request')
            ->join('gen_resource_details as t3', 't2.id_motivo', 't3.id')
            
            ->select(
                't3.description as motivo',
                DB::raw("count(t2.id_motivo) as total_motivo")
            )
            ->whereBetween('t1.date_reg', [$request->date_reg_ini.' 00:00:00', $request->date_reg_fin.' 23:59:59'])
            ->where('t1.type_request', $request->type_request)
            ->where('t1.category', $request->category)
            ->groupBy('t3.description')
            ->get();
            $datosVend = array();
            foreach($data as $item){
                $datosVend[]=([
                    $item->motivo, 
                    $item->total_motivo ]
                );
            }
            return response()->json(['data'=>$datosVend], 200);
        }
    }
    public function updmotivo(Request $request){
        $rules=[
            "id_motivo"=>  ['required'],
        ];        
        $validator = Validator::make($request->all(),$rules);
        if ($validator->fails()){
            return response()->json( ["estado"=>false, "data"=>$validator->errors(),"message"=>'warning'], 200);
        }else{
            $datos = array();
            $datos["id_motivo"]  =  $request->id_motivo;
            $rs = DB::table('poventa_request')->where('id','=', $request->id)->update($datos);
            if ($rs>0){
                return response()->json(["estado"=>true, "data"=>$rs, "message"=>'success'], 200);
            }else{
                return response()->json(["estado"=>false, "data"=>$rs, "message"=>'error'], 200);
            }
        }
    }
    public function getPdfInformeTecnico(Request $request){
        $prRoute =$request->id;
        $refDecode = base64_decode($prRoute);
        $refExplo = explode("|", $refDecode);
        $rsRequest=$this->getByRequest($refExplo[0]);
        $dataDetail=DB::table('poventa_produc_detail_request as t1')
        ->leftJoin('gen_resource_details as t2', 't1.status_product', '=', 't2.id')
        ->leftJoin('gen_resource_details as t3', 't1.id_motivo', '=', 't3.id')
        ->select(
            't1.code',
            't1.brand',
            't1.unit_rec',
            't1.origin_code',
            't1.line_code',
            't1.unit_proc',
            't1.description as descripcion_prod',
            "t2.description as estado_producto",
            "t3.description as motivo",
            "t1.detail as detalle_producto",
            't1.subjet',
            't1.detail',
            't1.factory_code',
            "t1.cause_failure",
            "t1.recommendations",
            't1.item_price',
            "t1.id",
            't1.detail_init'
        )
        ->where('t1.id_request',$refExplo[0] )
        ->orderBy('t1.id', 'asc')
        ->get();
        if(count($rsRequest)>0){
            $dateNow =$rsRequest[0]->date_reg;
            $rsFiles=DB::table('poventa_tracking_file as t1')
            ->select(
                "t1.id",
                "t1.name_file",
                DB::raw("concat('/storage/mymfiles/', t1.name_file ) as nombre_archivo"),
                't1.description',
                't1.name_icon_file',
                't1.id_product_detail_request',
                't1.type_file',
                't1.Adicional'
            )->where('id_request', $refExplo[0])
            ->where('status', 1)
            ->where('t1.status_type', 2)
            ->orderBy('type_file', 'desc')
            ->get();
            $rsDataSolicitud =  array( 
                'Type'=>'byid',
                'datadetial'=>$dataDetail,
                "NumRquest"=>$rsRequest[0]->num_request,
                'contact_name'=>$rsRequest[0]->contact_name,
                "NameClient"=>$rsRequest[0]->name_social_reason,
                "contact_phone"=>$rsRequest[0]->contact_phone,
                "Domicilio"=>'',
                "Domicilio_contact"=>$rsRequest[0]->address_contact,
                "Document_contact"=>$rsRequest[0]->document_contact,
                "Document"=>$rsRequest[0]->document_number,
                "Email"=>'',
                "Detalle"=>$rsRequest[0]->detail_request,
                "TypeRequest"=>$rsRequest[0]->description,
                'Day'=> $this->functionsReposirory->NameDate($dateNow,'d'),
                'Month'=> $this->functionsReposirory->NameDate($dateNow,'n_m'),
                'Year'=> $this->functionsReposirory->NameDate($dateNow,'Y'),
                "estado_des"=>$rsRequest[0]->estado_des,
                "fac_date_emision"=>$rsRequest[0]->fac_date_emision,
                "fecha_reg"=>$rsRequest[0]->fecha_reg,                
                "dataFiles"=>$rsFiles
            );
            $namePDF = 'Informe-tecnico-'.date('Y-m-d-h-i-s').'.pdf';
            $pdf = PDF::loadview('pdf.informetecnico', ['rsDataSolicitud'=>$rsDataSolicitud]); //informe Boton para ver
            $pdf->setPaper('letter', 'portrait');
            $pdf->render();
            return $pdf->stream($namePDF);
        }else{
            return view('pdf.filewhithout');
        }   
    }
    public function getByProductoPdfInformeTecnico(Request $request){
        $prRoute =$request->id;
        $refDecode = base64_decode($prRoute);
        $refExplo = explode("|", $refDecode);
        $rsRequest=$this->getByRequest($refExplo[0]);
        $dataDetail=DB::table('poventa_produc_detail_request as t1')
        ->leftJoin('gen_resource_details as t2', 't1.status_product', '=', 't2.id')
        ->leftJoin('gen_resource_details as t3', 't1.id_motivo', '=', 't3.id')
        ->Join('gen_resource_details as line', 't1.line_code', '=', 'line.code')
        ->leftJoin('gen_resource_details as t4', 't1.recover_discard', '=', 't4.id')
        ->select(
            't1.code',
            't1.brand',
            't1.unit_rec',
            't1.unit_ven',
            't1.origin_code',
            't1.line_code',
            't1.unit_proc',
            't1.description as descripcion_prod',
            "t2.description as estado_producto",
            "t3.description as motivo",
            "t1.detail as detalle_producto",
            't1.subjet',
            't1.detail',
            't1.factory_code',
            "t1.cause_failure",
            "t1.recommendations",
            't1.item_price',
            "t1.id",
            't1.detail_init',
            'line.description as line_description',
            't4.description as condicion_descripcion',
            't1.detail_recover_discard',
            't1.conclusion_detail',
            't1.evidence'
        )
        ->where('t1.id',$refExplo[1] )
        ->where('line.resource_id', 25)
        ->where('line.reg_status', 1)
        ->orderBy('t1.id', 'asc')
        ->get();
        if(count($rsRequest)>0){
            $dateNow =$rsRequest[0]->date_reg;
            $rsFiles=DB::table('poventa_tracking_file as t1')
            ->select(
                "t1.id",
                "t1.name_file",
                DB::raw("concat('/storage/mymfiles/', t1.name_file ) as nombre_archivo"),
                't1.description',
                't1.name_icon_file',
                't1.id_product_detail_request',
                't1.type_file',
                't1.Adicional'
            )->where('id_product_detail_request', $refExplo[1])
            ->where('status', 1)
            ->where('t1.status_type', 2)
            ->orderBy('type_file', 'asc')
            ->get();
            $counter =0;
            $arrayColum = array();
            $arrayFinalFiles = array();
            foreach ($rsFiles as $val){
                $counter++;
                $varparam = 0;
                if($counter>=1 && $counter<3){
                    $varparam =1;
                }else if ($counter>=3 && $counter<5){
                    $varparam =2;
                }else if ($counter>=5 && $counter<7){
                    $varparam =3;
                }else if ($counter>=7 && $counter<9){
                    $varparam =4;
                }else if ($counter>=9 && $counter<11){
                    $varparam =5;
                }
                $arrayColum[]=array(
                    'GrouBy'=>$varparam
                );
                $arrayColumaa[]=array(
                    "id" => $val->id,
                    "name_file"=>$val->name_file,
                    "nombre_archivo"=>$val->nombre_archivo,
                    'description'=>$val->description,
                    'name_icon_file'=>$val->name_icon_file,
                    'id_product_detail_request'=>$val->id_product_detail_request,
                    'type_file'=>$val->type_file,
                    'Adicional'=>$val->Adicional,
                    'GrouBy'=>$varparam
                );
            }
            $arrGroup =array_unique($arrayColum, SORT_REGULAR);
            foreach ($arrGroup as $val){
                $group = $val["GrouBy"];
                $arrayatydd= array();
                foreach ($arrayColumaa as $val1){
                    if ($val1["GrouBy"] ===$val["GrouBy"]){
                        $arrayatydd[]=array(
                            "id" => $val1["id"],
                            "name_file"=>$val1["name_file"],
                            "nombre_archivo"=>$val1["nombre_archivo"],
                            'description'=>$val1["description"],
                            'name_icon_file'=>$val1["name_icon_file"],
                            'id_product_detail_request'=>$val1["id_product_detail_request"],
                            'type_file'=>$val1["type_file"],
                            'Adicional'=>$val1["Adicional"],
                            'GrouBy'=>$group
                        );
                    }
                }
                $arrayFinalFiles[]=array(
                    'GrouBy'=>$group,
                    'dato'=>$arrayatydd
                );
            }
            $rsDataSolicitud =  array( 
                'Type'=>'byid',
                'datadetial'=>$dataDetail,
                "NumRquest"=>$rsRequest[0]->num_request,
                'contact_name'=>$rsRequest[0]->contact_name,
                "NameClient"=>$rsRequest[0]->name_social_reason,
                "contact_phone"=>$rsRequest[0]->contact_phone,
                "Domicilio"=>'',
                "Domicilio_contact"=>$rsRequest[0]->address_contact,
                "Document_contact"=>$rsRequest[0]->document_contact,
                "Document"=>$rsRequest[0]->document_number, 
                "Email"=>'',
                "Detalle"=>$rsRequest[0]->detail_request,
                "TypeRequest"=>$rsRequest[0]->description,
                'Day'=> $this->functionsReposirory->NameDate($dateNow,'d'),
                'Month'=> $this->functionsReposirory->NameDate($dateNow,'n_m'),
                'Year'=> $this->functionsReposirory->NameDate($dateNow,'Y'),
                "estado_des"=>$rsRequest[0]->estado_des,
                "fac_date_emision"=>$rsRequest[0]->fac_date_emision,
                "fecha_reg"=>$rsRequest[0]->fecha_reg,                
                "dataFiles"=>$rsFiles,
                'arrayFinalFiles'=>$arrayFinalFiles,
                "num_comprobante"=>$rsRequest[0]->num_comprobante,
                "serie_description"=>$rsRequest[0]->serie_description,
                'DayNow'=>date('d/m/Y'),
                "code_cli"=>$rsRequest[0]->code_cli
            );
            $namePDF = 'Informe-tecnico-'.date('Y-m-d-h-i-s').'.pdf';
            $pdf = PDF::loadview('pdf.informetecnicoproductoweb', ['rsDataSolicitud'=>$rsDataSolicitud]);
            $pdf->setPaper('letter', 'portrait');
            $pdf->render();
            return $pdf->stream($namePDF);
        }else{
            return view('pdf.filewhithout');
        }
    }
    public function exportDasboardExcell(Request $request) 
    {
        $users = [
            "type_request"=>$request->type_request,
            "category"=>$request->category,
            "id_client"=>$request->id_client,
            "id_state"=>$request->id_state,
            "type_document"=>$request->type_document,
            "num_request"=>$request->num_request,
            "date_reg_ini"=>$request->date_reg_ini,
            "date_reg_fin"=>$request->date_reg_fin,
            "id_user"=>$request->id_user,
        ];
        $requestexpor = new RequestDasboardExport($users);
        return Excel::download($requestexpor, 'dashboard.xlsx');
    }

    public function getGridRequestByProducto(Request $request){
        $data=DB::table('poventa_produc_detail_request as t1')
        ->Join('poventa_request as t2', 't1.id_request', '=', 't2.id')
        ->select(
            't1.code as code',
            't1.description as description',
            DB::raw("count(t1.code) as total_producto")
        )
        ->whereBetween('t2.date_reg', [$request->date_reg_ini.' 00:00:00', $request->date_reg_fin.' 23:59:59'])
        ->where('t2.type_request', $request->type_request)
        ->where('t2.category', $request->category)
        ->groupBy('t1.code', 't1.description')
        ->orderBY(DB::raw("count(t1.code)"), 'desc')
        ->get();
        $datosProd = array();
        $datosProd2 = array();
        $totalVen = 0;
        foreach($data as $item){
            $totalVen+=$item->total_producto;
        }
        foreach($data as $item){
            $total_porcentaje = ($item->total_producto / $totalVen) *100;
            $datosProd[]=([
                $item->code, 
                $item->total_producto]
            );
            $datosProd2[]=array(
                'code'=>$item->code, 
                'description'=>$item->description, 
                'total_producto'=>$item->total_producto,
                "total_porcentaje"=>$total_porcentaje,
            );
            
        }   
        return response()->json(['data'=>$datosProd, 'datos'=>$datosProd2], 200);
    }
    public function getGridRequestByProveedor(Request $request){
        $data=DB::table('poventa_produc_detail_request as t1')
        ->Join('poventa_request as t2', 't1.id_request', '=', 't2.id')
        ->leftJoin('gen_resource_details as t3', 't1.status_product', '=', 't3.id')
        ->leftJoin('gen_resource_details as t4', 't1.id_motivo', '=', 't4.id')
        ->select(
            't1.name_proveedor as name_proveedor',
            DB::raw("count(t1.name_proveedor) as total_proveedor")
        )
        ->whereBetween('t2.date_reg', [$request->date_reg_ini.' 00:00:00', $request->date_reg_fin.' 23:59:59'])
        ->where('t2.type_request', $request->type_request)
        ->where('t2.category', $request->category)
        ->where('t3.resource_id', 45)
        ->where('t3.code', '01')
        ->where('t4.resource_id', '43')
        ->where('t4.code', '13')
        ->groupBy('t1.name_proveedor')
        ->get();
        $datosProv = array();
        foreach($data as $item){
            $datosProv[]=([
                $item->name_proveedor, 
                $item->total_proveedor]
            );
        }   
        return response()->json(['data'=>$datosProv], 200);
    }
    public function byCodVendedorByFecha(Request $request)
    {
        $data =DB::table('poventa_request as t1')
        ->select(
            "t1.fac_nom_vendedor"
        )
        //->whereBetween('t1.date_reg', [$request->date_reg_ini.' 00:00:00', $request->date_reg_fin.' 23:59:59'])
        ->distinct()
        ->get();
        return response()->json($data, 200); 
    }
    public function filesRequest(Request $request){
        $files = $request->file("files");
        if($request->hasFile("files")){
            $counter=0;
            $daFile = array();
            foreach( $files as $val){
                $counter++;
                $date = date('y-m-d-H-i-s');
                $type_file = $val->getClientOriginalExtension();
                $name_icon_file = '';
                if($type_file==='pdf'){
                    $name_icon_file = 'pdf.png';
                }else if ($type_file==='mp4'){
                    $name_icon_file = 'video.png';
                }else if ($type_file==='png' ||$type_file==='jpg' || $type_file==='jpeg' || $type_file==='gif'){
                    $name_icon_file = 'image.png';
                }else{
                    $name_icon_file = 'more.png';
                }
                $nameFile = Str::slug($counter.'-'. $date .'-'.$val->getClientOriginalName()).'.'.$val->getClientOriginalExtension();
                if(Storage::putFileAs('/public/mymfiles/',$val, $nameFile )){
                }
                $daFile[]=array(
                    'type_file' => $type_file,
                    'name_file' => $nameFile,  
                    'description' => str_replace(" ","-", $val->getClientOriginalName()),
                    'name_icon_file' => $name_icon_file,
                    'nombre_archivo' => '/storage/mymfiles/'.$nameFile,
                );
            }
            return response()->json($daFile, 200); 
        }
    }

    public function totalRequestByUser(Request $request){
        $data=DB::table('poventa_request as t1')
        ->select(
            "t1.id_responsable",
            DB::raw("count(id_responsable) as total")
        )
        ->whereBetween('t1.date_reg', [$request->date_reg_ini.' 00:00:00', $request->date_reg_fin.' 23:59:59'])
        ->groupBy('t1.id_responsable')
        ->get();
        return response()->json($data, 200); 

    }
    public function getGridRequestByMarca(Request $request){
        $data=DB::table('poventa_produc_detail_request as t1')
        ->Join('poventa_request as t2', 't1.id_request', '=', 't2.id')
        ->select(
            't1.brand as brand',
            DB::raw("count(t1.brand) as total_marca")
        )
        ->whereBetween('t2.date_reg', [$request->date_reg_ini.' 00:00:00', $request->date_reg_fin.' 23:59:59'])
        ->where('t2.type_request', $request->type_request)
        ->where('t2.category', $request->category)
        ->groupBy('t1.brand')
        ->orderBY(DB::raw("count(t1.brand)"), 'desc')
        ->get();
        $datosProd = array();
        $datosProd2 = array();
        $totalVen = 0;
        foreach($data as $item){
            $totalVen+=$item->total_marca;
        }
        foreach($data as $item){
            $total_porcentaje = ($item->total_marca / $totalVen) *100;
            $datosProd[]=([
                $item->brand, 
                $item->total_marca]
            );
            $datosProd2[]=array(
                'brand'=>$item->brand, 
                'total_marca'=>$item->total_marca,
                "total_porcentaje"=>$total_porcentaje,
            );
            
        }   
        return response()->json(['data'=>$datosProd, 'datos'=>$datosProd2], 200);
    }
    //
    public function getDonutByProcedeNot(Request $request){ 
        $data=DB::table('poventa_request as t1')
        ->join('poventa_produc_detail_request as t2', 't1.id', 't2.id_request')
        ->join('gen_resource_details as t3', 't2.status_product', 't3.id')
        ->select(
            't3.description',
            DB::raw("count(t2.status_product) as total_estado"),
        )
        ->whereBetween('t1.date_reg', [$request->date_reg_ini.' 00:00:00', $request->date_reg_fin.' 23:59:59'])
        ->where('t1.type_request', $request->type_request)
        ->where('t1.category', $request->category)
        ->groupBy('t3.description')
        ->get();
        $datosProd = array();
        foreach($data as $item){
            $datosProd[]=([
                $item->description, 
                $item->total_estado]
            );            
        }  
        return response()->json($datosProd, 200);
    }
    public function resguardoFileTxt(Request $request){
        $consulta = 'reporte-generador-'.$request->id_user;
        $data = trim($request->html_content);
        $mensaje = "El archivo no existe";
        $fp = fopen("files/reclamos/".$consulta.".txt","w+"); 
        $miarchivo=fopen($consulta.'.json','w'); //abrir archivo, nombre archivo, modo apertura
        if($fp == false) { 
            die("Ocurrió un error al crear resguardo"); 
        }
        fwrite($fp, $data);
        fclose($fp);
    }
    public function lecturaFileTxt(Request $request){
        $consulta = 'reporte-generador-'.$request->id_user;
        $fileNameRuta = "files/reclamos/".$consulta.".json";
        $getContentJson=array();
        if(file_exists($fileNameRuta)){
            $getContentJson = file_get_contents($fileNameRuta);
            $getContentJson = json_decode($getContentJson);
        }
        $FileExist = file_exists($fileNameRuta) ? true:false;
        return response()->json(["datos"=> $getContentJson,'fileExist'=>$FileExist], 200);
    }
    public function formatPdf(){
        $dataProveedorDetail=DB::table('poventa_produc_detail_request as t1')
        ->leftJoin('gen_resource_details as t2', 't1.status_product', '=', 't2.id')
        ->leftJoin('gen_resource_details as t3', 't1.id_motivo', '=', 't3.id')
        ->Join('gen_resource_details as line', 't1.line_code', '=', 'line.code')
        ->leftJoin('gen_resource_details as t4', 't1.recover_discard', '=', 't4.id')
        ->select(
            't1.code',
            't1.brand',
            't1.unit_rec',
            't1.unit_ven',
            't1.origin_code',
            't1.line_code',
            't1.unit_proc',
            't1.description as descripcion_prod',
            "t2.description as estado_producto",
            "t3.description as motivo",
            "t1.detail as detalle_producto",
            't1.subjet',
            't1.detail',
            't1.factory_code',
            "t1.cause_failure",
            "t1.recommendations",
            't1.item_price',
            "t1.id",
            't1.detail_init',
            'line.description as line_description',
            't4.description as condicion_descripcion',
            't1.detail_recover_discard',
            't1.conclusion_detail',
            't1.evidence'
        )
        ->where('t1.id_request', 47)
        ->where('t2.resource_id', 45)
        ->where('t2.code', '01')
        ->where('t3.resource_id', '43')
        ->where('t3.code', '13')
        ->where('line.resource_id', 25)
        ->where('line.reg_status', 1)
        ->orderBy('t1.id', 'asc')
        ->get();
        $arrayFiles = array();
        foreach ($dataProveedorDetail as $val){
            $namePDFProv = $val->id.' EDWIN - Informe-tecnico-proveedor-'.date('Y-m-d-h-i-s').'.pdf';
            $fileNameProveedor = 'files/reclamos/'.$namePDFProv;
            $rsDataSolicitud =  array( 
                'dataProveedorDetail'=>$dataProveedorDetail,
                'code' =>$val->code,
                'brand'=>$val->brand,
                'unit_rec'=>$val->unit_rec,
                'unit_ven'=>$val->unit_ven,
                'origin_code'=>$val->origin_code,
                'line_code'=>$val->line_code,
                'unit_proc'=>$val->unit_proc,
                'descripcion_prod'=>$val->descripcion_prod,
                "estado_producto"=>$val->estado_producto,
                "motivo"=>$val->motivo,
                "detalle_producto"=>$val->detalle_producto,
                'subjet'=>$val->subjet,
                'detail'=>$val->detail,
                'factory_code'=>$val->factory_code,
                "cause_failure"=>$val->cause_failure,
                "recommendations"=>$val->recommendations,
                'item_price'=>$val->item_price,
                "id"=>$val->id,
                'detail_init'=>$val->detail_init,
                'line_description'=>$val->line_description,
                'condicion_descripcion'=>$val->condicion_descripcion,
                'detail_recover_discard'=>$val->detail_recover_discard,
                'conclusion_detail'=>$val->conclusion_detail,
                'evidence'=>$val->evidence
            );
            $provpdf = PDF::loadview('pdf.test', ['rsDataSolicitud'=>$rsDataSolicitud]);
            $provpdf->setPaper('letter', 'portrait');
            $provpdf->render();
            file_put_contents($fileNameProveedor, $provpdf->output());
            $arrayFiles[]=($fileNameProveedor);
        } 
        $destinatarioproveedor = [
            'elonazco@mym.com.pe'
        ]; 
        $rsData =[
            'maildestinatarioproveedor'=>$destinatarioproveedor,
            'arrayFiles'=>$arrayFiles
        ];
        $proveedor_mail = Mail::send('mails.solicitudes', ["rsSend"=>$rsData], function($message) use ($rsData) {
            $message->to($rsData['maildestinatarioproveedor'])
            ->subject('Proveedor - Notificación de los productos procedentes');
            //->attach(public_path($rsDataSolicitud['fileNameProveedor']));
            $attachments = $rsData['arrayFiles'];
            foreach ($attachments as $filePath) {
                $message->attach($filePath);
            }
        },true);
        return response()->json(["estado"=>true], 200);
    }    


    public function sendReenvioMailProveedor(Request $request){
        $dataProveedorDetail=DB::table('poventa_produc_detail_request as t1')
        ->leftJoin('gen_resource_details as t2', 't1.status_product', '=', 't2.id')
        ->leftJoin('gen_resource_details as t3', 't1.id_motivo', '=', 't3.id')
        ->Join('gen_resource_details as line', 't1.line_code', '=', 'line.code')
        ->leftJoin('gen_resource_details as t4', 't1.recover_discard', '=', 't4.id')
        ->leftJoin('purchase_orders as oc', 't1.oc_id', '=', 'oc.id')
        ->leftJoin('poventa_request as req', 'req.id', '=', 't1.id_request')
        ->leftJoin('gen_resource_details as t5', 'req.id_state', '=', 't5.id')
        ->select(
            't1.code',
            't1.brand',
            't1.unit_rec',
            't1.unit_ven',
            't1.origin_code',
            't1.line_code',
            't1.unit_proc',
            't1.description as descripcion_prod',
            "t2.description as estado_producto",
            "t3.description as motivo",
            "t1.detail as detalle_producto",
            't1.subjet',
            't1.detail',
            't1.factory_code',
            "t1.cause_failure",
            "t1.recommendations",
            't1.item_price',
            "t1.id",
            't1.detail_init',
            'line.description as line_description',
            't4.description as condicion_descripcion',
            't1.detail_recover_discard',
            't1.conclusion_detail',
            't1.evidence',
            'oc.purchase_description',
            't1.id_request'
        )
        ->where('t2.resource_id', 45)
        ->where('t2.code', '01')
        ->where('t3.resource_id', '43')
        ->where('t3.code', '13')
        ->where('line.resource_id', 25)
        ->where('line.reg_status', 1)
        ->where('t5.code', '02')
        ->whereNull('t1.prov_solution_proveedor')
        ->orderBy('t1.id', 'asc')
        ->get();
        $arrIdRequest = array();
        foreach ($dataProveedorDetail as $valor){
            $arrIdRequest[]=($valor->id_request);
        }
        $rsInforRequest=DB::table('poventa_request as t1')
        ->leftJoin('customers as t2', 't1.id_client','=','t2.id')
        ->leftJoin('gen_resource_details as t3', 't1.type_request', '=', 't3.id')
        ->leftJoin('customer_contacts as t4', 't4.id', '=', 't1.id_contact')
        ->leftJoin('gen_resource_details as t5', 't1.id_state', '=', 't5.id')
        ->leftJoin('gen_resource_details as t6', 't1.category', '=', 't6.id')
        ->leftJoin('gen_resource_details as t7', 't1.id_motivo', '=', 't7.id')
        ->leftJoin('gen_resource_details as t8', 't1.serie', '=', 't8.id')
        ->select(
            't1.id',
            't1.num_request',
            't1.detail_request',
            't2.name_social_reason', 
            't2.code as code_cli', 
            't2.document_number',
            't3.id as id_tipo_reclamo', 
            't3.description',
            't4.contact_name',
            't4.contact_email',
            't1.filenamepdf',
            't4.contact_phone',
            't4.identification_number as document_contact',
            't4.contact_address as address_contact',
            't1.date_reg',
            't5.description as estado_des',
            't6.description as categoria',
            't7.description as motivo',
            't1.num_fact as num_comprobante',
            DB::raw("to_char(t1.fac_date_emision,'DD/MM/YYYY') as fac_date_emision"),
            DB::raw("to_char(t1.date_reg,'DD/MM/YYYY') as fecha_reg"),
            't1.fac_fiscal_document_id',
            't1.serie_name as serie_description',
            DB::raw("(DATE_PART('day', current_timestamp::timestamp - t1.date_upd::timestamp)) as ditas_diff"),
        )
        ->whereIn('t1.id', $arrIdRequest)
        ->where('t5.code', '02')
        ->get();
        $ArrayDetailIdRequest =  array();  
        foreach ($rsInforRequest as $valor){
            $ditas_diff = $valor->ditas_diff;
            if($ditas_diff>4){
                $ArrayDetailIdRequest[]=($valor->id);
            }
        }
        $rsFiles=DB::table('poventa_tracking_file as t1')
        ->select(
            "t1.id",
            "t1.name_file",
            DB::raw("concat('/storage/mymfiles/', t1.name_file ) as nombre_archivo"),
            't1.description',
            't1.name_icon_file',
            't1.id_product_detail_request',
            't1.type_file',
            't1.Adicional',
            't1.id_request'
        )->whereIn('id_request', $ArrayDetailIdRequest)
        ->where('status', 1)
        ->where('t1.status_type', 2)
        ->orderBy('type_file', 'asc')
        ->get();
        if(count($rsInforRequest)>0){
            foreach ($rsInforRequest as $rsvalor){
                $ditas_diff = $rsvalor->ditas_diff;
                if($ditas_diff>4){
                    $counter =0;
                    $arrayColum = array();
                    $arrayFinalFiles = array();
                    foreach ($rsFiles as $val){
                        if($rsvalor->id===$val->id_request){
                            $counter++;
                            $varparam = 0;
                            if($counter>=1 && $counter<3){
                                $varparam =1;
                            }else if ($counter>=3 && $counter<5){
                                $varparam =2;
                            }else if ($counter>=5 && $counter<7){
                                $varparam =3;
                            }else if ($counter>=7 && $counter<9){
                                $varparam =4;
                            }else if ($counter>=9 && $counter<11){
                                $varparam =5;
                            }
                            $arrayColum[]=array(
                                'GrouBy'=>$varparam
                            );
                            $arrayColumaa[]=array(
                                "id" => $val->id,
                                "name_file"=>$val->name_file,
                                "nombre_archivo"=>$val->nombre_archivo,
                                'description'=>$val->description,
                                'name_icon_file'=>$val->name_icon_file,
                                'id_product_detail_request'=>$val->id_product_detail_request,
                                'type_file'=>$val->type_file,
                                'Adicional'=>$val->Adicional,
                                'GrouBy'=>$varparam
                            );
                        }
                    }
                    $arrGroup =array_unique($arrayColum, SORT_REGULAR);
                    foreach ($arrGroup as $val){
                        $group = $val["GrouBy"];
                        $arrayatydd= array();
                        foreach ($arrayColumaa as $val1){
                            if ($val1["GrouBy"] ===$val["GrouBy"]){
                                $arrayatydd[]=array(
                                    "id" => $val1["id"],
                                    "name_file"=>$val1["name_file"],
                                    "nombre_archivo"=>$val1["nombre_archivo"],
                                    'description'=>$val1["description"],
                                    'name_icon_file'=>$val1["name_icon_file"],
                                    'id_product_detail_request'=>$val1["id_product_detail_request"],
                                    'type_file'=>$val1["type_file"],
                                    'Adicional'=>$val1["Adicional"],
                                    'GrouBy'=>$group
                                );
                            }
                        }
                        $arrayFinalFiles[]=array(
                            'GrouBy'=>$group,
                            'dato'=>$arrayatydd
                        );
                    }
                    /**********************PROVEEDOR********************/
                    $arrayPdfFilesProveedor = array();
                    foreach ($dataProveedorDetail as $val){ 
                        if($rsvalor->id===$val->id_request){
                            $namePDFProv = $val->id.' - Informe-tecnico-proveedor-'.date('Y-m-d-h-i-s').'.pdf';
                            $fileNameProveedor = 'files/reclamos/'.$namePDFProv;
                            $rsDataProveedor =  array( 
                                'NumRquest'=>$rsvalor->num_request,
                                'DayNow'=>date('d/m/Y'),
                                'NameClient'=>$rsvalor->name_social_reason,
                                'Document'=>$rsvalor->document_number,
                                'code_cli'=>$rsvalor->code_cli,
                                'serie_description'=>$rsvalor->serie_description,
                                'fac_date_emision'=>$rsvalor->fac_date_emision,
                                'arrayFinalFiles'=>$arrayFinalFiles,
                                "num_comprobante"=>$rsvalor->num_comprobante,
                                'code' =>$val->code,
                                'brand'=>$val->brand,
                                'unit_rec'=>$val->unit_rec,
                                'unit_ven'=>$val->unit_ven,
                                'origin_code'=>$val->origin_code,
                                'line_code'=>$val->line_code,
                                'unit_proc'=>$val->unit_proc,
                                'descripcion_prod'=>$val->descripcion_prod,
                                "estado_producto"=>$val->estado_producto,
                                "motivo"=>$val->motivo,
                                "detalle_producto"=>$val->detalle_producto,
                                'subjet'=>$val->subjet,
                                'detail'=>$val->detail,
                                'factory_code'=>$val->factory_code,
                                "cause_failure"=>$val->cause_failure,
                                "recommendations"=>$val->recommendations,
                                'item_price'=>$val->item_price,
                                "id"=>$val->id,
                                'detail_init'=>$val->detail_init,
                                'line_description'=>$val->line_description,
                                'condicion_descripcion'=>$val->condicion_descripcion,
                                'detail_recover_discard'=>$val->detail_recover_discard,
                                'conclusion_detail'=>$val->conclusion_detail,
                                'evidence'=>$val->evidence
                            );
                            $provpdf = PDF::loadview('pdf.informeproductoproveedor', ['rsDataInforme'=>$rsDataProveedor]);
                            $provpdf->setPaper('letter', 'portrait');
                            $provpdf->render();
                            file_put_contents($fileNameProveedor, $provpdf->output());
                            $arrayPdfFilesProveedor[]=($fileNameProveedor);
                            //$ResponseFile = file_exists($fileNameProveedor) ? unlink($fileNameProveedor):'pdf no eliminado';
                        }
                    }
                    $dateNow =$rsvalor->date_reg;
                    $destinatarioproveedor = [
                        'sreynaga@mym.com.pe',
                        'elonazco@mym.com.pe'
                    ];
                    $rsDataSolicitud =  array( 
                        'Type'=>'byid',
                        "NumRquest"=>$rsvalor->num_request,
                        'contact_name'=>$rsvalor->contact_name,
                        "NameClient"=>$rsvalor->name_social_reason,
                        "contact_phone"=>$rsvalor->contact_phone,
                        "Domicilio"=>'',
                        "Domicilio_contact"=>$rsvalor->address_contact,
                        "Document_contact"=>$rsvalor->document_contact,
                        "Document"=>$rsvalor->document_number,
                        "Email"=>'',
                        "Detalle"=>$rsvalor->detail_request,
                        "TypeRequest"=>$rsvalor->description,
                        'Day'=> $this->functionsReposirory->NameDate($dateNow,'d'),
                        'Month'=> $this->functionsReposirory->NameDate($dateNow,'n_m'),
                        'Year'=> $this->functionsReposirory->NameDate($dateNow,'Y'),
                        "estado_des"=>$rsvalor->estado_des,
                        "fac_date_emision"=>$rsvalor->fac_date_emision,
                        "fecha_reg"=>$rsvalor->fecha_reg, 
                        "dataFiles"=>$rsFiles,
                        'arrayFinalFiles'=>$arrayFinalFiles,
                        "num_comprobante"=>$rsvalor->num_comprobante,
                        "serie_description"=>$rsvalor->serie_description,
                        'FilePDF'=>'files/reclamos/'.$rsvalor->filenamepdf,
                        'name'=>'esandoval@mym.com.pe',
                        "categoria"=> $rsvalor->categoria,
                        "code_cli"=> $rsvalor->code_cli,
                        "motivo"=> $rsvalor->motivo,
                        "detail_request"=> $rsvalor->detail_request,
                        "maildestinatarioproveedor"=> $destinatarioproveedor ,
                        'dataProveedorDetail'=>$dataProveedorDetail,
                        'DayNow'=>date('d/m/Y'),  
                        'arrayPdfFilesProveedor'=>$arrayPdfFilesProveedor,
                        'id'=>$rsvalor->id
                    );
                    $proveedor_mail = Mail::send('mails.proveedorprocedentereenvio', ["rsSend"=>$rsDataSolicitud], function($message) use ($rsDataSolicitud) {
                        $message->to($rsDataSolicitud['maildestinatarioproveedor'])
                        ->subject($rsDataSolicitud["NumRquest"] . ' Proveedor - Reenvío de Notificación de los productos procedentes');
                        $attachments = $rsDataSolicitud['arrayPdfFilesProveedor'];
                        foreach ($attachments as $filePath) {
                            $message->attach($filePath);
                        }
                    },true);
                }
            }
            return response()->json(["estado"=>true ], 200);
        }else{
            return response()->json(["estado"=>false], 200);
        }
    }
}




