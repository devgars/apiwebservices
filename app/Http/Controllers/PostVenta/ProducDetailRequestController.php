<?php

namespace App\Http\Controllers\PostVenta;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\PostVenta\ComentTrackingController;
use Illuminate\Support\Facades\Mail;
use App\Mail\ProveedorMailable;
class ProducDetailRequestController extends Controller
{
    public function index(Request $request){
        $rute =env('ROUTES_API_BACK');
        $data=DB::table('poventa_produc_detail_request as t1')
        ->leftJoin('gen_resource_details as t2', 't2.id', '=', 't1.status_product')
        ->leftJoin('gen_resource_details as t3', 't3.id', '=', 't1.id_motivo')
        ->leftJoin('gen_resource_details as t4', 't4.id', '=', 't1.recover_discard')
        ->select( 
            "t1.id",
            "t1.code as code",
            't1.part_detail_id',
            't1.id_request',
            't1.num_fac',
            't1.sku',
            "t1.brand as brand",    
            "t1.description as description",
            "t1.unit_ven as unit_ven",
            "t1.unit_rec as unit_rec",
            't1.status_product as id_estado_producto',
            't2.description as estado_producto_descripcion',
            't1.state as estado_producto',
            't3.id as id_motivo',
            't3.description as motivo_description',
            't1.detail as detalle',
            't1.subjet as asunto',
            't1.costo_eva as costo_eva',
            't1.unit_proc as unit_proc',
            't1.type_money_cli as tipo_moneda',
            't1.item_price',
            't1.line_code',
            't1.origin_code',
            't1.factory_code',
            't1.order_id',
            't1.oc_id',
            't1.costo_proveedor',
            't1.name_proveedor',
            't1.state_proveedor',
            't1.prov_solution_proveedor',
            't1.prov_num_nc',
            't1.prov_type_money_nc',  
            DB::raw("to_char(t1.prov_date_nc,'DD/MM/YYYY') as prov_date_nc"),
            't1.prov_importe_nc',
            DB::raw("concat('/storage/mymfiles/', t1.prov_file_name_nc ) as prov_file_name_nc"),
            't1.prov_file_description_nc',
            't1.prov_num_fac',
            DB::raw("to_char(t1.prov_date_fac,'DD/MM/YYYY') as prov_date_fac"),
            't1.prov_monto_desc',
            't1.prov_tipo_desc',
            DB::raw("NULLIF(cast(t1.prov_porcentaje_desc as varchar) ,'') as prov_porcentaje_desc"),
            DB::raw("CASE
            WHEN cast( t1.oc_purchase_num as varchar) is null  THEN 0
                else t1.oc_purchase_num
            END orden_compra"),
            't1.cause_failure as causa_falla',
            't1.recommendations as recomendaciones',
            't1.recover_discard',
            't1.detail_recover_discard',
            't4.description as estado_prod_description',
            't1.detail_init',
            't3.parent_resource_detail_id',
            't1.conclusion_detail',
            't1.evidence'
        )->where('id_request', $request->id_request)
        ->orderBy('id', 'asc')
        ->get();

        $dataOc=DB::table('v_productos_oc as t1')
        ->Join('poventa_produc_detail_request as t2', 't1.part_detail_id',  't2.part_detail_id')
        ->Join('poventa_request as t3', 't2.id_request',  't3.id')
        ->select(
            't1.oc_id',
            't1.purchase_number',
            't1.cost as costo',
            't1.provider_id',
            't1.provider_name',
            't1.reg_date',
            't1.company_id',
            't1.part_detail_id'
        )->where('t1.company_id', 1)
        ->where('t3.id', $request->id_request)
        ->orderBy('t1.reg_date', 'desc')
        ->get();
        $requestDetail = array();
        foreach($data as $val){
            $listDataToken = [
                $val->id_request,
                $val->id,
                $val->num_fac
            ];
            $keyurl = base64_encode(implode('|', $listDataToken));
            $requestDetail[]=array(
                "id" =>$val->id,
                "code" =>$val->code,
                "part_detail_id" =>$val->part_detail_id,
                "sku" =>$val->sku,
                "brand" =>$val->brand,
                "description" =>$val->description,
                "unit_ven" =>$val->unit_ven,
                "unit_rec" =>$val->unit_rec,
                "id_estado_producto" =>$val->id_estado_producto,
                "estado_producto_descripcion" =>$val->estado_producto_descripcion,
                "estado_producto" =>$val->estado_producto,
                "id_motivo" =>$val->id_motivo,
                "motivo_description" =>$val->motivo_description,
                "detalle" =>$val->detalle,
                "asunto" =>$val->asunto,
                "costo_eva" =>$val->costo_eva,
                "unit_proc" =>$val->unit_proc,
                "tipo_moneda" =>$val->tipo_moneda,
                "item_price" =>$val->item_price,
                "line_code" =>$val->line_code,
                "origin_code" =>$val->origin_code,
                "factory_code" =>$val->factory_code,
                "order_id" =>$val->order_id,
                "oc_id" =>$val->oc_id,
                "costo_proveedor" =>$val->costo_proveedor,
                "name_proveedor" =>$val->name_proveedor,
                "state_proveedor" =>$val->state_proveedor,
                "prov_solution_proveedor" =>$val->prov_solution_proveedor,
                "prov_num_nc" =>$val->prov_num_nc,
                "prov_type_money_nc" =>$val->prov_type_money_nc,
                "prov_date_nc" =>$val->prov_date_nc,
                "prov_importe_nc" =>$val->prov_importe_nc,
                "prov_file_name_nc" =>$val->prov_file_name_nc,
                "prov_file_description_nc" =>$val->prov_file_description_nc,
                "prov_num_fac" =>$val->prov_num_fac,
                "prov_date_fac" =>$val->prov_date_fac,
                "prov_monto_desc" =>$val->prov_monto_desc,
                "prov_tipo_desc" =>$val->prov_tipo_desc,
                "prov_porcentaje_desc" =>$val->prov_porcentaje_desc,
                "orden_compra" =>$val->orden_compra,
                "causa_falla" =>$val->causa_falla,
                "recomendaciones" =>$val->recomendaciones,
                'id_encode'=>$keyurl,
                'recover_discard'=>$val->recover_discard,
                'detail_recover_discard'=>$val->detail_recover_discard,
                'estado_prod_description'=>$val->estado_prod_description,
                'detail_init'=>$val->detail_init,
                'parent_resource_detail_id'=>$val->parent_resource_detail_id,
                'conclusion_detail'=>$val->conclusion_detail,
                'evidence'=>$val->evidence
            );
            $oc_id_par = $val->oc_id;
            if($oc_id_par===null || $oc_id_par===0){
                $contadorr = 0;
                foreach($dataOc as $val1){
                    if($val1->part_detail_id===$val->part_detail_id){
                        $contadorr++;
                        if($contadorr<=1){
                            $datos = array();      
                            $datos['oc_id'] = $val1->oc_id;       
                            $datos['costo_proveedor']=$val1->costo;
                            $datos['id_proveedor'] = $val1->provider_id;  
                            $datos['name_proveedor'] =$val1->provider_name;
                            $datos['oc_purchase_num'] =$val1->purchase_number;
                            $rs = DB::table('poventa_produc_detail_request')->where('id','=', $val->id)->update($datos);
                            //return response()->json(["estado"=>true, "data"=>$rs, "message"=>'success'], 200);  
                        }
                    }
                }
            }
        }
        return response()->json(["data"=>$requestDetail], 200);
    }
    //
    public function updateDetailProductNP(Request $request){
        $dataResources=DB::table('gen_resource_details as t1')
        ->select(
            't1.id',
            't1.resource_id',
            't1.code',
            't1.name',
            't1.description',
        )
        ->where('t1.id',$request->id_motivo)
        ->where('t1.resource_id', '43')
        ->limit(1)
        ->get();
        $requiredmotivodirec ='';
        $requiredmotivodetail ='';
        $causefailure ='required';
        $recommendations ='required';
        if(count( $dataResources)>0){
            $descr_motivo = $dataResources[0]->description;
            if($descr_motivo==='FALLA DE FÁBRICA'){
                $requiredmotivodirec =['required','numeric',function ($attribute, $value, $fail) {if ($value === '0') {$fail('Seleccione datos para tratamiento posterior al producto');}}];
                $requiredmotivodetail =  'required';
                $causefailure ='';
                $recommendations ='';
            }
        }
        $status_product = $request->status_product;
        $status_descript_product = $request->status_descript_product;
        $rules1= [];
        $id_user_up= $request->id_user_up; 
        if($status_descript_product==='PROCEDE'){//produccion
            $rules1=[
                "unit_proc"=>  ['required','numeric', 'max:'.$request->unit_rec]
            ];
        }else{

        }
        $rules2=[ 
            "type_money_cli"=>  'required',
            "costo_eva"=>  ['required','numeric'],
            "id_user_up"=> ['required','numeric',function ($attribute, $value, $fail) {if ($value ==='undefined') {$fail('Seleccione usuario');}}],
            "detail"=>  'required', 
            //"subjet"=>  'required', 
            "id_motivo"=> ['required','numeric',function ($attribute, $value, $fail) {if ($value == 0) {$fail('Seleccione motivo');}}],
            'conclusion_detail'=>'required',
            "status_product"=>  'required', 
            "evidencia_detail"=>  'required', 
            "cause_failure"=>  $causefailure , 
            "recommendations"=>  $recommendations, 
            "discard_recoverd"=>$requiredmotivodirec, 
            "detail_dis_rec"=>$requiredmotivodetail  
        ];
        $rules = array_merge($rules1, $rules2);  
        $files = $request->file("files");
        $validator = Validator::make($request->all(),$rules);
        if ($validator->fails()){
            return response()->json( ["estado"=>false, "data"=>$validator->errors(),"message"=>'warning'], 200);
        }else{
            $isvalidFile=false;
            $isvalidSaveFile=false;
            $isvalidRequiredFile=false;
            if($request->type_method==='save'){ 
                if($request->hasFile("files")){
                    $isvalidSaveFile=true;
                }else{
                    $isvalidRequiredFile=true;
                }
            }else{
                if($files!==null){
                    $isvalidSaveFile=true;
                }else{
                    $isvalidSaveFile=false;
                }
            }
            if($isvalidRequiredFile){
                return response()->json(["estado"=>false, "data"=>array("files"=>["Seleccione uno o mas archivos"]),"message"=>'warning'], 200);
            }
            if($isvalidSaveFile){
                $isvalidFile=true;
                $counter =0;
                $daFile=array();
                $rs = array();
                $jsonDeFiles = json_decode($request->jsonDeFiles, true);
                /*foreach( $files as $val){
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
                    $daFile['id_product_detail_request' ] = $request->id;    
                    $daFile['type_file' ] = $type_file;    
                    $daFile['id_request' ] = $request->id_request;    
                    $daFile['id_user' ] =$id_user_up;    
                    $daFile['name_file' ] = $nameFile;    
                    $daFile['name_file_encrypt' ] ='';  
                    $daFile['description' ] = $val->getClientOriginalName();  
                    $daFile['name_icon_file' ] = $name_icon_file;    
                    $daFile['date_reg' ] = date('Y-m-d H:i:s');  
                    $daFile['status_type' ] = 2;
                    $rsFile=DB::table('poventa_tracking_file')->insertGetId($daFile);


                    $rs['id_request' ] = $request->id_request;
                    $rs['id_user' ] = $id_user_up;
                    $rs['coment' ] =  'Se adjuntó '.$val->getClientOriginalName().' por '.$request->name_sesion.' el '.date('d/m/Y'). ' a las '. str_replace(".","",strtoupper(now()->isoFormat('hh:mm A')));
                    $rs['type_coment' ] =  'C';
                    $rs['state' ] =  1;
                    $rs['date_reg' ] = date("Y-m-d H:i:s");
                    $rsCmt=DB::table('poventa_coment_tracking')->insertGetId($rs);
                }*/
                foreach( $jsonDeFiles as $val){
                    $type_file = $val["type_file"];
                    $name_file = $val["name_file"];
                    $description = $val["description"];
                    //$name_icon_file = $val["name_icon_file"];
                    $adicionnal = $val["coment"];
                    $counter++;
                    $date = date('y-m-d-H-i');
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
                    $daFile['id_product_detail_request' ] = $request->id;    
                    $daFile['type_file' ] = $type_file;    
                    $daFile['id_request' ] = $request->id_request;    
                    $daFile['id_user' ] =$id_user_up;    
                    $daFile['name_file' ] = $name_file;    
                    $daFile['name_file_encrypt' ] ='';  
                    $daFile['description' ] = $description;  
                    $daFile['name_icon_file' ] = $name_icon_file;    
                    $daFile['date_reg' ] = date('Y-m-d H:i:s');  
                    $daFile['status_type' ] = 2;
                    $daFile['Adicional' ] = $adicionnal;
                    $rsFile=DB::table('poventa_tracking_file')->insertGetId($daFile);
                    $rs['id_request' ] = $request->id_request;
                    $rs['id_user' ] = $id_user_up;
                    $rs['coment' ] =  'Se adjuntó '.$description.' por '.$request->name_sesion.' el '.date('d/m/Y'). ' a las '. str_replace(".","",strtoupper(now()->isoFormat('hh:mm A')));
                    $rs['type_coment' ] =  'C';
                    $rs['state' ] =  1;
                    $rs['date_reg' ] = date("Y-m-d H:i:s");
                    $rsCmt=DB::table('poventa_coment_tracking')->insertGetId($rs);
                }
            }else{
                $isvalidFile=true;
            }
            if($isvalidFile){
                $data = array();  
                $data['state' ] = 2;        
                $data['id_motivo' ] = $request->id_motivo;       
                $data['detail' ] = $request->detail;    
                $data['subjet' ] = $request->subjet;  
                $data['costo_eva' ] = $request->costo_eva;   
                $data['unit_proc' ] =  $request->unit_proc;   
                $data['type_money_cli' ] = $request->type_money_cli; 
                $data['status_product' ] = $request->status_product; 
                $data['id_user_up' ] =$id_user_up;    
                $data['cause_failure' ] = $request->cause_failure;    
                $data['recommendations' ] = $request->recommendations;    
                $data['recover_discard' ] = $request->discard_recoverd;    
                $data['detail_recover_discard' ] = $request->detail_dis_rec;    
                $data['conclusion_detail' ] = $request->conclusion_detail;  
                $data['evidence' ] = $request->evidencia_detail;
                $rs = DB::table('poventa_produc_detail_request')->where('id','=', $request->id)->update($data);
                if($request->type_method==='upd'){
                    $ifnulljsonImgdelete= $request->jsonImgdelete;
                    if($ifnulljsonImgdelete!='' ){
                        $jsonImgdelete= explode(',',$request->jsonImgdelete);
                        $arrayIdFile=[];
                        for ($i = 0; $i < count($jsonImgdelete); $i++){
                            $arrayIdFile[] = ($jsonImgdelete[$i]); 
                        }
                        $updafile = [];
                        $updafile['status' ] = 0;
                        if(count($jsonImgdelete)>0){   
                            $rs = DB::table('poventa_tracking_file')->whereIn('id', $arrayIdFile)->update($updafile);
                        }
                    }
                }
                if($rs > 0){
                    return response()->json(["estado"=>true, "data"=>$files,"message"=>'success'], 200);
                }else{
                    return response()->json(["estado"=>false, "data"=>$rs,"message"=>'error'], 200);
                }
            }else{
                return response()->json(["estado"=>false, "data"=>array("files"=>["Seleccione uno o mas archivos"]),"message"=>'warning'], 200);
            } 
        }
    }

    public function filesByDetailProduct(Request $request){
        $data=DB::table('poventa_tracking_file as t1')
        ->select(
            "t1.id",
            "t1.name_file",
            DB::raw("concat('/storage/mymfiles/', t1.name_file ) as nombre_archivo"),
            't1.description',
            't1.name_icon_file',
            't1.id_product_detail_request'
        )->where('id_request', $request->id_request)
        ->where('status', 1)
        ->where('t1.status_type', 2)
        ->orderBy('id', 'asc')
        ->get();
        return response()->json($data, 200);
    }
    public function stateCerradoProveedor(Request $request){
        $provsolutionproveedor =$request->prov_solution_proveedor;//NC, FAC, DES
        $provtextsolution = '';
        $provtextenvioemail= '';
        $rulesAll=[ 
            "prov_solution_proveedor"=>  'required',
            //"prov_orden_comp"=> ['required','numeric',function ($attribute, $value, $fail) {if ($value == 0) {$fail('Seleccione una Orden de Compra en la sección Productos');}}],
        ];
        $rules=[];
        if($provsolutionproveedor==='NC'){
            $provtextsolution='Nota de Crédito';
            
            $rules1=[];
            if(!$request->hasFile("files")){
                $rules1=[
                    "files"=> 'required'
                ];
            }
            $rules2=[ 
                "prov_num_nc"=>  'required',
                "prov_type_money_nc"=>  'required',
                "prov_date_nc"=>   ['required', 'date'],
                "prov_importe_nc"=>  'required'
            ];
            $rules = array_merge($rules1, $rules2);
        }else if($provsolutionproveedor==='FAC'){
            $provtextsolution='Factura';
            $rules=[
                "prov_num_fac"=>  'required',
                "prov_date_fac"=>  ['required', 'date'],
               
            ];
        }else if($provsolutionproveedor==='DES'){
            $provtextsolution='Descuento';
            $rules=[
                "prov_monto_desc"=>  'required',
                "prov_tipo_desc"=>  'required',
                //"prov_porcentaje_desc"=>  'required'
            ];
        }
        $sulesMerge = array_merge($rules, $rulesAll);
        $validator = Validator::make($request->all(),$sulesMerge);
        if ($validator->fails()){
            return response()->json( ["estado"=>false, "data"=>$validator->errors(),"message"=>'warning'], 200);
        }else{
            $dataProveedor =$request->json_proveedor;
            $nameFile ='';
            if($provsolutionproveedor==='NC'){
                if($request->hasFile("files")){
                    $files= $request->file('files');
                    $date = date('y-m-d-H-i-s');
                    $nameFile = Str::slug( $date .'-'.$files->getClientOriginalName()).'.'.$files->getClientOriginalExtension();
                    if(Storage::putFileAs('/public/mymfiles/',$files, $nameFile )){
                    }
                    $datos["prov_file_name_nc"]  =  $nameFile;
                    $datos["prov_file_description_nc"]  =  $files->getClientOriginalName(); 
                }
            }
            $rulesProv=[];
            if($provsolutionproveedor==='FAC'){
                $prodProv= array();
                $unit_rec = 0;
                $unit_ven = 0;
                foreach ($dataProveedor as $itm){
                    $unidad_ven = $itm["unidad"];
                    $unidad_rec = $itm["unidad_rec"];
                    if ($itm["code"]==""){
                        $rulesProv["code"] = 'El campo Código es obligatorio';
                    }
                    if ($itm["brand"]==""){
                        $rulesProv["brand"] = 'El campo Marca es obligatorio';
                    }
                    if ($itm["description"]==""){
                        $rulesProv["description"] = 'El campo Descripción es obligatorio';
                    }
                    if ($itm["linea"]==""){
                        $rulesProv["linea"] = 'El campo Linea es obligatorio';
                    }
                    if ($itm["origin"]==""){
                        $rulesProv["origin"] = 'El campo Origen es obligatorio';
                    }
                    if ($itm["unidad"]==""){
                        $rulesProv["unidad"] = 'El campo Unidad es obligatorio';
                    }
                    if (!is_numeric($itm["unidad"])){
                        $rulesProv["unidad"] = 'El campo Unidad es obligatorio ';
                    }
                    if($unidad_ven>$unidad_rec){
                        $rulesProv["unidad_rec"] = 'La unidad reclamada de proveedor debe ser menor a la cantidad procedente';
                    }
                }
                if(count($rulesProv)==0){
                    foreach ($dataProveedor as $itm){   
                        $prodProv['id_request'] = $request->id_request;
                        $prodProv['id_product_detail_request'] = $request->id;
                        $prodProv['code'] = $itm["code"];
                        $prodProv['brand'] =  $itm["brand"];
                        $prodProv['description' ] =  $itm["description"];
                        $prodProv['linea'] =  $itm["linea"];
                        $prodProv['origin'] =  $itm["origin"];
                        $prodProv['unidad'] =  $itm["unidad"];
                        $prodProv['created_at'] =  date('Y-m-d H:i:s'); 
                        $rsPRD=DB::table('poventa_proveedor_request')->insertGetId($prodProv);
                    }
                }
            }
            if(count($rulesProv)>0){
                return response()->json( ["estado"=>false, "data"=>$rulesProv,"message"=>'warning'], 200);
            }else { 
                $datos["prov_solution_proveedor"]  = $provsolutionproveedor;                
                $datos["prov_num_nc"]  =  $request->prov_num_nc;
                $datos["prov_type_money_nc"]  =  $request->prov_type_money_nc;
                $datos["prov_date_nc"]  =  $request->prov_date_nc;
                $datos["prov_importe_nc"]  =  $request->prov_importe_nc;
                $datos["prov_num_fac"]  =  $request->prov_num_fac;
                $datos["prov_date_fac"]  =  $request->prov_date_fac;
                $datos["prov_tipo_desc"]  =  $request->prov_tipo_desc;
                $datos["prov_monto_desc"]  =  $request->prov_monto_desc;
                //$datos["prov_porcentaje_desc"]  =  $request->prov_porcentaje_desc;
                $datos["state_proveedor"]  =  2;
                $datos["date_upd"]  =  date('Y-m-d H:i:s');
                $rs = DB::table('poventa_produc_detail_request')->where('id','=', $request->id)->update($datos);
                if ($rs>0){
                    $rsDetailres=DB::table('poventa_produc_detail_request as t1')
                    ->select(
                        "t1.id as id",
                        't1.state_proveedor',
                    )->where('id_request', $request->id_request)
                    ->get();
                    $counter_state_proveedor = 0;
                    $counter_detail = 0;
                    foreach($rsDetailres as $item){
                        $counter_detail++;
                        if( $item->state_proveedor==2){
                            $counter_state_proveedor++;
                        }
                    }
                    $mensjaes = 'success';
                    $rsRequest=$this->getByRequest($request->id_request);
                    if(count($rsRequest)>0){
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
                            "t1.detail as detalle_producto"
                        )
                        ->where('t1.id_request', $request->id_request)
                        //->where('t1.status_product', 1539)//desarrollo
                        //->where('t1.status_product', 1918)//produccion
                        ->where('t2.resource_id', 45)
                        ->where('t2.code', '01')
                        ->orderBy('t1.id', 'asc')
                        ->get();
                        
                        $rsFactBol=DB::table('v_fac_bol_cab')
                        ->select(
                            'numero_interno'
                        )
                        ->where('fiscal_document_id', $rsRequest[0]->fac_fiscal_document_id)
                        ->skip(0)
                        ->take(1)
                        ->get();
                        $numero_pedido = '';
                        if(count($rsFactBol)>0){
                            $numero_pedido  = $rsFactBol[0]->numero_interno;
                        }
                        $datosMail=[
                            "prov_ord_compra" => $request->prov_orden_comp,
                            "prov_cod_prod" => $request->prov_cod_prod,
                            "prov_subject" => $provtextsolution,
                            "prov_solution_proveedor" => $provsolutionproveedor,
                            'prov_num_nc'=>$request->prov_num_nc,
                            "prov_type_money_nc"  =>  $request->prov_type_money_nc,
                            "prov_date_nc" =>  $request->prov_date_nc,
                            "prov_importe_nc" =>  $request->prov_importe_nc,
                            "prov_file" =>  '/storage/mymfiles/'.$nameFile,
                            "prov_num_fac" =>$request->prov_num_fac,
                            "prov_date_fac" => $request->prov_date_fac,
                            "prov_tipo_desc" =>  $request->prov_tipo_desc,
                            "prov_monto_desc" =>  $request->prov_monto_desc,
                            ///*****///
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
                            'FilePDF'=>'files/reclamos/'.$rsRequest[0]->filenamepdf,
                            'name'=>'elonazco@mym.com.pe',
                            "categoria"=> $rsRequest[0]->categoria,
                            'productos'=>$dataDetail,
                            "code_cli"=> $rsRequest[0]->code_cli,
                            "num_comprobante"=> $rsRequest[0]->num_comprobante,
                            "num_pedido"=>$numero_pedido,
                            "fac_date_emision"=> $rsRequest[0]->fac_date_emision,
                            "motivo"=> $rsRequest[0]->motivo,
                            "detail_request"=> $rsRequest[0]->detail_request,
                        ]; 
                        $destinatario = [
                            //'elonazco@mym.com.pe',
                            'gflores@mym.com.pe',
                        ];
                        $correo =  new ProveedorMailable($datosMail); 
                        Mail::to($destinatario)->send($correo);
                        if($provsolutionproveedor==='NC'){
                            Mail::to($destinatario)->send($correo);
                        }
                        $mensjaes = 'email';
                        return response()->json(["estado"=>true, "data"=>$mensjaes, "message"=>$mensjaes], 200);
                    }else{
                        return response()->json(["estado"=>false, "data"=>$rs, "message"=>'error'], 200);
                    }
                }else{
                    return response()->json(["estado"=>false, "data"=>$rs, "message"=>'error'], 200);
                }
            }
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
            't1.fac_fiscal_document_id'
        )->where('t1.id', $id)
        ->get();
        return $data; 
    }

    public function ocByCompanyId(Request $request){
        $data=DB::table('v_productos_oc as t1')//edwin
        ->Join('poventa_produc_detail_request as t2', 't1.part_detail_id',  't2.part_detail_id')
        ->Join('poventa_request as t3', 't2.id_request',  't3.id')
        ->select(
            't1.oc_id',
            't1.purchase_number',
            't1.cost as costo',
            't1.provider_id',
            't1.provider_name',
            't1.reg_date',
            't1.company_id',
            't1.part_detail_id'
        )->where('t1.company_id', $request->company_id)
        ->where('t3.id', $request->id_request)
        ->orderBy('t1.reg_date', 'desc')
        ->get();
        return response()->json($data, 200);
    }
    public function updOc(Request $request){
        $rules=[
            "oc_id"=> ['required','numeric'],
            "id_detalle"=>  ['required','numeric']
        ];  
        $validator = Validator::make($request->all(),$rules);
        if ($validator->fails()){
            return response()->json(["estado"=>false, "data"=>$validator->errors(), "message"=>'warning'], 200);
        }else{
            $data=DB::table('v_productos_oc')
            ->select(
                'oc_id',
                'cost as costo',
                'provider_id',
                'provider_name',
                'purchase_number'
            )
            ->where('oc_id', $request->oc_id)
            ->where('part_detail_id', $request->part_detail_id)
            ->skip(0)
            ->take(1)
            ->get();
            if(count($data)>0){
                $datos = array();      
                $datos['oc_id'] = $request->oc_id;       
                $datos['costo_proveedor']=$data[0]->costo;
                $datos['id_proveedor'] = $data[0]->provider_id;  
                $datos['name_proveedor'] =$data[0]->provider_name;
                $datos['oc_purchase_num'] =$data[0]->purchase_number;
                $rs = DB::table('poventa_produc_detail_request')->where('id','=', $request->id_detalle)->update($datos);
                return response()->json(["estado"=>true, "data"=>$rs, "message"=>'success'], 200);
            }else{
                return response()->json( ["estado"=>false, "data"=>0,"message"=>'error'], 200);
            }  
        }
    }

    public function updateDetailRevisionProductNP(Request $request){
        $status_product = $request->status_product;
        $id_user_up= $request->id_user_up;
        $array_id_detail= $request->array_id_detail;
        if(count($array_id_detail)===0){
            return response()->json(["estado"=>false, "data"=>array("files"=>["Seleccione uno o mas productos"]),"message"=>'warning'], 200);
        }else{
            $rules=[ 
                "id_user_up"=> ['required','numeric',function ($attribute, $value, $fail) {if ($value ==='undefined') {$fail('Seleccione usuario');}}],
                "id_motivo"=> ['required','numeric',function ($attribute, $value, $fail) {if ($value == 0) {$fail('Seleccione motivo');}}],
                "status_product"=>  'required'
            ];  
            $validator = Validator::make($request->all(),$rules);
            if ($validator->fails()){
                return response()->json( ["estado"=>false, "data"=>$validator->errors(),"message"=>'warning'], 200);
            }else{
                $rs=0;
                $dataRevisionDetail=DB::table('poventa_produc_detail_request as t1')
                ->select(
                    't1.id',
                    't1.unit_rec',
                    't1.unit_proc'
                )
                ->whereIn('t1.id', $array_id_detail)
                ->get();
                foreach ($dataRevisionDetail as $valor){
                    $id= $valor->id;
                    $unit_rec= $valor->unit_rec;
                    $data = array(); 
                    $data['status_product' ] = $status_product;      
                    $data['id_motivo' ] = $request->id_motivo;         
                    $data['unit_proc' ] =  $unit_rec;
                    $data['id_user_up' ] =$id_user_up;  
                    $data['state' ] = 2;    
                    $rs = DB::table('poventa_produc_detail_request')->where('id','=', $id)->update($data);
                }
                if($rs > 0){
                    return response()->json(["estado"=>true, "data"=>$rs,"message"=>'success'], 200);
                }else{
                    return response()->json(["estado"=>false, "data"=>$rs,"message"=>'error'], 200);
                }
                
            }
        }
    }
}
