<table>
    <thead>
    <tr>  
        @foreach ($data as $val1 )
        @if($val1["estado"]==true)
        <th style="font-weight: 700;background: #1190b8; color:white; border:1px solid #000;">{{$val1["label"]}}</th>
        @endif
        @endforeach
    </tr>
    </thead>
    <tbody>
    @foreach ($request as $val )
        <tr>
            @if($arrrayCamp["tipo_sol"]==true)
                <td style="border:1px solid #000;">{{$val["tipo_sol"]}}</td>	
            @endif
            @if($arrrayCamp["num_request"]==true)
                <td style="border:1px solid #000;">{{$val["num_request"]}}</td>	
            @endif
            @if($arrrayCamp["codigo_cliente"]==true)
                <td style="border:1px solid #000;">{{$val["codigo_cliente"]}}</td>	
            @endif
            @if($arrrayCamp["nombre_cliente"]==true)
                <td style="border:1px solid #000;">{{$val["nombre_cliente"]}}</td>
            @endif
            @if($arrrayCamp["detalle_solicitud"]==true)
                <td style="border:1px solid #000;">{{$val["detalle_solicitud"]}}</td>	
            @endif
            @if($arrrayCamp["date_reg"]==true)
                <td style="border:1px solid #000;">{{$val["date_reg"]}}</td>	
            @endif
            @if($arrrayCamp["hour_reg"]==true)
                <td style="border:1px solid #000;">{{$val["hour_reg"]}}</td>	
            @endif
            @if($arrrayCamp["categoria"]==true)
                <td style="border:1px solid #000;">{{$val["categoria"]}}</td>	
            @endif
            @if($arrrayCamp["estado"]==true)
                <td style="border:1px solid #000;">{{$val["estado"]}}</td>	
            @endif
            @if($arrrayCamp["num_nc_cli"]==true)
                <td style="border:1px solid #000;">{{$val["num_nc_cli"]}}</td>	
            @endif
            @if($arrrayCamp["accion_correctiva_cli"]==true)
                <td style="border:1px solid #000;">{{$val["accion_correctiva_cli"]}}</td>
            @endif
            @if($arrrayCamp["motivo_solicitud"]==true)
                <td style="border:1px solid #000;">{{$val["motivo_solicitud"]}}</td>	
            @endif
            @if($arrrayCamp["nombre_responsable"]==true)
                <td style="border:1px solid #000;">{{$val["nombre_responsable"]}}</td>	
            @endif
            @if($arrrayCamp["direccion_cliente"]==true)
                <td style="border:1px solid #000;">{{$val["direccion_cliente"]}}</td>	
            @endif
            @if($arrrayCamp["comentario_seguimiento"]==true)
                <td style="border:1px solid #000;">{{$val["comentario_seguimiento"]}}</td>	
            @endif

            @if($arrrayCamp["code"]==true)
                <td style="border:1px solid #000;">{{$val["code"]}}</td>
            @endif
            @if($arrrayCamp["brand"]==true)
                <td style="border:1px solid #000;">{{$val["brand"]}}</td>
            @endif
            @if($arrrayCamp["description"]==true)
                <td style="border:1px solid #000;">{{$val["description"]}}</td>
            @endif
            @if($arrrayCamp["unit_ven"]==true)
                <td style="border:1px solid #000;">{{$val["unit_ven"]}}</td>
            @endif
            @if($arrrayCamp["unit_rec"]==true)
                <td style="border:1px solid #000;">{{$val["unit_rec"]}}</td>
            @endif
            @if($arrrayCamp["origin_code"]==true)
                <td style="border:1px solid #000;">{{$val["origin_code"]}}</td>
            @endif
            @if($arrrayCamp["line_code"]==true)
                <td style="border:1px solid #000;">{{$val["line_code"]}}</td>
            @endif
            @if($arrrayCamp["factory_code"]==true)
                <td style="border:1px solid #000;">{{$val["factory_code"]}}</td>
            @endif
            @if($arrrayCamp["estado_prod"]==true)
                <td style="border:1px solid #000;">{{$val["estado_prod"]}}</td>
            @endif
            @if($arrrayCamp["motivo_prod"]==true)
                <td style="border:1px solid #000;">{{$val["motivo_prod"]}}</td>
            @endif
            @if($arrrayCamp["unidad_procedente"]==true)
                <td style="border:1px solid #000;">{{$val["unidad_procedente"]}}</td>
            @endif
            @if($arrrayCamp["costo_evaluacion"]==true)
                <td style="border:1px solid #000;">{{$val["costo_evaluacion"]}}</td>
            @endif
            @if($arrrayCamp["asunto_prod"]==true)
                <td style="border:1px solid #000;">{{$val["asunto_prod"]}}</td>
            @endif
            @if($arrrayCamp["detalle_prod"]==true)
                <td style="border:1px solid #000;">{{$val["detalle_prod"]}}</td>
            @endif
            @if($arrrayCamp["tipo_moneda_prod"]==true)
                <td style="border:1px solid #000;">{{$val["tipo_moneda_prod"]}}</td>
            @endif
            @if($arrrayCamp["orden_compra_prod"]==true)
                <td style="border:1px solid #000;">{{$val["orden_compra_prod"]}}</td>
            @endif
            @if($arrrayCamp["costo_prod"]==true)
                <td style="border:1px solid #000;">{{$val["costo_prod"]}}</td>
            @endif

          

            @if($arrrayCamp["num_fact"]==true)
                <td style="border:1px solid #000;">{{$val["num_fact"]}}</td>
            @endif
            @if($arrrayCamp["tipo_doc"]==true)
                <td style="border:1px solid #000;">{{$val["tipo_doc"]}}</td>
            @endif
            @if($arrrayCamp["costoventa"]==true)
                <td style="border:1px solid #000;">{{$val["costoventa"]}}</td>
            @endif
            @if($arrrayCamp["fac_guia_remision"]==true)
                <td style="border:1px solid #000;">{{$val["fac_guia_remision"]}}</td>
            @endif
            @if($arrrayCamp["fac_suc"]==true)
                <td style="border:1px solid #000;">{{$val["fac_suc"]}}</td>
            @endif
            @if($arrrayCamp["fac_alm"]==true)
                <td style="border:1px solid #000;">{{$val["fac_alm"]}}</td>
            @endif
            @if($arrrayCamp["fac_nom_vendedor"]==true)
                <td style="border:1px solid #000;">{{$val["fac_nom_vendedor"]}}</td>
            @endif
            
            @if($arrrayCamp["tipo_solution"]==true)
                <td style="border:1px solid #000;">{{$val["tipo_solution"]}}</td>
            @endif
            @if($arrrayCamp["prov_num_nc"]==true)
                <td style="border:1px solid #000;">{{$val["prov_num_nc"]}}</td>
            @endif
            @if($arrrayCamp["prov_date_nc"]==true)
                <td style="border:1px solid #000;">{{$val["prov_date_nc"]}}</td>
            @endif
            @if($arrrayCamp["prov_importe_nc"]==true)
                <td style="border:1px solid #000;">{{$val["prov_importe_nc"]}}</td>
            @endif
            @if($arrrayCamp["prov_type_money_nc"]==true)
                <td style="border:1px solid #000;">{{$val["prov_type_money_nc"]}}</td>
            @endif
            @if($arrrayCamp["prov_num_fac"]==true)
                <td style="border:1px solid #000;">{{$val["prov_num_fac"]}}</td>
            @endif
            @if($arrrayCamp["prov_date_fac"]==true)
                <td style="border:1px solid #000;">{{$val["prov_date_fac"]}}</td>
            @endif
            @if($arrrayCamp["prov_tipo_desc"]==true)
                <td style="border:1px solid #000;">{{$val["prov_tipo_desc"]}}</td>
            @endif
            @if($arrrayCamp["prov_monto_desc"]==true)
                <td style="border:1px solid #000;">{{$val["prov_monto_desc"]}}</td>
            @endif

            @if($arrrayCamp["nombre_prov"]==true)
                <td style="border:1px solid #000;">{{$val["nombre_prov"]}}</td>
            @endif


            
        </tr>
    @endforeach
    </tbody>
</table>