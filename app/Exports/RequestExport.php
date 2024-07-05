<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
class RequestExport implements FromView, ShouldAutoSize
{   
    public $request;
    public function __construct($request)
    {
        $this->request =  $request;
    }
    public function view(): View
    {
        /******************** */
        $dataUser =DB::table('poventa_request as t1')
        ->join('users as t2','t1.id_responsable','t2.id')
        ->select(
            "t2.id",
            "t2.name"
        )
        ->distinct()
        ->get();
        $arrayIdUser =array();
        foreach ($dataUser as $user){
            $arrayIdUser[] = ($user->id);
        }
        $arrayidUserFirs = $this->request["id_user"];
        if($arrayidUserFirs[0]==='all'){
            $arrayidUserFirs = $arrayIdUser;
        }
        /******************* */
        $codeiProd = $this->request["codigo_prod"];
        $filterCode=$this->request["id_user"];
        $columnCode='t1.id_responsable';        
        if(count($codeiProd)>0){
            $filterCode=$codeiProd;
            $columnCode='t9.code';
        }
        if($filterCode[0]==='all'){
            $filterCode = $arrayIdUser;
        }
        $data=DB::table('poventa_request as t1')
        ->leftJoin('customers as t2', 't1.id_client', '=', 't2.id')
        ->leftJoin('gen_resource_details as t3', 't1.type_document', '=', 't3.id')
        ->leftJoin('gen_resource_details as t4', 't1.type_request', '=', 't4.id')
        ->leftJoin('gen_resource_details as t5', 't1.category', '=', 't5.id')
        ->leftJoin('gen_resource_details as t6', 't1.id_state', '=', 't6.id')
        ->leftJoin('users as t7', 't7.id', '=', 't1.id_responsable')
        ->leftJoin('gen_resource_details as t8', 't8.id', '=', 't1.accion_correctiva_cli')
        ->leftJoin('poventa_produc_detail_request as t9', 't1.id', '=', 't9.id_request')
        ->leftJoin('gen_resource_details as t10', 't1.id_motivo', '=', 't10.id')
        ->leftJoin('gen_resource_details as t11', 't9.status_product', '=', 't11.id')
        ->leftJoin('gen_resource_details as t12', 't9.id_motivo', '=', 't12.id')
        ->leftJoin('gen_resource_details as t13', 't9.recover_discard', '=', 't13.id')
        ->leftJoin('customer_contacts as t14', 't14.id', '=', 't1.id_contact')
        ->select(
            "t1.id",
            "t4.description as tipo_sol",
            "t1.num_request",
            "t2.code as codigo_cliente",
            "t7.name as nombre_responsable", 
            't1.detail_request as detalle_solicitud',
            DB::raw("to_char(t1.date_reg,'DD/MM/YYYY') as date_reg"),
            DB::raw("to_char(t1.date_reg,'HH12:MI AM') as hour_reg"),
            "t5.description as categoria",
            "t6.description as estado",
            't1.num_nc_cli',
            't8.description as accion_correctiva_cli',
            "t2.name_social_reason as nombre_cliente", 
            't1.num_fact',
            "t3.description as tipo_doc",
            't1.fac_guia_remision',
            't1.fac_suc',
            't1.fac_alm',
            't1.fac_nom_vendedor',
            DB::raw("to_char(t1.fac_date_emision,'DD/MM/YYYY') as fac_date_emision"),
            't9.factory_code',
            't9.code', 
            't9.brand', 
            't9.description', 
            't9.unit_ven', 
            't9.unit_rec', 
            't9.origin_code', 
            't9.line_code',
            't9.prov_solution_proveedor as tipo_solution',
            't9.prov_num_nc',
            't9.prov_date_nc',
            't9.prov_importe_nc',
            't9.prov_type_money_nc',
            't9.prov_num_fac',
            't9.prov_date_fac',
            't9.prov_tipo_desc',
            't9.prov_monto_desc',
            't9.costo_proveedor',
            't10.description as motivo_solicitud',
            't1.fac_dir_cli as direccion_cliente',
            DB::raw("'-' as comentario_seguimiento"),
            't11.description as estado_prod',
            't12.description as motivo_prod',
            't9.unit_proc as unidad_procedente',
            't9.subjet as asunto_prod',
            't9.detail as detalle_prod',
            't9.costo_eva as costo_evaluacion',
            't9.type_money_cli as tipo_moneda_prod',
            't9.oc_purchase_num as orden_compra_prod',
            't9.costo_proveedor as costo_prod',
            DB::raw("t9.name_proveedor as nombre_prov"),
            't9.detail_init',
            't9.conclusion_detail',
            't9.evidence',
            't9.cause_failure',
            't9.recommendations',
            't13.description as recover_discard_desc',
            't1.serie_name',
            't14.contact_name',
        )
        ->where('t1.state',1 )
        ->where('t1.type_request','like', '%'.$this->request["type_request"].'%')
        ->where('t1.category','like', '%'.$this->request["category"].'%')
        ->where('t1.id_client','like', '%'.$this->request["id_client"].'%')
        ->where('t1.id_state','like', '%'.$this->request["id_state"].'%')
        ->where('t1.type_document','like', '%'.$this->request["type_document"].'%') 
        ->where('t1.num_request','like', '%'.$this->request["num_request"].'%')
        ->whereBetween('t1.date_reg', [$this->request["date_reg_ini"].' 00:00:00',$this->request["date_reg_fin"].' 23:59:59'])
        ->whereIn('t1.id_responsable', $arrayidUserFirs)
        //->whereIn($columnCode,'like', $filterCode)
        ->whereIn($columnCode, $filterCode)
        ->where('t1.fac_nom_vendedor','like', '%'.$this->request["fac_nom_vendedor"].'%')
        ->orderBy('id', 'asc')
        ->get();
        $detailDatos=DB::table('poventa_produc_detail_request as t1')
        ->select(
            "t1.id",
            "t1.id_request",
            "t1.unit_ven",
            "t1.unit_rec",
            't1.costo_proveedor'
        )
        ->get();
        $request = array();
        foreach($data as $val){
            $totalventa = 0;
            $totalsum = 0;
            foreach($detailDatos as $item){
                if($item->id_request == $val->id){
                    $totalventa = $item->unit_ven * $item->costo_proveedor;
                    $totalsum += $totalventa;
                }
            }
            $rs_tipo_solution='';
            if($val->tipo_solution==='NC'){
                $rs_tipo_solution= 'Nota De Crédito';
            }else if ($val->tipo_solution==='DES'){
                $rs_tipo_solution= 'Devolución De Productos En Siguiente Envio';
            }else if ($val->tipo_solution==='FAC'){
                $rs_tipo_solution= 'Descuento En El Siguiente Pedido';
            }
            $rec_x_costo = $val->unit_ven * $val->costo_proveedor;
            $request[]=array(
                "id" =>$val->id,
                "tipo_sol"=>$val->tipo_sol,
                "num_request" =>$val->num_request,
                "codigo_cliente" =>$val->codigo_cliente,
                "nombre_responsable" =>$val->nombre_responsable,
                "detalle_solicitud" =>$val->detalle_solicitud,
                "date_reg" =>$val->date_reg,
                "hour_reg" =>$val->hour_reg,
                "categoria" =>$val->categoria,
                "estado" =>$val->estado,
                "num_nc_cli" =>$val->num_nc_cli,
                "accion_correctiva_cli" =>$val->accion_correctiva_cli,
                "nombre_cliente" =>$val->nombre_cliente,
                "num_fact" =>$val->num_fact,
                "tipo_doc" =>$val->tipo_doc,
                "fac_guia_remision" =>$val->fac_guia_remision,
                "fac_suc" =>$val->fac_suc,
                "fac_alm" =>$val->fac_alm,
                "fac_nom_vendedor" =>$val->fac_nom_vendedor,
                "fac_date_emision" =>$val->fac_date_emision,
                //"costoventa" => number_format($totalsum, 2, '.', ',') ,
                "costoventa" => number_format($rec_x_costo, 2, '.', ',') ,
                'factory_code'=>$val->factory_code,
                'code' =>$val->code, 
                'brand'=>$val->brand, 
                'description'=>$val->description, 
                'unit_ven'=>$val->unit_ven, 
                'unit_rec'=>$val->unit_rec, 
                'origin_code'=>$val->origin_code, 
                'line_code'=>$val->line_code,
                'tipo_solution'=>strtoupper($rs_tipo_solution),
                'prov_num_nc'=>$val->prov_num_nc,
                'prov_date_nc'=>$val->prov_date_nc,
                'prov_importe_nc'=>$val->prov_importe_nc,
                'prov_type_money_nc'=>$val->prov_type_money_nc,
                'prov_num_fac'=>$val->prov_num_fac,
                'prov_date_fac'=>$val->prov_date_fac,
                'prov_tipo_desc'=>$val->prov_tipo_desc,
                'prov_monto_desc'=>$val->prov_monto_desc,
                'motivo_solicitud'=>$val->motivo_solicitud,
                'direccion_cliente'=>$val->direccion_cliente,
                'comentario_seguimiento'=>$val->comentario_seguimiento,
                'estado_prod'=>$val->estado_prod,
                'motivo_prod'=>$val->motivo_prod,
                'unidad_procedente'=>$val->unidad_procedente,
                'asunto_prod'=>$val->asunto_prod,
                'detalle_prod'=>$val->detalle_prod,
                'costo_evaluacion'=>$val->costo_evaluacion,
                'tipo_moneda_prod'=>$val->tipo_moneda_prod,
                'orden_compra_prod'=>$val->orden_compra_prod,
                'costo_prod'=>$val->costo_prod,
                'nombre_prov'=>$val->nombre_prov,
                'detail_init'=>$val->detail_init,  
                'conclusion_detail'=>$val->conclusion_detail,  
                'evidence'=>$val->evidence,      
                'cause_failure'=>$val->cause_failure,      
                'recommendations'=>$val->recommendations,  
                'recover_discard_desc'=>$val->recover_discard_desc,  
                'serie_name'=>$val->serie_name,         
                'contact_name'=>$val->contact_name,               
            );
        };
        return view('excel.request',[
            'request' =>$request
        ]);
    }
   
}

