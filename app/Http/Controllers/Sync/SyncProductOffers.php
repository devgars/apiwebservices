<?php

namespace App\Http\Controllers\Sync;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PartOffer;
use App\Models\PartOfferDetail;

use DB;

class SyncProductOffers extends Controller
{
    private $codCia = 1;

    public function mmscrep_ofertas($oferta)
    {
        echo "<br>OFERTA $oferta->sccodofe";

        $arrayWhereOffer = array(
            ['company_id', '=', 1],
            ['offer_code', '=', trim(strtoupper($oferta->sccodofe))],
        );

        $reg_status = ($oferta->scstsofe === 'A') ? 1 : 0;

        $arrayInsertOffer = array(
            'company_id' => 1,
            'offer_code' => trim(strtoupper($oferta->sccodofe)),
            'offer_description' => trim(strtoupper($oferta->scobserv)),
            'discount_state' => $oferta->sctipofe,
            'type_offer_id' => ($oferta->sccladsc == 'OF') ? 1358 : 1359,
            'year_offer' => $oferta->scano,
            'init_date' => $oferta->scfecini,
            'end_date' => $oferta->scfecfin,
            'created_at' => date("Y-m-d H:i:s"),
            'reg_status' => $reg_status,
        );
        echo '<pre>';
        print_r($arrayWhereOffer);
        print_r($arrayInsertOffer);

        PartOffer::updateOrCreate(
            $arrayWhereOffer,
            $arrayInsertOffer
        );

        if ($reg_status == 0) {
            echo "<br>DESACTIVAR PRODUCTOS EN OFERTA";
            $oferta_codigo = trim(strtoupper($oferta->sccodofe));
            $datos_oferta = PartOffer::where('offer_code', $oferta_codigo)->first();

            $arrayWhereProductOffer = array(
                ['offer_id', '=', $datos_oferta->id],
            );

            $offer_detail = new PartOfferDetail();
            $offer_detail->update(
                $arrayWhereProductOffer,
                ['reg_status' => 0]
            );
        }

        return 1;
    }

    public function mmsdrep_productos_oferta($producto)
    {
        if ($datos_oferta = DB::table('part_offers')->where('offer_code', trim($producto->sdcodint))->first()) {
            $sku = $producto->sdcodlin . '' . $producto->sdcodori . '' . $producto->sdcodmar . '' . utf8_encode(strtoupper(trim($producto->sdcodart)));

            echo "<br> SKU: $sku";

            $arrayWhere = array(
                ['line_code', '=', $producto->sdcodlin],
                ['origin_code', '=', $producto->sdcodori],
                ['trademark_code', '=', $producto->sdcodmar],
                ['part_code', '=', trim(strtoupper($producto->sdcodart))],
            );
            if (!$datos_producto = DB::table('v_partes')->where($arrayWhere)->first()) {
                echo ("<br>SKU: $sku NO ENCONTRADO");
                return 0;
            }
            echo '<pre>';
            print_r($datos_producto);
            print_r($producto);
            $arrayWhereProductOffer = array(
                ['offer_id', '=', $datos_oferta->id],
                ['part_detail_id', '=', $datos_producto->part_detail_id]
            );

            $arrayInsertProductOffer = array(
                'offer_id' => $datos_oferta->id,
                'part_detail_id' => $datos_producto->part_detail_id,
                'list_price' => $datos_producto->list_price,
                'min_price' => $producto->sdpreact, //($producto->sdpreact > 0) ? $producto->sdpreact : $producto->sdpremin,
                'cost_price' => $producto->sdprecos,
                'discount_rate' => $producto->sdpordsc,
                'profit_rate' => $producto->sdporuti,
                'new_profit_rate' => $producto->sdporut1,
                'base_factor' => $producto->sdfactor,
                'created_at' => date("Y-m-d H:i:s"),
                'reg_status' => ($producto->sdsts === 'A') ? 1 : 0,
            );

            PartOfferDetail::updateOrCreate(
                $arrayWhereProductOffer,
                $arrayInsertProductOffer
            );
            return 1;
        } else {
            echo "<br>OFERTA ($producto->sdcodint) NO EXISTE";
            return 0;
        }
    }


    public function mmvgrep_grupos_empresas($grupo)
    {
        echo '<pre>';
        print_r($grupo);
        /*
        $arrayWhere = array(
            ['offer_id', '=', $datos_oferta->id],
            ['part_detail_id', '=', $datos_producto->part_detail_id]
        );

        $arrayInsert = array(
            'offer_id' => $datos_oferta->id,
            'part_detail_id' => $datos_producto->part_detail_id,
            'list_price' => $datos_producto->list_price,
            'min_price' => ($producto->sdpreact > 0) ? $producto->sdpreact : $producto->sdpremin,
            'cost_price' => $producto->sdprecos,
            'discount_rate' => $producto->sdpordsc,
            'profit_rate' => $producto->sdporuti,
            'new_profit_rate' => $producto->sdporut1,
            'base_factor' => $producto->sdfactor,
            'created_at' => date("Y-m-d H:i:s"),
            'reg_status' => ($producto->sdsts === 'A') ? 1 : 0,
        );

        PartOfferDetail::updateOrCreate(
            $arrayWhereProductOffer,
            $arrayInsertProductOffer
        );
        */
    }

    public function mmvdrep_clientes_grupos_empresas($fila)
    {
        echo '<pre>';
        print_r($fila);
    }

    public function mmddrep_clientes_grupos_empresas($fila)
    {
        echo '<pre>';
        print_r($fila);
    }
}
