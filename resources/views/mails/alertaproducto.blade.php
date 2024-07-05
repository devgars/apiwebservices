<html>
    <head></head>
    <body>
        <b style="display: inline-block" >Productos:</b><br>
        <table class="table" width="100%" border="1" style="border:1px solid #000;border-collapse:collapse;margin-top:10px;" >
            <tbody>
                <tr>
                    <td>
                        <b class=" bold" >Código</b>
                    </td>
                    <td>
                        <b class=" bold" >Marca</b>
                    </td>
                </tr>
                @foreach ($rsSend['productos'] as $val )
                    <tr>
                        <td >
                            <p class=" bold" >{{$val["code"]}}</p>
                        </td>
                        <td >
                            <p class=" bold" >{{$val["description"]}}</p>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </body>
</html>