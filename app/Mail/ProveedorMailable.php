<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProveedorMailable extends Mailable
{
    use Queueable, SerializesModels;
    public $solicitud;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($solicitud)
    {
        $this->solicitud =  $solicitud;
    }
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        if($this->solicitud["prov_solution_proveedor"]==="NC"){
            return $this->view('mails.proveedor')
            ->subject($this->solicitud["NumRquest"].' - Datos de '.$this->solicitud["prov_subject"] .' del proveedor')
            ->attach(public_path($this->solicitud["prov_file"]));
        }else{
            return $this->view('mails.proveedor')
            ->subject($this->solicitud["NumRquest"].' - Datos de '.$this->solicitud["prov_subject"] .' del proveedor');
        }
    }
}
