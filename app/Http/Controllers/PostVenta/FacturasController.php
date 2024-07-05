<?php
namespace App\Http\Controllers\PostVenta;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
class FacturasController extends Controller
{
    public function index(Request $request){
        $number_correlative= str_replace(' ', '',$request->number);
        $explodeCorelative = explode('-',$number_correlative);
        if(count($explodeCorelative)>=2){
            $serie = $explodeCorelative[0];
            $number = '';
            for ($i=0; $i <count($explodeCorelative); $i++){
                if($i>0){
                    $number.= $explodeCorelative[$i].'-';
                }
            }
            $data=DB::table('v_fac_bol_det as t1')
            ->join('v_fac_bol_cab as t2', 't1.order_id', '=', 't2.order_id')
            ->select(
                't2.fiscal_document_id',
                't2.correlative_fiscal_number as number_fac',
                't1.order_detail_id',
                't1.trademark_name',
                't1.item_description',
                't1.item_qty',
                't1.item_price',
                't1.line_code',
                't1.origin_code',
                't1.order_id',
                't2.fecha_emision',
                't2.customer_address',
                't2.user_code',
                't1.part_code',
                't1.part_detail_id',
                DB::raw("concat(t2.subsidiary_code) as sucursal"),
                DB::raw("concat(t2.warehouse_code) as almacen"),  
                't1.sku',
                't1.factory_code',
                't2.serie_fiscal',
                DB::raw("REPLACE(t2.gr_number, 'GR', '') as gr_number"), 
            )->where('t2.serie_fiscal', strtoupper($serie))
            ->where('t2.correlative_fiscal_number', substr($number, 0, -1) )
            ->where('t2.customer_id', $request->client_id)
            ->skip(0)
            ->take(40)
            ->get();
            if (count($data)>0){
                return response()->json(["estado"=>true, "data"=>$data, "message"=>'success'], 200);
            }else{
                return response()->json(["estado"=>false, "data"=>$data, "message"=>'warning'], 200);
            }
        }else{
            return response()->json(["estado"=>false, "data"=>$explodeCorelative, "message"=>'warning'], 200);
        }
    }
    public function getGuiaRemision(Request $request){
        $number_correlative= str_replace(' ', '',$request->number);
        $explodeCorelative = explode('-',$number_correlative);
        $number = '';
        if(count($explodeCorelative)>=2){
            for ($i=0; $i <count($explodeCorelative); $i++){
                if($i>0){
                    $number.= $explodeCorelative[$i].'-';
                }
            }
        }
        /*
        $data=DB::table('v_fac_bol_cab')
        ->select(
            'fiscal_document_id',
            'correlative_fiscal_number as number_fac',
            'serie_fiscal'
        )
        ->where('gr_number', 'GR111-68460')  
        ->first();
        */
        $data=DB::table('fiscal_documents as fd')
        ->leftJoin('fiscal_documents as gr', 'fd.id', '=', 'gr.parent_fiscal_document_id')
        ->Join('gen_resource_details as serfis', 'fd.serie_fiscal_id', '=', 'serfis.id')
        ->select(
            'fd.id as fiscal_document_id',
            'fd.correlative_fiscal_number as number_fac',
            'serfis.name  as serie_fiscal'
        )
        ->where('gr.correlative_fiscal_number', substr($number, 0, -1))
        ->where('gr.serie_as_id', 1592)
        ->first();
        return response()->json(["estado"=>true, "data"=>$data, "message"=>'success'], 200);
    }
}
