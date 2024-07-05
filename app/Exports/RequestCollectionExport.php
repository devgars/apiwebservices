<?php

namespace App\Exports;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Support\Facades\DB;
class RequestCollectionExport implements FromCollection, ShouldAutoSize
{
    public $request;
    public function __construct($request)
    {
        $this->request =  $request;
    }
    public function collection()
    {
        $data=DB::table('poventa_request as t1')
        ->leftJoin('customers as t2', 't1.id_client', '=', 't2.id')
        ->leftJoin('gen_resource_details as t3', 't1.type_document', '=', 't3.id')
        ->leftJoin('gen_resource_details as t4', 't1.type_request', '=', 't4.id')
        ->leftJoin('gen_resource_details as t5', 't1.category', '=', 't5.id')
        ->leftJoin('gen_resource_details as t6', 't1.id_state', '=', 't6.id')
        ->select(
            "t1.id",
            "t1.num_request",
            DB::raw("to_char(t1.date_reg,'DD/MM/YYYY') as date_reg"),
            "t2.name_social_reason",
            "t1.fac_nom_vendedor",
            "t3.description as tipo_doc",
            "t4.description as tipo_sol",
            "t5.description as categoria",
            "t6.description as estado",
        )
        ->where('t1.state',1 )
        ->where('t1.type_request','like', '%'.$this->request["type_request"].'%')
        ->where('t1.category','like', '%'.$this->request["category"].'%')
        ->where('t1.id_client','like', '%'.$this->request["id_client"].'%')
        ->where('t1.id_state','like', '%'.$this->request["id_state"].'%')
        ->where('t1.type_document','like', '%'.$this->request["type_document"].'%')
        ->where('t1.num_request','like', '%'.$this->request["num_request"].'%')
        ->whereBetween('t1.date_reg', [$this->request["date_reg_ini"].' 00:00:00',$this->request["date_reg_fin"].' 23:59:59'])
        ->orderBy('id', 'asc')
        ->get();
        return $data;
    }
}
