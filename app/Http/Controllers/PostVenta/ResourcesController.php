<?php

namespace App\Http\Controllers\PostVenta;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
class ResourcesController extends Controller
{
    public function getResources(Request $request){
        $rq=$request->input();
        $data=DB::table('gen_resource_details as t1')
        ->Join('gen_resources as t', 't1.resource_id', '=', 't.id')
        ->select(
            't1.id',
            't1.code', 
            't1.abrv',
            't1.name',
            't1.description',
            't1.parent_resource_detail_id'
        )
        ->where('t.name',"=", $rq["name"])
        ->where('t1.reg_status',"=", 1)
        ->orderBy('t1.order', 'asc')
        ->get();
        $arr = array();
        foreach($data as $val){
            $descriptionTrans  =  mb_strtolower($val->description);
            $subsFirst = substr(mb_strtoupper($descriptionTrans), 0, 1);
            $subsRest= substr($descriptionTrans , 1);
            $arr[]=array(
                'label'=>$val->description,
                'description'=>$val->description,
                'id'=>$val->id,
                'year'=>$val->id,
                'descriptionTrans'=>$subsFirst.$subsRest,
                'parent_resource_detail_id'=>$val->parent_resource_detail_id,
                'code'=>$val->code
            );
        }  
        return response()->json($arr, 200);
    }
    public function getResourcesFacBol(Request $request){
        $rq=$request->input();
        $data=DB::table('gen_resource_details as t1')
        ->Join('gen_resources as t', 't1.resource_id', '=', 't.id')
        ->select(
            't1.id',
            't1.code', 
            't1.abrv',
            't1.name',
            't1.description',
            't1.parent_resource_detail_id'
        )
        ->where('t.name',"=", $rq["name"])
        ->where('t1.reg_status',"=", 1)
        //->whereIn('t1.id', [1500, 1501, 1589])//desarrollo
        ->whereIn('t1.code', ['01', '03', '10'])//produccion
        ->where('t1.resource_id', 38)//produccion
        ->orderBy('t1.order', 'asc')
        ->get();
        $arr = array();
        foreach($data as $val){
            $arr[]=array(
                'label'=>$val->description,
                'description'=>$val->description,
                'id'=>$val->id,
                'year'=>$val->id,
                'parent_resource_detail_id'=>$val->parent_resource_detail_id
            );
        }
        return response()->json($arr, 200);
    }
    public function getCategoiraByTS(Request $request){
        $rq=$request->input();
        $data=DB::table('gen_resource_details as t1')
        ->Join('gen_resources as t', 't1.resource_id', '=', 't.id')
        ->select(
            't1.id',
            't1.code', 
            't1.abrv',
            't1.name',
            't1.description',
            't1.parent_resource_detail_id'
        )
        ->where('t1.reg_status',"=", 1)
        ->where('t.name',"=", $rq["name"])
        ->where('t1.parent_resource_detail_id',"=", $rq["parent_resource_detail_id"])
        ->orderBy('t1.order', 'asc')
        ->get();
        $arr = array();
        foreach($data as $val){
            $code = $val->code;
            $txtLeyenda = '';
            $TypeLeyenda = '';
            if($code==='01' ){
                $TypeLeyenda='1';
                $txtLeyenda = 'Producto no solicitado';
            }else if($code==='02' ){
                $TypeLeyenda='1';
                $txtLeyenda = 'Falla de producto';
            }else if($code==='03' ){
                $TypeLeyenda='1';
                $txtLeyenda = 'Pedido incompleto';
            }else if($code==='04' ){
                $TypeLeyenda='1';
                $txtLeyenda = 'Pedido mal facturado';
            }else if($code==='05' ){
                $TypeLeyenda='1';
                $txtLeyenda = 'presentacion';
            }
            $arr[]=array(
                'label'=>$val->description,
                'description'=>$val->description,
                'id'=>$val->id,
                'year'=>$val->id,
                'code'=>$val->code,
                'leyenda'=>$txtLeyenda,
                'TypeLeyenda'=>$TypeLeyenda, 
                'parent_resource_detail_id'=>$val->parent_resource_detail_id
            );
        }
        return response()->json($arr, 200);
    }
    public function createMarca(Request $request){
        $newoptmarca = $request->newoptmarca; 
        $idmarcanew = $request->idmarcanew; 
        $newoptmodelo = $request->newoptmodelo; 
        $idmodelonew = $request->idmodelonew;
        if($newoptmarca==true){
            $rules=[ 
                "description"=>  ['required'],
                "description_model"=>  ['required'],
                "veh_year"=>  ['required','numeric','min:1950','max:2026'],
            ];
            $validator = Validator::make($request->all(),$rules);
            if ($validator->fails()){
                return response()->json( ["estado"=>false, "data"=>$validator->errors(),"message"=>'warning'], 200);
            }else{
                $validMarca =DB::table('gen_resource_details')
                ->select('*') 
                ->where('name',strtoupper($request->description))
                ->get(); 
                if(count($validMarca)==0){ 
                    $request_id_count =DB::table('gen_resource_details')
                    ->select(DB::raw('code as id_sum')) 
                    ->orderBy('id', 'desc')
                    ->first();        
                    $id = intval( $request_id_count->id_sum) + 1;
                    $data =array();                        
                    $dataMod =array(); 
                    $dataVeh =array();          
                    $data['resource_id'] = 25;
                    $data['code'] = $id;
                    $data['abrv'] = substr( str_replace(" ","",strtoupper($request->description)), 0,3);
                    $data['name'] = strtoupper($request->description);
                    $data['description' ] =strtoupper($request->description);
                    $data['order'] = $id;
                    $data['reg_status' ] = 1;
                    $data['created_at' ] = date('Y-m-d H:i:s');
                    $result=DB::table('gen_resource_details')->insertGetId($data);
                    $dataMod['line_id'] = $result;
                    $dataMod['model_code'] = strtoupper($request->description_model);
                    $dataMod['model_description'] = strtoupper($request->description_model);
                    $dataMod['created_at'] = date('Y-m-d H:i:s');
                    $dataMod['reg_status'] = 1;
                    $resultModel=DB::table('veh_models')->insertGetId($dataMod);
                    $dataVeh['model_id'] = $resultModel;
                    $dataVeh['veh_year'] = $request->veh_year;
                    $dataVeh['veh_traction'] = '';
                    $dataVeh['veh_engine'] = '';
                    $dataVeh['veh_gearbox'] = '';
                    $dataVeh['veh_front_axle'] = '';
                    $dataVeh['veh_rear_axle'] = '';
                    $dataVeh['veh_category_code'] = 99;
                    $dataVeh['veh_order'] = 1;
                    $dataVeh['created_at'] = date('Y-m-d H:i:s');
                    $dataVeh['reg_status'] = 1;
                    $resultVeh=DB::table('veh_vehicles')->insertGetId($dataVeh);
                    if($result>1){
                        return response()->json(["estado"=>true, "data"=>$result,"message"=>'success'], 200);
                    }else{
                        return response()->json(["estado"=>false, "data"=>$result,"message"=>'error'], 200);
                    }
                }else{
                    return response()->json(["estado"=>false, "data"=>'Esta descripción de marca ya existe.',"message"=>'exist'], 200);
                }
            }
        }else{
            if($newoptmodelo===true){
                $rules=[ 
                    "description_model"=>  ['required'],
                    "veh_year"=>  ['required','numeric','min:1950','max:2026'],
                ];
                $validator = Validator::make($request->all(),$rules);
                if ($validator->fails()){
                    return response()->json( ["estado"=>false, "data"=>$validator->errors(),"message"=>'warning'], 200);
                }else{
                    $dataMod['line_id'] = $idmarcanew;
                    $dataMod['model_code'] = strtoupper($request->description_model);
                    $dataMod['model_description'] = strtoupper($request->description_model);
                    $dataMod['created_at'] = date('Y-m-d H:i:s');
                    $dataMod['reg_status'] = 1;
                    $resultModel=DB::table('veh_models')->insertGetId($dataMod);
                    $dataVeh['model_id'] = $resultModel;
                    $dataVeh['veh_year'] = $request->veh_year;
                    $dataVeh['veh_traction'] = '';
                    $dataVeh['veh_engine'] = '';
                    $dataVeh['veh_gearbox'] = '';
                    $dataVeh['veh_front_axle'] = '';
                    $dataVeh['veh_rear_axle'] = '';
                    $dataVeh['veh_category_code'] = 99;
                    $dataVeh['veh_order'] = 1;
                    $dataVeh['created_at'] = date('Y-m-d H:i:s');
                    $dataVeh['reg_status'] = 1;
                    $resultVeh=DB::table('veh_vehicles')->insertGetId($dataVeh);
                    if($resultModel>1){
                        return response()->json(["estado"=>true, "data"=>$resultModel,"message"=>'success'], 200);
                    }else{
                        return response()->json(["estado"=>false, "data"=>$resultModel,"message"=>'error'], 200);
                    }
                }
            }else{
                $rules=[ 
                    "veh_year"=>  ['required','numeric', 'min:1950','max:2026'],
                ];
                $validator = Validator::make($request->all(),$rules);
                if ($validator->fails()){
                    return response()->json( ["estado"=>false, "data"=>$validator->errors(),"message"=>'warning'], 200);
                }else{
                    $validModelYeay =DB::table('veh_vehicles')
                    ->select('model_id') 
                    ->where('model_id',$idmodelonew)
                    ->where('veh_year',$request->veh_year)
                    ->get();
                    if(count($validModelYeay)>0){ 
                        return response()->json( ["estado"=>false, "data"=>'El año ingresado para el modelo seleccionado ya existe.',"message"=>'exist'], 200);
                    }else{
                        $dataVeh['model_id'] = $idmodelonew;
                        $dataVeh['veh_year'] = $request->veh_year;
                        $dataVeh['veh_traction'] = '';
                        $dataVeh['veh_engine'] = '';
                        $dataVeh['veh_gearbox'] = '';
                        $dataVeh['veh_front_axle'] = '';
                        $dataVeh['veh_rear_axle'] = '';
                        $dataVeh['veh_category_code'] = 99;
                        $dataVeh['veh_order'] = 1;
                        $dataVeh['created_at'] = date('Y-m-d H:i:s');
                        $dataVeh['reg_status'] = 1;
                        $resultVeh=DB::table('veh_vehicles')->insertGetId($dataVeh);
                        if($idmodelonew>1){
                            return response()->json(["estado"=>true, "data"=>$idmodelonew,"message"=>'success'], 200);
                        }else{
                            return response()->json(["estado"=>false, "data"=>$idmodelonew,"message"=>'error'], 200);
                        }
                    }
                }      
            }
        }
    }

    public function getEstadoQuejasAdmin(Request $request){
        $rq=$request->input();
        $data=DB::table('gen_resource_details as t1')
        ->Join('gen_resources as t2', 't1.resource_id', '=', 't2.id')
        ->select(
            't1.id',
            't1.code', 
            't1.abrv',
            't1.name',
            't1.description'
        )
        ->where('t2.name',"=", $rq["name"])
        ->where('t1.reg_status',"=", 1)
        //->whereIn('t1.id', [1522,1523,1524])desarrollo
        //->whereIn('t1.id', [1889,1890,1891])//produccion
        ->whereIn('t1.code', ['04','05','06'])//produccion
        ->where('t1.resource_id', 42)//produccion
        ->orderBy('t1.order', 'asc')
        ->get();
        return response()->json($data, 200);
    }
    public function get_lines(){
        $rs = DB::table('gen_resource_details AS line')
            ->where('line.resource_id', '=', '25')
            ->where('line.reg_status', '=', '1')
            ->orderBy('line.order')
            ->select(['line.id', 'line.code', 'line.abrv', 'line.name AS line', 'line.description', 'line.order'])
            ->get();
        //$rs = (is_array($rs) && sizeof($rs) > 0) ? $rs : false;
        //return response()->json($rs, 200);
        return response()->json($rs, 200);
    }
    public function get_models_by_line_code(Request $request)
    {
        $line_code = $request->line;
        $rs = DB::table('veh_models AS mod')
            ->join('gen_resource_details AS line', 'mod.line_id', '=', 'line.id') //, 'and', 'line.resource_id', '=', '25'
            ->where('line.code', '=', $line_code)
            ->where('mod.reg_status', '=', '1')
            ->orderBy('mod.model_code')
            ->select(['mod.id', 'mod.line_id', 'mod.model_code', 'mod.model_description'])
            ->get();
        return response()->json($rs, 200);
    }
    public function getResourcesByParentDetail(Request $request){
        $rq=$request->input();
        $data=DB::table('gen_resource_details as t1')
        ->select(
            't1.id',
            't1.code', 
            't1.abrv',
            't1.name',
            't1.description',
            't1.parent_resource_detail_id'
        )
        ->where('t1.reg_status',"=", 1)
        ->where('t1.id',"=", $rq["id_resources"])
        ->orderBy('t1.order', 'asc')
        ->get();
        $arr = array();
        foreach($data as $val){
            $arr[]=array(
                'label'=>$val->description,
                'description'=>$val->description,
                'id'=>$val->id,
                'year'=>$val->id,
                'parent_resource_detail_id'=>$val->parent_resource_detail_id
            );
        }
        return response()->json($arr, 200);
    }

}
