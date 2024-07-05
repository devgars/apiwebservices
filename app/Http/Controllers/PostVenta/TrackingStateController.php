<?php

namespace App\Http\Controllers\PostVenta;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
class TrackingStateController extends Controller
{
    public function create($id_request,$id_user,$id_state){
    //public function create(){
        $data = array();
        $data['id_request' ] = $id_request;       
        $data['id_user' ] = $id_user;       
        $data['id_state' ] = $id_state;       
        $data['date_reg' ] = date('Y-m-d H:i:s');      
        $result=DB::table('poventa_tracking_state')->insertGetId($data);
        return $result;
    }
}
