<?php

namespace App\Http\Controllers\PostVenta;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\PostVenta\TrackingStateController;
class TrackingRequestController extends Controller
{
    public function createRechazado(Request $request){
        $rules=[
            "id_request"=>  'required',
            "id_motivo"=> ['required','numeric',function ($attribute, $value, $fail) {if ($value == 0) {$fail('Seleccione motivo');}}],
            "detail"=>  'required',
            "subjet"=>  'required',
            "id_user"=>  'required',
            "costo_eva"=>  ['required','numeric'],
            "type_money_cli"=>  'required',
            //"id_state"=>  'required'
        ];  
        $validator = Validator::make($request->all(),$rules);
        if ($validator->fails()){
            return response()->json( ["estado"=>false, "data"=>$validator->errors(),"message"=>'warning'], 200);
        }else {
            $data = array();  
            $data['id_product_detail_request' ] = $request->id_product_detail_request;       
            $data['id_request' ] = $request->id_request;       
            $data['id_motivo' ] = $request->id_motivo;       
            $data['detail' ] = $request->detail;    
            $data['subjet' ] = $request->subjet;       
            $data['id_user' ] = $request->id_user;    
            $data['costo_eva' ] = $request->costo_eva;    
            $data['type_money_cli' ] = $request->type_money_cli;    
            $result=DB::table('poventa_tracking_request')->insertGetId($data);
            if($result>0){
                $datos = array();
                //$datos["id_state"]  = $request->id_state;
                $datos["date_upd"]  =  date('Y-m-d H:i:s');
                $datos["hour_upd"]  =  date('H:i:s');
                $datos["id_user_upd"]  =  $request->id_user;
                $rs = DB::table('poventa_request')->where('id','=', $request->id_request)->update($datos);
                $trakState =  new TrackingStateController();
                //$rsInsSta  =$trakState->create($request->id_request,$request->id_user,$request->id_state);
                return response()->json(["estado"=>true, "data"=>$result,"message"=>'success'], 200);
            }else{
                return response()->json(["estado"=>false, "data"=>$result,"message"=>'error'], 200);
            }
        }
    } 
}
