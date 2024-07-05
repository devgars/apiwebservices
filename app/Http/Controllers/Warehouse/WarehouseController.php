<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Sync\Utilidades;
use App\Models\Customer;
use App\Models\Vistas\ConsignmentWarehouses;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Illuminate\Support\Facades\Mail;
use App\Mail\InventarioPoloMailable;

class WarehouseController extends Controller
{
    public function consignment_warehouse_inventory(Request $request)
    {
        try {
            /*

            $rules = [
                'warehouse_code' => 'required',
                'customer_code' => 'required',
                'company_code' => 'required'
            ];
            $messages = [
                'required' => 'El campo :attribute es obligatorio'
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }
            

            $warehouse_code = $request->warehouse_code;
            $customer_code = $request->customer_code;
            $company_code = $request->company_code;
            */
            $warehouse_code = '29';
            $customer_code = '';
            $company_code = '10';
            //$cliente = Customer::where('code', $customer_code)->first();
            $almacen = ConsignmentWarehouses::where('code', $warehouse_code)->first();

            $util = new Utilidades();
            $response = new \stdClass();
            $response->cabecera = $util->retorna_datos_establecimiento($almacen->id);
            $response->cabecera->fecha = date("d-m-Y H:i:s");
            $response->data = (array)$util->retorna_productos_oferta_grupo_polo();
            return response()->json($response, 200);
        } catch (\Exception $e) {
            return response()->json($e, 500);
        }
    }

    public function retorna_inventario_polo()
    {
        $warehouse_code = '29';
        $customer_code = '';
        $company_code = '10';
        $almacen = ConsignmentWarehouses::where('code', $warehouse_code)->first();

        $util = new Utilidades();
        $response = [];
        $response['cabecera'] = $util->retorna_datos_establecimiento($almacen->id);
        $response['data'] = $util->retorna_productos_oferta_grupo_polo();
        return $response;
    }

    public function enviar_correo_inventario_polo()
    {
        $AllData = $this->retorna_inventario_polo();

        $filePath = $this->generar_archivo_excel_inventario_polo($AllData);

        $data = [
            'archivo_adjuntar' => $filePath
        ];

        $to = [
            'jefe.almacen@polosac.pe',
            'greicy.marquina@grupopolo.pe',
            'practicante.logistica@perubusinternacional.pe',
            'stockpolohuaycan@mym.com.pe'
        ];

        $cc = [
            'stockpolohuaycan@mym.com.pe'
        ];

        $correo =  new InventarioPoloMailable($data);
        Mail::to($to)->cc($cc)->send($correo);
    }

    public function generar_archivo_excel_inventario_polo($AllData)
    {
        if (!is_countable($AllData['data']) || count($AllData['data']) == 0) {
            die("No hay datos que mostrar");
        }

        $Head = $AllData['cabecera'] ?? [];
        $Data = $AllData['data'] ?? [];
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        // encabezados
        $Hs = [
            'A4' => 'Cod.Art.',
            'B4' => 'Desc.Art.',
            'C4' => 'Línea',
            'D4' => 'Desc.Línea',
            'E4' => 'Origen',
            'F4' => 'Cod. Marca',
            'G4' => 'Marca',
            'H4' => 'Stock',
            'I4' => 'Precio Unit.',
            'J4' => 'Total'
        ];
        foreach ($Hs as $k => $v) {
            $sheet->setCellValue($k, $v);
        }
        // mapeo 
        $Ds = [
            'part_code' => 'A',
            'part_name' => 'B',
            'line_code' => 'C',
            'line_name' => 'D',
            'origin_code' => 'E',
            'trademark_code' => 'F',
            'trademark_name' => 'G',
            'stock' => 'H',
            'price' => 'I'
        ];
        $Row = 4;
        foreach ($Data as $d) {
            $Row++;
            foreach ($Ds as $k => $v) {
                if (strlen($k) > 0) {
                    if ($v == 'A' || $v == 'C' || $v == 'E' || $v == 'F') {
                        // textual
                        $sheet->setCellValueExplicit("$v$Row", $d->$k, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING2);
                    } else {
                        $sheet->setCellValue("$v$Row", $d->$k);
                    }
                }
            }
            $sheet->setCellValue('J' . $Row, "=H$Row*I$Row");
        }
        // totales
        $sheet->setCellValue('G' . ($Row + 1), "Tot. Items");
        $sheet->getStyle('G' . ($Row + 1))->getAlignment()->setHorizontal('right');
        $sheet->setCellValue('H' . ($Row + 1), "=SUM(H5:H$Row)");
        $sheet->setCellValue('I' . ($Row + 1), "Total $");
        $sheet->getStyle('I' . ($Row + 1))->getAlignment()->setHorizontal('right');
        $sheet->setCellValue('J' . ($Row + 1), "=SUM(J5:$Row)");
        $sheet->getStyle('G' . ($Row + 1) . ':G' . ($Row + 1))->getFont()->setBold(true);
        // decimales en montos
        $sheet->getStyle("I5:J" . ($Row + 1))->getNumberFormat()->setFormatCode('0.00');
        // color encabezado
        $sheet->getStyle('A4:J4')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF0000FF');
        $sheet->getStyle('A4:J4')->getFont()
            ->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE);
        // negritas abajo
        $sheet->getStyle('A4:J4')->getFont()->setBold(true);


        $sheet->mergeCells('C1:H1');
        $sheet->mergeCells('C2:H2');
        $logo = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
        $logo->setName('Logo');
        $logo->setDescription('Logo');
        $logo->setPath(base_path() . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "imagenes" . DIRECTORY_SEPARATOR . 'logo.png');
        $logo->setCoordinates('A1');
        $logo->setWidth(60);
        // $logo->setOffsetX(110);
        //$logo->setRotation(25);
        // $logo->getShadow()->setVisible(true);
        // $logo->getShadow()->setDirection(45);
        $logo->setWorksheet($spreadsheet->getActiveSheet());

        $sheet->setCellValue('B1', 'Almacén #' . $Head->code);
        $sheet->setCellValue('B2', $Head->name);
        $sheet->setCellValue('C1', $Head->description);
        $sheet->setCellValue('C2', 'Fecha del reporte: ' . date("Y-m-d H:i:s"));
        $sheet->getStyle('B1:J3')->getFont()->setBold(true);
        $sheet->getStyle('B1:J3')->getFont()->setSize(14);

        // auto ancho
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }

        // Write an .xlsx file  
        $filename = "INVENTARIO-POLO-HUAYCAN-AL-" . date("Y-m-d_H_i_s") . ".xls";
        //$filePath = __DIR__ . DIRECTORY_SEPARATOR . "xls" . DIRECTORY_SEPARATOR . $filename; // chmod 777
        $filePath = base_path() . DIRECTORY_SEPARATOR . "storage" . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "xls_polo" . DIRECTORY_SEPARATOR . $filename;

        $writer = new Xls($spreadsheet);

        try {
            $writer->save($filePath);
            return $filePath;
        } catch (Exception $e) {
            exit($e->getMessage());
        }
    }
}
