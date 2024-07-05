<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0">
    <title>Mensaje de MMTrack</title>

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
</head>
<body>
    <style>
        .attachment ul {
            width: 100%;
            list-style: none;
            padding-left: 0;
            display: inline-block;
            margin-bottom: 30px;
        }
    </style>
    <p>Hola! {{$data['cliente']}} Se ha reportado un nuevo Cambio en el tracking de su pedido.</p>
    <p>Estos son los datos que se ha reportado:</p>
 
    <ul>
 
        <li>Pedido: {{$data['pedido']}}</li>
        <li>Estado:{{$data['estado']}}</li>
    </ul>

    <a target="_blank" class="button" href="https://mmtrack.mym.com.pe:444/panel/cliente/{{$data['cliente_id']}}">Ver Pedidos</a>

    <p>Esto es un mensaje de notificación</p>
    
</body>
</html>