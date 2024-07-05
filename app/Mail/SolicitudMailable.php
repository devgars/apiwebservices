<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
class SolicitudMailable extends Mailable
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
        //return $this->view('view.name');
        return $this->view('mails.solicitudes')
        ->subject($this->solicitud["NumRquest"].' - Formato de Hoja de Reclamación de Libro de reclamaciones - '.$this->solicitud["TypeRequest"])
        ->attach(public_path($this->solicitud["FilePDF"]));
    }
}
