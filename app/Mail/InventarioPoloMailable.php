<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InventarioPoloMailable extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mails.inventarioPolo')
            ->from('no-reply@mym.com.pe', "M&M Repuestos y Servicios")
            ->subject('Stock a la fecha ' . date("d-m-Y"))
            ->attach($this->data["archivo_adjuntar"]);
    }
}
