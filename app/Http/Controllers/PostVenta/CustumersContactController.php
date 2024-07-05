<?php

namespace App\Http\Controllers\PostVenta;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\PostVenta\CustomersContact;
use App\Models\User;
class CustumersContactController extends Controller
{
    public function index(Request $request){
        /*
        $user =  CustomersContact::all();
        return response()->json($user, 200); */
        $rq=$request->input();
        $data=DB::table('customer_contacts as t1')
        ->select(
            't1.id',
            't1.customer_id', 
            't1.contact_name'
        )
        ->where('t1.customer_id',"=", $rq["customer_id"])->get();
        $arr = array();
        foreach($data as $val){
            $arr[]=array(
                'label'=>$val->id.' - '.$val->contact_name,
                'description'=>$val->contact_name,
                'id'=>$val->id
            );
        }
        return response()->json($arr, 200);
    }
    public function create(Request $request){
        $contact=$request->input();
        //$data =  CustomersContact::all();
        $rules=[ 
            "customer_id"=>  'required|numeric',
            "work_position_id"=>  'required|numeric',
            "name"=>  'required',
            "phone"=>  'required|min:9',
            "email"=>  'required|email',
            "documento"=>  'required|numeric',
            "direccion"=>  'required',
        ]; 
        $validator = Validator::make($request->all(),$rules);
        if ($validator->fails()){
            return response()->json( ["estado"=>false, "data"=>$validator->errors()], 200);
        }else{
            $ContactEmail =DB::table('customer_contacts')
            ->select("contact_email")
            ->where('contact_email', $contact['email'])
            ->get();
            if(count($ContactEmail)>0){
                return response()->json(["estado"=>false, "data"=>array("email"=>['El correo electronico ya existe']) ], 200);
            }else{
                $data=array();
                $data['customer_id'] = $contact['customer_id'];
                $data['work_position_id' ] =  $contact['work_position_id'];
                $data['contact_name' ] =  $contact['name'];
                $data['contact_phone' ] =  $contact['phone'];
                $data['contact_email' ] =  $contact['email'];
                $data['created_at' ] = date('Y-m-d h:i:s');
                $data['reg_status' ] =  1;
                $data['identification_number' ] =  $contact['documento'];
                $data['contact_address' ] = $contact['direccion'];
                $result=DB::table('customer_contacts')->insertGetId($data);
                return response()->json(["estado"=>true, "data"=>$result], 200);
            }
        }
    }
}
