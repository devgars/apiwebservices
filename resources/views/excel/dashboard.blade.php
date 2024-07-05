<table>
    <tbody>
        <tr>
            <td colspan="2" style="background:#181c32; color:white;font-weight: 700;" >
                RECUENTO DE CANTIDAD
            </td>
        </tr>
    </tbody>
    <thead>
    <tr>  
        <th style="font-weight: 700;background: #1190b8; color:white; border:1px solid #000;">TIPO SOLICITUD  </th>    
        <th style="font-weight: 700;background: #1190b8; color:white; border:1px solid #000;">CATEGORIA</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($request as $val )
        <tr>
            <td style="border:1px solid #000;">{{$val["tipo_solicitud"]}}</td>
            <td style="border:1px solid #000;">{{$val["categoria"]}}</td>
        </tr>
    @endforeach
    </tbody>
    <tbody>
        <tr><td></td></tr>
        <tr><td></td></tr>
        <tr>
            <td colspan="2" style=" background:#181c32; color:white; font-weight: 700;" >
                RECUENTO DE CANTIDAD POR TIPO DE SOLICITUD
            </td>
        </tr>
    </tbody>
    <thead>
        <tr>
            <th style="font-weight: 700;background: #1190b8; color:white; border:1px solid #000;">TIPO SOLICITUD</th>    
            <th style="font-weight: 700;background: #1190b8; color:white; border:1px solid #000;">TOTAL</th>
        </tr>
        </thead>
    <tbody>
        @foreach ($requestPie as $val )
            <tr>
                <td style="border:1px solid #000;">{{$val["name"]}}</td>
                <td style="border:1px solid #000;">{{$val["total"]}}</td>
            </tr>
        @endforeach
        </tbody>
</table>
