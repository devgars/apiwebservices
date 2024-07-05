<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0">
    <title>Hoja de reclamación</title>
    <style>
        .button {
            background-color: #4CAF50;
            border: none;
            color: white;
            padding: 15px 32px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px 2px;
            cursor: pointer; 
        }
    </style>
    <style>
        
    </style>
</head>
<body style="" >
    <style>
        @page {
            margin-left: 0.5cm;
            margin-right: 0.5cm;
            margin-top: 0.5cm;
            margin-bottom: 0.5cm;
        }
        .attachment ul {
            width: 100%;
            list-style: none;
            padding-left: 0;
            display: inline-block;
            margin-bottom: 30px;
        }
        body{
            padding: 0;
            margin: 0;
        }
        .text-center{
            text-align: center
        }
        p, b, a, h1, h2, h3, h4, h5 {
            font-family: Exo,sans-serif !important;
            padding: 0cm;
            margin: 0;
        }
        p{
            font-size: 12px;
        }
        .bold{
            font-weight: 700;
        }
        tbody.border td {
            border:1px solid #000;
        }
        .table{
            border-collapse:collapse;
        }
        .table td{
            padding: 8px 8px;
        }
        td.gris{
            background: #ccc;
        }
        .padding-20{
            padding: 20px;
        }
        .mb-10{
            margin-bottom: 10px;
        }
        .text-upper{
            text-transform: uppercase
        }
        .m-t-5{
            margin-top: 5px;
        }
        .m-t-10{
            margin-top: 10px;
        }
        .m-t-15{
            margin-top: 15px;
        }
        .m-b-5{
            margin-bottom: 5px;
        }
        .m-b-10{
            margin-bottom: 10px;
        }
        .m-b-15{
            margin-bottom: 15px;
        }
        .img-bg{
            background:lightseagreen;
            color: red;
            text-align: center;
            padding: 5px;
            margin: 0 auto;
            width: 80%;
            margin-top: 10px;
        }
        .bg-cnt-img{
            display: inline-block;
            width: 200px;
        }
    </style>
    <div class=" ">
        <table class="table" width="100%" border="0" style="border:1px solid #000" >
            <tbody class="border" >
                <tr>
                    <td class="text-center bold" colspan="2" rowspan="3" >
                        <h1> M&M edw </h1>
                    </td>
                    <td colspan="4" >
                        <p class="text-center bold" >SISTEMA DE GESTIÓN DE CALIDAD</p>
                    </td>
                    <td colspan="2">
                        <p class="text-center" >{{$rsDataSolicitud['NumRquest']}}</p>
                    </td>
                </tr>
                <tr>
                    <td colspan="4" rowspan="2"  >
                        <p class="text-center bold" >INFORME TÉCNICO</p>
                    </td>
                    <td colspan="2" class=" bold padding-20" >
                        <p class="text-center" >Versión 2</p>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class=" bold padding-20" >
                        <p class="text-center" >{{$rsDataSolicitud['fecha_reg']}}</p>
                    </td>
                </tr>
            </tbody>
        </table>
        <h4 class="m-b-10 m-t-10" > INFORME N° {{$rsDataSolicitud['NumRquest']}}  </h4>
        <table class="table m-b-10" width="100%" border="0" >
            <tbody>
                <tr>
                    <td class="" style="width:150px;" >
                        <p class="text-start" >CLIENTE : </p>
                    </td>
                    <td class="">
                        <p class="text-start bold" > {{$rsDataSolicitud['NameClient']}}  </p>
                    </td>
                </tr>
                <tr>
                    <td class="" style="width:150px;" >
                        <p class="text-start" >DOCUMENTO : </p>
                    </td>
                    <td class="">
                        <p class="text-start bold" >{{$rsDataSolicitud['Document']}} </p>
                    </td>
                </tr>
                <tr>
                    <td class="" style="width:150px;" >
                        <p class="text-start" >FECHA : </p>
                    </td>
                    <td class="">
                        <p class="text-start bold" >{{$rsDataSolicitud['fac_date_emision']}} </p>
                    </td>
                </tr>
                <tr>
                    <td class="" style="width:150px;" >
                        <p class="text-start" >ASUNTO : </p>
                    </td>
                    <td class="">
                        <p class="text-start bold" > {{$rsDataSolicitud['Detalle']}} </p>
                    </td>
                </tr>
            </tbody>
        </table>
        <table class="table m-b-10 m-t-10" width="100%" border="0" style="border:1px solid #000" >
            <tbody class="border" >
                <tr>
                    <td class="gris" >
                        <p class=" bold" >#</p>
                    </td>
                    <td colspan="12" class="gris" >
                        <p class=" bold text-center" >PRODUCTOS</p>
                    </td>
                    <!--td class="gris" >
                        <p class=" bold" >Código</p>
                    </td>
                    <td class="gris" >
                        <p class=" bold" >Marca</p>
                    </td>
                    <td class="gris">
                        <p class=" bold" >Cód. Fab</p>
                    </td>
                    <td class="gris">
                        <p class=" bold" >Linea</p>
                    </td>
                    <td class="gris">
                        <p class=" bold" >Asunto</p>
                    </td>
                    <td class="gris">
                        <p class=" bold" >Detalle Prod.</p>
                    </td>
                    <td class="gris">
                        <p class=" bold" >Conclusión.</p>
                    </td-->
                </tr>
                @php
                    $inc = 0;
                @endphp
                @foreach ($rsDataSolicitud['datadetial'] as $val )
                    @php
                        $inc++;
                    @endphp
                    <tr>
                        <td rowspan="6" >
                            <p class="bold" >{{$inc}}</p>
                        </td>
                        <td colspan="2" class="" >
                            <p class="bold" >Código: </p>
                        </td>
                        <td  >
                            <p class=" " >{{$val->code}}</p>
                        </td>
                        <td class="" >
                            <p class="bold" >Marca: </p>
                        </td>
                        <td >
                            <p class=" " >{{$val->brand}}</p>
                        </td>
                        <td class="">
                            <p class="bold" >Cód_Fab: </p>
                        </td>
                        <td >
                            <p class=" " >{{$val->factory_code}}</p>
                        </td>
                        <td class="">
                            <p class="bold" >Linea:</p>
                        </td>
                        <td colspan="4" >
                            <p class=" " >{{$val->line_code}}</p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" >
                            <p class="bold" >Asunto: </p>
                        </td>
                        <td colspan="3" >
                            <p class=" " >{{$val->subjet}}</p>
                        </td>
                        <td colspan="2" >
                            <p class="bold" >Motivo: </p>
                        </td>
                        <td colspan="5" >
                            <p class=" " >{{$val->motivo}}</p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" >
                            <p class="bold" >Detalle : </p>
                        </td>
                        <td colspan="10" >
                            <p class=" " >{{$val->detail}}</p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" >
                            <p class="bold" >C. de Falla : </p>
                        </td>
                        <td colspan="10" >
                            <p class=" " >{{$val->cause_failure}}</p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" >
                            <p class="bold" >Recomendación : </p>
                        </td>
                        <td colspan="10" >
                            <p class=" " >{{$val->recommendations}}</p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="12">
                            <div style="margin-top:40px; border:0px solid red; padding:0;">
                                @php
                                    $counter = 0;
                                @endphp
                                @foreach ($rsDataSolicitud['dataFiles'] as $valor )
                                    @if($val->id===$valor->id_product_detail_request) 
                                        <div class="bg-cnt-img">
                                            @php
                                                $ruta = "storage/mymfiles/".$valor->name_file;
                                                $counter++;
                                            @endphp
                                            @if($valor->type_file==='png' || $valor->type_file==='jpeg' || $valor->type_file==='JPEG'|| $valor->type_file==='jpg' || $valor->type_file==='gif')
                                                <img width="200px" src="{{$ruta}}">
                                                <p class="img-bg" >FIGURA {{$counter}}</p>
                                            @else
                                                <a href="{{Request::root().'/'.$ruta}}" target="_blank" >{{$valor->name_file}} </p>
                                            @endif
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>