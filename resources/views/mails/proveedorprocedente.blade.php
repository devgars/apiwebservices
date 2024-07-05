<html>
    <head></head>
    <body>
        <b style="display: inline-block" >Nro de solicitud :</b> <p style="display: inline-block; margin:5px;" >{{$rsSend['NumRquest']}} </p><br>
        <b style="display: inline-block" >Tipo de solicitud :</b> <p style="display: inline-block; margin:5px;" >{{$rsSend['TypeRequest']}} </p><br>
        <b style="display: inline-block" >Categoría :</b> <p style="display: inline-block; margin:5px;" >{{$rsSend['categoria']}} </p><br>
        <b style="display: inline-block" >Cod. Cliente :</b> <p style="display: inline-block; margin:5px;" >{{$rsSend['code_cli']}} {{$rsSend['NameClient']}} </p><br>
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
                        <b class=" bold" >Referencia</b>
                    </td>
                </tr>
                @foreach ($rsSend['dataProveedorDetail'] as $val )
                    @if ($rsSend['id'] === $val->id_request)
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
                                <p class=" bold" >{{$val->purchase_description}}</p>
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </body>
</html>