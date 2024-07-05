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
            padding: 3px 3px;
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
        .m-t-20{
            margin-top: 20px;
        }
        .m-t-15{
            margin-top: 15px;
        }
        .m-t-25{
            margin-top: 25px;
        }
        .m-t-30{
            margin-top: 30px;
        }
        .m-b-5{
            margin-bottom: 5px;
        }
        .m-b-10{
            margin-bottom: 10px;
        }
        .m-b-30{
            margin-bottom: 30px;
        }
        .m-b-15{
            margin-bottom: 15px;
        }
        .img-bg{
            text-align: left;
            padding: 5px 0;
            margin: 0 auto;
            width: 100%;
            margin-top: 10px;
        }
        .img-texto{
            text-align: left;
            padding: 5px 0;
            margin: 0 auto;
            width: 100%;
            margin-top: 10px;
            border:1px solid #000;
        }
        .bg-cnt-img{
            display: inline-block;
            width: 250px;
            height: 220px;
        }
        .text-start{
            text-align: left
        }
        .text-end{
            text-align: right;
        }
        .fs-16{
            font-size: 16px;
        }
        .fs-14{
            font-size: 14px;
        }
        .fs-18{
            font-size: 18px;
        }
        .fs-20{
            font-size: 20px;
        }
        .fs-30{
            font-size: 30px;
        }
        .fs-40{
            font-size: 40px;
        }
        .p-t-20{
            padding-top: 20px;
        }
        .p-t-25{
            padding-top: 25px;
        }
        .p-t-30{
            padding-top: 30px;
        }
        .p-5{
            padding: 5px;
        }
        .text-blue{
            color:#4472C4;
        }
    </style>
    <div class=" ">
        <table class="table" width="100%" border="0" style="border:1px solid #000" >
            <tbody class="border" >
                <tr>
                    <td class="text-center bold" colspan="2" rowspan="3" >
                        <img width="150px" src="imagenes/logo_mym.jpg">
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
                    <td colspan="2" class="" >
                        <p class="text-center" >Versión 2</p>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="" >
                        <p class="text-center" >F. Versión: 03/02/2016 </p>
                    </td>
                </tr>
            </tbody>     
        </table>
        <table class="table m-b-10" width="100%" border="0">
            <tbody class="" >
                <tr >
                    <td style="padding:30px;" class=""> </td>
                    <td class="">
                        <p class="m-b-10 m-t-10 text-end fs-18" >Lima, {{$rsDataSolicitud['DayNow']}}</p>
                    </td>
                </tr>
                <tr class="m-t-30">
                    <td class=""  >
                        <p class="m-b-10 m-t-10 text-start fs-18" >  INFORME N° {{$rsDataSolicitud['NumRquest']}} / {{$rsDataSolicitud['datadetial'][0]->id}} M&M </p>
                    </td>
                    <td class=""> </td>
                </tr>
            </tbody>
        </table>
        @foreach ($rsDataSolicitud['datadetial'] as $val )
            <table class="table p-t-20" width="100%" border="0" >
                <tbody class="border" >
                    <tr >
                        <td style="100px" class="">
                            <p class="" >Cliente</p>
                        </td>
                        <td class="">
                            <p class="bold text-center" >{{$rsDataSolicitud['NameClient']}}</p>
                        </td>
                        <td class="">
                            <p class="" >RUC:</p>
                        </td>
                        <td class=""> 
                            <p class="bold text-center" > {{$rsDataSolicitud['Document']}} </p>
                        </td>
                        <td class="">
                            <p class="" >Cod. Cliente:</p>
                        </td>
                        <td class="">
                            <p class="bold text-center" >{{$rsDataSolicitud['code_cli']}}</p>
                        </td>
                    </tr>
                    <tr >
                        <td class=""  >
                            <p class="" >Documento</p>
                        </td>
                        <td class="" colspan="2"> 
                            <p class="bold text-center" >{{$rsDataSolicitud['serie_description']}} - {{$rsDataSolicitud['num_comprobante']}}</p>
                        </td>
                        <td class="" >
                            <p class="" >Fecha Comp.:</p>
                        </td>
                        <td class="" colspan="2" >
                            <p class="bold text-center" >{{$rsDataSolicitud['fac_date_emision']}}</p>
                        </td>
                    </tr>
                </tbody>
            </table>
            <table class="table p-t-25" width="100%" border="0" >
                <tbody class="border" >
                    <tr >
                        <td style="100px" class="">
                            <p class="" >Descripción</p>
                        </td>
                        <td class="">
                            <p class="text-center" > {{$val->descripcion_prod}}</p>
                        </td>
                        <td class="">
                            <p class="" >Cantidad</p>
                        </td>
                        <td class="">
                            <p class="text-center" >{{$val->unit_proc}}</p>
                        </td>
                        <td class="">
                            <p class="" >Línea:</p>
                        </td>
                        <td class="">
                            <p class=" text-center" > {{$val->line_code}}</p>
                        </td>
                    </tr>
                    <tr >
                        <td class=""  >
                            <p class="" >Código</p>
                        </td>
                        <td class="">
                            <p class="bold text-center" >{{$val->code}} </p> 
                        </td>
                        <td class="" >
                            <p class="" >Marca</p>
                        </td>
                        <td class="" >
                            <p class="bold text-center" >{{$val->brand}}</p> 
                        </td>
                        <td class="" >
                            <p class="" >Cod. Fabricante</p>
                        </td>
                        <td class=""  >
                            <p class="bold text-center" >{{$val->factory_code}}</p> 
                        </td>
                    </tr>
                </tbody>
            </table>
            <table class="table p-t-20" width="100%" border="0" >
                <tbody class="border" >
                    <tr >
                        
                        <td class="">
                            <p class="bold fs-14"  >
                                DETALLES SOBRE FALLA DEL PRODUCTO:  
                            </p>
                        </td>
                    </tr> 
                    <tr >
                        <td class=""  >
                            <p class="p-5" >{{$val->detail_init}}</p>
                        </td>
                    </tr>
                </tbody>
            </table>
            <table class="table p-t-20" width="100%" border="0" >
                <tbody class="border" >
                    <tr >
                        <td  colspan="2">
                            <p class="bold  fs-14" >
                                EVALUACIÓN:  
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" >
                            <p class="p-5"  > {{$val->detail}} </p>
                        </td>
                    </tr>
                    @php
                        $counter = 0;
                    @endphp
                    @foreach ($rsDataSolicitud['arrayFinalFiles'] as $valor)
                        <tr>
                            @foreach ($valor['dato'] as $valores)
                                @php
                                    $ruta = "storage/mymfiles/".$valores["name_file"];
                                    $counter++;
                                    $countRow = count($valor['dato']);
                                @endphp
                                <td colspan="{{$countRow ===2 ? "1" : "2"}}" style="width: 50%; padding:5 10px 10px 10px; "  >
                                    <p class="img-bg">Fig: {{$counter}} </p>
                                    @if($valores["type_file"]==='png' || $valores["type_file"]==='jpeg' || $valores["type_file"]==='JPEG'|| $valores["type_file"]==='jpg' || $valores["type_file"]==='gif')
                                        <img width="100%" style="margin: 0 auto; display:block;" src="{{$ruta}}">
                                    @else
                                        <a href="{{Request::root().'/'.$ruta}}" target="_blank" >{{$valores["name_file"]}} </a>
                                    @endif
                                </td>
                            @endforeach
                            
                        </tr>
                        <tr>
                            @foreach ($valor['dato'] as $valores)
                                <td colspan="{{$countRow ===2 ? "1" : "2"}}" style="width: 50%; padding:5 10px 10px 10px; " >
                                    <p >{{$valores["Adicional"]}}  </p>
                                </td>
                            @endforeach 
                        </tr>       
                    @endforeach
                    <tr>
                        <td colspan="2" >
                            <p class="p-5 "  > <b class="bold">Evidencia: </b> {{$val->evidence}}  </p>
                        </td>
                    </tr>
                </tbody>
            </table>
            @if($val->motivo!=='FALLA DE FÁBRICA') 
            <table class="table p-t-20" width="100%" border="0" >
                <tbody class="border" >
                    <tr >
                        <td>
                            <p class="bold  fs-14" >
                                CAUSAS DE FALLA DEL PRODUCTO:   
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td >
                            <p class="p-5"  >{{$val->cause_failure}} </p>
                        </td>
                    </tr>
                </tbody>
            </table>
            @endif
            <table class="table p-t-20" width="100%" border="0" >
                <tbody class="border" >
                    <tr >
                        <td>
                            <p class="bold  fs-14" >
                                CONCLUSIONES:  
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td >
                            <p class="p-5"  > {{$val->conclusion_detail}} </p>
                        </td>
                    </tr>
                    <tr>
                        <td >
                            <p class="bold  fs-14" >{{$val->motivo}}</p>
                        </td>
                    </tr>
                </tbody>
            </table>
            @if($val->motivo!=='FALLA DE FÁBRICA') 
            <table class="table p-t-20" width="100%" border="0" >
                <tbody class="border" >
                    <tr >
                        <td>
                            <p class="bold  fs-14" >
                                RECOMENDACIONES:  
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td >
                            <p class="p-5"  > {{$val->recommendations}} </p>
                        </td>
                    </tr>
                </tbody>
            </table>
            @endif
            <table class="table p-t-20" width="100%" border="0" >
                <tbody  >
                    <tr >
                        <td>
                            <p class="bold  fs-14 text-blue" >
                                MARCO PEREZ  
                            </p>
                            <p class="bold" >
                                Asesor Técnico Post Venta
                            </p>
                            <p class="" >
                                M&M Repuestos y Servicios S.A.
                            </p>
                            <p class="" >
                                Av. Nicolás Arriola 1723 La Victoria - Lima - Perú
                            </p>
                            <p class="" >
                                Tel.: <a class='text-blue'> + 511 613 1500</a> A. 1213 | Móvil: <a class='text-blue'>+51 985 428 080</a> | e-mail: <a class='text-blue'>mperez@mym.com.pe</a>
                            </p>
                        </td>
                    </tr>
                    
                </tbody>
            </table>
        @endforeach
    </div>
</body>
</html>