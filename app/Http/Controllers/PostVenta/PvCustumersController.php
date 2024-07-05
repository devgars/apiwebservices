<?php

namespace App\Http\Controllers\PostVenta;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PostVenta\CustomersContact;
class PvCustumersController extends Controller
{
    public function  customerByDocument(Request $request){
        $data =DB::table('customers')
        ->select("*")
        ->where('document_number','like', '%'.$request->document_number.'%')
        ->orWhere(DB::raw('upper(name_social_reason)'),'like', '%'. strtoupper($request->document_number.'%'))
        ->orWhere('code','like', '%'.$request->document_number.'%')
        //upper(name_social_reason) like upper('%LA MERCED%')
        ->skip(0)
        ->take(10)
        ->get();
        //$user =  User::all();
        //return json_encode($data);
        $arr = array();
        foreach($data as $val){ 
            $arr[]=array(
                'label'=>$val->document_number.' - '.$val->name_social_reason,
                'id'=>$val->id,
                'year'=>$val->id
            );
        }
        //return json_encode(array("data"=>$arr),JSON_UNESCAPED_UNICODE) ;
        return response()->json($arr, 200);
    }

    public function  customerRequestByDocument(Request $request){
        $data =DB::table('poventa_request as t1')
        ->join('customers as t2','t1.id_client','t2.id')
        ->select(
            't2.document_number',
            't2.name_social_reason',
            't2.id',
        )
        ->where('t2.document_number','like', '%'.$request->document_number.'%')
        ->orWhere(DB::raw('upper(t2.name_social_reason)'),'like', '%'. strtoupper($request->document_number.'%'))
        ->orWhere('t2.code','like', '%'.$request->document_number.'%')
        //->where('t1.id_responsable', $request->id_responsable)
        ->skip(0)
        ->take(10)
        ->distinct()
        ->get();
        $arr = array();
        foreach($data as $val){ 
            $arr[]=array(
                'label'=>$val->document_number.' - '.$val->name_social_reason,
                'id'=>$val->id,
                'year'=>$val->id
            );
        }
        return response()->json($arr, 200);
    }
}
