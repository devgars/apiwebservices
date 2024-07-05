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
        <table class="table p-t-25" width="100%" border="0" >
            <tbody class="border" >
                <tr >
                    <td style="100px" class="">
                        <h1>{{$rsDataSolicitud["id"]}}</h1>
                        <p class="" >Descripción </p>
                    </td>
                    <td class="">
                        <p class="text-center" > {{$rsDataSolicitud["descripcion_prod"]}}</p>
                    </td>
                    <td class="">
                        <p class="" >Cantidad</p>
                    </td>
                    <td class="">
                        <p class="text-center" >{{$rsDataSolicitud["unit_proc"]}}</p>
                    </td>
                    <td class="">
                        <p class="" >Línea:</p>
                    </td>
                    <td class="">
                        <p class=" text-center" > {{$rsDataSolicitud["line_code"]}}</p>
                    </td>
                </tr>
                <tr >
                    <td class=""  >
                        <p class="" >Código</p>
                    </td>
                    <td class="">
                        <p class="bold text-center" >{{$rsDataSolicitud["code"]}} </p> 
                    </td>
                    <td class="" >
                        <p class="" >Marca</p>
                    </td>
                    <td class="" >
                        <p class="bold text-center" >{{$rsDataSolicitud["brand"]}}</p> 
                    </td>
                    <td class="" >
                        <p class="" >Cod. Fabricante</p>
                    </td>
                    <td class=""  >
                        <p class="bold text-center" >{{$rsDataSolicitud["factory_code"]}}</p> 
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>