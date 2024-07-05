<html>
    <head></head>
    <body>
        <b style="display: inline-block" >Nro de solicitud :</b> <p style="display: inline-block; margin:5px;" >{{$rsSend['NumRquest']}} </p><br>
        <b style="display: inline-block" >Tipo de solicitud :</b> <p style="display: inline-block; margin:5px;" >{{$rsSend['TypeRequest']}} </p><br>
        <b style="display: inline-block" >Categoría :</b> <p style="display: inline-block; margin:5px;" >{{$rsSend['categoria']}} </p><br>
        <b style="display: inline-block" >Cod. Cliente :</b> <p style="display: inline-block; margin:5px;" >{{$rsSend['code_cli']}} {{$rsSend['NameClient']}} </p><br>
        <b style="display: inline-block" >Nro. Comprobante :</b> <p style="display: inline-block; margin:5px;" >{{$rsSend['num_comprobante']}} </p><br>
        <b style="display: inline-block" >Nro Pedido :</b> <p style="display: inline-block; margin:5px;" >{{$rsSend['num_pedido']}} </p><br>
        <b style="display: inline-block" >Fecha Factura :</b> <p style="display: inline-block; margin:5px;" >{{$rsSend['fac_date_emision']}} </p><br>
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
                    <td>
                        <b class=" bold" >Uni. Proc</b>
                    </td>
                    <td>
                        <b class=" bold" >Condición de producto</b>
                    </td> 
                    <td>
                        <b class=" bold" >Monto NC</b>
                    </td>           
                </tr>
                @foreach ($rsSend['productos'] as $val )
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
                        <td>
                            <p class=" bold" >{{$val->detalle_producto}}</p>
                        </td>
                        <td >
                            <p class=" bold" >{{$val->unit_proc}}</p>
                        </td>
                        <td >
                            <p class=" bold" >{{$val->condicion_descripcion}}</p>
                        </td>
                        <td >
                            <p class=" bold" > {{ number_format(($val->unit_proc * $val->item_price)*1.18, 2, '.', ',')}}</p>
                        </td>
                    </tr>
                @endforeach
                <tr>
                    <td colspan="8" >
                    </td>
                    <td>
                        <p class=" bold" >{{$rsSend['totalsum_nc']}}</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </body>
</html>