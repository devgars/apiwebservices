<?php

namespace App\Http\Controllers\PostVenta;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
class ComentTrackingController extends Controller
{
   
    public function index()
    {
        
    }
    public function create(Request $request)
    {   
        $req=$request->input();
        $rules=[ 
            "id_request"=>  ['required'],
            "id_user"=>  ['required'],
            "coment"=>  ['required'],
            "type_coment"=>  ['required']
        ];
        $validator = Validator::make($request->all(),$rules);
        if ($validator->fails()){
            return response()->json( ["estado"=>false, "data"=>$validator->errors(),"message"=>'warning'], 200);
        }else{
            $rs = array(); 
            $rs['id_request' ] = $req["id_request"];
            $rs['id_user' ] =  $req["id_user"];
            $rs['coment' ] =  $req["coment"];
            $rs['type_coment' ] =  $req["type_coment"];
            $rs['state' ] =  1;
            $rs['date_reg' ] = date("Y-m-d H:i:s");
            $rsCmt=DB::table('poventa_coment_tracking')->insertGetId($rs);
            return response()->json( ["estado"=>false, "data"=>$rsCmt,"message"=>'success'], 200);
        }
    }
    public function getByIdRequest(Request $request){
        $data=DB::table('poventa_coment_tracking as t1')
        ->LeftJoin('users as t2', 't1.id_user','=','t2.id')
        ->select(
            "t1.id_request",
            "t1.id_user",
            "t1.coment",
            "t1.type_coment",
            "t1.state",
            DB::raw("concat('Nuevo comentario del ', to_char(t1.date_reg,'DD/MM/YYYY'), ' a las ', to_char(t1.date_reg,'HH12:MI AM'), ' de ', t2.name) as comment_b"),
            DB::raw("concat(to_char(t1.date_reg,'DD/MM/YYYY'), ' a las ', to_char(t1.date_reg,'HH12:MI AM')) as comment_c")
        )->where('t1.id_request', $request->id_request)
        ->orderBy('t1.date_reg', 'asc')
        ->get();
        return response()->json($data, 200);
    }

}
