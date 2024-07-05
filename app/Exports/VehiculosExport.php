<?php

namespace App\Exports;

use App\Models\MMTrack\Vehicles;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VehiculosExport implements FromCollection,WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    private $pedidos;

    public function __construct($pedidos) 
    {
        $this->pedidos = $pedidos;
    }

 
    public function collection()
    {
 
        return $this->pedidos;
    }

    public function headings(): array
    {
        return [
            "codigo_sucursal",
            "nombre_sucursal",
            "codigo_almacen",
            "nombre_almacen",
            "origen",
            "numero_orden",
            "numero_pedido",
            "codigo_usuario",
            "vendedor",
            "codigo_cliente",
            "rason_social",
            "numero_documento",
            "codigo_transportista",
            "transportista",
            "forma_pago",
            "metodo_paga",
            "condicion_pago",
            "tipo_documento",
            "nombre_documento",
            "fecha_documento",
            "hora_documento",
            "pedido_recibido",
            "pedido_recibido_codigo",
            "pedido_recibido_descripcion",
            "pedido_recibido_fecha",
            "pedido_recibido_hora",
            "pedido_recibido_usuario",
            "pedido_aprobado",
            "pedido_aprobado_codigo",
            "pedido_aprobado_descripcion",
            "pedido_aprobado_fecha",
            "pedido_aprobado_hora",
            "pedido_aprobado_usuario",
            "pedido_sacador",
            "pedido_sacador_codigo",
            "pedido_sacador_descripcion",
            "pedido_sacador_fecha",
            "pedido_sacador_hora",
            "pedido_sacador_usuario",
            "pedido_empaquetado",
            "pedido_empaquetado_codigo",
            "pedido_empaquetado_descripcion",
            "pedido_empaquetado_fecha",
            "pedido_empaquetado_hora",
            "pedido_empaquetado_usuario",
            "pedido_guia",
            "pedido_guia_codigo",
            "pedido_guia_descripcion",
            "pedido_guia_fecha",
            "pedido_guia_hora",
            "pedido_guia_usuario",
            "pedido_facturado",
            "pedido_facturado_codigo",
            "pedido_facturado_descripcion",
            "pedido_facturado_fecha",
            "pedido_facturado_hora",
            "pedido_facturado_usuario",
            "pedido_en_ruta",
            "pedido_en_ruta_codigo",
            "pedido_en_ruta_descripcion",
            "pedido_en_ruta_fecha",
            "pedido_en_ruta_hora",
            "pedido_en_ruta_usuario",
            "pedido_entregado",
            "pedido_entregado_codigo",
            "pedido_entregado_descripcion",
            "pedido_entregado_fecha",
            "pedido_entregado_hora",
            "pedido_entregado_usuario"
        ];
    }

}
