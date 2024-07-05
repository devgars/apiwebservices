<html>
    <head></head>
    <body>
        <h1 style=" font-family: Exo,sans-serif !important;"  > </h1>
            <b style="display: inline-block" >Nro de solicitud :</b> <p style="display: inline-block; margin:5px;" >{{$solicitud['NumRquest']}} </p><br>
            <b style="display: inline-block" >Tipo de solicitud :</b> <p style="display: inline-block; margin:5px;" >{{$solicitud['TypeRequest']}} </p><br>
            <b style="display: inline-block" >Categoría :</b> <p style="display: inline-block; margin:5px;" >{{$solicitud['categoria']}} </p><br>
            <b style="display: inline-block" >Cod. Cliente :</b> <p style="display: inline-block; margin:5px;" >{{$solicitud['code_cli']}} {{$solicitud['NameClient']}} </p><br>
            <b style="display: inline-block" >Nro. Comprobante :</b> <p style="display: inline-block; margin:5px;" >{{$solicitud['num_comprobante']}} </p><br>
            <b style="display: inline-block" >Nro Pedido :</b> <p style="display: inline-block; margin:5px;" >{{$solicitud['num_pedido']}} </p><br>
            <b style="display: inline-block" >Fecha Factura :</b> <p style="display: inline-block; margin:5px;" >{{$solicitud['fac_date_emision']}} </p><br>
            <br>
            <b style="display: inline-block" >Orden Comp. :</b> <p style="display: inline-block; margin:5px;" >{{$solicitud['prov_ord_compra']}} </p><br>
            <b style="display: inline-block" >Cod. Prod. :</b> <p style="display: inline-block; margin:5px;" >{{$solicitud['prov_cod_prod']}} </p><br>
            @if($solicitud['prov_solution_proveedor']=='NC')
                <b style="display: inline-block" >N° de NC :</b> <p style="display: inline-block; margin:5px;" >{{$solicitud['prov_num_nc']}} </p><br>
                <b style="display: inline-block">Moneda : </b> <p style="display: inline-block; margin:5px;" >{{$solicitud['prov_type_money_nc']}}</p> <br>
                <b style="display: inline-block">Fecha de NC : </b> <p style="display: inline-block; margin:5px;" >{{$solicitud['prov_date_nc']}}</p><br>
                <b style="display: inline-block">Importe : </b> <p style="display: inline-block; margin:5px;" >{{$solicitud['prov_importe_nc']}}</p><br>
            @elseif($solicitud['prov_solution_proveedor']=='FAC')
                <b style="display: inline-block">N° de Factura : </b> <p style="display: inline-block; margin:5px;" >{{$solicitud['prov_num_fac']}}</p><br>
                <b style="display: inline-block">Fecha de Factura : </b> <p style="display: inline-block; margin:5px;" >{{$solicitud['prov_date_fac']}}</p><br>
            @elseif($solicitud['prov_solution_proveedor']=='DES')
                <b style="display: inline-block">Tipo Descuento : </b> <p style="display: inline-block; margin:5px;" >{{$solicitud['prov_tipo_desc']}}</p><br>
                <b style="display: inline-block">Monto a Descontar : </b> <p style="display: inline-block; margin:5px;" > {{$solicitud['prov_monto_desc']}}</p><br>
            @endif

            <b style="display: inline-block" >Productos :</b><br>
        <table class="table" width="100%" border="1" style="border:1px solid #000;border-collapse:collapse;margin-top:10px;" >
            <tbody>
                <tr>
                    <td>
                        <b class=" bold" >Código</b>
                    </td>
                    <td>
                        <b class=" bold" >Descripción</b>
                    </td>
                    <td>
                        <b class=" bold" >Linea</b>
                    </td>
                    <td>
                        <b class=" bold" >Origen</b>
                    </td>
                    <td>
                        <b class=" bold" >Motivo</b>
                    </td>
                    <td>
                        <b class=" bold" >Detalle</b>
                    </td>
                </tr>

                @foreach ($solicitud['productos'] as $val )
                    <tr>
                        <td >
                            <p class=" bold" >{{$val->code}}</p>
                        </td>
                        <td >
                            <p class=" bold" >{{$val->descripcion_prod}}</p>
                        </td>
                        <td >
                            <p class=" bold" >{{$val->line_code}}</p>
                        </td>
                        <td >
                            <p class=" bold" >{{$val->origin_code}}</p>
                        </td>
                        <td >
                            <p class=" bold" >{{$val->motivo}}</p>
                        </td>
                        <td >
                            <p class=" bold" >{{$val->detalle_producto}}</p>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
       
    </body>
</html>