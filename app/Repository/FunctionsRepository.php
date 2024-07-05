<?php

namespace App\Repository;

//use Illuminate\Http\Request;
//use Illuminate\Support\Facades\Config;

class FunctionsRepository { 
    function getApi($url, $parameter =[], $method){ 
        $data =$parameter;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($data));
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, JSON_FORCE_OBJECT);
    }
    function getDayFormatName($fecha){
        $month = array('','Enero', 'Febreo', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Setiembre', 'Octubre', 'Noviembre', 'Diciembre');
        $fechats = strtotime($fecha); 
        $Name ='';
        switch (date('w', $fechats)){
            case 0: $Name = "Domingo"; 
            case 1: $Name = "Lunes"; 
            case 2: $Name = "Martes"; 
            case 3: $Name = "Miércoles"; 
            case 4: $Name = "Jueves"; 
            case 5: $Name = "Viernes"; 
            case 6: $Name = "Sábado"; 
        }
        return date('d', $fechats) .' de '.$month[intval(date('m', $fechats))] .' del ' .date('Y', $fechats).' '.date('h', $fechats).':'.date('i', $fechats);
    }
    function NameDate($fecha, $d){
        $month = array('','Enero', 'Febreo', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Setiembre', 'Octubre', 'Noviembre', 'Diciembre');
        $fechats = strtotime(date($fecha)); 
        $dia =  date('d', $fechats);
        $mes = $month[intval(date('m', $fechats))];
        $anio = date('Y', $fechats);
        if($d==='d'){
            return date('d', $fechats);
        }else if($d==='n_m'){
            return $month[intval(date('m', $fechats))];
        } else if($d==='Y'){
            return  date('Y', $fechats);
        }


    }
}