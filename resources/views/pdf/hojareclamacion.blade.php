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
            font-size: 10px;
        }
        .bold{
            font-weight: 700;
        }
        tbody td{
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
        .d-none{
            display: none;
        }

    </style>
    <div class=" ">
        <h5 class="text-center bold"  >ANEXOS</h5>
        <p class="text-center bold mb-10">Anexo I: Formato de Hoja de Reclamación de Libro de reclamaciones</p>
        <table class="table" width="100%" border="0" style="border:1px solid #000" >
            <tbody>
                <tr>
                    <td colspan="4" class="gris" > <p class="text-center bold" >LIBRO DE RECLAMACIONES</p> </td>
                    <td colspan="4"  rowspan="2" > <p class="text-center bold" >HOJA DE RECLAMACIONES <br> N° {{$rsDataSolicitud['NumRquest']}}</p> </td>
                </tr>
                <tr>
                    <td><p class=" bold" >FECHA</p></td>
                    <td><p class="text-center bold" >{{$rsDataSolicitud['Day']}}</p></td>
                    <td><p class="text-center bold text-upper" >{{$rsDataSolicitud['Month']}}</p></td>
                    <td><p class="text-center bold" >{{$rsDataSolicitud['Year']}}</p></td>
                </tr>
                <tr>
                    <td colspan="8" >
                        <p class=" bold" > {{$rsDataSolicitud['NameClient']}}</p>
                    </td>
                </tr>
                <tr>
                    <td colspan="8"  class="gris">
                        <p class=" bold" >1. IDENTIFICACIÓN DEL CONSUMIDOR RECLAMANTE</p>
                    </td>
                </tr>
                <tr>
                    <td colspan="8" >
                        <p class=" bold" >NOMBRE:  {{$rsDataSolicitud['contact_name']}}</p> 
                    </td>
                </tr>
                <tr class="d-none" >
                    <td colspan="8"  >
                        <p class=" bold" > DOMICILIO: {{$rsDataSolicitud['Domicilio_contact']}}</p>
                    </td>
                </tr>
                <tr>
                    <td colspan="3"  >
                        <p class=" bold " > DNI/CE: {{$rsDataSolicitud['Document_contact']}}</p>
                    </td>
                    <td colspan="5"  >
                        <p class=" bold " > TEÉLEFONO / EMAIL : {{$rsDataSolicitud['Email']}} {{$rsDataSolicitud['contact_phone']}}</p>
                    </td>
                </tr>
                <tr>
                    <td colspan="8"  >
                        <p class=" bold text-center" > PADRE O MADRE PARA EL CASO DE : </p>
                    </td>
                </tr>
                <tr>
                    <td colspan="8"  class="gris">
                        <p class=" bold" >2. IDENTIFICACIÓN DEL BIEN CONTRATADO</p>
                    </td>
                </tr>
                <tr>
                    <td  >
                        <p class=" bold " > PRODUCTO:</p>
                    </td>
                    <td  >
                        <p class=" bold text-center" > X </p>
                    </td>
                    <td colspan="6" rowspan="2" >
                        <p class=" bold " > </p>
                    </td>
                </tr>
                <tr>
                    <td  >
                        <p class=" bold " > SERVICIO:</p>
                    </td>
                    <td  >
                        <p class=" bold " > </p>
                    </td>
                </tr>
                <tr>
                    <td  colspan="4" class="gris">
                        <p class=" bold" >3. DETALLE DE RECLAMACIÓN :</p>
                    </td>
                    <td colspan="2" >
                        <p class=" bold" >T. SOLICITUD</p>
                    </td>
                    <td colspan="2">
                        <p class=" bold" >{{$rsDataSolicitud['TypeRequest']}} </p>
                    </td>
                </tr>
                
                <tr style="display: none;" >
                    <td colspan="8" >
                        <p class=" bold" >
                            DETALLE: 
                        </p>
                        <p class=" bold" style="padding: 20px;" >

                        </p>
                    </td>
                </tr>
                <tr>
                    <td colspan="6" rowspan="2">
                        <p class=" bold">
                            DETALLE: 
                        </p>
                        <p class=" bold padding-20" >
                            {{$rsDataSolicitud['Detalle']}}
                        </p>
                    </td>
                    <td colspan="2" class=" bold padding-20" ></td>
                </tr>
                <tr>
                    <td colspan="2" >
                        <p class="bold text-center" >
                            FIRMA DEL CONSUMIDOR
                        </p>
                    </td>
                </tr>

                @if($rsDataSolicitud["Type"]=='byid')
                    <tr>
                        <td  colspan="4" class="gris">
                            <p class=" bold" >4. ESTADO :</p>
                        </td>
                        <td colspan="4" >
                            <p class=" bold" >{{$rsDataSolicitud['estado_des']}}</p>
                        </td>
                    </tr>
                    <tr>
                        <td  colspan="8" class="gris">
                            <p class=" bold" >5:PRODUCTOS</p>
                        </td>
                    </tr>
                    <tr>
                        <td  colspan="8" >
                            <table class="table" width="100%" border="0" style="border:1px solid #000" >
                                <tbody>
                                    <tr>
                                        <td class="gris">
                                            <p class=" bold text-center" >Código</p>
                                        </td>
                                        <td class="gris">
                                            <p class=" bold text-center" >Marca</p>
                                        </td>
                                        <td class="gris">
                                            <p class=" bold text-center" >Uni. Rec</p>
                                        </td>
                                        <td class="gris">
                                            <p class=" bold text-center" >Uni. Proc</p>
                                        </td>
                                        <td class="gris">
                                            <p class=" bold text-center" >Estado</p>
                                        </td>
                                        <td class="gris">
                                            <p class=" bold text-center" >Motivo</p>
                                        </td>
                                        <td class="gris">
                                            <p class=" bold text-center" >Línea</p>
                                        </td>
                                        <td class="gris">
                                            <p class=" bold text-center" >Cod. Fac.</p>
                                        </td>
                                        <td class="gris">
                                            <p class=" bold text-center" >Descripción</p>
                                        </td>
                                        <td class="gris">
                                            <p class=" bold text-center" >Monto</p>
                                        </td>
                                    </tr>
                                    @php
                                        $totalRow = 0;
                                    @endphp
                                    @foreach ($rsDataSolicitud['datadetial'] as $val )
                                        @php
                                            $total = ($val->item_price * $val->unit_rec)*1.18;
                                            $totalRow = $totalRow + $total;
                                        @endphp
                                        <tr>
                                            <td >
                                                <p class=" bold text-center" >{{$val->code}}</p>
                                            </td>
                                            <td >
                                                <p class=" bold text-center" >{{$val->brand}}</p>
                                            </td>
                                            <td >
                                                <p class=" bold text-center" >{{$val->unit_rec}}</p>
                                            </td>
                                            <td >
                                                <p class=" bold text-center" >{{$val->unit_proc}}</p>
                                            </td>
                                            <td >
                                                <p class=" bold text-center" >{{$val->estado_producto}}</p>
                                            </td>
                                            <td >
                                                <p class=" bold text-center" >{{substr($val->motivo, 0, 15).'.'}}</p>
                                            </td>
                                            <td >
                                                <p class=" bold text-center" >{{$val->line_code}}</p>
                                            </td>
                                            <td >
                                                <p class=" bold text-center" >{{$val->factory_code}}</p>
                                            </td>
                                            <td >
                                                <p class=" bold text-center" > {{substr($val->description, 0, 15).'.'}}</p>
                                            </td>
                                            <td>
                                                <p class=" bold text-center" > {{number_format($total, 2, '.', ',')}}</p>
                                            </td>
                                           
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <td colspan="9" >
                                        </td>
                                        
                                        <td class="gris" >
                                            <p class=" bold text-center" >{{number_format($totalRow, 2, '.', ',')}}</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                @endif

                <tr style="display: none" >
                    <td colspan="8" class="gris" >
                        <p class="bold" > 4. OBVERVACIONES Y ACCIONE ADAPTADAS POR EL PROVEEDOR </p>
                    </td>
                </tr>
                <tr style="display: none">
                    <td colspan="3" ><p class="bold " >FECHA DE COMUNICACIÓN DE LA RESPUESTA</p></td>
                    <td  > <p class="bold" >DIA</p> </td>
                    <td  > <p class="bold" >MES</p> </td>
                    <td  > <p class="bold" >AÑO</p> </td>
                    <td colspan="2" rowspan="2" > 
                        <p class="padding-20" > </p>
                    </td>
                </tr>
                <tr style="display: none">
                    <td colspan="6 " rowspan="2">
                        <p class="padding-20" > </p>
                    </td>
                    
                </tr>
                <tr style="display: none">
                    <td colspan="2" >
                        <p class="bold text-center" >
                            FIRMA DEL PROVEEDOR
                        </p>
                    </td>
                </tr>
                <tr style="display: none">
                    <td colspan="4" >
                        <p class="text-center" >
                            <b class="bold" >RECLAMO:</b> <small> lorem esthis lore hit his car d </small>
                        </p>
                    </td>
                    <td colspan="4" >
                        <p class=" text-center" >
                            <b class="bold" >QUEJA:</b> <small> lorem esthis lore hit his car d </small>
                        </p>
                    </td>
                </tr>
                <tr style="display: none">
                    <td colspan="8" >
                        <p class="bold text-center" > INDECOPI </p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>