<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
class RequestDasboardExport implements FromView, ShouldAutoSize
{
    public $request;
    public function __construct($request)
    {
        $this->request =  $request;
    }
    public function view(): View
    {
        
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
        ->whereBetween('t1.date_reg', [$this->request["date_reg_ini"].' 00:00:00',$this->request["date_reg_fin"].' 23:59:59'])
        ->groupBy('t2.description', 't3.description', 't1.type_request', 't1.category')
        ->get();

        $dataPie=DB::table('poventa_request as t1')
        ->join('gen_resource_details as t2', 't1.type_request', 't2.id')
        ->select(
            't2.description as name',
            DB::raw("count(t2.id) as total"),
        )
        ->whereBetween('t1.date_reg', [$this->request["date_reg_ini"].' 00:00:00',$this->request["date_reg_fin"].' 23:59:59'])
        ->groupBy('t2.description')
        ->get();

        $request = array();
        foreach($data as $val){
            $request[]=array( 
                "type_request" =>$val->type_request,
                "category" =>$val->category,
                "tipo_solicitud" =>$val->tipo_solicitud,
                "categoria" =>$val->categoria,
                "total_tipo_sol" =>$val->total_tipo_sol,
            );
        };
        $requestPie = array();
        foreach($dataPie as $val){
            $requestPie[]=array( 
                "name" =>$val->name,
                "total" =>$val->total
            );
        };
        return view('excel.dashboard',[
            'request' =>$request,
            'requestPie' =>$requestPie
        ]);
    }
}
