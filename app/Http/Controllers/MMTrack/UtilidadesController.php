<?php

namespace App\Http\Controllers\MMTrack;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\MMTrack\OrderDeliveryDetail;

class UtilidadesController extends Controller
{
    public function convertirImagen64PorPng()
    {

        //obtener imagenes que aun no se han formateado
        $registros_por_procesar = OrderDeliveryDetail::select('id','customer_id','dispatch','image_64','image_64_bultos')
                                                        ->whereNull('imagen')
                                                        ->orderBy('id','ASC')
                                                        ->take(10)
                                                        ->get();
 
        foreach ($registros_por_procesar as $registro) {
            //guardar las imagenes
            $this->guardarImagen($registro,"bulto");
            $this->guardarImagen($registro);
        }

    }

    public function guardarImagen($registro, $tipo="guia")
    {
        $imagen_b64 = $tipo=="guia" ? $registro->image_64 : $registro->image_64_bultos;

        //validar que la imagen exista
        if (!$imagen_b64) {
            return;
        }
        // Obtener los datos de la imagen
        $imagen = $this->getB64Image($imagen_b64);
        
        // Obtener la extensión de las Imagenes
        $imagen_extension = 'jpg';//$this->getB64Extension($imagen_b64);

        // Crear un nombre aleatorio para la imagen
        $nombre_imagen = "cliente$registro->customer_id/pedido$registro->dispatch-imagen$tipo.$imagen_extension";   
        // Usando el Storage guardar en el disco creado anteriormente y pasandole a 
        // la función "put" el nombre de la imagen y los datos de la imagen como 
        // segundo parametro
        Storage::disk('mmtrack')->put($nombre_imagen, $imagen); 
        
        $ruta = "/storage/mmtrack/$nombre_imagen";

        $editar_registro = OrderDeliveryDetail::find($registro->id);
        
        if ($tipo=="guia") {
            OrderDeliveryDetail::find($registro->id)->update(['imagen'=>$ruta]);
        
        }else {
            OrderDeliveryDetail::find($registro->id)->update(['imagen_bultos'=>$ruta]);

        }

        $editar_registro->save();
    }


    /**
     * metodo encargado de convertir una imagen b64 a una imagen
     */
    public function getB64Image($base64_image){  
        // Obtener el String base-64 de los datos         
        $image_service_str = substr($base64_image, strpos($base64_image, ",")+1);
        // Decodificar ese string y devolver los datos de la imagen        
        $image = base64_decode($image_service_str);   
        // Retornamos el string decodificado
        return $image; 
    }

    public function getB64Extension($base64_image, $full=null){  
        // Obtener mediante una expresión regular la extensión imagen y guardarla
        // en la variable "img_extension"        
        preg_match("/^data:image\/(.*);base64/i",$base64_image, $img_extension);   
        // Dependiendo si se pide la extensión completa o no retornar el arreglo con
        // los datos de la extensión en la posición 0 - 1
        return ($full) ?  $img_extension[0] : $img_extension[1];  
    }

    
}
