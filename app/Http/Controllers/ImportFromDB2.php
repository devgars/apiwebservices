<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpParser\Builder\Function_;

use App\Models\Customers\CustomerGroup;
use App\Models\Customers\CustomerPaymentMethod;
use App\Models\BlackListCustomer;
use App\Models\PartPartDetail;
use App\Models\PartDetailWarehouse;
use App\Models\OrdOrder;
use App\Models\OrdOrderDetail;
use App\Http\Controllers\Sync\Utilidades;
use App\Models\FiscalDocument;
use App\Models\Provider;
use App\Models\PurchaseOrder;
use App\Models\CustomerCreditNote;
use App\Models\UserUser;
use App\Models\CustomerContact;
use App\Models\VehModel;
use App\Models\VehVehicle;
use App\Models\VehPartVehicle;
use App\Models\PartDetailImage;


use App\Http\Controllers\Sync\SyncCustomer;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\PartOffers\PartOfferGroup;
use App\Models\PartOffers\PartOfferGroupDetail;
use stdClass;

class ImportFromDB2 extends Controller
{
    private $codCia = '10';


    public function import_ubigeo()
    {
        die('LISTO!');
        $pais = 163;

        // ---- DEPARTAMENTOS ---- //
        $ubigeo_type_id = 2; //DPTO
        $arrayWhere = array(
            ['EUCODTBL', '=', '19'],
            ['EUCODELE', '<>', '26'],
            ['EUCODELE', '<>', '99'],
            ['EUSTS', '=', 'A'],
        );
        if ($registros = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMEUREP', $arrayWhere)) {
            //echo '<pre>';
            //die(print_r($registros));
            foreach ($registros as $fila) {
                $arrayInsert = array(
                    'ubigeo_type_id' => $ubigeo_type_id,
                    'code' => $fila->eucodele,
                    'abrv' => utf8_encode(strtoupper(trim($fila->eudscabr))),
                    'name' => utf8_encode(strtoupper(trim($fila->eudsclar))),
                    'parent_ubigeo_id' => $pais,
                );
                //echo '<pre>';
                //print_r($arrayInsert);
                $this->inserta_into_tabla('ubigeos', $arrayInsert);
            }
            echo '<br> FIN MIGRACIÓN - DEPARTAMENTOS';
        }
        // ---- DEPARTAMENTOS ---- //


        // ---- PROVINCIAS ---- //
        $ubigeo_type_id = 3; //PROVINCIAS
        $arrayWhere = array(
            ['BIDEPART', '<>', '99'],
            ['BISTS', '=', 'A'],
        );
        if ($registros = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMBIREP', $arrayWhere)) {

            foreach ($registros as $fila) {
                //echo '<pre>';
                //print_r($fila);
                $arrayWhere = array(
                    ['ubigeo_type_id', '=', 2],
                    ['code', '=', $fila->bidepart]
                );
                $parent_reg = $this->selecciona_fila_from_tabla('ubigeos', $arrayWhere);
                //echo '<pre>';
                ///die(print_r($parent_reg));
                $arrayInsert = array(
                    'ubigeo_type_id' => $ubigeo_type_id,
                    'code' => $fila->biprovin,
                    'abrv' => utf8_encode(strtoupper(trim($fila->biprovin))),
                    'name' => utf8_encode(strtoupper(trim($fila->bidsclar))),
                    'parent_ubigeo_id' => $parent_reg->id,
                );
                //echo '<pre>';
                //print_r($arrayInsert);
                $this->inserta_into_tabla('ubigeos', $arrayInsert);
            }
            echo '<br> FIN MIGRACIÓN - PROVINCIAS';
        }
        // ---- PROVINCIAS ---- //


        // ---- DISTRITOS ---- //
        $ubigeo_type_id = 4; //DISTRITOS
        $arrayWhere = array(
            ['BJDISTRI', '<>', '99'],
            ['BJSTS', '=', 'A'],
        );
        if ($registros = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMBJREP', $arrayWhere)) {
            //echo '<pre>';
            //die(print_r($registros));
            foreach ($registros as $fila) {
                //echo '<pre>';
                //print_r($fila);

                $dpto = $this->selecciona_fila_from_tabla('ubigeos', array('ubigeo_type_id' => 2, 'code' => $fila->bjdepart));
                $arrayWhere = array(
                    ['ubigeo_type_id', '=', 3],
                    ['code', '=', $fila->bjprovin],
                    ['parent_ubigeo_id', '=', $dpto->id],
                );
                //echo '<pre>';
                //die(print_r($dpto));
                $parent_reg = $this->selecciona_fila_from_tabla('ubigeos', $arrayWhere);
                //$registro_distrito = $this->retorna_datos_provincia($fila->bjprovin, $fila->bjdepart);
                //echo '<pre>';
                //print_r($parent_reg);
                //exit;
                $arrayInsert = array(
                    'ubigeo_type_id' => $ubigeo_type_id,
                    'code' => $fila->bjdistri,
                    'abrv' => utf8_encode(strtoupper(trim($fila->bjdistri))),
                    'name' => utf8_encode(strtoupper(trim($fila->bjdsclar))),
                    'parent_ubigeo_id' => $parent_reg->id,
                );
                //echo '<pre>';
                //print_r($arrayInsert);
                $this->inserta_into_tabla('ubigeos', $arrayInsert);
            }
            echo '<br> FIN MIGRACIÓN - DISTRITOS';
        }
        // ---- DISTRITOS ---- //
    }

    public function import_generics()
    {
        die('LISTO!');
        // ---- TIPOS DE EMPRESA (01) ---- //
        $arrayWhere = array(
            ['EUCODTBL', '=', 'BB'],
            ['EUSTS', '=', 'A'],
        );
        if ($registros = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMEUREP', $arrayWhere)) {
            $resource_id = 1;
            foreach ($registros as $fila) {
                $arrayWhere = array(
                    ['resource_id', '=', $resource_id],
                    ['name', '=', utf8_encode(strtoupper(trim($fila->eudsccor)))],
                );
                if ($this->selecciona_fila_from_tabla('gen_resource_details', $arrayWhere)) {
                    echo '<br>REGISTRO EXISTE: ';
                    echo "$fila->eucodele $fila->eudscabr utf8_encode(strtoupper(trim($fila->eudsccor)))";
                } else {
                    $arrayInsert = array(
                        'resource_id' => $resource_id,
                        'code' => $fila->eucodele,
                        'abrv' => $fila->eudscabr,
                        'name' => utf8_encode(strtoupper(trim($fila->eudsccor))),
                        'description' => utf8_encode(strtoupper(trim($fila->eudsclar)))
                    );
                    //echo '<pre>';
                    //print_r($arrayInsert);
                    $this->inserta_into_tabla('gen_resource_details', $arrayInsert);
                }
            }
            echo '<br> FIN MIGRACIÓN - TIPOS DE EMPRESA (01)';
        }
        // ---- TIPOS DE EMPRESA (01) ---- //

        // ---- TIPOS DE IDENTIFICACIÓN (02) ---- //
        $arrayWhere = array(
            ['EUCODTBL', '=', '26'],
            ['EUSTS', '=', 'A'],
        );
        if ($registros = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMEUREP', $arrayWhere)) {
            $resource_id = 2;
            foreach ($registros as $fila) {
                $arrayWhere = array(
                    ['resource_id', '=', $resource_id],
                    ['name', '=', utf8_encode(strtoupper(trim($fila->eudsccor)))],
                );
                if ($this->selecciona_fila_from_tabla('gen_resource_details', $arrayWhere)) {
                    echo '<br>REGISTRO EXISTE: ';
                    echo "$fila->eucodele $fila->eudscabr utf8_encode(strtoupper(trim($fila->eudsccor)))";
                } else {
                    $arrayInsert = array(
                        'resource_id' => $resource_id,
                        'code' => $fila->eucodele,
                        'abrv' => $fila->eudscabr,
                        'name' => utf8_encode(strtoupper(trim($fila->eudsccor))),
                        'description' => utf8_encode(strtoupper(trim($fila->eudsclar)))
                    );
                    //echo '<pre>';
                    //print_r($arrayInsert);
                    $this->inserta_into_tabla('gen_resource_details', $arrayInsert);
                }
            }
            echo '<br> FIN MIGRACIÓN - TIPOS DE IDENTIFICACIÓN (02)';
        }
        // ---- TIPOS DE IDENTIFICACIÓN (02) ---- //


        // ---- TIPOS DE DIRECCIONES - CLIENTES (03) ---- //
        $arrayWhere = array(
            ['EUCODTBL', '=', '15'],
            ['EUSTS', '=', 'A'],
        );
        if ($registros = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMEUREP', $arrayWhere)) {
            $resource_id = 3;
            foreach ($registros as $fila) {
                $arrayWhere = array(
                    ['resource_id', '=', $resource_id],
                    ['name', '=', utf8_encode(strtoupper(trim($fila->eudsccor)))],
                );
                if ($this->selecciona_fila_from_tabla('gen_resource_details', $arrayWhere)) {
                    echo '<br>REGISTRO EXISTE: ';
                    echo "$fila->eucodele $fila->eudscabr utf8_encode(strtoupper(trim($fila->eudsccor)))";
                } else {
                    $arrayInsert = array(
                        'resource_id' => $resource_id,
                        'code' => $fila->eucodele,
                        'abrv' => $fila->eudscabr,
                        'name' => utf8_encode(strtoupper(trim($fila->eudsccor))),
                        'description' => utf8_encode(strtoupper(trim($fila->eudsclar)))
                    );
                    //echo '<pre>';
                    //print_r($arrayInsert);
                    $this->inserta_into_tabla('gen_resource_details', $arrayInsert);
                }
            }
            echo '<br> FIN MIGRACIÓN - TIPOS DE DIRECCIONES - CLIENTES (03)';
        }
        // ---- TIPOS DE DIRECCIONES - CLIENTES (03) ---- //


        // ---- TIPOS DE VIA (04) ---- //
        $arrayWhere = array(
            ['EUCODTBL', '=', '17'],
            ['EUSTS', '=', 'A'],
        );
        if ($registros = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMEUREP', $arrayWhere)) {
            $resource_id = 4;
            foreach ($registros as $fila) {
                $arrayWhere = array(
                    ['resource_id', '=', $resource_id],
                    ['name', '=', utf8_encode(strtoupper(trim($fila->eudsccor)))],
                );
                if ($this->selecciona_fila_from_tabla('gen_resource_details', $arrayWhere)) {
                    echo '<br>REGISTRO EXISTE: ';
                    echo "$fila->eucodele $fila->eudscabr utf8_encode(strtoupper(trim($fila->eudsccor)))";
                } else {
                    $arrayInsert = array(
                        'resource_id' => $resource_id,
                        'code' => $fila->eucodele,
                        'abrv' => $fila->eudscabr,
                        'name' => utf8_encode(strtoupper(trim($fila->eudsccor))),
                        'description' => utf8_encode(strtoupper(trim($fila->eudsclar)))
                    );
                    //echo '<pre>';
                    //print_r($arrayInsert);
                    $this->inserta_into_tabla('gen_resource_details', $arrayInsert);
                }
            }
            echo '<br> FIN MIGRACIÓN - TIPOS DE VIA (04)';
        }
        // ---- TIPOS DE VIA (04) ---- //

        // ---- TIPOS DE ZONA (05) ---- //
        $arrayWhere = array(
            ['EUCODTBL', '=', '18'],
            ['EUSTS', '=', 'A'],
        );
        if ($registros = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMEUREP', $arrayWhere)) {
            $resource_id = 5;
            foreach ($registros as $fila) {
                $arrayWhere = array(
                    ['resource_id', '=', $resource_id],
                    ['name', '=', utf8_encode(strtoupper(trim($fila->eudsccor)))],
                );
                if ($this->selecciona_fila_from_tabla('gen_resource_details', $arrayWhere)) {
                    echo '<br>REGISTRO EXISTE: ';
                    echo "$fila->eucodele $fila->eudscabr utf8_encode(strtoupper(trim($fila->eudsccor)))";
                } else {
                    $arrayInsert = array(
                        'resource_id' => $resource_id,
                        'code' => $fila->eucodele,
                        'abrv' => $fila->eudscabr,
                        'name' => utf8_encode(strtoupper(trim($fila->eudsccor))),
                        'description' => utf8_encode(strtoupper(trim($fila->eudsclar)))
                    );
                    //echo '<pre>';
                    //print_r($arrayInsert);
                    $this->inserta_into_tabla('gen_resource_details', $arrayInsert);
                }
            }
            echo '<br> FIN MIGRACIÓN - TIPOS DE ZONA (05)';
        }
        // ---- TIPOS DE ZONA (05) ---- //


        // ---- ORÍGENES DE  PRODUCTO (06) ---- //
        $arrayWhere = array(
            ['EUCODTBL', '=', '11'],
            ['EUSTS', '=', 'A'],
        );
        if ($registros = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMEUREP', $arrayWhere)) {
            $resource_id = 6;
            foreach ($registros as $fila) {
                $arrayWhere = array(
                    ['resource_id', '=', $resource_id],
                    ['name', '=', utf8_encode(strtoupper(trim($fila->eudsccor)))],
                );
                if ($this->selecciona_fila_from_tabla('gen_resource_details', $arrayWhere)) {
                    echo '<br>REGISTRO EXISTE: ';
                    echo "$fila->eucodele $fila->eudscabr utf8_encode(strtoupper(trim($fila->eudsccor)))";
                } else {
                    $arrayInsert = array(
                        'resource_id' => $resource_id,
                        'code' => $fila->eucodele,
                        'abrv' => $fila->eudscabr,
                        'name' => utf8_encode(strtoupper(trim($fila->eudsccor))),
                        'description' => utf8_encode(strtoupper(trim($fila->eudsclar)))
                    );
                    //echo '<pre>';
                    //print_r($arrayInsert);
                    $this->inserta_into_tabla('gen_resource_details', $arrayInsert);
                }
            }
            echo '<br> FIN MIGRACIÓN - ORÍGENES DE  PRODUCTO (06)';
        }
        // ---- ORÍGENES DE  PRODUCTO (06) ---- //


        // ---- LÍNEAS DE PRODUCTOS (07) ---- //
        $arrayWhere = array(
            ['EUCODTBL', '=', '12'],
            ['EUSTS', '=', 'A'],
        );
        if ($registros = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMEUREP', $arrayWhere)) {
            $resource_id = 7;
            foreach ($registros as $fila) {
                $arrayWhere = array(
                    ['resource_id', '=', $resource_id],
                    ['name', '=', utf8_encode(strtoupper(trim($fila->eudsccor)))],
                );
                if ($this->selecciona_fila_from_tabla('gen_resource_details', $arrayWhere)) {
                    echo '<br>REGISTRO EXISTE: ';
                    echo "$fila->eucodele $fila->eudscabr utf8_encode(strtoupper(trim($fila->eudsccor)))";
                } else {
                    $arrayInsert = array(
                        'resource_id' => $resource_id,
                        'code' => $fila->eucodele,
                        'abrv' => $fila->eudscabr,
                        'name' => utf8_encode(strtoupper(trim($fila->eudsccor))),
                        'description' => utf8_encode(strtoupper(trim($fila->eudsclar)))
                    );
                    //echo '<pre>';
                    //print_r($arrayInsert);
                    $this->inserta_into_tabla('gen_resource_details', $arrayInsert);
                }
            }
            echo '<br> FIN MIGRACIÓN - LÍNEAS DE PRODUCTOS (07)';
        }
        // ---- LÍNEAS DE PRODUCTOS (07) ---- //


        // ---- CARGOS (08) ---- //
        $arrayWhere = array(
            ['EUCODTBL', '=', 'CG'],
            ['EUSTS', '=', 'A'],
        );
        if ($registros = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMEUREP', $arrayWhere)) {
            $resource_id = 8;
            foreach ($registros as $fila) {
                $arrayWhere = array(
                    ['resource_id', '=', $resource_id],
                    ['name', '=', utf8_encode(strtoupper(trim($fila->eudsccor)))],
                );
                if ($this->selecciona_fila_from_tabla('gen_resource_details', $arrayWhere)) {
                    echo '<br>REGISTRO EXISTE: ';
                    echo "$fila->eucodele $fila->eudscabr utf8_encode(strtoupper(trim($fila->eudsccor)))";
                } else {
                    $arrayInsert = array(
                        'resource_id' => $resource_id,
                        'code' => $fila->eucodele,
                        'abrv' => $fila->eudscabr,
                        'name' => utf8_encode(strtoupper(trim($fila->eudsccor))),
                        'description' => utf8_encode(strtoupper(trim($fila->eudsclar)))
                    );
                    //echo '<pre>';
                    //print_r($arrayInsert);
                    $this->inserta_into_tabla('gen_resource_details', $arrayInsert);
                }
            }
            echo '<br> FIN MIGRACIÓN - CARGOS (08)';
        }
        // ---- CARGOS (08) ---- //


        // ---- SISTEMAS (09) ---- //
        $arrayWhere = array(
            ['FZSTS', '=', 'A'],
        );
        if ($registros = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMFZREP', $arrayWhere)) {
            $resource_id = 9;
            foreach ($registros as $fila) {
                $arrayWhere = array(
                    ['resource_id', '=', $resource_id],
                    ['name', '=', utf8_encode(strtoupper(trim($fila->fzdscsis)))],
                );
                if ($this->selecciona_fila_from_tabla('gen_resource_details', $arrayWhere)) {
                    echo '<br>REGISTRO EXISTE: ';
                    echo "$fila->fzcodsis utf8_encode(strtoupper(trim($fila->fzdscsis)))";
                } else {
                    $arrayInsert = array(
                        'resource_id' => $resource_id,
                        'code' => $fila->fzcodsis,
                        'abrv' => '',
                        'name' => utf8_encode(strtoupper(trim($fila->fzdscsis))),
                        'description' => utf8_encode(strtoupper(trim($fila->fzdscsis)))
                    );
                    //echo '<pre>';
                    //print_r($arrayInsert);
                    $this->inserta_into_tabla('gen_resource_details', $arrayInsert);
                }
            }
            echo '<br> FIN MIGRACIÓN - SISTEMAS (09)';
        }
        // ---- SISTEMAS (09) ---- //


        // ---- SUBSISTEMAS (10) ---- //
        $arrayWhere = array(
            ['PZSTS', '=', 'A'],
        );
        if ($registros = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMPZREP', $arrayWhere)) {
            $resource_id = 10;
            $system_resource_id = 9;
            foreach ($registros as $fila) {
                //echo '<pre>';
                ///die(print_r($fila));
                $arrayWhere = array(
                    ['resource_id', '=', $system_resource_id],
                    ['code', '=', $fila->pzcodsis],
                );
                $system_resource = $this->selecciona_fila_from_tabla('gen_resource_details', $arrayWhere);

                $arrayWhere = array(
                    ['resource_id', '=', $resource_id],
                    ['name', '=', utf8_encode(strtoupper(trim($fila->pzdscsbs)))],
                    ['parent_resource_detail_id', '=', $system_resource->id],
                );
                if ($this->selecciona_fila_from_tabla('gen_resource_details', $arrayWhere)) {
                    echo '<br>REGISTRO EXISTE: ';
                    echo "$fila->pzcodsbs utf8_encode(strtoupper(trim($fila->pzdscsbs)))";
                } else {
                    $arrayInsert = array(
                        'resource_id' => $resource_id,
                        'code' => $fila->pzcodsbs,
                        'abrv' => '',
                        'name' => utf8_encode(strtoupper(trim($fila->pzdscsbs))),
                        'description' => utf8_encode(strtoupper(trim($fila->pzdscsbs))),
                        'parent_resource_detail_id' => $system_resource->id
                    );
                    //echo '<pre>';
                    //print_r($arrayInsert);
                    $this->inserta_into_tabla('gen_resource_details', $arrayInsert);
                }
            }
            echo '<br> FIN MIGRACIÓN - SUBSISTEMAS (10)';
        }
        // ---- SUBSISTEMAS (10) ---- //





        // ---- MONEDAS (12) ---- //
        $arrayWhere = array(
            ['EUCODTBL', '=', '35'],
            ['EUSTS', '=', 'A'],
        );
        if ($registros = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMEUREP', $arrayWhere)) {
            $resource_id = 12;
            foreach ($registros as $fila) {
                $arrayWhere = array(
                    ['resource_id', '=', $resource_id],
                    ['name', '=', utf8_encode(strtoupper(trim($fila->eudsccor)))],
                );
                if ($this->selecciona_fila_from_tabla('gen_resource_details', $arrayWhere)) {
                    echo '<br>REGISTRO EXISTE: ';
                    echo "$fila->eucodele $fila->eudscabr utf8_encode(strtoupper(trim($fila->eudsccor)))";
                } else {
                    $arrayInsert = array(
                        'resource_id' => $resource_id,
                        'code' => $fila->eucodele,
                        'abrv' => $fila->eudscabr,
                        'name' => utf8_encode(strtoupper(trim($fila->eudsccor))),
                        'description' => utf8_encode(strtoupper(trim($fila->eudsclar)))
                    );
                    //echo '<pre>';
                    //print_r($arrayInsert);
                    $this->inserta_into_tabla('gen_resource_details', $arrayInsert);
                }
            }
            echo '<br> FIN MIGRACIÓN - MONEDAS (12)';
        }
        // ---- MONEDAS (12) ---- //


        // ---- MOTIVO DEVOLUCIÓN MERCADERÍA (13) ---- //
        $arrayWhere = array(
            ['EUCODTBL', '=', 'ZY'],
            ['EUSTS', '=', 'A'],
        );
        if ($registros = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMEUREP', $arrayWhere)) {
            $resource_id = 13;
            foreach ($registros as $fila) {
                $arrayWhere = array(
                    ['resource_id', '=', $resource_id],
                    ['name', '=', utf8_encode(strtoupper(trim($fila->eudsccor)))],
                );
                if ($this->selecciona_fila_from_tabla('gen_resource_details', $arrayWhere)) {
                    echo '<br>REGISTRO EXISTE: ';
                    echo "$fila->eucodele $fila->eudscabr utf8_encode(strtoupper(trim($fila->eudsccor)))";
                } else {
                    $arrayInsert = array(
                        'resource_id' => $resource_id,
                        'code' => $fila->eucodele,
                        'abrv' => $fila->eudscabr,
                        'name' => utf8_encode(strtoupper(trim($fila->eudsclar))),
                        'description' => utf8_encode(strtoupper(trim($fila->eudsclar)))
                    );
                    //echo '<pre>';
                    //print_r($arrayInsert);
                    $this->inserta_into_tabla('gen_resource_details', $arrayInsert);
                }
            }
            echo '<br> FIN MIGRACIÓN - MOTIVO DEVOLUCIÓN MERCADERÍA (13)';
        }
        // ---- MOTIVO DEVOLUCIÓN MERCADERÍA (13) ---- //


        // ---- LÍNEAS MACRO (14) ---- //
        $arrayWhere = array(
            ['EUCODTBL', '=', 'LM'],
            ['EUSTS', '=', 'A'],
        );
        if ($registros = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMEUREP', $arrayWhere)) {
            $resource_id = 14;
            foreach ($registros as $fila) {
                $arrayWhere = array(
                    ['resource_id', '=', $resource_id],
                    ['name', '=', utf8_encode(strtoupper(trim($fila->eudsccor)))],
                );
                if ($this->selecciona_fila_from_tabla('gen_resource_details', $arrayWhere)) {
                    echo '<br>REGISTRO EXISTE: ';
                    echo "$fila->eucodele $fila->eudscabr utf8_encode(strtoupper(trim($fila->eudsccor)))";
                } else {
                    $arrayInsert = array(
                        'resource_id' => $resource_id,
                        'code' => $fila->eucodele,
                        'abrv' => $fila->eudscabr,
                        'name' => utf8_encode(strtoupper(trim($fila->eudsccor))),
                        'description' => utf8_encode(strtoupper(trim($fila->eudsclar)))
                    );
                    //echo '<pre>';
                    //print_r($arrayInsert);
                    $this->inserta_into_tabla('gen_resource_details', $arrayInsert);
                }
            }
            echo '<br> FIN MIGRACIÓN - LÍNEAS MACRO (14)';
        }
        // ---- LÍNEAS MACRO (14) ---- //



        // ---- TIPOS DE VEHÍCULOS (16) ---- //
        $arrayWhere = array(
            ['EUCODTBL', '=', '87'],
            ['EUSTS', '=', 'A'],
        );
        if ($registros = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMEUREP', $arrayWhere)) {
            $resource_id = 16;
            foreach ($registros as $fila) {
                $arrayWhere = array(
                    ['resource_id', '=', $resource_id],
                    ['name', '=', utf8_encode(strtoupper(trim($fila->eudsccor)))],
                );
                if ($this->selecciona_fila_from_tabla('gen_resource_details', $arrayWhere)) {
                    echo '<br>REGISTRO EXISTE: ';
                    echo "$fila->eucodele $fila->eudscabr utf8_encode(strtoupper(trim($fila->eudsccor)))";
                } else {
                    $arrayInsert = array(
                        'resource_id' => $resource_id,
                        'code' => $fila->eucodele,
                        'abrv' => $fila->eudscabr,
                        'name' => utf8_encode(strtoupper(trim($fila->eudsccor))),
                        'description' => utf8_encode(strtoupper(trim($fila->eudsclar)))
                    );
                    //echo '<pre>';
                    //print_r($arrayInsert);
                    $this->inserta_into_tabla('gen_resource_details', $arrayInsert);
                }
            }
            echo '<br> FIN MIGRACIÓN - TIPOS DE VEHÍCULOS (16)';
        }
        // ---- TIPOS DE VEHÍCULOS (16) ---- //


        // ---- CLASIFICACIÓN DE CLIENTES (18) ---- //
        $arrayWhere = array(
            ['EUCODTBL', '=', '06'],
            ['EUSTS', '=', 'A'],
        );
        if ($registros = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMEUREP', $arrayWhere)) {
            $resource_id = 18;
            foreach ($registros as $fila) {
                $arrayWhere = array(
                    ['resource_id', '=', $resource_id],
                    ['name', '=', utf8_encode(strtoupper(trim($fila->eudsccor)))],
                );
                if ($this->selecciona_fila_from_tabla('gen_resource_details', $arrayWhere)) {
                    echo '<br>REGISTRO EXISTE: ';
                    echo "$fila->eucodele $fila->eudscabr utf8_encode(strtoupper(trim($fila->eudsccor)))";
                } else {
                    $arrayInsert = array(
                        'resource_id' => $resource_id,
                        'code' => $fila->eucodele,
                        'abrv' => $fila->eudscabr,
                        'name' => utf8_encode(strtoupper(trim($fila->eudsccor))),
                        'description' => utf8_encode(strtoupper(trim($fila->eudsclar)))
                    );
                    //echo '<pre>';
                    //print_r($arrayInsert);
                    $this->inserta_into_tabla('gen_resource_details', $arrayInsert);
                }
            }
            echo '<br> FIN MIGRACIÓN - CLASIFICACIÓN DE CLIENTES (18)';
        }
        // ---- CLASIFICACIÓN DE CLIENTES (18) ---- //

        // ---- ESTADOS UNIDADES DE TRANSPORTE (17) ---- //
        $resource_id = 17;
        $arrayInsert = array(
            [
                'resource_id' => $resource_id,
                'code' => '01',
                'abrv' => 'DISPO',
                'name' => 'DISPONIBILE',
                'description' => 'UNIDAD DISPONIBILE'
            ],
            [
                'resource_id' => $resource_id,
                'code' => '02',
                'abrv' => 'RUTA',
                'name' => 'EN RUTA',
                'description' => 'EN RUTA'
            ],
            [
                'resource_id' => $resource_id,
                'code' => '03',
                'abrv' => 'RETORNO',
                'name' => 'DE RETORNO AL CD',
                'description' => 'DE RETORNO AL CD'
            ],
            [
                'resource_id' => $resource_id,
                'code' => '04',
                'abrv' => 'MANT',
                'name' => 'MANTENIMIENTO',
                'description' => 'MANTENIMIENTO'
            ],
            [
                'resource_id' => $resource_id,
                'code' => '05',
                'abrv' => 'OUT',
                'name' => 'FUERA DE SERVICIO',
                'description' => 'FUERA DE SERVICIO'
            ],
        );
        DB::table('gen_resource_details')->insert($arrayInsert);
        echo '<br> ESTADOS UNIDADES DE TRANSPORTE (17)';
        // ---- ESTADOS UNIDADES DE TRANSPORTE (17) ---- //



        // ---- TIPOS DE VEHÍCULOS - INTERNOS (15) ---- //
        $resource_id = 15;
        $arrayInsert = array(
            [
                'resource_id' => $resource_id,
                'code' => 'MOTO',
                'abrv' => 'MOTO',
                'name' => 'MOTOS',
                'description' => 'MOTOS'
            ],
            [
                'resource_id' => $resource_id,
                'code' => 'COMBI',
                'abrv' => 'COMBI',
                'name' => 'COMBI',
                'description' => 'COMBI'
            ],
            [
                'resource_id' => $resource_id,
                'code' => 'CAMION',
                'abrv' => 'CAMION',
                'name' => 'CAMIÓN',
                'description' => 'CAMIÓN'
            ]
        );
        DB::table('gen_resource_details')->insert($arrayInsert);
        echo '<br> FIN MIGRACIÓN - TIPOS DE VEHÍCULOS - INTERNOS (15)';
        // ---- TIPOS DE VEHÍCULOS - INTERNOS (15) ---- //
    }

    public function import_clientes(Request $request)
    {
        // ini_set('max_execution_time', '300');

        $offset = ($request->offset) ? intval($request->offset) : 0;
        $limit = 5000;
        //Tipo
        $whereInField = 'resource_id';
        $whereInArray = array(1, 2, 3, 4, 5);
        $arraySelect = ['id', 'code', 'name', 'resource_id'];
        $array_tipos = $this->selecciona_from_tabla_where_in('gen_resource_details', $whereInField, $whereInArray, $arraySelect);
        //$cantidad_clientes = $this->retorna_total_filas_tabla('mmakrep_libprddat');
        $cantidad_clientes = DB::connection('ibmi')->table('LIBPRDDAT.MMAKREP')->count();
        $datos_clientes = $this->retorna_datos_clientes_as400($offset, $limit);
        echo "<br>Total Clientes: $cantidad_clientes <br>";
        echo "Desde: $offset <br>";
        echo "Registros: $limit <br>";
        $i = ($offset - 1);

        foreach ($datos_clientes as $cliente) {
            $i++;

            $arrayWhere = array('code' => trim($cliente->akcodcli));
            if (!$datos_cliente = $this->selecciona_fila_from_tabla('customers', $arrayWhere)) {
                $tipo_persona_id = $this->busca_datos_vector($cliente->aktipemp, 1, $array_tipos);
                $tipo_identificacion_id = $this->busca_datos_vector($cliente->aktipide, 2, $array_tipos);
                $tipo_identificacion_id = ($tipo_identificacion_id) ? $tipo_identificacion_id : (($tipo_persona_id == 2) ? 6 : 5);
                $tipo_persona_id = ($tipo_persona_id) ? $tipo_persona_id : (($tipo_identificacion_id == 5) ? 1 : 2);
                $pais_id = ($cliente->akcodpai === '001') ? 163 : 1;
                $ident_natural = trim($cliente->aknroide);
                $ident_juridica = trim($cliente->ifnvoruc);
                $numero_identificacion = ($cliente->aktipide === '01' && strlen($ident_natural) > 0) ? $ident_natural : $ident_juridica;
                if (empty($numero_identificacion)) {
                    $numero_identificacion = $cliente->akcodcli;
                    //die('SIN DATOS DE RUC NI DNI');
                }

                $arrayInsert = array(
                    'code' => strtoupper(trim($cliente->akcodcli)),
                    'name_social_reason' => strtoupper(trim(utf8_encode($cliente->akrazsoc))),
                    'tradename' => strtoupper(trim(utf8_encode($cliente->aknomcom))),
                    'ruc_code_old' => strtoupper(trim($cliente->aknroruc)),
                    'company_type_id' => $tipo_persona_id,
                    'document_type_id' => $tipo_identificacion_id,
                    'document_number' => $numero_identificacion,
                    'country_id' => $pais_id,
                    'client_class' => strtoupper(trim($cliente->akclsclt)),
                    'reg_date' => trim($cliente->akfecins),
                    'capital_amount' => $cliente->akimpcso,
                    'tax_condition' => strtoupper(trim($cliente->akcndtrb)),
                    'currency_code' => strtoupper(trim($cliente->akcodmon)),
                    'max_credit_limit' => $cliente->akimplmt,
                    'consumption_amount' => $cliente->akimpcsm,
                    'sales_block' => strtoupper(trim($cliente->akblqvta)),
                    'credit_block' => strtoupper(trim($cliente->akblqcrd)),
                    'reg_status' => (strtoupper(trim($cliente->aksts)) == 'A') ? 1 : 0
                );
                echo '<pre>';
                (print_r($arrayInsert));
                $cliente_id = $this->inserta_into_tabla('customers', $arrayInsert);
                echo "<br>($i) Cliente registrado: $cliente->akcodcli";
                $arrayWhere = array('id' => $cliente_id);
                $datos_cliente = $this->selecciona_fila_from_tabla('customers', $arrayWhere);
            } else {
                echo "<br>($i) Cliente ya existe: $cliente->akcodcli";
            }



            $arrayWhere = array(
                'alcodcli' => $datos_cliente->code
            );
            $cliente->direcciones = $this->selecciona_from_tabla_db2('libprddat.mmalrep', $arrayWhere);

            foreach ($cliente->direcciones as $direccion) {

                if ($datos_cliente->country_id == 163) {
                    $arrayWhere = array(
                        ['dpto_code', '=', $direccion->aldepart],
                        ['prov_code', '=', $direccion->alprovin],
                        ['dist_code', '=', $direccion->aldistri],
                    );
                    if ($region = $this->selecciona_fila_from_tabla('dist_prov_dpto_peru', $arrayWhere))
                        $distrito_id = $region->dist_id;
                    else $distrito_id = 1807;
                    if (!$tipo_direccion_id = $this->busca_datos_vector($direccion->altipdir, 3, $array_tipos)) $tipo_direccion_id = 11; //TIPO DIR LEGAL
                    $tipo_via_id = $this->busca_datos_vector($direccion->alviadir, 4, $array_tipos);
                    $tipo_zona_id = $this->busca_datos_vector($direccion->alzondir, 5, $array_tipos);
                    //echo '<pre>';
                    //die(print_r($region));
                }

                $arrayWhere = array(
                    ['customer_id', '=', $datos_cliente->id],
                    ['address_order', '=', $direccion->alitem01],
                );
                /*
                ['address_type_id', '=', $tipo_direccion_id],
                    ['road_type_id', '=', $tipo_via_id],
                    ['number', '=', strtoupper(trim($direccion->alnrodir))],
                    ['zone_type_id', '=', $tipo_zona_id],
                    ['region_id', '=', $distrito_id],
                    ['road_name', '=', strtoupper(trim($direccion->aldscdir))],
                     */
                if (!$datos_direccion_cliente = $this->selecciona_fila_from_tabla('customer_addresses', $arrayWhere)) {
                    $arrayWhere = array('customer_id' => $datos_cliente->id);
                    /*
                    $address_order = intval($this->selecciona_max_from_tabla('customer_addresses', 'address_order', $arrayWhere));
                    $address_order += 1;
                    //die("O: $orden");
                    */
                    $address_order = $direccion->alitem01;
                    $arrayInsert = array(
                        'customer_id' => $datos_cliente->id,
                        'country_id' => $datos_cliente->country_id,
                        'address_type_id' => $tipo_direccion_id,
                        'address_order' => $address_order,
                        'road_type_id' => $tipo_via_id,
                        'road_name' => strtoupper(trim(utf8_encode($direccion->aldscdir))),
                        'number' => strtoupper(trim(($direccion->alnrodir))),
                        'apartment' => strtoupper(trim(utf8_encode($direccion->alnrodpt))),
                        'floor' => strtoupper(trim($direccion->alnropso)),
                        'block' => strtoupper(trim($direccion->alnrodir)),
                        'allotment' => strtoupper(trim($direccion->alnrolte)),
                        'zone_type_id' => $tipo_zona_id,
                        'zone_name' => strtoupper(trim(utf8_encode($direccion->aldsczdr))),
                        'region_id' => $distrito_id,
                        'contact_name' => strtoupper(trim(utf8_encode($direccion->alnrotl2))),
                        'contact_phone' => strtoupper(trim(utf8_encode($direccion->alnrotl1))),
                        'contact_email' => strtoupper(trim(utf8_encode($direccion->alemail))),
                        'reg_status' => (strtoupper(trim($direccion->alsts)) == 'A') ? 1 : 0
                    );
                    echo '<pre>';
                    print_r($arrayInsert);
                    $direccion_id = $this->inserta_into_tabla('customer_addresses', $arrayInsert);
                    //$arrayWhere = array('id' => $direccion_id);
                    //$datos_direccion_cliente = $this->selecciona_fila_from_tabla('customer_addresses', $arrayWhere);
                } else {
                    echo "<br>Cliente-dirección ya existe: $direccion->aldscdir - $direccion->alitem01";
                }
            }
        }
        exit;
        $n_offset = (int)$offset + (int)$limit;
        if ($n_offset < $cantidad_clientes) {
            echo '<a href="imp_clientes/' . $n_offset . '">Siguiente</a>';
            return redirect('imp_clientes/' . $n_offset);
        } else echo '<br>Fin de registro de clientes';
    }

    public function corrige_clientes()
    {
        $offset = 0;
        $limit = 10000;
        $sql = "SELECT cli.*, ruc.ifnroruc, ruc.ifnvoruc FROM customers as cli
        LEFT JOIN mmifrep_libprddat as ruc ON cli.code = ruc.ifcodcli and ruc.ifsts='A'
        where cli.code = cli.document_number and trim(ruc.ifnvoruc) <> ''
        LIMIT :cantidad
        OFFSET :desde";
        $registros = DB::select(DB::raw($sql), array('cantidad' => $limit, 'desde' => $offset));
        echo 'Cantidad: ' . sizeof($registros);
        //echo '<pre>';
        //die(print_r($registros));
        if ($registros && is_array($registros)) {
            foreach ($registros as $fila) {
                $arrayWhere = array(['id', '=', $fila->id]);
                $arrayUpdate = array(
                    'document_number' => trim($fila->ifnvoruc),
                    'ruc_code_old' => trim($fila->ifnroruc)
                );
                if ($this->actualiza_tabla('customers', $arrayWhere, $arrayUpdate)) {
                    echo '<br>' . $fila->id . ' - ' . $fila->code . ' - ' . $fila->ifnvoruc;
                }
            }
        } else {
            echo '<br>Fin de correccion de clientes';
            exit;
        }
        echo '<a href="corrige_clientes">Siguiente</a>';
        return redirect('corrige_clientes');
    }

    public function retorna_total_filas_tabla($tabla)
    {
        return DB::table($tabla)->count();
    }


    public function busca_datos_vector($search, $resource_id, $array, $campo_comparar = 'resource_id')
    {
        foreach ($array as $fila) {
            if ($fila->$campo_comparar == $resource_id && $fila->code === $search) {
                return $fila->id;
            }
        }
        return null;
    }

    public function busca_datos_vector2($search, $array)
    {
        foreach ($array as $fila) {
            if ($fila->code === $search) {
                return $fila->id;
            }
        }
        return null;
    }

    public function retorna_datos_clientes_as400($offset = 0, $limit = 10000)
    {
        $sql = "SELECT cli.*, ruc.ifnvoruc FROM libprddat.mmakrep as cli
        LEFT JOIN libprddat.mmifrep as ruc ON cli.aknroruc = ruc.ifnroruc and ruc.ifsts='A'
        where cli.aknroruc <> '' and cli.akcodcli='033014'
        union
        SELECT cli.*, '' as ifnvoruc FROM libprddat.mmakrep as cli
        where cli.aknroruc = '' and cli.akcodcli='033014'
        LIMIT :cantidad
        OFFSET :desde";
        $rs = DB::connection('ibmi')->select(DB::raw($sql), array('cantidad' => $limit, 'desde' => $offset));
        return (is_array($rs) && sizeof($rs) > 0) ? $rs : false;
    }


    public function selecciona_fila_from_tabla_db2($tabla_db2, $arrayWhere)
    {
        return DB::connection('ibmi')
            ->table($tabla_db2)
            ->where($arrayWhere)
            ->first();
    }

    public function selecciona_from_tabla_db2($tabla_db2, $arrayWhere)
    {
        return DB::connection('ibmi')
            ->table($tabla_db2)
            ->where($arrayWhere)
            ->get()
            ->toArray();
    }

    public function inserta_into_tabla($tabla, $arrayInsert)
    {
        return DB::table($tabla)
            ->insertGetId($arrayInsert);
    }

    public function selecciona_fila_from_tabla($tabla, $arrayWhere)
    {
        return DB::table($tabla)
            ->where($arrayWhere)
            ->first();
    }

    public function selecciona_max_from_tabla($tabla, $campo, $arrayWhere = '')
    {
        //if ($arrayWhere === '') $arrayWhere = array([$campo, '<>', null]);
        //die(print_r($arrayWhere));
        return DB::table($tabla)->where($arrayWhere)->max($campo);
    }

    public function selecciona_from_tabla($tabla, $arrayWhere, $arraySelect = '')
    {
        if (empty($arraySelect)) $arraySelect = ['*'];
        return DB::table($tabla)
            ->select($arraySelect)
            ->where($arrayWhere)
            ->get()
            ->toArray();
    }

    public function selecciona_from_tabla_where_in($tabla, $whereInField, $whereInArray, $arraySelect = '', $orderBy = '')
    {
        if (empty($arraySelect)) $arraySelect = ['*'];
        if (empty($orderBy)) $orderBy = $whereInField;
        return DB::table($tabla)
            ->select($arraySelect)
            ->whereIn($whereInField, $whereInArray)
            ->orderBy($orderBy)
            ->get()
            ->toArray();
    }


    public function importar_partes(Request $request)
    {
        // ini_set('max_execution_time', '300');
        $offset = ($request->offset) ? intval($request->offset) : 0;
        $limit = 5000;
        //Tipo
        $whereInField = 'resource_id';
        /*
            Lineas      -> 7
            Orígenes    -> 6
        */
        $whereInArray = array(7, 6);
        $arraySelect = ['id', 'code', 'name', 'resource_id'];
        $array_tipos = $this->selecciona_from_tabla_where_in('gen_resource_details', $whereInField, $whereInArray, $arraySelect);
        //$tabla, $arrayWhere, $arraySelect 
        $arrayWhere = array(['id', '>', 0]);
        $arraySelect = array('id', 'code', 'name');
        $marcas = $this->selecciona_from_tabla('part_trademarks', $arrayWhere, $arraySelect);
        //echo '<pre>';
        //die(print_r($marcas));
        $cantidad_productos = $this->retorna_datos_productos(0, 10000000, true);
        $datos_productos = $this->retorna_datos_productos($offset, $limit);
        echo "<br>Total Productos: $cantidad_productos <br>";
        echo "Desde: $offset <br>";
        echo "Registros: $limit <br>";
        $i = ($offset > 0) ? ($offset - 1) : 0;
        if (is_array($datos_productos)) {
            foreach ($datos_productos as $producto) {
                $i++;
                $arrayWhere = array(['code', '=', $producto->etcodart]);
                $arrayInsert = array(
                    'code' => $producto->etcodart,
                    'short_name' => $producto->acdsccor,
                    'name' => $producto->acdsclar,
                    'reg_status' => ($producto->etsts === 'A') ? 1 : 0,
                );
                if (!$this->selecciona_fila_from_tabla('part_parts', $arrayWhere)) {
                    $parte_nueva = $this->inserta_into_tabla('part_parts', $arrayInsert);
                    echo '<br>Parte registrada (' . $parte_nueva . ') -> ' . $producto->etcodart;
                } else {
                    $arrayInsert2 = array(
                        'tabla' => 'part_parts',
                        'mensaje' => "CÓDIGO DE PRODUCTO $producto->etcodart EXISTE",
                        'otro' => json_encode($arrayInsert)
                    );
                    $this->inserta_into_tabla('log_migraciones', $arrayInsert2);
                }
            }
        }

        $n_offset = (int)$offset + (int)$limit;
        if ($n_offset < $cantidad_productos) {
            echo '<a href="imp_productos/' . $n_offset . '">Siguiente</a>';
            return redirect('imp_productos/' . $n_offset);
        } else echo '<br>Fin de registro de Partes';
    }

    public function retorna_datos_productos($offset = 0, $limit = 10000, $retorna_cantidad = false)
    {
        $sql = "SELECT distinct pro.etcodart, prode.acdsccor, prode.acdsclar, pro.etsts
        FROM mmetrep_productos as pro
        INNER JOIN mmacrep_libprddat as prode ON pro.etcodart = prode.accodart
        left join part_parts pp on pro.etcodart=pp.part_code
        where prode.acdsclar <> '' and pro.etsts = 'A' and pp.part_code is null
        LIMIT :cantidad
        OFFSET :desde";
        $rs = DB::select(DB::raw($sql), array('cantidad' => $limit, 'desde' => $offset));
        if ($retorna_cantidad) return sizeof($rs);
        return (is_array($rs) && sizeof($rs) > 0) ? $rs : false;
    }
    /*
    public function retorna_productos_cantidades_almacen($offset = 0, $limit = 10000, $retorna_cantidad = false, $almacen = '29')
    {
        $sql = "SELECT distinct pro.etcodart, prode.acdsccor, prode.acdsclar, pro.etsts
        FROM mmetrep_productos as pro
        inner join part_parts pp on pro.etcodart=pp.part_code
        inner join part_part_details ppd on pp.id=ppd.part_id
        inner join part_detail_warehouses pwd on ppd.id=pdw.part_detail_id
        where pro.etcodsuc = ':almacen' and pro.etsts = 'A' and pp.part_code is null
        LIMIT :cantidad
        OFFSET :desde";
        $rs = DB::select(DB::raw($sql), array('cantidad' => $limit, 'desde' => $offset, 'almacen' => $almacen));
        if ($retorna_cantidad) return sizeof($rs);
        return (is_array($rs) && sizeof($rs) > 0) ? $rs : false;
    }
    */


    public function importar_partes_detalles(Request $request)
    {
        // ini_set('max_execution_time', '300');

        $offset = ($request->offset) ? intval($request->offset) : 0;
        $limit = 5000;

        $cantidad_productos = $this->retorna_productos_linea_origen_marca(0, 10000000, true);
        $datos_productos = $this->retorna_productos_linea_origen_marca($offset, $limit);
        echo "<br>Total Productos: $cantidad_productos <br>";
        echo "Desde: $offset <br>";
        echo "Registros: $limit <br>";
        echo '<pre>';
        //die(print_r($datos_productos));
        $i = ($offset > 0) ? ($offset - 1) : 0;
        if (is_array($datos_productos)) {
            foreach ($datos_productos as $producto) {
                $i++;
                $arrayWhere = array(
                    ['part_id', '=', $producto->part_id],
                    ['line_id', '=', $producto->linea_id],
                    ['origin_id', '=', $producto->origen_id],
                    ['trademark_id', '=', $producto->marca_id],
                );
                $arrayInsert = array(
                    'part_id' => $producto->part_id,
                    'line_id' => $producto->linea_id,
                    'origin_id' => $producto->origen_id,
                    'trademark_id' => $producto->marca_id,
                    'reg_status' => ($producto->etsts === 'A') ? 1 : 0,
                );
                if (!$this->selecciona_fila_from_tabla('part_part_details', $arrayWhere)) {
                    $parte_nueva = $this->inserta_into_tabla('part_part_details', $arrayInsert);
                    echo '<br>Parte_detalle registrada (' . $parte_nueva . ') -> ' . $producto->part_id;
                } else {
                    $arrayInsert2 = array(
                        'tabla' => 'part_part_details',
                        'mensaje' => "COMBINACION PARTE-LINEA-ORIGEN-MARCA $producto->part_id-$producto->linea_id-$producto->origen_id-$producto->marca_id YA EXISTE",
                        'otro' => json_encode($arrayInsert)
                    );
                    $this->inserta_into_tabla('log_migraciones', $arrayInsert2);
                }
            }
        }
        //exit;
        $n_offset = (int)$offset + (int)$limit;
        if ($n_offset < $cantidad_productos) {
            echo '<a href="imp_productos_detalles/' . $n_offset . '">Siguiente</a>';
            return redirect('imp_productos_detalles/' . $n_offset);
        } else echo '<br>Fin de registro de Partes-Detalles';
    }


    public function actualizar_partes_detalles(Request $request)
    {
        //ini_set('max_execution_time', '300');

        //$offset = ($request->offset) ? intval($request->offset) : 0;
        $offset = 0;
        $limit = 5000;

        $cantidad_productos = $this->retorna_productos_linea_origen_marca(0, 10000000, true);
        $datos_productos = $this->retorna_productos_linea_origen_marca($offset, $limit);
        echo "<br>Total Productos: $cantidad_productos <br>";
        echo "Desde: $offset <br>";
        echo "Registros: $limit <br>";
        echo '<pre>';
        //die(print_r($datos_productos));
        $i = ($offset > 0) ? ($offset - 1) : 0;
        if (is_array($datos_productos)) {
            foreach ($datos_productos as $producto) {
                $i++;

                $arrayWhere = array(
                    ['id', '=', $producto->part_detail_id],
                );
                $arrayUpdate = array(
                    'sku' => $producto->sku_comp,
                    'factory_code' => $producto->factory_code
                );
                if ($this->actualiza_tabla('part_part_details', $arrayWhere, $arrayUpdate)) {
                    //echo '<br> SKU: ' . $producto->sku_comp . ' ACTUALIZADO';
                    echo "<br>($i) part_detail_id: $producto->part_detail_id  --- SKU Actual: $producto->sku --- SKU Correcto: $producto->sku_comp";
                }
            }
        }
        /*
        $n_offset = (int)$offset + (int)$limit;
        if ($n_offset < $cantidad_productos) {
            echo '<a href="act_productos_detalles/' . $n_offset . '">Siguiente</a>';
            return redirect('act_productos_detalles/' . $n_offset);
        } else echo '<br>Fin de registro de Partes-Detalles';
        */
        if ($cantidad_productos > 0) return redirect('act_productos_detalles/0');
    }

    public function retorna_productos_linea_origen_marca($offset = 0, $limit = 10000, $retorna_cantidad = false)
    {
        /*
        $sql = "SELECT distinct pro.etcodart codigo, part.id part_id, upper(trim(pro.etcodlin)) linea, lin.id as linea_id, upper(trim(pro.etcodori)) origen, ori.id as origen_id, upper(trim(pro.etcodmar)) marca, trade.id marca_id, pro.etsts
        FROM mmetrep_productos as pro
        INNER JOIN part_parts as part ON pro.etcodart = part.code
        INNER JOIN gen_resource_details as lin ON lin.resource_id = 7 and upper(trim(pro.etcodlin)) = lin.code
        INNER JOIN gen_resource_details as ori ON ori.resource_id = 6 and upper(trim(pro.etcodori)) = ori.code
        INNER JOIN part_trademarks as trade ON upper(trim(pro.etcodmar)) = trade.code
        where  pro.etsts = 'A'
        */
        /*
        $sql = "SELECT distinct pd.id as part_detail_id, upper(trim(pro.etcodart)) codigo, part.id part_id, upper(trim(pro.etcodlin)) linea, lin.id as linea_id, upper(trim(pro.etcodori)) origen, ori.id as origen_id, upper(trim(pro.etcodmar)) marca, trade.id marca_id, upper(trim(pro.etcodfab)) as fabricante, pro.etsts
        FROM mmetrep_productos as pro
        INNER JOIN part_parts as part ON pro.etcodart = part.code
        INNER JOIN part_part_details as pd ON part.id=pd.part_id
        INNER JOIN gen_resource_details as lin ON lin.resource_id = 7 and upper(trim(pro.etcodlin)) = lin.code
        INNER JOIN gen_resource_details as ori ON ori.resource_id = 6 and upper(trim(pro.etcodori)) = ori.code
        INNER JOIN part_trademarks as trade ON upper(trim(pro.etcodmar)) = trade.code
        where  pro.etsts = 'A' and pro.etcodcia='10' and pd.sku='0'
        LIMIT :cantidad
        OFFSET :desde";
        */

        $sql = "select part_detail_id, factory_code, sku, concat(line_code, origin_code, trademark_code, part_code) as sku_comp
        from v_partes
        where sku <> concat(line_code, origin_code, trademark_code, part_code)
        LIMIT :cantidad
        OFFSET :desde";
        $rs = DB::select(DB::raw($sql), array('cantidad' => $limit, 'desde' => $offset));
        if ($retorna_cantidad) return sizeof($rs);
        return (is_array($rs) && sizeof($rs) > 0) ? $rs : false;
    }

    public function actualiza_rotacion_productos(Request $request)
    {
        ini_set('max_execution_time', '3000');
        $cantidad_productos = 122400;
        $limit = 1000;
        $offset = ($request->offset) ? intval($request->offset) : 0;
        $partes = $this->retorna_part_details($offset, $limit);
        if ($partes && is_array($partes)) {
            $i = 0;
            foreach ($partes as $parte) {
                $i++;
                echo "<br> ($i) CÓDIGO: $parte->sku ";
                $parte->datos_as = $this->trae_datos_parte_as($parte->sku);
                //echo ($parte->datos_as);
                $datos_as = json_decode($parte->datos_as);
                if (isset($datos_as->rotacion)) {
                    $arrayWhere = array(['id', '=', $parte->part_detail_id]);
                    $arrayUpdate = array('rotation' => $datos_as->rotacion);
                    if (!$this->actualiza_tabla('part_part_details', $arrayWhere, $arrayUpdate)) {
                        echo ' -> NO ACTUALIZADO';
                    } else echo ' *** REGISTRO ACTUALIZADO ***';
                }
            }

            $n_offset = (int)$offset + $limit;
            if ($n_offset < $cantidad_productos) {
                echo '<a href="act_rotacion_productos/' . $n_offset . '">Siguiente</a>';
                return redirect('act_rotacion_productos/' . $n_offset);
            } else echo '<br>Fin de rotación de productos';
        }
    }

    public function trae_datos_parte_as($sku)
    {
        //$sku = str_replace('/', '-', $sku);
        //$sku = str_replace(' ', '%20', $sku);
        $linea = substr($sku, 0, 2);
        $origen = substr($sku, 2, 2);
        $marca = substr($sku, 4, 3);
        $codigo = substr($sku, 7, strlen($sku));
        $codigo = str_replace('/', '-', $codigo);
        $codigo = str_replace(' ', '%20', $codigo);


        //echo ' *** ' . $sku . ' ***';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://api.mym.com.pe:8887/appwsmym/api/v1/articulos/find/articulorotacion/' . $linea . '/' . $origen . '/' . $marca . '/' . $codigo);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    public function actualiza_und_medida_productos(Request $request)
    {
        ini_set('max_execution_time', '300');
        $whereInField = 'resource_id';
        $whereInArray = array(27);
        $arraySelect = ['id', 'code', 'name', 'resource_id'];
        $array_tipos = $this->selecciona_from_tabla_where_in('gen_resource_details', $whereInField, $whereInArray, $arraySelect);

        $cantidad_productos = 53000;
        $limit = 5000;
        $offset = ($request->offset) ? intval($request->offset) : 0;
        $partes = $this->retorna_partes_und_medida($offset, $limit);
        if ($partes && is_array($partes)) {
            $i = 0;
            foreach ($partes as $parte) {
                $i++;
                echo "<br> ($i) CÓDIGO: $parte->code ";
                //echo '<pre>';
                //die(print_r($parte));
                $unidad_medida_id = $this->busca_datos_vector($parte->acunimed, 27, $array_tipos);
                $arrayWhere = array(['id', '=', $parte->part_id]);
                $arrayUpdate = array('measure_unit' => $unidad_medida_id);
                if (!$this->actualiza_tabla('part_parts', $arrayWhere, $arrayUpdate)) {
                    echo ' -> NO ACTUALIZADO';
                } else echo ' *** REGISTRO OK ***';
            }
        }
        $n_offset = (int)$offset + $limit;
        if ($n_offset < $cantidad_productos) {
            echo '<a href="act_und_medida_productos/' . $n_offset . '">Siguiente</a>';
            return redirect('act_und_medida_productos/' . $n_offset);
        } else echo '<br>Fin Actualización Unidad de medida de productos';
    }

    public function retorna_partes_und_medida($offset, $limit)
    {
        //$limit = 500;
        $sql = "select pp.id part_id, pp.code, um.acunimed  from part_parts pp
        INNER JOIN mmacrep_libprddat um on pp.code=trim(upper(um.accodart))
        OFFSET :desde
        LIMIT  :cantidad";

        $rs = DB::select(DB::raw($sql), array('desde' => $offset, 'cantidad' => $limit));
        return (is_array($rs) && sizeof($rs) > 0) ? $rs : false;
    }

    public function actualiza_subsistema_productos(Request $request)
    {
        ini_set('max_execution_time', '300');
        $whereInField = 'resource_id';
        $whereInArray = array(10);
        $arraySelect = ['id', 'code', 'name', 'resource_id'];
        $array_tipos = $this->selecciona_from_tabla_where_in('gen_resource_details', $whereInField, $whereInArray, $arraySelect);

        $cantidad_productos = 55000;
        $limit = 5000;
        $offset = ($request->offset) ? intval($request->offset) : 0;
        $partes = $this->retorna_partes_subsistema($offset, $limit);
        if ($partes && is_array($partes)) {
            $i = 0;
            foreach ($partes as $parte) {
                $i++;
                echo "<br> ($i) CÓDIGO: $parte->code ";
                //echo '<pre>';
                //die(print_r($parte));
                $subsistema_id = $this->busca_datos_vector($parte->subsistema, 10, $array_tipos);
                $arrayWhere = array(['id', '=', $parte->part_id]);
                $arrayUpdate = array('subsystem_id' => $subsistema_id);
                if (!$this->actualiza_tabla('part_parts', $arrayWhere, $arrayUpdate)) {
                    echo ' -> NO ACTUALIZADO';
                } else echo ' *** REGISTRO OK ***';
            }
        }
        $n_offset = (int)$offset + $limit;
        if ($n_offset < $cantidad_productos) {
            echo '<a href="act_sub_sistema_productos/' . $n_offset . '">Siguiente</a>';
            return redirect('act_sub_sistema_productos/' . $n_offset);
        } else echo '<br>Fin Actualización Sub-sistema de productos';
    }

    public function retorna_partes_subsistema($offset, $limit)
    {
        $sql = "select pp.id part_id, pp.code, sub.subsistema  from part_parts pp
        INNER JOIN mmohrep_prod_sis_subsis sub on pp.code=trim(upper(sub.ohcodart))
        where pp.subsystem_id=0
        OFFSET :desde
        LIMIT  :cantidad";

        $rs = DB::select(DB::raw($sql), array('desde' => $offset, 'cantidad' => $limit));
        return (is_array($rs) && sizeof($rs) > 0) ? $rs : false;
    }


    public function actualiza_imagen_principal_productos(Request $request)
    {
        ini_set('max_execution_time', '3000');
        $cantidad_productos = 150000;
        $limit = 5000;
        $offset = ($request->offset) ? intval($request->offset) : 0;
        $partes = $this->retorna_part_details_mysql($offset, $limit);
        echo '<br>';
        if ($partes && is_array($partes)) sizeof($partes);
        else die('NO HAY MAS PRODUCTOS');
        if ($partes && is_array($partes)) {
            $i = 0;
            foreach ($partes as $parte) {
                $i++;
                echo "<br> ($i) CÓDIGO: $parte->sku ";
                echo $parte->image;
                /*
                $coma = ',';
                $linea = substr($parte->sku, 0, 2);
                $origen = substr($parte->sku, 2, 2);
                $marca = substr($parte->sku, 4, 3);
                $codigo = substr($parte->sku, 7, strlen($parte->sku));
                $codigo = str_replace('/', '-', $codigo);
                $codigo = urlencode($codigo);
                //$codigo = str_replace(' ', '%20', $codigo);

                $str_img = 'https://api.mym.com.pe/images/prod/?f=';
                $str_img .= $linea . $coma;
                $str_img .= $origen . $coma;
                $str_img .= $marca . $coma;
                $str_img .= $codigo;
                echo ' ' . $str_img;
                */

                $arrayWhere = array(['sku', '=', $parte->sku]);
                $arrayUpdate = array('principal_image' => $parte->image, 'rotation' => $parte->rotation, 'weight' => $parte->weight);
                if (!$this->actualiza_tabla('part_part_details', $arrayWhere, $arrayUpdate)) {
                    echo ' -> NO ACTUALIZADO';
                } else echo ' *** REGISTRO OK ***';
            }
        }
        //die();
        $n_offset = (int)$offset + $limit;
        if ($n_offset < $cantidad_productos) {
            echo '<a href="act_img_productos/' . $n_offset . '">Siguiente</a>';
            return redirect('act_img_productos/' . $n_offset);
        } else echo '<br>Fin Actualización Imagen principal de productos';
    }


    public function retorna_part_details_mysql($desde, $limit)
    {
        $sql = "select * from products
        LIMIT :cantidad
        OFFSET :desde";

        $rs = DB::connection('mysql')->select(DB::raw($sql), array('cantidad' => $limit, 'desde' => $desde));
        return (is_array($rs) && sizeof($rs) > 0) ? $rs : false;
    }

    public function actualiza_imagen_principal_productos_OLD(Request $request)
    {
        ini_set('max_execution_time', '300');
        $cantidad_productos = 150000;
        $limit = 5000;
        $offset = ($request->offset) ? intval($request->offset) : 0;
        $partes = $this->retorna_part_details($offset, $limit);
        if ($partes && is_array($partes)) {
            $i = 0;
            foreach ($partes as $parte) {
                $i++;
                echo "<br> ($i) CÓDIGO: $parte->sku ";
                $coma = ',';
                $linea = substr($parte->sku, 0, 2);
                $origen = substr($parte->sku, 2, 2);
                $marca = substr($parte->sku, 4, 3);
                $codigo = substr($parte->sku, 7, strlen($parte->sku));
                $codigo = str_replace('/', '-', $codigo);
                $codigo = urlencode($codigo);
                //$codigo = str_replace(' ', '%20', $codigo);

                $str_img = 'https://api.mym.com.pe/images/prod/?f=';
                $str_img .= $linea . $coma;
                $str_img .= $origen . $coma;
                $str_img .= $marca . $coma;
                $str_img .= $codigo;
                echo ' ' . $str_img;
                /*
                $arrayWhere = array(['id', '=', $parte->part_detail_id]);
                $arrayUpdate = array('principal_image' => $str_img);
                if (!$this->actualiza_tabla('part_part_detailss', $arrayWhere, $arrayUpdate)) {
                    echo ' -> NO ACTUALIZADO';
                } else echo ' *** REGISTRO OK ***';
                */
            }
        }
        die();
        $n_offset = (int)$offset + $limit;
        if ($n_offset < $cantidad_productos) {
            echo '<a href="act_img_productos/' . $n_offset . '">Siguiente</a>';
            return redirect('act_img_productos/' . $n_offset);
        } else echo '<br>Fin Actualización Imagen principal de productos';
    }

    public function retorna_part_details($desde, $limit)
    {
        $sql = "select id part_detail_id, sku from part_part_details
        WHERE rotation = '0'
        LIMIT :cantidad
        OFFSET :desde";

        $rs = DB::select(DB::raw($sql), array('cantidad' => $limit, 'desde' => $desde));
        return (is_array($rs) && sizeof($rs) > 0) ? $rs : false;
    }


    public function importar_marcas_de_productos()
    {
        $sql = "select distinct a.EYCODMAR,a.EYDSCABR,a.EYDSCCOR,a.EYDSCLAR,a.EYLEATIM,a.EYTIPREP,a.EYESTIMP, a.EYSTS 
        from LIBPRDDAT.MMEYREP a 
        where a.EYSTS='A' 
        and a.EYCODMAR not in('999','777','**7')";
        $registros = DB::connection('ibmi')->select(DB::raw($sql));
        //echo '<pre>';
        //die(print_r($registros));
        if ($registros && is_array($registros)) {
            foreach ($registros as $fila) {
                $sql = 'select * from part_trademarks where code = :codigo or name like :marca';
                $rs = DB::select(DB::raw($sql), array('codigo' => utf8_encode(strtoupper(trim($fila->eycodmar))), 'marca' => utf8_encode(strtoupper(trim($fila->eydsclar)))));
                if ($rs && is_array($rs)) {
                    echo "<br> Código ($fila->eycodmar) o Marca ($fila->eydsclar) Ya existe";
                } else {
                    $arrayInsert = array(
                        'code' => utf8_encode(strtoupper(trim($fila->eycodmar))),
                        'abrv' => utf8_encode(strtoupper(trim($fila->eydscabr))),
                        'short_name' => utf8_encode(strtoupper(trim($fila->eydsccor))),
                        'name' => utf8_encode(strtoupper(trim($fila->eydsclar))),
                        'reg_status' => ($fila->eysts === 'I') ? 0 : 1
                    );
                    //echo '<pre>';
                    //print_r($arrayInsert);
                    $this->inserta_into_tabla('part_trademarks', $arrayInsert);
                }
            }
            echo '<br> FIN MIGRACIÓN - MARCAS';
        }
    }

    public function actualiza_tabla($tabla, $arrayWhere, $arrayUpdate)
    {
        return DB::table($tabla)
            ->where($arrayWhere)
            ->update($arrayUpdate);
    }

    public function importar_usuarios()
    {
        //selecciona_from_tabla('mmbmrep_usuarios', array())
        if ($registros = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMBMREP', array())) {
            echo '<pre>';
            //die(print_r($registros));
            foreach ($registros as $fila) {
                /*
                if ($fila->bmfecnac != 0) {

                    $fecha = Carbon::createFromFormat('Ymd', $fila->bmfecnac, 'America/Lima');
                    $fecha_formateada =  $fecha->format('Y-m-d');
                    die($fecha . ' - ' . $fecha_formateada . ' - ' . $fila->bmfecnac);
                }
                */
                $user_code = trim(strtoupper(utf8_encode($fila->bmuserid)));
                $user_code = str_replace('Ñ', 'N', $user_code);
                echo '<br>' . $user_code;
                $arrayWhere = array(
                    ['code', '=', $user_code]
                );
                if (!$datos_usuario = $this->selecciona_fila_from_tabla('user_users', $arrayWhere)) {
                    $usuario_as = strtolower($user_code);

                    //if (!strtotime(trim($fila->bmfecnac))) die(trim($fila->bmfecnac));
                    if (strlen(trim($fila->bmfecnac)) == 8 && strtotime(trim($fila->bmfecnac)) <> 0 && trim($fila->bmfecnac) <> '99999999') {
                        //die(trim($fila->bmfecnac) . ' - ' . strtotime(trim($fila->bmfecnac)));
                        $fecha_nac = Carbon::createFromFormat('Ymd', $fila->bmfecnac, 'America/Lima')->format('Y-m-d');
                    } else $fecha_nac = '2000-01-01';

                    //if (!strtotime(trim($fila->bmfecing))) die(trim($fila->bmfecing));
                    if (strlen(trim($fila->bmfecing)) == 8 && strtotime(trim($fila->bmfecing)) <> 0 && trim($fila->bmfecing) <> '99999999') {
                        //die(trim($fila->bmfecing) . ' - ' . strtotime(trim($fila->bmfecing)));
                        $fecha_ing = Carbon::createFromFormat('Ymd', $fila->bmfecing, 'America/Lima')->format('Y-m-d');
                    } else $fecha_ing = date("Y-m-d");


                    $arrayInsert = array(
                        //'code' => utf8_encode(strtoupper(trim($fila->bmuserid))),
                        'code' => utf8_encode(strtoupper(trim($user_code))),
                        'last_name' => utf8_encode(strtoupper(trim($fila->bmprapll))),
                        'mother_last_name' => utf8_encode(strtoupper(trim($fila->bmsgapll))),
                        'first_name' => utf8_encode(strtoupper(trim($fila->bmprnomb))),
                        'second_name' => utf8_encode(strtoupper(trim($fila->bmsgnomb))),
                        'email' => $usuario_as . '@mym.com.pe',
                        'cellphone' => '123',
                        'birthdate' => $fecha_nac,
                        'startdate' => $fecha_ing,
                        'country_id' => 163,
                        'reg_status' => ($fila->bmsts === 'A') ? 1 : 0,
                    );
                    //echo '<br>User user';
                    //print_r($arrayInsert);
                    $this->inserta_into_tabla('user_users', $arrayInsert);
                    $datos_usuario = $this->selecciona_fila_from_tabla('user_users', $arrayWhere);
                }
                $arrayWhere = array(
                    ['bmuserid', '=', $datos_usuario->code],
                );

                $datos_usuario->detalles = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMBMREP', $arrayWhere);
                //$this->selecciona_from_tabla('mmbmrep_usuarios', $arrayWhere)
                //print_r($datos_usuario->detalles);
                //exit;
                if (is_array($datos_usuario->detalles)) {
                    foreach ($datos_usuario->detalles as $detalle) {
                        ///echo '<br>Usuario empresa: ';
                        //print_r($detalle);
                        $company = ($detalle->bmcodcia == '10') ? 1 : 2;
                        $suc = ($detalle->bmcodcia == '10') ? 3 : 4;
                        $arrayWhere = array(
                            ['user_user_id', '=', $datos_usuario->id],
                            ['company_id', '=', $company],
                            //['user_code', '=', $datos_usuario->code],
                            //['operator_code', '=', strtoupper(trim($detalle->bmcodper))],

                        );
                        //print_r($arrayWhere);
                        if (!$this->selecciona_fila_from_tabla('user_user_companies', $arrayWhere)) {
                            $arrayInsert = array(
                                'user_user_id' => $datos_usuario->id,
                                'company_id' => $company,
                                'subsidiary_id' => $suc,
                                'user_code' => $datos_usuario->code,
                                'operator_code' => utf8_encode(strtoupper(trim($detalle->bmcodper))),
                                'reg_status' => ($detalle->bmsts === 'A') ? 1 : 0,
                                'staff_id' => 1,
                                'control_center_id' => 1,
                                'cost_center_id' => 1,
                                'job_type_id' => 1
                            );
                            //echo '<br>User company';
                            //print_r($arrayInsert);
                            $this->inserta_into_tabla('user_user_companies', $arrayInsert);
                        }
                    }
                }
            }
        }
    }

    public function importar_sublineas()
    {
        die('Listo');
        // ---- SUB-LÍNEAS MACRO (14) ---- //
        $sql = "select m.LMCODLIN, s.EUCODELE, s.EUDSCABR, s.EUDSCCOR, s.EUDSCLAR 
        from LIBPRDDAT.colmrep m 
        inner join LIBPRDDAT.MMEUREP s on m.LMSUBLIN=s.EUCODELE
        WHERE s.EUCODTBL='12' 
        and s.EUSTS='A' 
        AND m.LMSTS='A'";
        $registros = DB::connection('ibmi')->select(DB::raw($sql));
        if ($registros && is_array($registros)) {
            $resource_id_padre = 14;
            $resource_id = 25;
            foreach ($registros as $fila) {
                $arrayWhere = array(
                    ['resource_id', '=', $resource_id_padre],
                    ['code', '=', strtoupper(trim($fila->lmcodlin))],
                );
                $parent = $this->selecciona_fila_from_tabla('gen_resource_details', $arrayWhere);
                //echo '<pre>';
                //die(print_r($parent));
                //die(print_r($fila));

                $arrayWhere = array(
                    ['resource_id', '=', $resource_id],
                    ['name', '=', utf8_encode(strtoupper(trim($fila->eudsccor)))],
                );
                if ($this->selecciona_fila_from_tabla('gen_resource_details', $arrayWhere)) {
                    echo '<br>REGISTRO EXISTE: ';
                    echo "$fila->eucodele $fila->eudscabr utf8_encode(strtoupper(trim($fila->eudsccor)))";
                } else {
                    $arrayInsert = array(
                        'resource_id' => $resource_id,
                        'code' => $fila->eucodele,
                        'abrv' => $fila->eudscabr,
                        'name' => utf8_encode(strtoupper(trim($fila->eudsccor))),
                        'description' => utf8_encode(strtoupper(trim($fila->eudsclar))),
                        'parent_resource_detail_id' => $parent->id
                    );
                    //echo '<pre>';
                    //print_r($arrayInsert);
                    $this->inserta_into_tabla('gen_resource_details', $arrayInsert);
                }
            }
            echo '<br> FIN MIGRACIÓN - LÍNEAS MACRO (14)';
        }
        // ---- SUB-LÍNEAS MACRO (14) ---- //

    }


    public function importar_productos_almacenes(Request $request)
    {
        ini_set('max_execution_time', '3000');
        $offset = ($request->offset) ? intval($request->offset) : 0;
        $limit = 50000;
        $arrayWhere = array(['id', '>', 0]);
        $almacenes = $this->retorna_almacenes_empresa(1, 'alm.id, alm.code');
        //echo '<pre>';
        //die(print_r($almacenes));
        echo 'Inicio: ' . date("d-m-Y H:i:s");
        $cantidad_productos = $this->retorna_datos_productos_almacen(0, 999999, true);
        $datos_productos = $this->retorna_datos_productos_almacen($offset, $limit);
        echo "<br>Total Productos: $cantidad_productos <br>";
        echo "Desde: $offset <br>";
        echo "Registros: $limit <br>";
        $i = ($offset > 0) ? ($offset - 1) : 0;
        if (is_array($datos_productos)) {
            foreach ($datos_productos as $producto) {
                $i++;
                $stock_inicial = round(floatval($producto->etstkini), 2);
                $stock_ingreso = round(floatval($producto->etcaning), 2);
                $stock_salida = round(floatval($producto->etcansld), 2);
                $total_stock = ($stock_inicial + $stock_ingreso - $stock_salida);
                $warehouse_id = $this->busca_datos_vector2($producto->etcodsuc, $almacenes);
                if ($warehouse_id) {
                    $arrayWhere = array(['part_detail_id', '=', $producto->part_detail_id], ['warehouse_id', '=', $warehouse_id]);
                    $arrayInsert = array(
                        'part_detail_id' => $producto->part_detail_id,
                        'warehouse_id' => $warehouse_id,
                        'init_qty' => $stock_inicial,
                        'in_qty' => $stock_ingreso,
                        'out_qty' => $stock_salida,
                        'in_warehouse_stock' => $total_stock,
                        'reg_status' => ($producto->etsts === 'A') ? 1 : 0,
                        'created_at' => date("Y-m-d H:i:s")
                    );
                    if (!$this->selecciona_fila_from_tabla('part_detail_warehouses', $arrayWhere)) {
                        $parte_nueva = $this->inserta_into_tabla('part_detail_warehouses', $arrayInsert);
                        echo '<br>(' . $i . ') Parte registrada (' . $parte_nueva . ') -> ' . $producto->etcodart;
                    } else {
                        $arrayInsert2 = array(
                            'tabla' => 'part_detail_warehouses',
                            'mensaje' => "CÓDIGO DE PRODUCTO $producto->etcodart - $producto->part_detail_id EXISTE",
                            'otro' => json_encode($arrayInsert)
                        );
                        $this->inserta_into_tabla('log_migraciones', $arrayInsert2);
                    }
                } else {
                    echo "<br>ERROR: ALMACEN ($producto->etcodsuc) NO ENCONTRADO: PARTE -> $producto->etcodart - $producto->part_detail_id <BR>";
                    $arrayInsert2 = array(
                        'tabla' => 'part_detail_warehouses',
                        'mensaje' => "ERROR: ALMACEN ($producto->etcodsuc) NO ENCONTRADO:  CÓDIGO DE PRODUCTO -> $producto->etcodart - $producto->part_detail_id ",
                        'otro' => json_encode($producto)
                    );
                    $this->inserta_into_tabla('log_migraciones', $arrayInsert2);
                }
            }
        }
        $n_offset = (int)$offset + (int)$limit;
        if ($n_offset < $cantidad_productos) {
            echo '<a href="imp_productos_alm/' . $n_offset . '">Siguiente</a>';
            return redirect('imp_productos_alm/' . $n_offset);
        } else echo '<br>Fin de registro de existencias de productos';
    }

    public function retorna_datos_productos_almacen($offset = 0, $limit = 10000, $retorna_cantidad = false, $warehouse_code = '29')
    {
        if ($retorna_cantidad) {
            $sql = "SELECT count(id) as cant
        FROM mmetrep_productos as pa
        where pa.etsts = 'A' and pa.etcodcia='10' and pa.etcodsuc = '29'";
            //die($sql);
            $rs = DB::select(DB::raw($sql));
        } else {
            $sql = "SELECT distinct pa.etcodcia, pa.etcodsuc, pa.etcodlin, pa.etcodart, pa.etcodori, pa.etcodmar, pa.etstkini, pa.etcaning, pa.etcansld, pa.etcodalm, pa.etsts, vp.part_detail_id
        FROM mmetrep_productos as pa
        inner join v_partes as vp on pa.etcodlin = vp.line_code and pa.etcodori=vp.origin_code and pa.etcodmar=vp.trademark_code and pa.etcodart=vp.part_code
        where pa.etsts = 'A' and pa.etcodcia='10' and pa.etcodsuc = :codigo_almacen
        LIMIT :cantidad
        OFFSET :desde";
            // die($sql);
            $rs = DB::select(DB::raw($sql), array('cantidad' => $limit, 'desde' => $offset, 'codigo_almacen' => $warehouse_code));
        }

        if ($retorna_cantidad) return $rs[0]->cant;
        return (is_array($rs) && sizeof($rs) > 0) ? $rs : false;
    }

    public function retorna_almacenes_empresa($empresa, $select = 'alm.id, alm.code, alm.name')
    {
        $sql = "select $select 
        from establishments alm
        inner join establishments suc on alm.parent_establishment_id = suc.id 
        where suc.parent_establishment_id= :emp";
        $rs = DB::select(DB::raw($sql), array('emp' => $empresa));
        return (is_array($rs) && sizeof($rs) > 0) ? $rs : false;
    }


    public function retorna_datos_productos_precio($offset = 0, $limit = 10000, $retorna_cantidad = false)
    {
        if ($retorna_cantidad) {
            $sql = "SELECT count(id) as cant
        FROM mmetrep_productos as pa
        where pa.etsts = 'A' and pa.etcodcia='10'";
            //die($sql);
            $rs = DB::select(DB::raw($sql));
        } else {
            $sql = "SELECT distinct pa.etcodcia, pa.etcodsuc, pa.etcodlin, pa.etcodart, pa.etcodori, pa.etcodmar, pa.etsts, pa.etimplis as precio_lista, pa.etimppmi as precio_minimo, pa.etimpcrp as costo_reposicion, pa.etfacpmi as factor_precio_min, pa.etfacpma as factor_precio_max, pa.etprcumi as porc_utilidad_min, pa.etprcuma as porc_utilidad_max, vp.part_detail_id
        FROM mmetrep_productos as pa
        inner join v_partes as vp on pa.etcodlin = vp.line_code and pa.etcodori=vp.origin_code and pa.etcodmar=vp.trademark_code and pa.etcodart=vp.part_code
        where pa.etsts = 'A' and pa.etcodcia='10'
        LIMIT :cantidad
        OFFSET :desde";
            //die($sql);
            $rs = DB::select(DB::raw($sql), array('cantidad' => $limit, 'desde' => $offset));
        }

        if ($retorna_cantidad) return $rs[0]->cant;
        return (is_array($rs) && sizeof($rs) > 0) ? $rs : false;
    }

    public function actualizar_precio_partes_detalles(Request $request)
    {
        ini_set('max_execution_time', '3000');
        $offset = ($request->offset) ? intval($request->offset) : 0;
        $limit = 50000;
        echo 'Inicio: ' . date("d-m-Y H:i:s");
        $cantidad_productos = $this->retorna_datos_productos_precio(0, 999999, true);
        $datos_productos = $this->retorna_datos_productos_precio($offset, $limit);
        echo "<br>Total Productos: $cantidad_productos <br>";
        echo "Desde: $offset <br>";
        echo "Registros: $limit <br>";
        //$i = ($offset > 0) ? ($offset - 1) : 0;
        $i = 0;
        if (is_array($datos_productos)) {
            foreach ($datos_productos as $producto) {

                $i++;
                $precio_lista = round(floatval($producto->precio_lista), 2);
                $precio_minimo = round(floatval($producto->precio_minimo), 2);
                $costo_reposicion = round(floatval($producto->costo_reposicion), 2);
                $factor_precio_min = round(floatval($producto->factor_precio_min), 2);
                $factor_precio_max = round(floatval($producto->factor_precio_max), 2);
                $porc_utilidad_min = round(floatval($producto->porc_utilidad_min), 2);
                $porc_utilidad_max = round(floatval($producto->porc_utilidad_max), 2);

                $arrayWhere = array(['id', '=', $producto->part_detail_id]);
                $arrayUpdate = array(
                    'list_price' => $precio_lista,
                    'min_price' => $precio_minimo,
                    'replacement_cost' => $costo_reposicion,
                    'min_price_factor' => $factor_precio_min,
                    'max_price_factor' => $factor_precio_max,
                    'min_profit_rate' => $porc_utilidad_min,
                    'max_profit_rate' => $porc_utilidad_max
                );
                if (!$this->actualiza_tabla('part_part_details', $arrayWhere, $arrayUpdate)) {
                    $arrayInsert2 = array(
                        'tabla' => 'part_part_details',
                        'mensaje' => "CÓDIGO DE PRODUCTO $producto->part_detail_id EXISTE",
                        'otro' => json_encode($arrayWhere)
                    );
                    $this->inserta_into_tabla('log_migraciones', $arrayInsert2);
                } else {
                    echo "<br>($i) $producto->part_detail_id - $precio_lista";
                }
            }
        }

        $n_offset = (int)$offset + (int)$limit;
        if ($n_offset < $cantidad_productos) {
            echo '<a href="act_productos_detalles_precio/' . $n_offset . '">Siguiente</a>';
            return redirect('act_productos_detalles_precio/' . $n_offset);
        } else echo '<br>Fin de registro de existencias de productos';
    }

    public function importar_productos_en_oferta(Request $request)
    {
        ini_set('max_execution_time', '3000');
        $offset = ($request->offset) ? intval($request->offset) : 0;
        $limit = 50000;
        //$arrayWhere = array(['id', '>', 0]);
        //$almacenes = $this->retorna_almacenes_empresa(1, 'alm.id, alm.code');
        echo '<pre>';
        echo 'Inicio: ' . date("d-m-Y H:i:s");
        $ofertas = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMSCREP', array(['SCANO', '>=', '2022']));
        if ($ofertas && is_array($ofertas)) {
            foreach ($ofertas as $oferta) {

                $arrayWhereOffer = array(
                    ['company_id', '=', 1],
                    ['offer_code', '=', trim(strtoupper($oferta->sccodofe))],
                );
                if (!$datos_oferta = $this->selecciona_fila_from_tabla('part_offers', $arrayWhereOffer)) {
                    $arrayInsertOffer = array(
                        'company_id' => 1,
                        'offer_code' => trim(strtoupper($oferta->sccodofe)),
                        'offer_description' => trim(strtoupper($oferta->scobserv)),
                        'discount_state' => $oferta->sctipofe,
                        'type_offer_id' => ($oferta->sccladsc == 'OF') ? 1358 : 1359,
                        'year_offer' => $oferta->scano,
                        'init_date' => $oferta->scfecini,
                        'end_date' => $oferta->scfecfin,
                        'created_at' => date("Y-m-d H:i:s"),
                        'reg_status' => ($oferta->scsts == 'A') ? 1 : 0,
                    );
                    $this->inserta_into_tabla('part_offers', $arrayInsertOffer);
                    $datos_oferta = $this->selecciona_fila_from_tabla('part_offers', $arrayWhereOffer);
                }

                $productos_oferta = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMSDREP', array(['SDCODINT', '=', $oferta->sccodofe]));
                foreach ($productos_oferta as $producto) {
                    $sku = $producto->sdcodlin . '' . $producto->sdcodori . '' . $producto->sdcodmar . '' . utf8_encode(strtoupper(trim($producto->sdcodart)));
                    $arrayWhere = array(
                        ['line_code', '=', $producto->sdcodlin],
                        ['origin_code', '=', $producto->sdcodori],
                        ['trademark_code', '=', $producto->sdcodmar],
                        ['part_code', '=', trim(strtoupper($producto->sdcodart))],
                    );
                    if (!$datos_producto = $this->selecciona_fila_from_tabla('v_partes', $arrayWhere)) {
                        die("<br>SKU: $sku NO ENCONTRADO");
                    }
                    $arrayWhereProductOffer = array(
                        ['offer_id', '=', $datos_oferta->id],
                        ['part_detail_id', '=', $datos_producto->part_detail_id]
                    );
                    if (!$this->selecciona_fila_from_tabla('part_offer_details', $arrayWhereProductOffer)) {
                        $arrayInsertProductOffer = array(
                            'offer_id' => $datos_oferta->id,
                            'part_detail_id' => $datos_producto->part_detail_id,
                            'list_price' => $datos_producto->list_price,
                            'min_price' => $producto->sdpremin,
                            'cost_price' => $producto->sdprecos,
                            'discount_rate' => $producto->sdpordsc,
                            'profit_rate' => $producto->sdporuti,
                            'new_profit_rate' => $producto->sdporut1,
                            'base_factor' => $producto->sdfactor,
                            'created_at' => date("Y-m-d H:i:s"),
                            'reg_status' => ($producto->sdsts == 'A') ? 1 : 0,
                        );
                        $this->inserta_into_tabla('part_offer_details', $arrayInsertProductOffer);
                    }

                    //die(print_r($datos_producto));
                    echo "<br>OFERTA-PRODUCTO AGREGADO ($oferta->sccodofe - $datos_producto->sku)";
                }
            }
        } else {
            die('NO HAY OFERTAS');
        }
        die('listo');


        $cantidad_productos = $this->retorna_datos_productos_almacen(0, 999999, true);
        $datos_productos = $this->retorna_datos_productos_almacen($offset, $limit);
        echo "<br>Total Productos: $cantidad_productos <br>";
        echo "Desde: $offset <br>";
        echo "Registros: $limit <br>";
        $i = ($offset > 0) ? ($offset - 1) : 0;
        if (is_array($datos_productos)) {
            foreach ($datos_productos as $producto) {
                $i++;
                $stock_inicial = round(floatval($producto->etstkini), 2);
                $stock_ingreso = round(floatval($producto->etcaning), 2);
                $stock_salida = round(floatval($producto->etcansld), 2);
                $total_stock = ($stock_inicial + $stock_ingreso - $stock_salida);
                $warehouse_id = $this->busca_datos_vector2($producto->etcodsuc, $almacenes);
                if ($warehouse_id) {
                    $arrayWhere = array(['part_detail_id', '=', $producto->part_detail_id], ['warehouse_id', '=', $warehouse_id]);
                    $arrayInsert = array(
                        'part_detail_id' => $producto->part_detail_id,
                        'warehouse_id' => $warehouse_id,
                        'init_qty' => $stock_inicial,
                        'in_qty' => $stock_ingreso,
                        'out_qty' => $stock_salida,
                        'in_warehouse_stock' => $total_stock,
                        'reg_status' => ($producto->etsts === 'A') ? 1 : 0,
                        'created_at' => date("Y-m-d H:i:s")
                    );
                    if (!$this->selecciona_fila_from_tabla('part_detail_warehouses', $arrayWhere)) {
                        $parte_nueva = $this->inserta_into_tabla('part_detail_warehouses', $arrayInsert);
                        echo '<br>(' . $i . ') Parte registrada (' . $parte_nueva . ') -> ' . $producto->etcodart;
                    } else {
                        $arrayInsert2 = array(
                            'tabla' => 'part_detail_warehouses',
                            'mensaje' => "CÓDIGO DE PRODUCTO $producto->etcodart - $producto->part_detail_id EXISTE",
                            'otro' => json_encode($arrayInsert)
                        );
                        $this->inserta_into_tabla('log_migraciones', $arrayInsert2);
                    }
                } else {
                    echo "<br>ERROR: ALMACEN ($producto->etcodsuc) NO ENCONTRADO: PARTE -> $producto->etcodart - $producto->part_detail_id <BR>";
                    $arrayInsert2 = array(
                        'tabla' => 'part_detail_warehouses',
                        'mensaje' => "ERROR: ALMACEN ($producto->etcodsuc) NO ENCONTRADO:  CÓDIGO DE PRODUCTO -> $producto->etcodart - $producto->part_detail_id ",
                        'otro' => json_encode($producto)
                    );
                    $this->inserta_into_tabla('log_migraciones', $arrayInsert2);
                }
            }
        }
        $n_offset = (int)$offset + (int)$limit;
        if ($n_offset < $cantidad_productos) {
            echo '<a href="imp_productos_alm/' . $n_offset . '">Siguiente</a>';
            return redirect('imp_productos_alm/' . $n_offset);
        } else echo '<br>Fin de registro de existencias de productos';
    }

    public function importa_grupos_empresas()
    {
        // ---- GRUPOS DE EMPRESAS ---- //
        $arrayWhere = array(
            ['VGJDT', '>', 0],
        );
        if ($registros = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMVGREP', $arrayWhere)) {
            //echo '<pre>';
            //die(print_r($registros));
            $resource_id = 30;
            foreach ($registros as $fila) {
                $arrayWhere = array(
                    ['resource_id', '=', $resource_id],
                    ['name', '=', utf8_encode(strtoupper(trim($fila->vgdscgrp)))],
                );
                if ($this->selecciona_fila_from_tabla('gen_resource_details', $arrayWhere)) {
                    echo '<br>REGISTRO EXISTE: ';
                    echo "$fila->vgcodgrp utf8_encode(strtoupper(trim($fila->vgdscgrp)))";
                } else {
                    $arrayInsert = array(
                        'resource_id' => $resource_id,
                        'code' => $fila->vgcodgrp,
                        'abrv' => $fila->vgcodgrp,
                        'name' => utf8_encode(strtoupper(trim($fila->vgdscgrp))),
                        'description' => utf8_encode(strtoupper(trim($fila->vgdscgrp))),
                        'reg_status' => ($fila->vgsts === 'A') ? 1 : 0
                    );
                    //echo '<pre>';
                    //print_r($arrayInsert);
                    $this->inserta_into_tabla('gen_resource_details', $arrayInsert);
                }
            }
            echo '<br> FIN MIGRACIÓN - GRUPOS DE EMPRESAS';
        }
        // ---- FIN GRUPOS DE EMPRESAS ---- //
    }

    public function importa_cliente_grupo_empresa()
    {
        // ---- CLIENTE-GRUPO DE EMPRESAS ---- //
        $arrayWhere = array(
            ['VDJDT', '>', 0],
        );
        if ($registros = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMVDREP', $arrayWhere)) {
            echo "<br>Cantidad: " . sizeof($registros);
            foreach ($registros as $fila) {
                if (!$datos_cliente = $this->selecciona_fila_from_tabla('customers', array(['code', '=', $fila->vdcodcli]))) {
                    echo "<br>CLIENTE NO EXISTE";
                }
                if (!$datos_grupo = $this->selecciona_fila_from_tabla('gen_resource_details', array(['resource_id', '=',  30], ['code', '=', $fila->vdcodgrp]))) {
                    echo "<br>GRUPO NO EXISTE";
                }
                if ($datos_cliente && $datos_grupo) {
                    $arrayWhere = array(
                        ['customer_id', '=', $datos_cliente->id],
                        ['customer_group_id', '=', $datos_grupo->id],
                    );
                    $arrayInsert = array(
                        'customer_id' => $datos_cliente->id,
                        'customer_group_id' => $datos_grupo->id,
                        'created_at' => date("Y-m-d H:i:s"),
                        'reg_status' => ($fila->vdsts === 'A') ? 1 : 0
                    );
                    CustomerGroup::updateOrCreate(
                        $arrayWhere,
                        $arrayInsert
                    );
                } else {
                    echo '<pre>';
                    print_r($fila);
                }
            }
            echo '<br> FIN MIGRACIÓN - GRUPOS DE EMPRESAS';
        }
        // ---- FIN CLIENTE-GRUPO DE EMPRESAS ---- //
    }

    public function importar_productos_en_oferta_grupo_empresa(Request $request)
    {
        ini_set('max_execution_time', '3000');
        $offset = ($request->offset) ? intval($request->offset) : 0;
        $limit = 50000;
        //$arrayWhere = array(['id', '>', 0]);
        //$almacenes = $this->retorna_almacenes_empresa(1, 'alm.id, alm.code');
        //$grupos_emrpesas = $this->retorna_grupos_empresas();
        echo '<pre>';
        echo 'Inicio: ' . date("d-m-Y H:i:s");
        $select = ['id as group_id', 'code as group_code', 'name as group_name'];
        $grupos = DB::table('gen_resource_details')->select($select)
            ->where('resource_id', 30)
            ->where('reg_status', 1)
            ->get()->toArray();
        //print_r($grupos);
        $x = 0;
        foreach ($grupos as $grupo) {
            $x++;
            $codigo = str_pad($grupo->group_code, 6, 0, STR_PAD_LEFT);
            echo "<br>Nro: $x - Grupo: $codigo";

            $ofertas = DB::connection('ibmi')->table('LIBPRDDAT.MMDDREP')
                ->where('DDFECVGC', '>=', date("Ymd"))
                ->where('DDCODBCO', '10')
                ->where('DDCODPRV', $codigo)
                //->where('DDSTS', 'A')
                ->get()->toArray();

            //print_r($ofertas);
            $y = 0;
            foreach ($ofertas as $oferta) {
                $y++;
                echo "<br>Nro. $y - Oferta " . trim(strtoupper($oferta->dddscref));

                $arrayWhereOffer = array(
                    ['company_id', '=', 1],
                    ['offer_id', '=', $oferta->ddnrocti],
                );
                $arrayInsertOffer = array(
                    'company_id' => 1,
                    'offer_id' => $oferta->ddnrocti,
                    'offer_description' => trim(strtoupper($oferta->dddscref)),
                    'company_group_id' => $grupo->group_id,
                    'offer_type_id' => 1,
                    'origin_code' => '07',
                    'currency_code' => $oferta->ddcodmon,
                    'init_date' => $oferta->ddfecemi,
                    'end_date' => $oferta->ddfecvgc,
                    'created_at' => date("Y-m-d H:i:s"),
                    'reg_status' => ($oferta->ddsts == 'A') ? 1 : 0,
                );
                PartOfferGroup::updateOrCreate(
                    $arrayWhereOffer,
                    $arrayInsertOffer
                );


                $datos_oferta = $this->selecciona_fila_from_tabla('part_offer_groups', $arrayWhereOffer);

                $productos_oferta = DB::connection('ibmi')->table('LIBPRDDAT.MMDEREP')
                    ->where('DENROCTI', $datos_oferta->offer_id)
                    ->get()->toArray();
                //print_r($productos_oferta);
                $z = 0;
                foreach ($productos_oferta as $producto) {
                    $z++;
                    $sku = $producto->decodlin . '' . $producto->decodori . '' . $producto->decodmar . '' . trim(strtoupper(utf8_encode($producto->decodart)));

                    echo "<br>Nro. $z - SKU: $sku";

                    if (!$datos_producto = DB::table('v_partes')->where('sku', $sku)->first()) {
                        echo ("<br>SKU: $sku NO ENCONTRADO");
                        continue;
                    }
                    $arrayWhereProductOffer = array(
                        ['part_offer_group_id', '=', $datos_oferta->id],
                        ['part_detail_id', '=', $datos_producto->part_detail_id]
                    );
                    $arrayInsertProductOffer = [
                        'part_offer_group_id' => $datos_oferta->id,
                        'part_detail_id' => $datos_producto->part_detail_id,
                        'offer_price' => $producto->deimppre,
                        'discount_rate' => $producto->deprcdct,
                        'created_at' => date("Y-m-d H:i:s"),
                        'reg_status' => ($producto->dests == 'A') ? 1 : 0,
                    ];
                    PartOfferGroupDetail::updateOrCreate(
                        $arrayWhereProductOffer,
                        $arrayInsertProductOffer
                    );
                    //part_offer_group_details

                    //die(print_r($datos_producto));
                    echo "<br>OFERTA-PRODUCTO AGREGADO ($oferta->ddnrocti - $datos_producto->sku)";
                }
            }
        }
        echo '<br>LISTO';
        exit;

        $ofertas = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMDDREP', array(['DDFECVGC', '>', '20220914'], ['DDCODBCO', '=', '10'], ['DDCODPRV', '=', '000015']));
        $grupo_id = 1377;
        //die(print_r($ofertas));
        if ($ofertas && is_array($ofertas)) {
            foreach ($ofertas as $oferta) {

                $arrayWhereOffer = array(
                    ['company_id', '=', 1],
                    ['offer_id', '=', $oferta->ddnrocti],
                );
                if (!$datos_oferta = $this->selecciona_fila_from_tabla('part_offer_groups', $arrayWhereOffer)) {
                    $arrayInsertOffer = array(
                        'company_id' => 1,
                        'offer_id' => $oferta->ddnrocti,
                        'offer_description' => trim(strtoupper($oferta->dddscref)),
                        'company_group_id' => $grupo_id,
                        'offer_type_id' => 1,
                        'origin_code' => '07',
                        'currency_code' => $oferta->ddcodmon,
                        'init_date' => $oferta->ddfecemi,
                        'end_date' => $oferta->ddfecvgc,
                        'created_at' => date("Y-m-d H:i:s"),
                        'reg_status' => ($oferta->ddsts == 'A') ? 1 : 0,
                    );
                    $this->inserta_into_tabla('part_offer_groups', $arrayInsertOffer);
                    $datos_oferta = $this->selecciona_fila_from_tabla('part_offer_groups', $arrayWhereOffer);
                }

                $productos_oferta = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMDEREP', array(['DENROCTI', '=', $datos_oferta->offer_id]));
                //echo '<pre>';
                //die(print_r($productos_oferta));
                foreach ($productos_oferta as $producto) {
                    $sku = $producto->decodlin . '' . $producto->decodori . '' . $producto->decodmar . '' . trim(strtoupper($producto->decodart));
                    $arrayWhere = array(
                        ['line_code', '=', $producto->decodlin],
                        ['origin_code', '=', $producto->decodori],
                        ['trademark_code', '=', $producto->decodmar],
                        ['part_code', '=', trim(strtoupper($producto->decodart))],
                    );
                    if (!$datos_producto = $this->selecciona_fila_from_tabla('v_partes', $arrayWhere)) {
                        echo ("<br>SKU: $sku NO ENCONTRADO");
                        continue;
                    }
                    $arrayWhereProductOffer = array(
                        ['part_offer_group_id', '=', $datos_oferta->id],
                        ['part_detail_id', '=', $datos_producto->part_detail_id]
                    );
                    if (!$this->selecciona_fila_from_tabla('part_offer_group_details', $arrayWhereProductOffer)) {
                        $arrayInsertProductOffer = array(
                            'part_offer_group_id' => $datos_oferta->id,
                            'part_detail_id' => $datos_producto->part_detail_id,
                            'offer_price' => $producto->deimppre,
                            'discount_rate' => $producto->deprcdct,
                            'created_at' => date("Y-m-d H:i:s"),
                            'reg_status' => ($producto->dests == 'A') ? 1 : 0,
                        );
                        $this->inserta_into_tabla('part_offer_group_details', $arrayInsertProductOffer);
                    }

                    //die(print_r($datos_producto));
                    echo "<br>OFERTA-PRODUCTO AGREGADO ($oferta->ddnrocti - $datos_producto->sku)";
                }
            }
        } else {
            die('NO HAY OFERTAS');
        }
        die('listo');


        $cantidad_productos = $this->retorna_datos_productos_almacen(0, 999999, true);
        $datos_productos = $this->retorna_datos_productos_almacen($offset, $limit);
        echo "<br>Total Productos: $cantidad_productos <br>";
        echo "Desde: $offset <br>";
        echo "Registros: $limit <br>";
        $i = ($offset > 0) ? ($offset - 1) : 0;
        if (is_array($datos_productos)) {
            foreach ($datos_productos as $producto) {
                $i++;
                $stock_inicial = round(floatval($producto->etstkini), 2);
                $stock_ingreso = round(floatval($producto->etcaning), 2);
                $stock_salida = round(floatval($producto->etcansld), 2);
                $total_stock = ($stock_inicial + $stock_ingreso - $stock_salida);
                $warehouse_id = $this->busca_datos_vector2($producto->etcodsuc, $almacenes);
                if ($warehouse_id) {
                    $arrayWhere = array(['part_detail_id', '=', $producto->part_detail_id], ['warehouse_id', '=', $warehouse_id]);
                    $arrayInsert = array(
                        'part_detail_id' => $producto->part_detail_id,
                        'warehouse_id' => $warehouse_id,
                        'init_qty' => $stock_inicial,
                        'in_qty' => $stock_ingreso,
                        'out_qty' => $stock_salida,
                        'in_warehouse_stock' => $total_stock,
                        'reg_status' => ($producto->etsts === 'A') ? 1 : 0,
                        'created_at' => date("Y-m-d H:i:s")
                    );
                    if (!$this->selecciona_fila_from_tabla('part_detail_warehouses', $arrayWhere)) {
                        $parte_nueva = $this->inserta_into_tabla('part_detail_warehouses', $arrayInsert);
                        echo '<br>(' . $i . ') Parte registrada (' . $parte_nueva . ') -> ' . $producto->etcodart;
                    } else {
                        $arrayInsert2 = array(
                            'tabla' => 'part_detail_warehouses',
                            'mensaje' => "CÓDIGO DE PRODUCTO $producto->etcodart - $producto->part_detail_id EXISTE",
                            'otro' => json_encode($arrayInsert)
                        );
                        $this->inserta_into_tabla('log_migraciones', $arrayInsert2);
                    }
                } else {
                    echo "<br>ERROR: ALMACEN ($producto->etcodsuc) NO ENCONTRADO: PARTE -> $producto->etcodart - $producto->part_detail_id <BR>";
                    $arrayInsert2 = array(
                        'tabla' => 'part_detail_warehouses',
                        'mensaje' => "ERROR: ALMACEN ($producto->etcodsuc) NO ENCONTRADO:  CÓDIGO DE PRODUCTO -> $producto->etcodart - $producto->part_detail_id ",
                        'otro' => json_encode($producto)
                    );
                    $this->inserta_into_tabla('log_migraciones', $arrayInsert2);
                }
            }
        }
        $n_offset = (int)$offset + (int)$limit;
        if ($n_offset < $cantidad_productos) {
            echo '<a href="imp_productos_alm/' . $n_offset . '">Siguiente</a>';
            return redirect('imp_productos_alm/' . $n_offset);
        } else echo '<br>Fin de registro de existencias de productos';
    }


    public function importar_productos_en_oferta_grupo_empresa_old(Request $request)
    {
        ini_set('max_execution_time', '3000');
        $offset = ($request->offset) ? intval($request->offset) : 0;
        $limit = 50000;
        //$arrayWhere = array(['id', '>', 0]);
        //$almacenes = $this->retorna_almacenes_empresa(1, 'alm.id, alm.code');
        //$grupos_emrpesas = $this->retorna_grupos_empresas();
        echo '<pre>';
        echo 'Inicio: ' . date("d-m-Y H:i:s");
        $ofertas = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMDDREP', array(['DDFECVGC', '>', '20220914'], ['DDCODBCO', '=', '10'], ['DDCODPRV', '=', '000015']));
        $grupo_id = 1377;
        //die(print_r($ofertas));
        if ($ofertas && is_array($ofertas)) {
            foreach ($ofertas as $oferta) {

                $arrayWhereOffer = array(
                    ['company_id', '=', 1],
                    ['offer_id', '=', $oferta->ddnrocti],
                );
                if (!$datos_oferta = $this->selecciona_fila_from_tabla('part_offer_groups', $arrayWhereOffer)) {
                    $arrayInsertOffer = array(
                        'company_id' => 1,
                        'offer_id' => $oferta->ddnrocti,
                        'offer_description' => trim(strtoupper($oferta->dddscref)),
                        'company_group_id' => $grupo_id,
                        'offer_type_id' => 1,
                        'origin_code' => '07',
                        'currency_code' => $oferta->ddcodmon,
                        'init_date' => $oferta->ddfecemi,
                        'end_date' => $oferta->ddfecvgc,
                        'created_at' => date("Y-m-d H:i:s"),
                        'reg_status' => ($oferta->ddsts == 'A') ? 1 : 0,
                    );
                    $this->inserta_into_tabla('part_offer_groups', $arrayInsertOffer);
                    $datos_oferta = $this->selecciona_fila_from_tabla('part_offer_groups', $arrayWhereOffer);
                }

                $productos_oferta = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMDEREP', array(['DENROCTI', '=', $datos_oferta->offer_id]));
                //echo '<pre>';
                //die(print_r($productos_oferta));
                foreach ($productos_oferta as $producto) {
                    $sku = $producto->decodlin . '' . $producto->decodori . '' . $producto->decodmar . '' . trim(strtoupper($producto->decodart));
                    $arrayWhere = array(
                        ['line_code', '=', $producto->decodlin],
                        ['origin_code', '=', $producto->decodori],
                        ['trademark_code', '=', $producto->decodmar],
                        ['part_code', '=', trim(strtoupper($producto->decodart))],
                    );
                    if (!$datos_producto = $this->selecciona_fila_from_tabla('v_partes', $arrayWhere)) {
                        echo ("<br>SKU: $sku NO ENCONTRADO");
                        continue;
                    }
                    $arrayWhereProductOffer = array(
                        ['part_offer_group_id', '=', $datos_oferta->id],
                        ['part_detail_id', '=', $datos_producto->part_detail_id]
                    );
                    if (!$this->selecciona_fila_from_tabla('part_offer_group_details', $arrayWhereProductOffer)) {
                        $arrayInsertProductOffer = array(
                            'part_offer_group_id' => $datos_oferta->id,
                            'part_detail_id' => $datos_producto->part_detail_id,
                            'offer_price' => $producto->deimppre,
                            'discount_rate' => $producto->deprcdct,
                            'created_at' => date("Y-m-d H:i:s"),
                            'reg_status' => ($producto->dests == 'A') ? 1 : 0,
                        );
                        $this->inserta_into_tabla('part_offer_group_details', $arrayInsertProductOffer);
                    }

                    //die(print_r($datos_producto));
                    echo "<br>OFERTA-PRODUCTO AGREGADO ($oferta->ddnrocti - $datos_producto->sku)";
                }
            }
        } else {
            die('NO HAY OFERTAS');
        }
        die('listo');


        $cantidad_productos = $this->retorna_datos_productos_almacen(0, 999999, true);
        $datos_productos = $this->retorna_datos_productos_almacen($offset, $limit);
        echo "<br>Total Productos: $cantidad_productos <br>";
        echo "Desde: $offset <br>";
        echo "Registros: $limit <br>";
        $i = ($offset > 0) ? ($offset - 1) : 0;
        if (is_array($datos_productos)) {
            foreach ($datos_productos as $producto) {
                $i++;
                $stock_inicial = round(floatval($producto->etstkini), 2);
                $stock_ingreso = round(floatval($producto->etcaning), 2);
                $stock_salida = round(floatval($producto->etcansld), 2);
                $total_stock = ($stock_inicial + $stock_ingreso - $stock_salida);
                $warehouse_id = $this->busca_datos_vector2($producto->etcodsuc, $almacenes);
                if ($warehouse_id) {
                    $arrayWhere = array(['part_detail_id', '=', $producto->part_detail_id], ['warehouse_id', '=', $warehouse_id]);
                    $arrayInsert = array(
                        'part_detail_id' => $producto->part_detail_id,
                        'warehouse_id' => $warehouse_id,
                        'init_qty' => $stock_inicial,
                        'in_qty' => $stock_ingreso,
                        'out_qty' => $stock_salida,
                        'in_warehouse_stock' => $total_stock,
                        'reg_status' => ($producto->etsts === 'A') ? 1 : 0,
                        'created_at' => date("Y-m-d H:i:s")
                    );
                    if (!$this->selecciona_fila_from_tabla('part_detail_warehouses', $arrayWhere)) {
                        $parte_nueva = $this->inserta_into_tabla('part_detail_warehouses', $arrayInsert);
                        echo '<br>(' . $i . ') Parte registrada (' . $parte_nueva . ') -> ' . $producto->etcodart;
                    } else {
                        $arrayInsert2 = array(
                            'tabla' => 'part_detail_warehouses',
                            'mensaje' => "CÓDIGO DE PRODUCTO $producto->etcodart - $producto->part_detail_id EXISTE",
                            'otro' => json_encode($arrayInsert)
                        );
                        $this->inserta_into_tabla('log_migraciones', $arrayInsert2);
                    }
                } else {
                    echo "<br>ERROR: ALMACEN ($producto->etcodsuc) NO ENCONTRADO: PARTE -> $producto->etcodart - $producto->part_detail_id <BR>";
                    $arrayInsert2 = array(
                        'tabla' => 'part_detail_warehouses',
                        'mensaje' => "ERROR: ALMACEN ($producto->etcodsuc) NO ENCONTRADO:  CÓDIGO DE PRODUCTO -> $producto->etcodart - $producto->part_detail_id ",
                        'otro' => json_encode($producto)
                    );
                    $this->inserta_into_tabla('log_migraciones', $arrayInsert2);
                }
            }
        }
        $n_offset = (int)$offset + (int)$limit;
        if ($n_offset < $cantidad_productos) {
            echo '<a href="imp_productos_alm/' . $n_offset . '">Siguiente</a>';
            return redirect('imp_productos_alm/' . $n_offset);
        } else echo '<br>Fin de registro de existencias de productos';
    }

    public function retorna_datos_clientes_formas_pago($offset = 0, $limit = 10000)
    {
        $sql = "select a.arcodcia, a.arcodcli, a.arfrmpag, a.armodpag, a.arsts, b.B2CNDPAG, b.B2DIAPLZ 
        from LIBPRDDAT.MMARREP a 
        inner join LIBPRDDAT.MMB2REP b on a.arfrmpag=b.b2frmpag and a.armodpag=b.b2modpag
        WHERE a.arsts='A' 
        and b.b2sts='A' 
        AND b.B2DIAPLZ = (case 
        when a.armodpag='FA' then '30'
        when a.armodpag='BV' then '30'
        when a.armodpag='CH' then '30'
        ELSE '0'
        end)
        order by a.arcodcli            
        LIMIT :cantidad
        OFFSET :desde";
        //echo "<br>$offset - $limit";
        //die($sql);
        //and a.arcodcli in('106844','106845','106846','106847','106848','106849','106850')

        return DB::connection('ibmi')->select(DB::raw($sql), array('cantidad' => $limit, 'desde' => $offset));
    }

    public function actualiza_formas_pago_clientes(Request $request)
    {
        ini_set('max_execution_time', '3000');

        $offset = ($request->offset) ? intval($request->offset) : 0;
        $limit = 10000;

        $cantidad = DB::table('customers as c')->where('reg_status', 1)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw('cpm.id'))
                    ->from('customer_payment_methods as cpm')
                    ->whereColumn('c.id', 'cpm.customer_id');
            })->count();

        $clientes = DB::table('customers as c')->where('reg_status', 1)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw('cpm.id'))
                    ->from('customer_payment_methods as cpm')
                    ->whereColumn('c.id', 'cpm.customer_id');
            })
            ->offset($offset)
            ->limit($limit)
            ->get()->toArray();

        $whereInField = 'resource_id';
        $whereInArray = array(31, 32, 33);
        $arraySelect = ['id', 'code', 'name', 'resource_id'];
        $array_tipos = $this->selecciona_from_tabla_where_in('gen_resource_details', $whereInField, $whereInArray, $arraySelect);
        $i = 0;
        echo "<br>Cantidad: $cantidad";
        foreach ($clientes as $cliente) {
            $i++;
            echo "<br>($i) Cliente: $cliente->code";
            //INACTIVAR REGISTROS EN TABLA CUSTOMER_PAYMENT_METHODS PARA ESE CLIENTE
            DB::table('customer_payment_methods')
                ->where('customer_id', $cliente->id)
                ->update(['reg_status' => 0, 'updated_at' => date("Y-m-d H:i:s")]);


            //BUSCAR FORMAS DE PAGO DE CLIENTE
            $select = ['a.arcodcia', 'a.arcodcli', 'a.arfrmpag', 'a.armodpag', 'a.arsts', 'b.B2CNDPAG', 'b.B2DIAPLZ'];

            $cliente->formas_pago = DB::connection('ibmi')
                ->table('LIBPRDDAT.MMARREP as a')
                ->select($select)
                ->join('LIBPRDDAT.MMB2REP as b', function ($join) {
                    $join->on('a.arfrmpag', '=', 'b.b2frmpag');
                    $join->on('a.armodpag', '=', 'b.b2modpag');
                })
                ->where('a.arcodcli', $cliente->code)
                ->where('a.arcodcia', '10')
                ->where('a.arsts', 'A')
                ->where('b.b2sts', 'A')
                ->whereRaw("b.B2DIAPLZ = (case 
                when a.armodpag='FA' then '30'
                when a.armodpag='BV' then '30'
                when a.armodpag='CH' then '30'
                ELSE '0'
                end)")
                ->get()->toArray();

            echo " - Qty formas de pago: " . sizeof($cliente->formas_pago);
            foreach ($cliente->formas_pago as $fila) {
                $forma_pago_id = $this->busca_datos_vector($fila->arfrmpag, 31, $array_tipos);
                $modalidad_pago_id = $this->busca_datos_vector($fila->armodpag, 32, $array_tipos);
                $condicion_pago_id = $this->busca_datos_vector($fila->b2cndpag, 33, $array_tipos);

                if (!$condicion_pago_id) {
                    echo "<br>Cliente: $fila->arcodcli - Cond. Pago: $fila->b2cndpag";
                    exit;
                }

                if ($forma_pago_id && $modalidad_pago_id && $condicion_pago_id) {
                    $arrayWhere = array(
                        ['customer_id', '=', $cliente->id],
                        ['payment_method_id', '=', $forma_pago_id],
                        ['payment_modality_id', '=', $modalidad_pago_id],
                        ['payment_condition_id', '=', $condicion_pago_id]
                    );
                    $arrayInsert = array(
                        'customer_id' => $cliente->id,
                        'payment_method_id' => $forma_pago_id,
                        'payment_modality_id' => $modalidad_pago_id,
                        'payment_condition_id' => $condicion_pago_id,
                        'created_at' => date("Y-m-d H:i:s"),
                        'updated_at' => date("Y-m-d H:i:s")
                    );

                    CustomerPaymentMethod::updateOrCreate(
                        $arrayWhere,
                        $arrayInsert
                    );
                    echo " - Actualizado - FM: $fila->arfrmpag - MP: $fila->armodpag - CP: $fila->b2cndpag  -> FECHA: " . date('Y-m-d H:i:s');
                } else {
                    echo "<br>Error: $forma_pago_id --- $modalidad_pago_id -- $condicion_pago_id";
                }
            }
        }

        $n_offset = (int)$offset + (int)$limit;
        if ($n_offset < $cantidad) {
            echo '<a href="imp_clientes_fp/' . $n_offset . '">Siguiente</a>';
            return redirect('imp_clientes_fp/' . $n_offset);
        } else echo '<br>Fin de registro de formas de pago de clientes';
    }


    public function actualiza_formas_pago_clientes_old(Request $request)
    {
        $offset = ($request->offset) ? intval($request->offset) : 0;
        $limit = 30000;
        $registros = $this->retorna_datos_clientes_formas_pago($offset, $limit);
        $cli_no_existe = 0;
        if ($registros && is_array($registros)) {
            $whereInField = 'resource_id';
            $whereInArray = array(31, 32, 33);
            $arraySelect = ['id', 'code', 'name', 'resource_id'];
            $array_tipos = $this->selecciona_from_tabla_where_in('gen_resource_details', $whereInField, $whereInArray, $arraySelect);
            $i = 0;
            foreach ($registros as $fila) {
                $i++;
                echo "<br>Registro: $i";
                $codigo_cliente = trim($fila->arcodcli);
                if (!$datos_cliente = $this->selecciona_fila_from_tabla('customers', array(['code', '=', $codigo_cliente]))) {
                    $registro = new stdClass();
                    $registro->datos_consulta = DB::connection('ibmi')->table('LIBPRDDAT.MMAKREP')->where('AKCODCLI', $codigo_cliente)->first();
                    if ($registro->datos_consulta) {
                        $resultado = SyncCustomer::mmakrep_maestro_clientes($registro);
                        if ($resultado) {
                            echo " - CLIENTE REGISTRADO";
                            if (!$datos_cliente = DB::table('customers')->where('code', $codigo_cliente)->first()) {
                                $cli_no_existe++;
                                echo '<br> ' . $codigo_cliente . ' CLIENTE NO EXISTE';
                                echo '<pre>';
                                print_r($fila);
                                continue;
                            }
                        }
                    } else {
                        $cli_no_existe++;
                        echo '<br>CLIENTE NO EXISTE';
                        echo '<pre>';
                        print_r($fila);
                        continue;
                    }
                }

                if ($datos_cliente) {
                    //INACTIVAR REGISTROS EN TABLA CUSTOMER_PAYMENT_METHODS PARA ESE CLIENTE
                    DB::table('customer_payment_methods')
                        ->where('customer_id', $datos_cliente->id)
                        ->update(['reg_status' => 0, 'updated_at' => date("Y-m-d H:i:s")]);

                    $forma_pago_id = $this->busca_datos_vector($fila->arfrmpag, 31, $array_tipos);
                    $modalidad_pago_id = $this->busca_datos_vector($fila->armodpag, 32, $array_tipos);
                    $condicion_pago_id = $this->busca_datos_vector($fila->b2cndpag, 33, $array_tipos);
                    if (!$condicion_pago_id) {
                        echo "<br>Cliente: $fila->arcodcli - Cond. Pago: $fila->b2cndpag";
                        exit;
                    }
                    if ($forma_pago_id && $modalidad_pago_id && $condicion_pago_id) {
                        $arrayWhere = array(
                            ['customer_id', '=', $datos_cliente->id],
                            ['payment_method_id', '=', $forma_pago_id],
                            ['payment_modality_id', '=', $modalidad_pago_id],
                            ['payment_condition_id', '=', $condicion_pago_id]
                        );
                        $arrayInsert = array(
                            'customer_id' => $datos_cliente->id,
                            'payment_method_id' => $forma_pago_id,
                            'payment_modality_id' => $modalidad_pago_id,
                            'payment_condition_id' => $condicion_pago_id,
                            'created_at' => date("Y-m-d H:i:s"),
                            'updated_at' => date("Y-m-d H:i:s")
                        );

                        CustomerPaymentMethod::updateOrCreate(
                            $arrayWhere,
                            $arrayInsert
                        );
                        echo " - Cliente: {$datos_cliente->code}";
                    } else {
                        echo "<br>Error: $forma_pago_id --- $modalidad_pago_id -- $condicion_pago_id";
                    }
                }
            }
        }

        echo "<br> CLIENTES QUE NO EXISTEN: $cli_no_existe";
    }


    public function importar_lista_negra_clientes()
    {
        $sql = "select BLCODCLI FROM LIBPRDDAT.COBCKLIS WHERE BLSTS='A' AND BLCODCIA = '10'";
        $bloqueados = DB::connection('ibmi')->select(DB::raw($sql));
        //echo '<pre>';
        //die(print_r($bloqueados));
        if ($bloqueados && is_array($bloqueados)) {
            foreach ($bloqueados as $cliente) {
                $code = trim(strtoupper($cliente->blcodcli));
                if ($datos_cliente = $this->selecciona_fila_from_tabla('customers', array(['code', '=', $code]))) {
                    $arrayWhere = array(['customer_id', '=', $datos_cliente->id]);
                    $arrayInsert = array('customer_id' => $datos_cliente->id);
                    BlackListCustomer::updateOrCreate(
                        $arrayWhere,
                        $arrayInsert
                    );
                }
            }
        }
    }

    public function importar_productos_almacen_as400(Request $request)
    {
        ini_set('max_execution_time', '3000');

        $util = new Utilidades();

        $offset = ($request->offset) ? intval($request->offset) : 0;
        $limit = 50000;
        $codigo_almacen = $request->codigo_almacen;
        $tipo_almacen_in = array(3, 4);
        //die($codigo_almacen);
        $array_skus_no_encontrados = array();
        $arrayWhere = array(
            ['alm.code', '=', $codigo_almacen]
        );
        if (!$datos_almacen = $this->retorna_datos_almacen($arrayWhere)) {
            die('ALMACÉN NO ENCONTRADO');
        }
        $warehouse_id = $datos_almacen->id;
        //die(print_r($datos_almacen));

        if ($offset == 0) {
            $arrayWhereWh = array(
                ['warehouse_id', '=', $warehouse_id],
                //['in_warehouse_stock', '>', 0]
            );
            $arrayUpdateWh = array(
                'init_qty' => 0,
                'in_qty' => 0,
                'out_qty' => 0
            );
            DB::table('part_detail_warehouses')
                ->where($arrayWhereWh)
                ->update($arrayUpdateWh);
            echo '<br>STOCK DE ALMACÉN ' . $codigo_almacen . ' INICIAIZADO<br>';
            // exit;
        }

        echo 'Inicio: ' . date("d-m-Y H:i:s");
        $arrayWhereAS = array(
            ['ETCODCIA', '=', $this->codCia],
            ['ETCODSUC', '=', $codigo_almacen],
            ['(ETSTKINI + ETCANING - ETCANSLD )', '>', 0]
        );
        $arraySelectAS = 'ETCODCIA, ETCODSUC, ETCODLIN, upper(trim(ETCODART)) as etcodart, ETCODORI, ETCODMAR, ETSTKINI, ETCANING, ETCANSLD, ETCODALM, ETSTS, ETIMPLIS, ETIMPPMI';

        $cantidad_productos = $util->retorna_datos_productos_almacen_as400($arrayWhereAS, 'MMETREP', 0, 0, 1);
        $datos_productos = $util->retorna_datos_productos_almacen_as400($arrayWhereAS, $arraySelectAS, $offset, $limit, 0);
        echo "<br>Total Productos: $cantidad_productos <br>";
        echo "Desde: $offset <br>";
        echo "Registros: $limit <br>";
        //$i = ($offset > 0) ? ($offset - 1) : 0;
        $i = 0;
        if (is_array($datos_productos)) {
            $no_encontrados = 0;
            $encontrados = 0;
            foreach ($datos_productos as $producto) {
                $i++;
                $stock_inicial = round(floatval($producto->etstkini), 2);
                $stock_ingreso = round(floatval($producto->etcaning), 2);
                $stock_salida = round(floatval($producto->etcansld), 2);
                $total_stock = ($stock_inicial + $stock_ingreso - $stock_salida);
                $linea = $producto->etcodlin;
                $origen = $producto->etcodori;
                $marca = $producto->etcodmar;
                $codigo = trim(strtoupper(utf8_encode($producto->etcodart)));
                $sku = $linea . $origen . $marca . $codigo;
                $precio_lista = round(floatval($producto->etimplis), 2);
                $precio_minimo = round(floatval($producto->etimppmi), 2);

                if (!$datos_part_detail = $util->retorna_datos_parte(array(['sku', '=', $sku]))) {
                    echo "<br>SKU -> $sku: INICIAR PROCESO DE REGISTRO DE PRODUCTO";
                    if (!$datos_part_detail = $util->crear_producto_dado_linea_origen_marca_codigo($linea, $origen, $marca, $codigo)) {
                        $no_encontrados++;
                        echo "<br>$no_encontrados SKU NO ENCONTRADO -> $sku";
                        array_push($array_skus_no_encontrados, $sku);
                    } else {
                        $encontrados++;
                        echo "<br>($encontrados) SKU: $sku - Existencia: $total_stock";

                        if ($warehouse_id) {
                            $arrayWhere = array(['part_detail_id', '=', $datos_part_detail->part_detail_id], ['warehouse_id', '=', $warehouse_id]);
                            $arrayInsert = array(
                                'part_detail_id' => $datos_part_detail->part_detail_id,
                                'warehouse_id' => $warehouse_id,
                                'init_qty' => $stock_inicial,
                                'in_qty' => $stock_ingreso,
                                'out_qty' => $stock_salida,
                                'reg_status' => $datos_part_detail->reg_status,
                                'created_at' => date("Y-m-d H:i:s")
                            );
                            PartDetailWarehouse::updateOrCreate(
                                $arrayWhere,
                                $arrayInsert
                            );
                            //echo '<pre>';
                            //die(print_r($arrayInsert));
                        } else {
                            echo "<br>ERROR: ALMACEN ($producto->etcodsuc) NO ENCONTRADO: PARTE -> $producto->etcodart - $datos_part_detail->part_detail_id <BR>";
                            $arrayInsert2 = array(
                                'tabla' => 'part_detail_warehouses',
                                'mensaje' => "ERROR: ALMACEN ($producto->etcodsuc) NO ENCONTRADO:  CÓDIGO DE PRODUCTO -> $producto->etcodart - $datos_part_detail->part_detail_id ",
                                'otro' => json_encode($producto)
                            );
                            $this->inserta_into_tabla('log_migraciones', $arrayInsert2);
                        }
                    }
                } else {
                    $encontrados++;
                    echo "<br>($encontrados) SKU: $sku - Existencia: $total_stock";

                    //ACTUALIZAR PRECIO EN PART_PART_DETAILS
                    $arrayWhere = array(['id', '=', $datos_part_detail->part_detail_id]);
                    $arrayInsert = array(
                        'list_price' => $precio_lista,
                        'min_price' => $precio_minimo,
                        'updated_at' => date("Y-m-d H:i:s")
                    );
                    PartPartDetail::updateOrCreate(
                        $arrayWhere,
                        $arrayInsert
                    );

                    if ($warehouse_id) {
                        $arrayWhere = array(['part_detail_id', '=', $datos_part_detail->part_detail_id], ['warehouse_id', '=', $warehouse_id]);
                        $arrayInsert = array(
                            'part_detail_id' => $datos_part_detail->part_detail_id,
                            'warehouse_id' => $warehouse_id,
                            'init_qty' => $stock_inicial,
                            'in_qty' => $stock_ingreso,
                            'out_qty' => $stock_salida,
                            'reg_status' => $datos_part_detail->reg_status,
                            'created_at' => date("Y-m-d H:i:s")
                        );
                        PartDetailWarehouse::updateOrCreate(
                            $arrayWhere,
                            $arrayInsert
                        );
                        //echo '<pre>';
                        //die(print_r($arrayInsert));
                    } else {
                        echo "<br>ERROR: ALMACEN ($producto->etcodsuc) NO ENCONTRADO: PARTE -> $producto->etcodart - $datos_part_detail->part_detail_id <BR>";
                        $arrayInsert2 = array(
                            'tabla' => 'part_detail_warehouses',
                            'mensaje' => "ERROR: ALMACEN ($producto->etcodsuc) NO ENCONTRADO:  CÓDIGO DE PRODUCTO -> $producto->etcodart - $datos_part_detail->part_detail_id ",
                            'otro' => json_encode($producto)
                        );
                        $this->inserta_into_tabla('log_migraciones', $arrayInsert2);
                    }
                }
            }
        }
        echo '<br>Fin de registro de existencias de productos';
        echo "<br>Cantidad de SKUs no encontrados: " . sizeof($array_skus_no_encontrados) . "<br>";
        echo json_encode($array_skus_no_encontrados);
        echo '<pre>';
        print_r($array_skus_no_encontrados);
        /*
        $n_offset = (int)$offset + (int)$limit;
        if ($n_offset < $cantidad_productos) {
            echo '<a href="imp_productos_alm_as/' . $n_offset . '">Siguiente</a>';
            return redirect('imp_productos_alm_as/' . $n_offset);
        } else echo '<br>Fin de registro de existencias de productos';
        */
    }

    public function retorna_datos_almacen($arrayWhere)
    {
        return DB::table('establishments AS alm')
            ->where($arrayWhere)
            ->whereIn('id', array(10, 18, 11, 12, 13, 14, 15, 16))
            ->whereIn('alm.type_id', array(3, 4))
            ->first();
    }
    /*
    public function retorna_datos_productos_almacen_as400($arrayWhere, $str_select, $offset = 0, $limit = 10000, $retorna_cantidad = false)
    {
        if ($retorna_cantidad) {
            return DB::connection('ibmi')->table('LIBPRDDAT.MMETREP')
                ->where($arrayWhere)
                ->select('ETCODART')
                ->count();
        } else {
            return  DB::connection('ibmi')
                ->table('LIBPRDDAT.MMETREP')
                //->distinct()
                ->select($str_select)
                ->where($arrayWhere)
                ->limit($limit)
                //->offset($offset)
                ->get()
                ->toArray();

            //->toSql();
            //die($sql);
        }
    }
    */


    /*
    public function retorna_datos_parte($arrayWhere)
    {
        return DB::table('v_partes')
            ->where($arrayWhere)
            ->first();
    }
    */



    public function importar_pedidos(Request $request)
    {
        ini_set('max_execution_time', '3000');
        $init_time = date("Y-m-d H:i:s");
        $offset = ($request->offset) ? intval($request->offset) : 0;
        $limit = 5000;
        $registros = $this->retorna_pedidos_cabecera_db2($offset, $limit);
        $cantidad_pedidos = 30000;

        if ($registros && is_array($registros)) {
            echo "<br>Cantidad Registros: " . sizeof($registros);

            $arraySelect = ['id', 'code', 'name', 'type_id', 'reg_status'];
            $empresas = $this->selecciona_from_tabla('establishments', array(['type_id', '=', 1]), $arraySelect);
            foreach ($empresas as $empresa) {
                $empresa->sucursales = $this->selecciona_from_tabla('establishments', array(['type_id', '=', 2], ['parent_establishment_id', '=', $empresa->id]), $arraySelect);
                $empresa->almacenes = $this->retorna_almacenes_empresa($empresa->id, 'alm.id, alm.code, alm.name, alm.type_id, alm.reg_status');
                $empresa->usuarios = $this->selecciona_from_tabla('v_users_by_companies', array(['company_id', '=', $empresa->id]));
            }
            echo '<pre>';
            //die(print_r($empresas ));
            $whereInField = 'resource_id';
            /*
            Empresas de envío   -> 23
            Formas de pago  -> 31
            Modalidades de pago -> 32
            Condiciones de pago -> 33
            Documentos SUNAT      -> 38
            Origen de pedidos  -> 44
            */
            $whereInArray = array(23, 31, 32, 33, 38, 44);
            $arraySelect = ['id', 'code', 'name', 'resource_id'];
            $array_tipos = $this->selecciona_from_tabla_where_in('gen_resource_details', $whereInField, $whereInArray, $arraySelect);
            //die(print_r($array_tipos));
            $i = 0;
            foreach ($registros as $registro) {
                $i++;
                echo "<br>Registro: $i <br>";

                echo "CIA: $registro->cbcodcia - SUC: $registro->cbcodsuc - PDC: $registro->cbnropdc - COT: $registro->cbnroped - Cliente: $registro->cbcodcli - " . trim($registro->cbrazsoc) . " - Estado: $registro->cbsts - Fecha: $registro->cbfecdoc <br>";
                if ($registro->cbcodcli === '082410') {
                    (print_r($registro));
                }
                $company_id = ($registro->cbcodcia === '10') ? 1 : 2;
                $array_company = array_column($empresas, 'code');
                $index_company = array_search($registro->cbcodcia, $array_company);

                $usuarios = $empresas[$index_company]->usuarios;
                $user_code_array = array_column($usuarios, 'operator_code');
                $index = array_search($registro->cbcodven, $user_code_array);
                $seller_id = ($usuarios[$index]->user_user_id !== false) ? $usuarios[$index]->user_user_id : false;

                $attended_by_user_array = array_column($usuarios, 'user_code');
                $index = array_search(trim($registro->cbatnpor), $attended_by_user_array);
                $attended_by_id = ($usuarios[$index]->user_user_id !== false) ? $usuarios[$index]->user_user_id : false;

                $user_transaction_array = array_column($usuarios, 'user_code');
                $index = array_search(trim($registro->cbusr), $user_transaction_array);
                $user_transaction_id = ($usuarios[$index]->user_user_id !== false) ? $usuarios[$index]->user_user_id : false;

                $payment_type_id = $this->busca_datos_vector($registro->cbfrmpag, 31, $array_tipos);
                $payment_condition_id = $this->busca_datos_vector($registro->cbmodpag, 32, $array_tipos);
                $credit_days = $this->busca_datos_vector($registro->cbcndpag, 33, $array_tipos);
                if ($registro->cbimptot > 0) {
                    $porcentaje_igv = round((float)round($registro->cbimpimp, 2) * 100 / (float) round($registro->cbimptot, 2), 2);
                    $total = round(((float)$registro->cbimptot + (float)$registro->cbimpimp), 2);
                    //$subtotal = round((float)$registro->cbimptot, 2);
                } else {
                    $porcentaje_igv = 0;
                    $total = round(((float)$registro->cbimptot + (float)$registro->cbimpimp), 2);
                    //$subtotal = 0;
                }

                $order_time = ($registro->cbjtm && $registro->cbjtm > 0) ? $registro->cbjtm : 1;


                switch ($registro->q1codtrn) {
                        //delivery_type_id: 504 -> Entrega M&M , 503 -> Recojo Cliente, 505 -> Empresa de envíos
                    case '003174':
                        $delivery_type_id = 504;
                        $carrier_id = null;
                        break;
                    case '003151':
                        $delivery_type_id = 503;
                        $carrier_id = null;
                        break;
                    case '003174':
                        $delivery_type_id = 505;
                        $carrier_id = $this->busca_datos_vector($registro->cbmodpag, 23, $array_tipos);
                        break;

                    default:
                        $delivery_type_id = 504;
                        $carrier_id = null;
                        break;
                }

                if ($index_company !== false) {
                    $sub_array = array_column($empresas[$index_company]->sucursales, 'code');
                    $index_subsidiary = array_search($registro->cbcodsuc, $sub_array);
                    //echo "<br>Suc.: " . $registro->cbcodsuc;
                    //print_r($sub_array);
                    if ($index_subsidiary !== false) {
                        $subsidiary_id = $empresas[$index_company]->sucursales[$index_subsidiary]->id;

                        $ware_array = array_column($empresas[$index_company]->almacenes, 'code');
                        $index_warehouse = array_search($registro->cbcodalm, $ware_array);
                        if ($index_warehouse !== false) {
                            $warehouse_id = $empresas[$index_company]->almacenes[$index_warehouse]->id;
                            if (!$datos_cliente = $this->selecciona_fila_from_tabla('customers', array(['code', '=', $registro->cbcodcli]))) {
                                echo ('<br>Cliente no encontrado ' . $registro->cbcodcli . ' -> Llamar a función para crear clientes');
                                $registro->datos_consulta = $this->selecciona_fila_from_tabla_db2('LIBPRDDAT.MMAKREP', array(['AKCODCLI', '=', $registro->cbcodcli]));
                                $CustomerController = new SyncCustomer;
                                $resultado = $CustomerController->mmakrep_maestro_clientes($registro);
                                if ($resultado) {
                                    $n_offset = (int)($request->offset) + $i - 5;
                                    if (($n_offset) < $cantidad_pedidos) {
                                        echo '<a href="' . $n_offset . '" target="_SELF">Siguiente</a>';
                                        echo '<script>window.location.replace("' . $n_offset . '");</script>';
                                        exit;
                                    }
                                }
                                die("Resultado crear cliente: $resultado");
                            }
                            $origin_id = $this->busca_datos_vector($registro->cboriped, 44, $array_tipos);
                            $document_type_id = $this->busca_datos_vector($registro->cbtipdoc, 38, $array_tipos);
                            $arrayInsert = array(
                                'company_id' => $company_id,
                                'subsidiary_id' => $subsidiary_id,
                                'warehouse_id' => $warehouse_id,
                                'customer_id' => $datos_cliente->id,
                                'document_type_id' => $document_type_id,
                                'origin_id' => ($origin_id) ? $origin_id : null,
                                'order_number' => $registro->cbnropdc,
                                'order_date' => $registro->cbfecdoc,
                                'order_time' => $order_time,
                                'seller_id' => $seller_id,
                                'attended_by_user_id' => $attended_by_id,
                                'currency_id' => ($registro->cbcodmon === '02') ? 391 : 390,
                                'payment_type_id' => $payment_type_id,
                                'payment_condition_id' => $payment_condition_id,
                                'credit_days' => $credit_days,
                                'delivery_type_id' => $delivery_type_id,
                                'carrier_id' => $carrier_id,
                                'customer_class_discount_rate' => $registro->cbdctcls,
                                'customer_class_total_discount' => $registro->cbimpdcc,
                                'payment_type_discount_rate' => $registro->cbdctcnd,
                                'payment_type_total_discount' => $registro->cbimpdcp,
                                'global_discount' => 0,
                                'subtotal' => $registro->cbimptot,
                                'igv_tax' => $porcentaje_igv,
                                'total_tax' => $registro->cbimpimp,
                                'total' => $total,
                                'user_id' => $user_transaction_id,
                                'reg_doc_status' => $registro->cbstsdgr,
                                'reg_order_doc_status' => $registro->cbstspdo,
                                'reg_status' => ($registro->cbsts === 'A') ? 1 : 0,
                            );
                            $arrayWhere = array(
                                ['company_id', '=', $company_id],
                                ['subsidiary_id', '=', $subsidiary_id],
                                ['order_number', '=', $registro->cbnropdc],
                            );
                            //(print_r($arrayInsert));
                            //print_r($registro);
                            $json_pedido = OrdOrder::updateOrCreate(
                                $arrayWhere,
                                $arrayInsert
                            );
                            $arrayWhere = array(
                                ['CECODCIA', '=', $registro->cbcodcia],
                                ['CECODSUC', '=', $registro->cbcodsuc],
                                ['CENROPDC', '=', $registro->cbnropdc],
                            );
                            $datos_productos_pedido_db2 = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMCEREP', $arrayWhere);
                            if ($datos_productos_pedido_db2 && is_array($datos_productos_pedido_db2)) {
                                foreach ($datos_productos_pedido_db2 as $producto_pedido) {
                                    $codigo_articulo = trim(utf8_encode($producto_pedido->cecodart));
                                    $sku = $producto_pedido->cecodlin . $producto_pedido->cecodori . $producto_pedido->cecodmar . $codigo_articulo;
                                    echo "<br>SKU: $sku";
                                    if (!$datos_producto = $this->selecciona_fila_from_tabla('v_partes', array(['sku', '=', $sku]))) {
                                        echo ("<br>SKU: $sku NO ENCONTRADO");
                                        $arrayInsert2 = array(
                                            'tabla' => 'part_part_details',
                                            'mensaje' => "SKU $sku NO ENCONTRADO",
                                            'otro' => json_encode($arrayInsert)
                                        );
                                        $this->inserta_into_tabla('log_migraciones', $arrayInsert2);
                                        //die(print_r($producto_pedido));
                                    } else {
                                        $datos_pedido = json_decode($json_pedido);
                                        //echo "<br> id_pedido: $datos_pedido->id";
                                        //print_r($producto_pedido);
                                        //die(print_r($datos_producto));
                                        $arrayInsert = array(
                                            'order_id' => $datos_pedido->id,
                                            'sku_id' => $datos_producto->part_detail_id,
                                            'item_number' => $producto_pedido->ceitem01,
                                            'item_description' => trim(utf8_encode($producto_pedido->cedscart)),
                                            'item_qty' => $producto_pedido->cecandsp,
                                            'item_qty_return' => $producto_pedido->cecandev,
                                            'item_price' => $producto_pedido->ceimppre,
                                            'item_line_discount' => $producto_pedido->cedctlin,
                                            'item_discount' => $producto_pedido->cedctadi,
                                            'item_tax' => $producto_pedido->ceprcimp,
                                            'reg_status' => ($producto_pedido->cests === 'A') ? 1 : 0,
                                        );
                                        // print_r($arrayInsert);
                                        $arrayWhere = array(
                                            ['order_id', '=', $datos_pedido->id],
                                            ['item_number', '=', $producto_pedido->ceitem01],
                                        );
                                        $json_pedido_detalle = OrdOrderDetail::updateOrCreate(
                                            $arrayWhere,
                                            $arrayInsert
                                        );
                                        //die($json_pedido_detalle);
                                    }
                                }
                            }
                        } else {
                            print_r($registro);
                            die("<br>Almacen no encontrado");
                        }
                    } else {
                        echo ("<br>Sucursal no encontrada");
                        print_r($registro);
                        exit;
                    }
                } else {
                    echo ('ERROR: INDICE NO ENCONTRADO');
                    echo "<br>Index: $index_company  - $registro->cbcodcia<br>";
                    print_r($registro);
                    die(print_r($array_company));
                }
            }
        } else {
            echo '<br>No se encontraron registros';
            exit;
        }
        $end_time = date("Y-m-d H:i:s");
        echo "<br>Inicio: $init_time - Fin: $end_time";
        /*
        $n_offset = (int)$offset + (int)$limit;
        if ($n_offset < $cantidad_pedidos) {
            echo '<a href="imp_pedidos/' . $n_offset . '">Siguiente</a>';
            return redirect('imp_pedidos/' . $n_offset);
        } else echo '<br>Fin de registro de pedidos';
		*/
    }


    public function retorna_pedidos_cabecera_db2($offset, $limit)
    {
        $util = new Utilidades();
        $fecha_inicio = $util->sumar_restar_dias_fecha(date("Ymd"), 2, 'restar');
        $fecha_inicio = $util->retorna_fecha_formateada('Y-m-d H:i:s', 'Ymd', $fecha_inicio);
        $sql = "SELECT ped.*, track.q1codtrn
        FROM LIBPRDDAT.MMCBREP ped
        left JOIN LIBPRDDAT.MMQ1REP AS track on ped.cbcodcia=track.q1codcia and ped.cbcodsuc=track.q1codsuc and ped.cbnropdc=track.q1nropdc and ped.cbcodcli=track.q1codcli
        where ped.CBSTS='A' AND ped.CBCODCIA='10' 
		AND ped.CBFECDOC >= $fecha_inicio
        LIMIT :cantidad
        OFFSET :desde";
        echo $sql;
        $rs = DB::connection('ibmi')->select(DB::raw($sql), array('cantidad' => $limit, 'desde' => $offset));
        return (is_array($rs) && sizeof($rs) > 0) ? $rs : false;
    }

    public function importar_fac_bol_fiscales(Request $request)
    {
        $util = new Utilidades();
        ini_set('max_execution_time', '3000');
        $init_time = date("Y-m-d H:i:s");
        $offset = ($request->offset) ? intval($request->offset) : 0;
        $limit = 5000;
        $registros = $this->retorna_fac_bol_db2($offset, $limit);
        $cantidad_documentos = 335000;
        echo '<pre>';
        echo "<br>Offset: $offset - Limit: $limit";
        echo "<br>Cantidad Registros: " . sizeof($registros);
        //print_r($registros);
        if ($registros && is_array($registros)) {
            $whereInField = 'resource_id';
            /*
            Tipo Documento Fiscal (AS-SUNAT) -> 38
            Serie de documentos fiscales -> 39
            Tipo Moneda -> 12
            */
            $whereInArray = array(12, 38, 39);
            $arraySelect = ['id', 'code', 'name', 'resource_id'];
            $array_tipos = $this->selecciona_from_tabla_where_in('gen_resource_details', $whereInField, $whereInArray, $arraySelect);
            //die(print_r($array_tipos));
            $arraySelect = ['id', 'code', 'name', 'type_id', 'reg_status'];
            $empresas = $this->selecciona_from_tabla('establishments', array(['type_id', '=', 1]), $arraySelect);
            foreach ($empresas as $empresa) {
                $empresa->sucursales = $this->selecciona_from_tabla('establishments', array(['type_id', '=', 2], ['parent_establishment_id', '=', $empresa->id]), $arraySelect);
                $empresa->almacenes = $this->retorna_almacenes_empresa($empresa->id, 'alm.id, alm.code, alm.name, alm.type_id, alm.reg_status');
                $empresa->usuarios = $this->selecciona_from_tabla('v_users_by_companies', array(['company_id', '=', $empresa->id]));
            }
            //echo "<br>Empresas: ";
            //print_r($empresas[0]->almacenes);
            $i = 0;
            foreach ($registros as $registro) {
                $i++;
                echo "<br><br>Fila: $i ";
                echo "<br>{$registro->sctefec} - {$registro->sctesuca} - {$registro->sctepdca} - {$registro->scteseri} - {$registro->sctecorr} - {$registro->sctcclie} - " . trim($registro->sctcrzso);
                $codigo_vendedor = $util->retorna_limpia_cadena($registro->sctvende);
                $usuario_transaccion = $util->retorna_limpia_cadena($registro->sctcusut);
                $support_as = $util->retorna_limpia_cadena($registro->sctcsust);
                $total_importe_neto = $registro->sctgneto;
                $total_neto_gravado = $registro->sctggexe;
                $total_neto_exonerado = $registro->sctgnexo;
                $total_igv = $registro->sctgigv;
                $total_importe = $registro->sctgtota;
                $importe_retencion = $registro->sctmoncd;
                $monto_base_retencion = $registro->sctbascd;
                $total_importe = $registro->sctgtota;

                $tipo_factura_boleta = $registro->sctipfac;
                $tipo_documento_fiscal = $registro->sctetdoc;

                $currencyCode = ($registro->sctctmon === 'USD') ? '02' : '01';
                $currency_id = $this->busca_datos_vector($currencyCode, 12, $array_tipos);

                $company_id = ($registro->scteciaa === '10') ? 1 : 2;
                $array_company = array_column($empresas, 'code');
                $index_company = array_search($registro->scteciaa, $array_company);

                if ($index_company !== false) {
                    $sub_array = array_column($empresas[$index_company]->sucursales, 'code');
                    $index_subsidiary = array_search($registro->sctesuca, $sub_array);
                    if ($index_subsidiary !== false) {
                        $subsidiary_id = $empresas[$index_company]->sucursales[$index_subsidiary]->id;
                    }
                    //print_r($registro);
                    $ware_array = array_column($empresas[$index_company]->almacenes, 'code');
                    $index_warehouse = array_search($registro->sctealma, $ware_array);
                    if ($index_warehouse !== false) {
                        $warehouse_id = $empresas[$index_company]->almacenes[$index_warehouse]->id;
                    }
                }

                if (!$datos_cliente = $this->selecciona_fila_from_tabla('customers', array(['code', '=', $registro->sctcclie]))) {
                    //print_r($registro);
                    echo ('<br>Cliente no encontrado ' . $registro->sctcclie . ' -> Registrar ');
                    $registro->datos_consulta = $this->selecciona_fila_from_tabla_db2('LIBPRDDAT.MMAKREP', array(['AKCODCLI', '=', $registro->sctcclie]));
                    $CustomerController = new SyncCustomer;
                    $resultado = $CustomerController->mmakrep_maestro_clientes($registro);
                    if ($resultado) {
                        $n_offset = (int)($request->offset) + $i - 5;
                        echo '<a href="' . $n_offset . '" target="_SELF">Siguiente</a>';
                        echo '<script>window.location.replace("' . $n_offset . '");</script>';
                        exit;
                    }
                }

                $usuarios = $empresas[$index_company]->usuarios;
                $user_code_array = array_column($usuarios, 'operator_code');
                $index = array_search($codigo_vendedor, $user_code_array);
                $seller_id = ($usuarios[$index]->user_user_id !== false) ? $usuarios[$index]->user_user_id : false;

                $user_transaction_array = array_column($usuarios, 'user_code');
                $index = array_search($usuario_transaccion, $user_transaction_array);
                $user_transaction_id = ($usuarios[$index]->user_user_id !== false) ? $usuarios[$index]->user_user_id : false;

                $currency_id = $this->busca_datos_vector($currencyCode, 12, $array_tipos);
                $fiscal_document_type_id = $this->busca_datos_vector($registro->sctipfac, 38, $array_tipos);
                $serie_id = $this->busca_datos_vector($registro->sctesera, 39, $array_tipos);
                if (!$serie_id) {
                    echo '<br>Serie no encontrada: ' . $registro->sctesera . '<br>';
                    print_r($registro);
                    exit;
                }

                $arrayWhere = array(
                    ['company_id', '=', $company_id],
                    ['subsidiary_id', '=', $subsidiary_id],
                    ['order_number', '=', $registro->sctepdca],
                );
                if ($datos_pedido = $this->selecciona_fila_from_tabla('ord_orders', $arrayWhere)) {
                    $id_pedido = $datos_pedido->id;
                } else {
                    $id_pedido = null;
                }

                $fecha_hora_status_add = ($registro->sctcfect > 0) ? $registro->sctcfect . ' ' . sprintf("%'.06d", $registro->sctchort) : null;
                $fecha_hora_status_baja = ($registro->sctcfecb > 0) ? $registro->sctcfecb . ' ' . sprintf("%'.06d", $registro->sctchorb) : null;
                $fecha_anulacion = ($registro->sctcfef3 > 0) ? $registro->sctcfef3 : null;

                $arrayInsert = array(
                    'company_id' => $company_id,
                    'company_name' => $util->retorna_limpia_cadena($registro->scterzso),
                    'company_address' => $util->retorna_limpia_cadena($registro->sctedire),
                    'company_ubigeo' => $util->retorna_limpia_cadena($registro->scteubig),
                    'subsidiary_id' => $subsidiary_id,
                    'warehouse_id' => $warehouse_id,
                    'customer_id' => $datos_cliente->id,
                    'customer_name' => $util->retorna_limpia_cadena($registro->sctcrzso),
                    'customer_address' => $util->retorna_limpia_cadena($registro->sctcdire),
                    'fiscal_document_type_id' => $fiscal_document_type_id,
                    'internal_number' => $registro->sctepdca,
                    'reg_date' => $registro->sctefec,
                    'year_month' => $registro->scteper,
                    'serie_as_id' => $serie_id,
                    'correlative_as_number' => $registro->sctecora,
                    'serie_fiscal_id' => $serie_id,
                    'correlative_fiscal_number' => $registro->sctecorr,
                    'seller_id' => $seller_id,
                    'auth_code_as' => $registro->sctcodau,
                    'support_as' => $support_as,
                    'currency_id' => $currency_id,
                    'net_amount' => $total_importe_neto,
                    'net_amount_taxed' => $total_neto_gravado,
                    'net_exonerated_amount' => $total_neto_exonerado,
                    'principal_tax_amount' => $total_igv,
                    'total_amount' => $total_importe,
                    'withholding_tax_amount' => $importe_retencion,
                    'withholding_tax_base_amount' => $monto_base_retencion,
                    'additional_tax_code' => trim($registro->sctccimp),
                    'additional_tax_amount' => $registro->sctmtimp,
                    'additional_tax_rate' => $registro->scttasai,
                    'ord_order_id' => $id_pedido,
                    'parent_fiscal_document_id' => null,
                    'additional_status' => $registro->sctcstst,
                    'additional_status_date' => $fecha_hora_status_add,
                    'down_status_reg' => $registro->sctcstsb,
                    'down_status_date' => $fecha_hora_status_baja,
                    'reg_status' => ($registro->sctcsts === 'A') ? 1 : 0,
                    'anullment_date' => $fecha_anulacion,
                    'user_id' => $user_transaction_id,
                    'created_at' => date("Y-m-d H:i:s"),
                );
                //print_r($arrayInsert);
                //$util->inserta_into_tabla('fiscal_documents', $arrayInsert);
                $arrayWhere = array(
                    ['company_id', '=', $company_id],
                    ['subsidiary_id', '=', $subsidiary_id],
                    ['fiscal_document_type_id', '=', $fiscal_document_type_id],
                    ['internal_number', '=', $registro->sctepdca],
                    ['serie_fiscal_id', '=', $serie_id],
                    ['correlative_fiscal_number', '=', $registro->sctecorr],
                );
                if (!$util->selecciona_fila_from_tabla('fiscal_documents', $arrayWhere)) {
                    print_r($arrayWhere);
                    $util->inserta_into_tabla('fiscal_documents', $arrayInsert);
                } else {
                    echo ' - Registro ya existe';
                }
            }
            $end_time = date("Y-m-d H:i:s");
            echo "<br>Inicio: $init_time - Fin: $end_time";
            /*
            $n_offset = (int)$offset + (int)$limit;
            if ($n_offset < $cantidad_documentos) {
                echo '<a href="/imp_facbol/' . $n_offset . '">Siguiente</a>';
                return redirect('imp_facbol/' . $n_offset);
            } else echo '<br>Fin de registro de documentos fiscales';
			*/
        } else {
            echo "<br>No se encontraron registros";
        }
    }


    public function importar_nc_fiscales(Request $request)
    {
        $util = new Utilidades();
        ini_set('max_execution_time', '3000');
        $init_time = date("Y-m-d H:i:s");
        $offset = ($request->offset) ? intval($request->offset) : 0;
        $limit = 5000;
        $registros = $this->retorna_nc_db2($offset, $limit);
        $cantidad_documentos = 135000;
        echo '<pre>';
        echo "<br>Offset: $offset - Limit: $limit";
        //print_r($registros[0]);
        if ($registros && is_array($registros)) {
            $whereInField = 'resource_id';
            /*
            Tipo Documento Fiscal (AS-SUNAT) -> 38
            Serie de documentos fiscales -> 39
            Tipo Moneda -> 12
            */
            $whereInArray = array(12, 38, 39);
            $arraySelect = ['id', 'code', 'name', 'resource_id'];
            $array_tipos = $this->selecciona_from_tabla_where_in('gen_resource_details', $whereInField, $whereInArray, $arraySelect);
            //die(print_r($array_tipos));
            $arraySelect = ['id', 'code', 'name', 'type_id', 'reg_status'];
            $empresas = $this->selecciona_from_tabla('establishments', array(['type_id', '=', 1]), $arraySelect);
            foreach ($empresas as $empresa) {
                $empresa->sucursales = $this->selecciona_from_tabla('establishments', array(['type_id', '=', 2], ['parent_establishment_id', '=', $empresa->id]), $arraySelect);
                $empresa->almacenes = $this->retorna_almacenes_empresa($empresa->id, 'alm.id, alm.code, alm.name, alm.type_id, alm.reg_status');
                $empresa->usuarios = $this->selecciona_from_tabla('v_users_by_companies', array(['company_id', '=', $empresa->id]));
            }
            //echo "<br>Empresas: ";
            //print_r($empresas);
            $i = 0;
            foreach ($registros as $registro) {
                $i++;
                echo "<br>Registro: $i<br>";
                $codigo_vendedor = $util->retorna_limpia_cadena($registro->sctvende);
                $usuario_transaccion = $util->retorna_limpia_cadena($registro->sctcusut);
                $total_importe_neto = $registro->sctgneto;
                $total_neto_gravado = $registro->sctggexe;
                $total_neto_exonerado = $registro->sctgnexo;
                $total_igv = $registro->sctgigv;
                $total_importe = $registro->sctgtota;
                $importe_retencion = $registro->sctmoncd;
                $monto_base_retencion = $registro->sctbascd;
                $total_importe = $registro->sctgtota;

                $tipo_factura_boleta = $registro->sctipfac;
                $tipo_documento_fiscal = $registro->sctetdoc;

                $currencyCode = ($registro->sctctmon === 'USD') ? '02' : '01';
                $currency_id = $this->busca_datos_vector($currencyCode, 12, $array_tipos);

                $company_id = ($registro->scteciaa === '10') ? 1 : 2;
                $array_company = array_column($empresas, 'code');
                $index_company = array_search($registro->scteciaa, $array_company);

                if ($index_company !== false) {
                    $sub_array = array_column($empresas[$index_company]->sucursales, 'code');
                    $index_subsidiary = array_search($registro->sctesuca, $sub_array);
                    if ($index_subsidiary !== false) {
                        $subsidiary_id = $empresas[$index_company]->sucursales[$index_subsidiary]->id;
                    }
                    echo "<br>Almacén: $registro->sctealma - IndexCompany: $index_company";
                    //print_r($registro);
                    $ware_array = array_column($empresas[$index_company]->almacenes, 'code');
                    $index_warehouse = array_search($registro->sctealma, $ware_array);
                    if ($index_warehouse !== false) {
                        $warehouse_id = $empresas[$index_company]->almacenes[$index_warehouse]->id;
                    }
                }

                if (!$datos_cliente = $this->selecciona_fila_from_tabla('customers', array(['code', '=', $registro->sctcclie]))) {
                    //print_r($registro);
                    //die('<br>Cliente no encontrado ' . $registro->sctcclie);
                    echo ('<br>Cliente no encontrado ' . $registro->sctcclie . ' -> Registrar ');
                    $registro->datos_consulta = $this->selecciona_fila_from_tabla_db2('LIBPRDDAT.MMAKREP', array(['AKCODCLI', '=', $registro->sctcclie]));
                    $CustomerController = new SyncCustomer;
                    $resultado = $CustomerController->mmakrep_maestro_clientes($registro);
                    if ($resultado) {
                        $n_offset = (int)($request->offset) + $i - 5;
                        echo '<a href="' . $n_offset . '" target="_SELF">Siguiente</a>';
                        echo '<script>window.location.replace("' . $n_offset . '");</script>';
                        exit;
                    }
                }
                $arrayWhere = array(
                    ['company_id', '=', $company_id],
                    ['customer_id', '=', $datos_cliente->id],
                    ['serie_fiscal', '=', trim($registro->sctserrf)],
                    ['correlative_fiscal_number', '=', trim($registro->sctcorrf)],
                );
                if (!$documento_padre = $this->selecciona_fila_from_tabla('v_fac_bol_cab', $arrayWhere)) {
                    print_r($arrayWhere);
                    //print_r($registro);
                    //die('<br>Documento padre (Pedido) no encontrado ');
                    continue;
                }

                $usuarios = $empresas[$index_company]->usuarios;
                $user_code_array = array_column($usuarios, 'operator_code');
                $index = array_search($codigo_vendedor, $user_code_array);
                $seller_id = ($usuarios[$index]->user_user_id !== false) ? $usuarios[$index]->user_user_id : false;

                $user_transaction_array = array_column($usuarios, 'user_code');
                $index = array_search($usuario_transaccion, $user_transaction_array);
                $user_transaction_id = ($usuarios[$index]->user_company_id !== false) ? $usuarios[$index]->user_company_id : false;

                $currency_id = $this->busca_datos_vector($currencyCode, 12, $array_tipos);
                $fiscal_document_type_id = $this->busca_datos_vector($registro->sctetdoc, 38, $array_tipos);
                $serie_id = $this->busca_datos_vector($registro->sctesera, 39, $array_tipos);
                if (!$serie_id) {
                    echo $registro->sctesera;
                    print_r($registro);
                    exit;
                }

                $arrayWhere = array(
                    ['company_id', '=', $company_id],
                    ['subsidiary_id', '=', $subsidiary_id],
                    ['order_number', '=', $registro->sctepdca],
                );
                if ($datos_pedido = $this->selecciona_fila_from_tabla('ord_orders', $arrayWhere)) {
                    $id_pedido = $datos_pedido->id;
                } else {
                    $id_pedido = null;
                }

                $fecha_hora_status_add = ($registro->sctcfect > 0) ? $registro->sctcfect . ' ' . sprintf("%'.06d", $registro->sctchort) : null;
                $fecha_hora_status_baja = ($registro->sctcfecb > 0) ? $registro->sctcfecb . ' ' . sprintf("%'.06d", $registro->sctchorb) : null;
                $fecha_anulacion = ($registro->sctcfef3 > 0) ? $registro->sctcfef3 : null;

                $arrayWhere = array(
                    ['company_id', '=', $company_id],
                    ['subsidiary_id', '=', $subsidiary_id],
                    ['internal_number', '=', $registro->sctepdca],
                    ['fiscal_document_type_id', '=', $fiscal_document_type_id],
                    ['serie_fiscal_id', '=', $serie_id],
                    ['correlative_fiscal_number', '=', $registro->sctecorr],
                );

                $arrayInsert = array(
                    'company_id' => $company_id,
                    'company_name' => $util->retorna_limpia_cadena($registro->scterzso),
                    'company_address' => $util->retorna_limpia_cadena($registro->sctedire),
                    'company_ubigeo' => $util->retorna_limpia_cadena($registro->scteubig),
                    'subsidiary_id' => $subsidiary_id,
                    'warehouse_id' => $warehouse_id,
                    'customer_id' => $datos_cliente->id,
                    'customer_name' => $util->retorna_limpia_cadena($registro->sctcrzso),
                    'customer_address' => $util->retorna_limpia_cadena($registro->sctcdire),
                    'fiscal_document_type_id' => $fiscal_document_type_id,
                    'internal_number' => $registro->sctepdca,
                    'reg_date' => $registro->sctefec,
                    'year_month' => $registro->scteper,
                    'serie_as_id' => $serie_id,
                    'correlative_as_number' => $registro->sctecora,
                    'serie_fiscal_id' => $serie_id,
                    'correlative_fiscal_number' => $registro->sctecorr,
                    'seller_id' => $seller_id,
                    'auth_code_as' => $registro->sctcodau,
                    'support_as' => $registro->sctcsust,
                    'currency_id' => $currency_id,
                    'net_amount' => $total_importe_neto,
                    'net_amount_taxed' => $total_neto_gravado,
                    'net_exonerated_amount' => $total_neto_exonerado,
                    'principal_tax_amount' => $total_igv,
                    'total_amount' => $total_importe,
                    'withholding_tax_amount' => $importe_retencion,
                    'withholding_tax_base_amount' => $monto_base_retencion,
                    'additional_tax_code' => trim($registro->sctccimp),
                    'additional_tax_amount' => $registro->sctmtimp,
                    'additional_tax_rate' => $registro->scttasai,
                    'ord_order_id' => $id_pedido,
                    'parent_fiscal_document_id' => $documento_padre->fiscal_document_id,
                    'additional_status' => $registro->sctcstst,
                    'additional_status_date' => $fecha_hora_status_add,
                    'down_status_reg' => $registro->sctcstsb,
                    'down_status_date' => $fecha_hora_status_baja,
                    'reg_status' => ($registro->sctcsts === 'A') ? 1 : 0,
                    'anullment_date' => $fecha_anulacion,
                    'user_id' => $user_transaction_id,
                    'created_at' => date("Y-m-d H:i:s"),
                );

                if (!$util->selecciona_fila_from_tabla('fiscal_documents', $arrayWhere)) {
                    //print_r($arrayInsert);
                    //print_r($arrayWhere);
                    $util->inserta_into_tabla('fiscal_documents', $arrayInsert);
                }
            }
            $end_time = date("Y-m-d H:i:s");
            echo "<br>Inicio: $init_time - Fin: $end_time";
            /*
            $n_offset = (int)$offset + (int)$limit;
            if ($n_offset < $cantidad_documentos) {
                echo '<a href="imp_nc_fiscales/' . $n_offset . '">Siguiente</a>';
                return redirect('imp_nc_fiscales/' . $n_offset);
            } else echo '<br>Fin de registro de NC fiscales';
			*/
        } else {
            echo "<br>No se encontraron registros";
        }
    }

    public function retorna_fac_bol_db2($offset, $limit)
    {
        /*
		$sql = "SELECT *
        FROM LIBPRDDAT.SNT_CTRAM 
        WHERE SCTCSTS='A' and SCTECIAA='10'
		and SCTEPER = 202206
        --and SCTEPER between 202101 and 202212
        and SCTETDOC in ('01','03')
        -- LIMIT $limit
        -- OFFSET $offset";
		*/
        $util = new Utilidades();
        $fecha_inicio = $util->sumar_restar_dias_fecha(date("Ymd"), 2, 'restar');
        $fecha_inicio = $util->retorna_fecha_formateada('Y-m-d H:i:s', 'Ymd', $fecha_inicio);
        $sql = "SELECT *
        FROM LIBPRDDAT.SNT_CTRAM 
        WHERE SCTCSTS='A' and SCTECIAA='10'
		and SCTEFEC >= $fecha_inicio
        and SCTETDOC in ('01','03')
		LIMIT $limit
        OFFSET $offset";
        //echo $sql;exit;
        $result =  DB::connection('ibmi')->select(DB::raw($sql));
        //die('Cantidad: '.sizeof($result));
        return ($result && is_array($result)) ? $result : false;
    }

    public function retorna_nc_db2($offset, $limit)
    {
        $util = new Utilidades();
        $fecha_inicio = $util->sumar_restar_dias_fecha(date("Ymd"), 2, 'restar');
        $fecha_inicio = $util->retorna_fecha_formateada('Y-m-d H:i:s', 'Ymd', $fecha_inicio);
        $sql = "SELECT *
        FROM LIBPRDDAT.SNT_CTRAM 
        WHERE SCTCSTS='A'
        and SCTEFEC >= $fecha_inicio
        and SCTETDOC in ('07')
        LIMIT $limit
        OFFSET $offset";
        //echo $sql;exit;
        $result =  DB::connection('ibmi')->select(DB::raw($sql));
        //die('Cantidad: '.sizeof($result));
        return ($result && is_array($result)) ? $result : false;
    }


    public function importar_ordenes_de_compra(Request $request)
    {
        $util = new Utilidades();
        ini_set('max_execution_time', '3000');
        $init_time = date("Y-m-d H:i:s");
        $offset = ($request->offset) ? intval($request->offset) : 0;
        $limit = 5000;
        $registros = $this->retorna_ordenes_de_compra($offset, $limit);
        $cantidad_documentos = 135000;
        echo "<br>Offset: $offset - Limit: $limit";
        if ($registros && is_array($registros)) {
            $whereInField = 'resource_id';
            /*
            Tipo Documento Fiscal (AS-SUNAT) -> 38
            Serie de documentos fiscales -> 39
            Tipo Moneda -> 12
            */
            $whereInArray = array(12, 38, 39);
            $arraySelect = ['id', 'code', 'name', 'resource_id'];
            $array_tipos = $this->selecciona_from_tabla_where_in('gen_resource_details', $whereInField, $whereInArray, $arraySelect);
            //die(print_r($array_tipos));
            $arraySelect = ['id', 'code', 'name', 'type_id', 'reg_status'];
            $empresas = $this->selecciona_from_tabla('establishments', array(['type_id', '=', 1]), $arraySelect);
            foreach ($empresas as $empresa) {
                $empresa->sucursales = $this->selecciona_from_tabla('establishments', array(['type_id', '=', 2], ['parent_establishment_id', '=', $empresa->id]), $arraySelect);
                $empresa->almacenes = $this->retorna_almacenes_empresa($empresa->id, 'alm.id, alm.code, alm.name, alm.type_id, alm.reg_status');
                $empresa->usuarios = $this->selecciona_from_tabla('v_users_by_companies', array(['company_id', '=', $empresa->id]));
            }
            //echo "<br>Empresas: ";
            //print_r($empresas);
            $i = 0;
            foreach ($registros as $registro) {
                $i++;
                echo "<br>Registro $i";
                $codigo_usuario = $util->retorna_limpia_cadena($registro->ckusr);
                $descripcion = $util->retorna_limpia_cadena($registro->ckdscref);
                $total_importe_bruto = (float) round($registro->ckimpbrt, 2);
                $total_importe_neto =  (float) round($registro->ckimpnet, 2);
                $porcentaje_igv =  (float) round($registro->ckprcimp, 2);
                $flete =  (float) round($registro->ckimpflt, 2);
                $porcentajedescuento1 =  (float) round($registro->ckprcds1, 2);
                $porcentajedescuento2 =  (float) round($registro->ckprcds2, 2);
                $descuento1 =  (float) round($registro->ckimpdc1, 2);
                $descuento2 =  (float) round($registro->ckimpdc2, 2);
                $gastos =  (float) round($registro->ckimpgto, 2);
                $total_impuestos =  (float) round($registro->ckimpimp, 2);

                $currencyCode = $registro->ckcodmon;
                $currency_id = $this->busca_datos_vector($currencyCode, 12, $array_tipos);

                $company_id = ($registro->ckcodcia === '10') ? 1 : 2;
                $array_company = array_column($empresas, 'code');
                $index_company = array_search($registro->ckcodcia, $array_company);

                if ($index_company !== false) {
                    $sub_array = array_column($empresas[$index_company]->sucursales, 'code');
                    $index_subsidiary = array_search($registro->ckcodsuc, $sub_array);
                    if ($index_subsidiary !== false) {
                        $subsidiary_id = $empresas[$index_company]->sucursales[$index_subsidiary]->id;
                    }

                    $ware_array = array_column($empresas[$index_company]->almacenes, 'code');
                    $index_warehouse = array_search($registro->ckcodalm, $ware_array);
                    if ($index_warehouse !== false) {
                        $warehouse_id = $empresas[$index_company]->almacenes[$index_warehouse]->id;
                    }
                }

                if (!$datos_proveedor = $this->selecciona_fila_from_tabla('providers', array(['code', '=', $registro->ckcodprv]))) {
                    if (!$datos_proveedor = $this->registrar_proveedor($registro->ckcodprv)) {
                        print_r($registro);
                        die('<br>Proveedor no encontrado ' . $registro->ckcodprv);
                    }
                }

                $usuarios = $empresas[$index_company]->usuarios;
                $user_code_array = array_column($usuarios, 'user_code');
                $index = array_search($codigo_usuario, $user_code_array);
                $user_id = ($usuarios[$index]->user_user_id !== false) ? $usuarios[$index]->user_user_id : false;

                //REGISTRAR ENCABEZADO DE ORDEN DE COMPRA
                $arrayWhereOCEnc = array(
                    ['subsidiary_id', '=', $subsidiary_id],
                    ['purchase_number', '=', $registro->cknroocp],
                );

                $arrayInsertOCEnc = array(
                    'company_id' => $company_id,
                    'subsidiary_id' => $subsidiary_id,
                    'warehouse_id' => $warehouse_id,
                    'provider_id' => $datos_proveedor->id,
                    'currency_id' => $currency_id,
                    'purchase_number' => $registro->cknroocp,
                    'reg_date' => $registro->ckfecemi,
                    'estimated_delivery_date' => $registro->ckfecees,
                    'discount_rate_1' => $porcentajedescuento1,
                    'discount_rate_2' => $porcentajedescuento2,
                    'tax_rate' => $porcentaje_igv,
                    'total_amount' => $total_importe_bruto,
                    'discount_amount_1' => $descuento1,
                    'discount_amount_2' => $descuento2,
                    'freight_amount' => $flete,
                    'outlay_amount' => $gastos,
                    'tax_amount' => $total_impuestos,
                    'net_amount' => $total_importe_neto,
                    'created_at' => date("Y-m-d H:i:s"),
                    'reg_status' => ($registro->cksts === 'A') ? 1 : 0,
                    'purchase_description' => $descripcion,
                    'user_id' => $user_id
                );

                if (!$datos_oc = $this->selecciona_fila_from_tabla('purchase_orders', $arrayWhereOCEnc)) {
                    PurchaseOrder::Create(
                        $arrayInsertOCEnc
                    );
                    $datos_oc = $this->selecciona_fila_from_tabla('purchase_orders', $arrayWhereOCEnc);
                }
                //FIN - REGISTRAR ENCABEZADO DE ORDEN DE COMPRA

                //REGISTRAR DETALLE DE ORDEN DE COMPRA
                $productos_oc = $this->retorna_productos_orden_de_compra($registro->ckcodcia, $registro->cknroocp);
                if ($productos_oc && is_array($productos_oc))
                    foreach ($productos_oc as $producto) {
                        $sku = $producto->cmcodlin . $producto->cmcodori . $producto->cmcodmar . utf8_encode(strtoupper(trim($producto->cmcodart)));
                        if (!$datos_producto = $this->selecciona_fila_from_tabla('v_partes', array(['sku', '=', $sku]))) {
                            if (!$datos_producto = $util->crear_producto_dado_linea_origen_marca_codigo($producto->cmcodlin, $producto->cmcodori, $producto->cmcodmar, utf8_encode(strtoupper(trim($producto->cmcodart))))) {
                                echo (' -> SKU ' . $sku . ' no encontrado');
                                continue;
                            }
                        }
                        $arrayInsert = array(
                            'purchase_order_id' => $datos_oc->id,
                            'sku_id' => $datos_producto->part_detail_id,
                            'measurement_unit_id' => $datos_producto->measure_unit_id,
                            'discount_rate' => round($producto->cmprcdct, 2),
                            'ordered_quantity' => $producto->cmcancmp,
                            'returned_quantity' => $producto->cmcandev,
                            'price' => round($producto->cmimppre, 2),
                            'reg_status' => ($producto->cmsts === 'A') ? 1 : 0,
                            'created_at' => date("Y-m-d H:i:s")
                        );
                        $arrayWhere = array(
                            ['purchase_order_id', '=', $datos_oc->id],
                            ['sku_id', '=', $datos_producto->part_detail_id],
                        );
                        if (!$this->selecciona_fila_from_tabla('purchase_order_details', $arrayWhere)) {
                            $this->inserta_into_tabla('purchase_order_details', $arrayInsert);
                        }
                    }
                //FIN - REGISTRAR DETALLE DE ORDEN DE COMPRA
            }
        }
        $end_time = date("Y-m-d H:i:s");
        echo "<br>Inicio: $init_time - Fin: $end_time";
        /*
        $n_offset = (int)$offset + (int)$limit;
        if ($n_offset < $cantidad_documentos) {
            echo '<a href="imp_ocs/' . $n_offset . '">Siguiente</a>';
            return redirect('imp_ocs/' . $n_offset);
        } else echo '<br>Fin de registro de ordenes de compra';
		*/
    }

    public function retorna_ordenes_de_compra($offset, $limit)
    {
        $util = new Utilidades();
        $fecha_inicio = $util->sumar_restar_dias_fecha(date("Ymd"), 2, 'restar');
        $fecha_inicio = $util->retorna_fecha_formateada('Y-m-d H:i:s', 'Ymd', $fecha_inicio);
        $sql = "SELECT *
        FROM LIBPRDDAT.MMCKREP 
        WHERE CKSTS='A'
		and CKCODCIA='10'
        and CKFECEMI >= $fecha_inicio
        LIMIT $limit
        OFFSET $offset";
        //echo $sql;
        $result =  DB::connection('ibmi')->select(DB::raw($sql));
        //die(' - Cantidad: '.sizeof($result));
        return ($result && is_array($result)) ? $result : false;
    }

    public function retorna_productos_orden_de_compra($codcia, $nro_oc)
    {
        $sql = "SELECT *
        FROM LIBPRDDAT.MMCMREP 
        WHERE CMSTS='A'
        AND CMCODCIA='$codcia'
        AND CMNROOCP=$nro_oc";
        $result =  DB::connection('ibmi')->select(DB::raw($sql));
        return ($result && is_array($result)) ? $result : false;
    }

    public function retorna_datos_proveedor_as($codigo)
    {
        $sql = "SELECT Distinct a.AHCODPRV,b.IPNVORUC,a.AHRAZSOC, a.AHTIPPRV, c.CGCODPAI,a.AHJDT, a.AHSTS 
        FROM MMAHREP a 
        Left join MMIPREP b on a.AHCODPRV=b.IPCODCLI 
        Left join MMCGREP c on a.AHCODPRV=c.CGCODPRV 
        where a.AHCODPRV='$codigo'";
        $result =  DB::connection('ibmi')->select(DB::raw($sql));
        return ($result && is_array($result)) ? $result[0] : false;
    }

    public function registrar_proveedor($codigo)
    {
        $util = new Utilidades();
        if (!$datos_proveedor = $this->retorna_datos_proveedor_as($codigo)) {
            return false;
        }
        $codigo_prov_as = $util->retorna_limpia_cadena($datos_proveedor->ahcodprv);
        $nombre_prov_as = $util->retorna_limpia_cadena($datos_proveedor->ahrazsoc);
        $ruc_prov_as = $util->retorna_limpia_cadena($datos_proveedor->ipnvoruc);
        $pais_prov_as = $util->retorna_limpia_cadena($datos_proveedor->cgcodpai);
        $tipo_prov = $datos_proveedor->ahtipprv;
        $reg_status = ($datos_proveedor->ahsts === 'A') ? 1 : 0;


        $arrayWhere = array(
            ['code', '=', $codigo_prov_as],
        );
        $arrayInsert = array(
            'code' => $codigo_prov_as,
            'identification_number' => $ruc_prov_as,
            'name' => $nombre_prov_as,
            'country_code' => $pais_prov_as,
            'provider_type_code' => $tipo_prov,
            'created_at' => date("Y-m-d H:i:s"),
            'reg_status' => $reg_status
        );

        Provider::updateOrCreate(
            $arrayWhere,
            $arrayInsert
        );
        return $this->selecciona_fila_from_tabla('providers', $arrayWhere);
    }


    public function importar_guias_de_remision(Request $request)
    {
        $util = new Utilidades();
        ini_set('max_execution_time', '3000');
        $init_time = date("Y-m-d H:i:s");
        $offset = ($request->offset) ? intval($request->offset) : 0;
        $limit = 5000;
        $registros = $this->retorna_guias_remision($offset, $limit);
        echo '<pre>';
        //print_r($registros[0]));
        $cantidad_documentos = 135000;
        echo "<br>Offset: $offset - Limit: $limit";

        if ($registros && is_array($registros)) {
            $serie_id = 1878; //1592;
            echo "<br>Cantidad Registros: " . sizeof($registros);

            $arraySelect = ['id', 'code', 'name', 'type_id', 'reg_status'];
            $empresas = $this->selecciona_from_tabla('establishments', array(['type_id', '=', 1]), $arraySelect);
            foreach ($empresas as $empresa) {
                $empresa->sucursales = $this->selecciona_from_tabla('establishments', array(['type_id', '=', 2], ['parent_establishment_id', '=', $empresa->id]), $arraySelect);
            }
            //echo "<br>Empresas: ";
            //print_r($empresas);
            $i = 0;
            foreach ($registros as $registro) {
                $i++;

                $nro_serie = $util->retorna_limpia_cadena($registro->jqnroser);
                $numero_gr = $nro_serie . '-' . $registro->jqnrocor;
                echo "<br>Registro $i -> $numero_gr";

                $company_id = ($registro->jqcodcia === '10') ? 1 : 2;
                $array_company = array_column($empresas, 'code');
                $index_company = array_search($registro->jqcodcia, $array_company);

                if ($index_company !== false) {
                    $sub_array = array_column($empresas[$index_company]->sucursales, 'code');
                    $index_subsidiary = array_search($registro->jqcodsuc, $sub_array);
                    if ($index_subsidiary !== false) {
                        $subsidiary_id = $empresas[$index_company]->sucursales[$index_subsidiary]->id;
                    }

                    $arrayWhereFD = array(
                        ['company_id', '=', $company_id],
                        ['subsidiary_id', '=', $subsidiary_id],
                        ['internal_number', '=', $registro->jqnropdc]
                    );

                    if ($datos_FD = $this->selecciona_fila_from_tabla('fiscal_documents', $arrayWhereFD)) {
                        $arrayWhere2 = array(
                            ['company_id', '=', $datos_FD->company_id],
                            ['serie_fiscal_id', '=', $serie_id],
                            ['correlative_fiscal_number', '=', $numero_gr]
                        );
                        if (!$this->selecciona_fila_from_tabla('fiscal_documents', $arrayWhere2)) {
                            $arrayInsert = array(
                                'company_id' => $datos_FD->company_id,
                                'company_name' => $datos_FD->company_name,
                                'company_address' => $datos_FD->company_address,
                                'company_ubigeo' => $datos_FD->company_ubigeo,
                                'subsidiary_id' => $datos_FD->subsidiary_id,
                                'warehouse_id' => $datos_FD->warehouse_id,
                                'customer_id' => $datos_FD->customer_id,
                                'customer_name' => $datos_FD->customer_name,
                                'customer_address' => $datos_FD->customer_address,
                                'fiscal_document_type_id' => $datos_FD->fiscal_document_type_id,
                                'internal_number' => $datos_FD->internal_number,
                                'reg_date' => $datos_FD->reg_date,
                                'year_month' => $datos_FD->year_month,
                                'serie_as_id' => $serie_id,
                                'correlative_as_number' => $numero_gr,
                                'serie_fiscal_id' => $serie_id,
                                'correlative_fiscal_number' => $numero_gr,
                                'seller_id' => $datos_FD->seller_id,
                                'auth_code_as' => trim($datos_FD->auth_code_as),
                                'support_as' => trim($datos_FD->support_as),
                                'currency_id' => $datos_FD->currency_id,
                                'net_amount' => $datos_FD->net_amount,
                                'net_amount_taxed' => $datos_FD->net_amount_taxed,
                                'net_exonerated_amount' => $datos_FD->net_exonerated_amount,
                                'principal_tax_amount' => $datos_FD->principal_tax_amount,
                                'total_amount' => $datos_FD->total_amount,
                                'withholding_tax_amount' => $datos_FD->withholding_tax_amount,
                                'withholding_tax_base_amount' => $datos_FD->withholding_tax_base_amount,
                                'additional_tax_code' => $datos_FD->additional_tax_code,
                                'additional_tax_amount' => $datos_FD->additional_tax_amount,
                                'additional_tax_rate' => $datos_FD->additional_tax_rate,
                                'ord_order_id' => $datos_FD->ord_order_id,
                                'parent_fiscal_document_id' => $datos_FD->id,
                                'additional_status' => $datos_FD->additional_status,
                                'additional_status_date' => $datos_FD->additional_status_date,
                                'down_status_reg' => $datos_FD->down_status_reg,
                                'down_status_date' => $datos_FD->down_status_date,
                                'reg_status' => 1,
                                'anullment_date' => $datos_FD->anullment_date,
                                'user_id' => $datos_FD->user_id,
                                'created_at' => date("Y-m-d H:i:s"),
                            );
                            //print_r($arrayInsert);
                            $util->inserta_into_tabla('fiscal_documents', $arrayInsert);
                        }
                    } else {
                        echo "<br>($numero_gr) NO EXISTE COMO FACTURA - SE BUSCARÁ EN PEDIDOS...";

                        $arrayWherePed = array(
                            ['company_id', '=', $company_id],
                            ['subsidiary_id', '=', $subsidiary_id],
                            ['order_number', '=', $registro->jqnropdc]
                        );
                        if ($datos_Ped = $this->selecciona_fila_from_tabla('ord_orders', $arrayWherePed)) {
                            //print_r($datos_Ped);
                            $arrayWhere2 = array(
                                ['company_id', '=', $datos_Ped->company_id],
                                ['serie_fiscal_id', '=', $serie_id],
                                ['correlative_fiscal_number', '=', $numero_gr]
                            );
                            if (!$this->selecciona_fila_from_tabla('fiscal_documents', $arrayWhere2)) {
                                $arrayInsert = array(
                                    'company_id' => $datos_Ped->company_id,
                                    'company_name' => '',
                                    'company_address' => '',
                                    'company_ubigeo' => '',
                                    'subsidiary_id' => $datos_Ped->subsidiary_id,
                                    'warehouse_id' => $datos_Ped->warehouse_id,
                                    'customer_id' => $datos_Ped->customer_id,
                                    'customer_name' => '',
                                    'customer_address' => '',
                                    'fiscal_document_type_id' => $datos_Ped->document_type_id,
                                    'internal_number' => $datos_Ped->order_number,
                                    'reg_date' => $datos_Ped->order_date,
                                    'year_month' => substr($datos_Ped->order_date, 0, 6),
                                    'serie_as_id' => $serie_id,
                                    'correlative_as_number' => $numero_gr,
                                    'serie_fiscal_id' => $serie_id,
                                    'correlative_fiscal_number' => $numero_gr,
                                    'seller_id' => $datos_Ped->seller_id,
                                    'auth_code_as' => '',
                                    'support_as' => '',
                                    'currency_id' => $datos_Ped->currency_id,
                                    'net_amount' => $datos_Ped->subtotal,
                                    'net_amount_taxed' => $datos_Ped->subtotal,
                                    'net_exonerated_amount' => 0,
                                    'principal_tax_amount' => $datos_Ped->total_tax,
                                    'total_amount' => $datos_Ped->total,
                                    'withholding_tax_amount' => 0,
                                    'withholding_tax_base_amount' => 0,
                                    'additional_tax_code' => 0,
                                    'additional_tax_amount' => 0,
                                    'additional_tax_rate' => 0,
                                    'ord_order_id' => $datos_Ped->id,
                                    'parent_fiscal_document_id' => null,
                                    'additional_status' => '',
                                    'additional_status_date' => null,
                                    'down_status_reg' => '',
                                    'down_status_date' => null,
                                    'reg_status' => 1,
                                    'anullment_date' => null,
                                    'user_id' => ($datos_Ped->user_id) ? $datos_Ped->user_id : 183, //SISTEMAS
                                    'created_at' => date("Y-m-d H:i:s"),
                                );
                                //print_r($arrayInsert);
                                // exit;
                                $util->inserta_into_tabla('fiscal_documents', $arrayInsert);
                            }
                        } else {
                            echo ('<br>pedido no existe');
                            DB::table('log_migraciones')->insert([
                                'tabla' => 'ord_orders',
                                'mensaje' => 'Pedido no encontrado: ' . $company_id . ' - ' . $subsidiary_id . ' - ' . $registro->jqnropdc,
                                'otro'  => json_encode($registro),
                                'created_at' => date("Y-m-d H:i:s")
                            ]);
                        }
                    }
                }
            }
        } else {
            echo "<br>No hay registros";
            exit;
        }
        $end_time = date("Y-m-d H:i:s");
        echo "<br>Inicio: $init_time - Fin: $end_time";
        /*
        $n_offset = (int)$offset + (int)$limit;
        if ($n_offset < $cantidad_documentos) {
            echo '<a href="imp_grs/' . $n_offset . '">Siguiente</a>';
            return redirect('imp_grs/' . $n_offset);
        } else echo '<br>Fin de registro de guias de remision';
		*/
    }

    public function retorna_guias_remision($offset, $limit)
    {

        $util = new Utilidades();
        $fecha_inicio = $util->sumar_restar_dias_fecha(date("Ymd"), 2, 'restar');
        $fecha_inicio = $util->retorna_fecha_formateada('Y-m-d H:i:s', 'Ymd', $fecha_inicio);

        $fecha_inicio = 20220101;
        $sql = "SELECT JQCODCIA, JQCODSUC, JQNROPDC, JQNROSER, JQNROCOR, JQFECGUI 
        FROM LIBPRDDAT.MMJQREP 
        WHERE JQSTS ='A'
        AND JQFECGUI >= $fecha_inicio
        AND JQNROSER <> '' AND JQNROCOR <> '' AND JQCODSUC <>''
        LIMIT $limit
        OFFSET $offset";
        echo $sql;

        $result =  DB::connection('ibmi')->select(DB::raw($sql));
        //die(' - Cantidad: '.sizeof($result));
        return ($result && is_array($result)) ? $result : false;
    }


    public function importar_notas_de_credito(Request $request)
    {
        $util = new Utilidades();
        ini_set('max_execution_time', '3000');
        $init_time = date("Y-m-d H:i:s");
        $offset = ($request->offset) ? intval($request->offset) : 0;
        $limit = 5000;
        $registros = $this->retorna_notas_de_credito($offset, $limit);
        $cantidad_documentos = 135000;
        echo "<br>Offset: $offset - Limit: $limit";
        if ($registros && is_array($registros)) {
            $whereInField = 'resource_id';
            /*
            Tipo Documento Fiscal (AS-SUNAT) -> 38
            Serie de documentos fiscales -> 39
            Tipo Moneda -> 12
            */
            $whereInArray = array(12, 38, 39);
            $arraySelect = ['id', 'code', 'name', 'resource_id'];
            $array_tipos = $this->selecciona_from_tabla_where_in('gen_resource_details', $whereInField, $whereInArray, $arraySelect);
            //die(print_r($array_tipos));
            $arraySelect = ['id', 'code', 'name', 'type_id', 'reg_status'];
            $empresas = $this->selecciona_from_tabla('establishments', array(['type_id', '=', 1]), $arraySelect);
            foreach ($empresas as $empresa) {
                $empresa->sucursales = $this->selecciona_from_tabla('establishments', array(['type_id', '=', 2], ['parent_establishment_id', '=', $empresa->id]), $arraySelect);
                $empresa->almacenes = $this->retorna_almacenes_empresa($empresa->id, 'alm.id, alm.code, alm.name, alm.type_id, alm.reg_status');
                $empresa->usuarios = $this->selecciona_from_tabla('v_users_by_companies', array(['company_id', '=', $empresa->id]));
            }
            //echo "<br>Empresas: ";
            //print_r($empresas);
            $i = 0;
            foreach ($registros as $registro) {
                $i++;
                echo "<br>Registro $i";
                $codigo_usuario = $util->retorna_limpia_cadena($registro->fxusr);
                $codigo_vendedor = trim($registro->fxcodven);
                $tipo_devolucion = $util->retorna_limpia_cadena($registro->fxtipdev);
                $tipo_documento = $util->retorna_limpia_cadena($registro->fxtipdoc);
                $serie = $util->retorna_limpia_cadena($registro->fxnroser);
                $correlativo = $util->retorna_limpia_cadena($registro->fxnrocor);
                $numero_nc =  $registro->fxnropdc;
                $total_importe = (float) round($registro->fximptot, 2);
                $total_igv =  (float) round($registro->fximpimp, 2);

                $company_id = ($registro->fxcodcia === '10') ? 1 : 2;
                $array_company = array_column($empresas, 'code');
                $index_company = array_search($registro->fxcodcia, $array_company);

                if ($index_company !== false) {
                    $sub_array = array_column($empresas[$index_company]->sucursales, 'code');
                    $index_subsidiary = array_search($registro->fxcodsuc, $sub_array);
                    if ($index_subsidiary !== false) {
                        $subsidiary_id = $empresas[$index_company]->sucursales[$index_subsidiary]->id;
                    }

                    $ware_array = array_column($empresas[$index_company]->almacenes, 'code');
                    $index_warehouse = array_search($registro->fxcodalm, $ware_array);
                    if ($index_warehouse !== false) {
                        $warehouse_id = $empresas[$index_company]->almacenes[$index_warehouse]->id;
                    }
                }

                $usuarios = $empresas[$index_company]->usuarios;
                $user_code_array = array_column($usuarios, 'user_code');
                $index = array_search($codigo_usuario, $user_code_array);
                $user_id = ($usuarios[$index]->user_user_id !== false) ? $usuarios[$index]->user_user_id : false;

                $seller_array = array_column($usuarios, 'operator_code');
                $index = array_search($codigo_vendedor, $seller_array);
                $seller_id = ($usuarios[$index]->user_user_id !== false) ? $usuarios[$index]->user_user_id : false;

                //REGISTRAR ENCABEZADO DE NOTA DE CRÉDITO
                $arrayWhere = array(
                    ['company_id', '=', $company_id],
                    ['subsidiary_id', '=', $subsidiary_id],
                    ['credit_note_number', '=', $numero_nc],
                );
                $fecha = Carbon::createFromFormat('Ymd', $registro->fxfecdvl, 'America/Lima');
                $fecha_formateada =  $fecha->format('Y-m-d');
                //echo ('<br>' . $fecha . ' - ' . $fecha_formateada . ' - ' . $registro->fxfecdvl);
                $arrayInsert = array(
                    'company_id' => $company_id,
                    'subsidiary_id' => $subsidiary_id,
                    'warehouse_id' => $warehouse_id,
                    'credit_note_number' => $numero_nc,
                    'return_type_code' => $registro->fxtipdev,
                    'reason_type_code' => $registro->fxmotdev,
                    'condition_payment_discount_rate' => $registro->fxdctcnd,
                    'customer_class_discount_rate' => $registro->fxdctcls,
                    'credit_note_date' => $fecha_formateada,
                    'total_amount' => $total_importe,
                    'tax_amount' => $total_igv,
                    'condition_payment_discount_amount' => $registro->fximpdcp,
                    'customer_class_discount_amount' => $registro->fximpdcc,
                    'document_type_code' => $registro->fxtipdoc,
                    'created_at' => date("Y-m-d H:i:s"),
                    'reg_status' => ($registro->fxsts === 'A') ? 1 : 0,
                    'user_id' => $user_id,
                    'seller_id' => $seller_id,
                    'serie' => $registro->fxnroser,
                    'correlative' => $registro->fxnrocor
                );

                if (!$datos_nc = $this->selecciona_fila_from_tabla('customer_credit_notes', $arrayWhere)) {
                    CustomerCreditNote::Create(
                        $arrayInsert
                    );
                    $datos_nc = $this->selecciona_fila_from_tabla('customer_credit_notes', $arrayWhere);
                }
                //FIN - REGISTRAR ENCABEZADO DE ORDEN DE COMPRA

                //REGISTRAR DETALLE DE ORDEN DE COMPRA
                $productos_nc = $this->retorna_productos_notas_de_credito($registro->fxcodcia, $registro->fxcodsuc, $registro->fxnropdc);
                if ($productos_nc && is_array($productos_nc))
                    foreach ($productos_nc as $producto) {
                        $sku = $producto->fycodlin . $producto->fycodori . $producto->fycodmar . utf8_encode(strtoupper(trim($producto->fycodart)));
                        if (!$datos_producto = $this->selecciona_fila_from_tabla('v_partes', array(['sku', '=', $sku]))) {
                            if (!$datos_producto = $util->crear_producto_dado_linea_origen_marca_codigo($producto->fycodlin, $producto->fycodori, $producto->fycodmar, utf8_encode(strtoupper(trim($producto->fycodart))))) {
                                echo (' -> SKU ' . $sku . ' no encontrado');
                                continue;
                            }
                        }
                        $arrayInsert = array(
                            'credit_note_id' => $datos_nc->id,
                            'part_detail_id' => $datos_producto->part_detail_id,
                            'item1' => $producto->fyitem01,
                            'item2' => $producto->fyitem02,
                            'returned_quantity' => $producto->fycandev,
                            'price' => round($producto->fyimppre, 2),
                            'tax_rate' => round($producto->fyprcimp, 2),
                            'line_discount' => round($producto->fydctlin, 2),
                            'additional_discount' => round($producto->fydctadi, 2),
                            'reg_status' => ($producto->fysts === 'A') ? 1 : 0,
                            'created_at' => date("Y-m-d H:i:s")
                        );
                        $arrayWhere = array(
                            ['credit_note_id', '=', $datos_nc->id],
                            ['part_detail_id', '=', $datos_producto->part_detail_id],
                        );
                        if (!$this->selecciona_fila_from_tabla('customer_credit_note_details', $arrayWhere)) {
                            $this->inserta_into_tabla('customer_credit_note_details', $arrayInsert);
                        }
                    }
                //FIN - REGISTRAR DETALLE DE ORDEN DE COMPRA
            }
        }
        $end_time = date("Y-m-d H:i:s");
        echo "<br>Inicio: $init_time - Fin: $end_time";
        /*
        $n_offset = (int)$offset + (int)$limit;
        if ($n_offset < $cantidad_documentos) {
            echo '<a href="imp_ncs/' . $n_offset . '">Siguiente</a>';
            return redirect('imp_ncs/' . $n_offset);
        } else echo '<br>Fin de registro de notas de crédito';
		*/
    }

    public function retorna_notas_de_credito($offset, $limit)
    {
        $util = new Utilidades();
        $fecha_inicio = $util->sumar_restar_dias_fecha(date("Ymd"), 2, 'restar');
        $fecha_inicio = $util->retorna_fecha_formateada('Y-m-d H:i:s', 'Ymd', $fecha_inicio);
        $sql = "SELECT *
        FROM LIBPRDDAT.MMFXREP 
        WHERE FXSTS='A'
        and FXJDT >= $fecha_inicio
        LIMIT $limit
        OFFSET $offset";
        //echo $sql;
        $result =  DB::connection('ibmi')->select(DB::raw($sql));
        return ($result && is_array($result)) ? $result : false;
    }

    public function retorna_productos_notas_de_credito($empresa, $sucursal, $numero)
    {
        $sql = "SELECT *
        FROM LIBPRDDAT.MMFYREP 
        WHERE FYSTS='A'
        AND FYCODCIA='$empresa'
        AND FYCODSUC='$sucursal'
        AND FYNROPDC='$numero'";
        //echo $sql;
        $result =  DB::connection('ibmi')->select(DB::raw($sql));
        return ($result && is_array($result)) ? $result : false;
    }

    public function migrar_bancos()
    {
        // ---- BANCOS (11) ---- //
        $arrayWhere = array(
            ['EUCODTBL', '=', '04'],
            ['EUSTS', '=', 'A'],
        );
        if ($registros = $this->selecciona_from_tabla_db2('LIBPRDDAT.MMEUREP', $arrayWhere)) {
            $resource_id = 11;
            foreach ($registros as $fila) {
                $arrayWhere = array(
                    ['resource_id', '=', $resource_id],
                    ['name', '=', utf8_encode(strtoupper(trim($fila->eudsccor)))],
                );
                if ($this->selecciona_fila_from_tabla('gen_resource_details', $arrayWhere)) {
                    echo '<br>REGISTRO EXISTE: ';
                    echo "$fila->eucodele $fila->eudscabr utf8_encode(strtoupper(trim($fila->eudsccor)))";
                } else {
                    $arrayInsert = array(
                        'resource_id' => $resource_id,
                        'code' => $fila->eucodele,
                        'abrv' => $fila->eudscabr,
                        'name' => utf8_encode(strtoupper(trim($fila->eudsccor))),
                        'description' => utf8_encode(strtoupper(trim($fila->eudsclar)))
                    );
                    //echo '<pre>';
                    //print_r($arrayInsert);
                    $this->inserta_into_tabla('gen_resource_details', $arrayInsert);
                }
            }
            echo '<br> FIN MIGRACIÓN - BANCOS (11)';
        }
        // ---- BANCOS (11) ---- //
    }

    public function actualiza_skus()
    {
        $select = ['part_detail_id', DB::raw('(concat(line_code,origin_code,trademark_code,part_code)) as nuevo_sku'), 'sku'];

        /* dd(DB::table('part_part_details')
            ->where('id', 4)
            ->update(['sku' => '0102329422057'])); */
        //dd(DB::table('v_partes')->select($select)->whereRaw("concat(line_code,origin_code,trademark_code,part_code) <> sku")->count());
        //dd(DB::table('v_partes')->select($select)->where(DB::raw('(concat(line_code,origin_code,trademark_code,part_code))'), '<>', 'sku')->orderBy('part_detail_id')->count());
        DB::table('v_partes')->select($select)->whereRaw('concat(line_code,origin_code,trademark_code,part_code) <> sku')->orderBy('part_detail_id')->chunk(400, function ($skus) {
            foreach ($skus as $sku) {
                DB::table('part_part_details')
                    ->where('id', '=', $sku->part_detail_id)
                    ->update(['sku' => $sku->nuevo_sku]);
            }
        });
    }

    public function importar_reemplazos_de_partes(Request $request)
    {
        ini_set('max_execution_time', '3000');
        //ini_set('memory_limit', '512M');

        $util = new Utilidades();

        /*
        //ESTÁ OK
        DB::connection('ibmi')->table('LIBPRDDAT.MMAEREP')->where('AESTS', 'A')->orderBy('AELINANT')->orderBy('AEARTANT')->chunk(100, function ($datos_partes_ree_as) {
            $i = 0;
            $array_insert_lote = array();
            foreach ($datos_partes_ree_as as $parte_reemplazo) {
                $i++;
                $cod_linea_a_reemplazar = (string) trim($parte_reemplazo->aelinant);
                $cod_parte_a_reemplazar = trim(utf8_encode($parte_reemplazo->aeartant));
                $cod_linea_nueva = trim(utf8_encode($parte_reemplazo->aelinree));
                $cod_parte_nueva = trim(utf8_encode($parte_reemplazo->aeartree));

                $sql = "select distinct part_detail_id, part_id, line_id, line_code, origin_id, origin_code, trademark_id, trademark_code from v_partes where line_code='$cod_linea_a_reemplazar' and part_code='$cod_parte_a_reemplazar'";
                $registros = DB::select(DB::raw($sql));

                if (sizeof($registros) > 0) {
                    echo '<br>Cantidad: ' . sizeof($registros);
                    echo " - $cod_linea_a_reemplazar $cod_parte_a_reemplazar  - $cod_parte_nueva";
                }

                if ($registros && is_array($registros)) {
                    foreach ($registros as $fila) {
                        $sql_new = "select part_detail_id from v_partes where line_code='$cod_linea_nueva' and part_code='$cod_parte_nueva' and origin_id=$fila->origin_id and trademark_id=$fila->trademark_id";
                        $registros_reem = DB::select(DB::raw($sql_new));
                        if ($registros_reem && is_array($registros_reem)) {
                            //$array_reemp = [];
                            foreach ($registros_reem as $reem) {

                                //array_push($array_reemp,[$reem->part_detail_id]);
                                if ($fila->part_detail_id && $reem->part_detail_id) {
                                    $array_insert = [
                                        'part_detail_id' => $fila->part_detail_id,
                                        'part_detail_replacement_id' => $reem->part_detail_id,
                                        'reg_status' => 1,
                                        'created_at' => date("Y-m-d H:i:s"),
                                    ];
                                    array_push($array_insert_lote, $array_insert);
                                } else {
                                    echo '<pre>';
                                    print_r($fila);
                                    print_r($reem);
                                }
                            }
                        }
                    }
                }
            }
            DB::table('part_detail_replacements')->insert($array_insert_lote);
        });
        */
        $cantidad = 1000;
        $registros = DB::table('part_detail_replacements')
            ->where('part_detail_last_replace_id', null)
            ->where('reg_status', 1)
            ->orderBy('id')
            ->limit($cantidad)
            ->get()->toArray();
        $i = 0;
        echo '<pre>';
        //$array_new = array();
        foreach ($registros as $registro) {
            $i++;
            //exit;
            ob_start();
            echo "<br>Reg: $i";
            print_r($registro);
            $inicio = date("H:i:s");
            echo "<br> $inicio";
            //$registro->part_detail_last_replace_id = $util->rec_retorna_part_detail_id_reemplazo($registro->part_detail_replacement_id, $registros);
            $registro->part_detail_last_replace_id = $util->rec_retorna_part_detail_id_reemplazo($registro->part_detail_replacement_id);
            $fin = date("H:i:s");
            //array_push($array_new, $registro);
            $arrayWhere = array(['id', '=', $registro->id]);
            $arrayUpdate = array('part_detail_last_replace_id' => $registro->part_detail_last_replace_id);
            $this->actualiza_tabla('part_detail_replacements', $arrayWhere, $arrayUpdate);
            echo " - $fin -> $registro->id - $registro->part_detail_replacement_id - $registro->part_detail_last_replace_id";
            ob_end_flush();
            if ($i == $cantidad) {
                die('hasta aqui!');
                /* echo '<pre>';
                die(print_r($array_new)); */
                return redirect('imp_reemp');
            }
        }
    }


    public function importa_gen_resources_and_details_dev_to_prod()
    {
        $from_resource_id = 35;
        $resources_dev = DB::connection('pgsql')->table('gen_resources')->where('id', '>=', $from_resource_id)->orderBy('id')->get()->toArray();

        /*         echo '<pre>';
        print_r($resources_dev);
        exit; */

        foreach ($resources_dev as $dev) {
            $dev->resources_details = DB::connection('pgsql')->table('gen_resource_details')->where('resource_id', $dev->id)->orderBy('id')->get()->toArray();

            $resource_number =  DB::connection('pgsql9')->table('gen_resources')
                ->insertGetId([
                    'code' => $dev->code,
                    'abrv' => $dev->abrv,
                    'name' => $dev->name,
                    'description' => $dev->description,
                    'reg_status' => $dev->reg_status,
                    'created_at' => date("Y-m-d H:i:s"),
                ]);
            if (sizeof($dev->resources_details) > 0) {
                foreach ($dev->resources_details as $detail) {
                    DB::connection('pgsql9')->table('gen_resource_details')
                        ->insertGetId([
                            'resource_id' => $resource_number,
                            'code' => $detail->code,
                            'abrv' => $detail->abrv,
                            'name' => $detail->name,
                            'description' => $detail->description,
                            'order' => $detail->order,
                            'parent_resource_detail_id' => null,
                            'other_fields' => $detail->other_fields,
                            'reg_status' => $detail->reg_status,
                            'created_at' => date("Y-m-d H:i:s"),
                        ]);
                }
            }
        }
    }

    public function retorna_contactos_clientes($offset, $limit)
    {
        $codCia = '10';
        return DB::connection('ibmi')
            ->table('CCPCREP')
            ->where('PCSTS', 'A')
            ->where('PCCODCIA', $codCia)
            ->orderBy('PCCODCLI')
            ->orderBy('PCITEM01')
            ->offset($offset)
            ->limit($limit)
            ->get()->toArray();
    }

    public function migrar_contactos_clientes(Request $request)
    {
        $util = new Utilidades();
        ini_set('max_execution_time', '3000');
        $init_time = date("Y-m-d H:i:s");
        $offset = ($request->offset) ? intval($request->offset) : 0;
        $limit = 5000;
        $registros = $this->retorna_contactos_clientes($offset, $limit);
        $cantidad_registros = 2000;
        echo "<br>Offset: $offset - Limit: $limit";
        echo '<pre>';
        if ($registros) {
            $arrayWhere = array(['resource_id', '=', 8]);
            $arraySelect = ['id', 'code', 'name', 'resource_id'];
            $array_tipos = $this->selecciona_from_tabla('gen_resource_details', $arrayWhere, $arraySelect);
            $i = 0;
            foreach ($registros as $registro) {
                $i++;
                echo "<br>Registro $i";
                if (!$datos_cliente = $this->selecciona_fila_from_tabla('customers', array(['code', '=', trim($registro->pccodcli)]))) {
                    echo " - CLIENTE $registro->pccodcli NO EXISTE, -> REGISTRAR";
                    $registro->datos_consulta = $this->selecciona_fila_from_tabla_db2('LIBPRDDAT.MMAKREP', array(['AKCODCLI', '=', $registro->pccodcli]));
                    $CustomerController = new SyncCustomer;
                    $resultado = $CustomerController->mmakrep_maestro_clientes($registro);
                    if ($resultado) {
                        echo " - CLIENTE REGISTRADO";
                        if (!$datos_cliente = $this->selecciona_fila_from_tabla('customers', array(['code', '=', trim($registro->pccodcli)]))) {
                            die('<br>CLIENTE NO EXISTE');
                        }
                    }
                }

                /*
                $usuarios = $empresas[$index_company]->usuarios;
                $user_code_array = array_column($usuarios, 'user_code');
                $index = array_search($codigo_usuario, $user_code_array);
                $user_id = ($usuarios[$index]->user_user_id !== false) ? $usuarios[$index]->user_user_id : false;
                 */

                $cargo_array = array_column($array_tipos, 'code');
                $index = array_search($registro->pccodcar, $cargo_array);
                $cargo_id = ($array_tipos[$index]->id !== false) ? $array_tipos[$index]->id : null;
                $item = ((int)$registro->pcitem01 > 0) ? (int)$registro->pcitem01 : 0;
                echo "<br>Item: $item - Cargo: $registro->pccodcar - Id cargo: $cargo_id idx: $index";
                $arrayWhereC = [
                    'customer_id' => $datos_cliente->id,
                    'customer_contact_number' => $item
                ];
                $arrayInsertC = [
                    'work_position_id' => $cargo_id,
                    'contact_name' => $util->retorna_limpia_cadena(trim($registro->pcprnomb) . ' ' . trim($registro->pcprapll)),
                    'contact_phone' => trim($registro->pctelef1),
                    'contact_email' => (filter_var(trim($registro->pccorreo), FILTER_VALIDATE_EMAIL)) ? trim($registro->pccorreo) : null,
                    'reg_status' => ($registro->pcsts === 'A') ? 1 : 0,
                    'identification_type_id' => 5,
                    'identification_number' => trim($registro->pcdocide),
                    'created_at' => date("Y-m-d H:i:s")
                ];
                CustomerContact::updateOrCreate(
                    $arrayWhereC,
                    $arrayInsertC
                );
            }
        }
    }

    public function llenar_columna_line_id_en_part_parts(Request $request) //PARTE I
    {
        $offset = ($request->offset) ? intval($request->offset) : 0;
        $limit = 5000;
        //$select = ['id', 'code', 'name', 'resource_id'];
        //$lineas = DB::table('gen_resource_details')->select($select)->where('resource_id', 7)->get()->toArray();

        $cantidad = $this->retorna_line_codarticulos_as400($offset, 999999999, 1);
        $registros = $this->retorna_line_codarticulos_as400($offset, $limit, 0);
        echo "<br>Total Registros: $cantidad <br>";
        echo "Desde: $offset <br>";
        echo 'Registros: ' . sizeof($registros) . ' <br>';
        $i = ($offset - 1);
        //echo '<pre>';
        //print_r($registros);
        $no_encontrados = 0;
        foreach ($registros as $registro) {
            $i++;
            // die(print_r($registro));
            $cod_lin = trim($registro->accodlin);
            $cod_art = trim(utf8_encode(strtoupper($registro->accodart)));
            $desc_corta = trim(utf8_encode(strtoupper($registro->acdsccor)));
            $desc_larga = trim(utf8_encode(strtoupper($registro->acdsclar)));
            $reg_status = ($registro->acdsclar === 'A') ? 1 : 0;

            $select = ['part_id', 'line_id'];
            $datos_parte = DB::table('v_partes')
                ->select($select)
                ->where('part_code', $cod_art)
                ->where('part_name', $desc_larga)
                ->first();

            if ($datos_parte) {
                echo "<br>($i) Part_code: $cod_art - line_code: $cod_lin -> line_id ({$datos_parte->line_id}) - part_name: $desc_larga";
                DB::table('part_parts')
                    ->where('line_id', null)
                    ->where('code', $cod_art)
                    ->where('name', $desc_larga)
                    ->update(['line_id' => $datos_parte->line_id, 'updated_at' => date("Y-m-d H:i:s")]);
                //exit;
            } else {
                $no_encontrados++;
                echo "<br>($i) parte no encontrada -> Lin: $cod_lin - Cod: $cod_art - Desc: $desc_larga";
                /*
                $util = new Utilidades;
                if (!$part_detail = $util->crear_producto_dado_linea_origen_marca_codigo($cod_lin, '', '', $cod_art)) {
                    echo ("<br>ERROR: EL CÓDIGO DE PRODUCTO NO CREADO");
                    $arrayInsert2 = array(
                        'tabla' => 'part_parts',
                        'mensaje' => "CÓDIGO DE PRODUCTO $cod_art NO EXISTE",
                        'otro' => json_encode($registro)
                    );
                    $this->inserta_into_tabla('log_migraciones', $arrayInsert2);
                    continue;
                } else {
                    echo "<br>parte creada: $cod_lin - $cod_art";
                }
                */
            }
        }
        $n_offset = (int)$offset + (int)$limit;
        if ($n_offset < $cantidad) {
            echo '<a href="add_col_line_parts/' . $n_offset . '">Siguiente</a>';
            return redirect('add_col_line_parts/' . $n_offset);
        } else echo '<br>Fin agregar columna linea en tabla de Partes';
    }

    public function retorna_line_codarticulos_as400($offset = 0, $limit = 10000, $retorna_cantidad = false, $parte3 = false)
    {
        $offset = ($retorna_cantidad) ? 0 : $offset;

        if ($parte3) $str_sql = 'inner join LIBPRDDAT.MMETREP ai on ad.ACCODART=ai.ETCODART AND ad.ACCODLIN=ai.ETCODLIN';
        else $str_sql = '';

        $sql = "SELECT DISTINCT ACCODLIN, ACCODART, ACDSCCOR,  ACDSCLAR, ACSTS
        FROM LIBPRDDAT.MMACREP ad
        $str_sql
        WHERE ACDSCLAR<>'' 
        ORDER BY ACCODART
        LIMIT :cantidad
        OFFSET :desde";
        //inner join LIBPRDDAT.MMETREP ai on ad.ACCODART=ai.ETCODART AND ad.ACCODLIN=ai.ETCODLIN
        /*
        if ($retorna_cantidad) {
            echo "<br>$sql";
            echo "<br>Limite: $limit - Offset: $offset<br>";
        }
        */

        $rs = DB::connection('ibmi')->select(DB::raw($sql), array('cantidad' => $limit, 'desde' => $offset));
        if ($retorna_cantidad) return sizeof($rs);
        return (is_array($rs) && sizeof($rs) > 0) ? $rs : array();
    }

    public function llenar_columna_line_id_en_part_parts_2(Request $request) //PARTE II
    {
        $sql = "select pp.*, (select min(line_id)  from part_part_details ppd where pp.id=ppd.part_id) as ppd_line_id
                from part_parts pp
                where pp.line_id is null ";
        $registros = DB::select(DB::raw($sql));
        echo "cantidad: " . sizeof($registros);
        echo '<pre>';
        //print_r($registros);
        if ($registros && is_array($registros)) {
            foreach ($registros as $registro) {
                if ($registro->ppd_line_id) {
                    DB::table('part_parts')
                        ->where('line_id', null)
                        ->where('id', $registro->id)
                        ->update(['line_id' => $registro->ppd_line_id, 'updated_at' => date("Y-m-d H:i:s")]);
                }
            }
        }
    }

    public function llenar_columna_line_id_en_part_parts_3(Request $request) //PARTE III
    {
        $offset = ($request->offset) ? intval($request->offset) : 0;
        $limit = 5000;
        //$select = ['id', 'code', 'name', 'resource_id'];
        //$lineas = DB::table('gen_resource_details')->select($select)->where('resource_id', 7)->get()->toArray();

        $cantidad = $this->retorna_line_codarticulos_as400($offset, 999999999, 1, true);
        $registros = $this->retorna_line_codarticulos_as400($offset, $limit, 0, true);
        echo "<br>Total Registros: $cantidad <br>";
        echo "Desde: $offset <br>";
        echo 'Registros: ' . sizeof($registros) . ' <br>';
        $i = ($offset - 1);
        //echo '<pre>';
        //print_r($registros);
        $no_encontrados = 0;
        foreach ($registros as $registro) {
            $i++;
            // die(print_r($registro));
            $cod_lin = trim($registro->accodlin);
            $cod_art = trim(utf8_encode(strtoupper($registro->accodart)));
            $desc_corta = trim(utf8_encode(strtoupper($registro->acdsccor)));
            $desc_larga = trim(utf8_encode(strtoupper($registro->acdsclar)));
            $reg_status = ($registro->acdsclar === 'A') ? 1 : 0;

            $select = ['part_id', 'line_id'];
            $datos_parte = DB::table('v_partes')
                ->select($select)
                ->where('part_code', $cod_art)
                ->where('part_name', $desc_larga)
                ->first();

            if ($datos_parte) {
                echo "<br>($i) Part_code: $cod_art - line_code: $cod_lin -> line_id ({$datos_parte->line_id}) - part_name: $desc_larga";
                DB::table('part_parts')
                    ->where('line_id', null)
                    ->where('code', $cod_art)
                    ->where('name', $desc_larga)
                    ->update(['line_id' => $datos_parte->line_id, 'updated_at' => date("Y-m-d H:i:s")]);
                //exit;
            } else {
                $no_encontrados++;
                echo "<br>($i) parte no encontrada -> Lin: $cod_lin - Cod: $cod_art - Desc: $desc_larga";

                $util = new Utilidades;
                if (!$part_detail = $util->crear_producto_dado_linea_origen_marca_codigo($cod_lin, '', '', $cod_art)) {
                    echo ("<br>ERROR: EL CÓDIGO DE PRODUCTO NO CREADO");
                    $arrayInsert2 = array(
                        'tabla' => 'part_parts',
                        'mensaje' => "CÓDIGO DE PRODUCTO $cod_art NO EXISTE",
                        'otro' => json_encode($registro)
                    );
                    $this->inserta_into_tabla('log_migraciones', $arrayInsert2);
                    continue;
                } else {
                    echo "<br>parte creada: $cod_lin - $cod_art";
                }
            }
        }
        $n_offset = (int)$offset + (int)$limit;
        if ($n_offset < $cantidad) {
            echo '<a href="add_col_line_parts3/' . $n_offset . '">Siguiente</a>';
            return redirect('add_col_line_parts3/' . $n_offset);
        } else echo '<br>Fin agregar columna linea en tabla de Partes - PARTE III';
    }

    /*public function corregir_coincidencias_en_part_parts()
    {
        $sql = "select distinct code, count(id) cant
        from part_parts pp 
        group by code
        having count(id) > 1";
        $registros = DB::select(DB::raw($sql));
        if ($registros && is_array($registros)) {
            echo "<br>Cantidad: " . sizeof($registros);
            foreach ($registros as $registro) {
                $select = ['part_code', 'part_id', 'line_id', 'part_detail_id'];
                $v_partes = DB::table('v_partes')->select($select)->where('part_code', $registro->code)->orderBy('part_code')->get()->toArray();
                //echo '<pre>';
                //print_r($v_partes);
                foreach ($v_partes as $vp) {
                    $sql_update = "UPDATE part_part_details SET part_id=(select id from part_parts where line_id=" . $vp->line_id . " and code='" . $vp->part_code . "') WHERE id=" . $vp->part_detail_id;
                    echo "<br>$sql_update";
                    DB::statement($sql_update);
                }
            }
        }
    }
    */


    public function actualiza_part_id_en_part_part_details()
    {
        $sql = "select distinct code, count(id) cant
        from part_parts pp 
        group by code
        having count(id) > 1
        order by code";
        $registros_repetidos = DB::select(DB::raw($sql));
        if ($registros_repetidos && is_array($registros_repetidos)) {
            echo "<br>Cantidad: " . sizeof($registros_repetidos);
            foreach ($registros_repetidos as $rr) {
                $select = ['id', 'code', 'line_id'];
                $registros = DB::table('part_parts')->select($select)->where('code', $rr->code)->limit(10)->get()->toArray();

                foreach ($registros as $registro) {
                    $arrayWhere = [
                        ['part_code', '=', $registro->code],
                        ['line_id', '=', $registro->line_id],
                    ];
                    $select_v = ['part_detail_id', 'sku', 'part_id', 'part_code', 'line_id', 'line_code'];
                    $filas_actualizar = DB::table('v_partes')->distinct()->select($select_v)->where($arrayWhere)->get()->toArray();
                    foreach ($filas_actualizar as $fila) {
                        echo '<pre>';
                        print_r($registro);
                        print_r($fila);

                        $reg_actualizados =  DB::table('part_part_details')
                            ->where('id', '=', $fila->part_detail_id)
                            ->where('part_id', '<>', $registro->id)
                            ->update(['part_id' => $registro->id]);
                        echo "<br>Reg Act: $reg_actualizados";
                    }
                }
            }
        }
    }

    public function actualiza_datos_partes(Request $request)
    {
        //ACTUALIZA CARACTERISTICAS Y SUBSISTEMA
        $offset = ($request->offset) ? intval($request->offset) : 0;
        $limit = 5000;

        $select_pp = ['pp.id', 'pp.code', 'pp.line_id', 'lin.code as line_code', 'pp.name'];
        $datos_partes = DB::table('part_parts AS pp')->distinct()->select($select_pp)
            ->join('gen_resource_details AS lin', 'pp.line_id', '=', 'lin.id')
            ->orderBy('code')->orderBy('line_id')->offset($offset)->limit($limit)->get()->toArray();

        $cantidad = DB::table('part_parts')->distinct()->select($select_pp)->count();
        echo "<br>Cantidad: $cantidad";
        $i = 0;
        foreach ($datos_partes as $part) {
            $i++;
            echo "<br>Registro: $i";
            echo '<pre>';
            print_r($part);
            if (!$datos_parte_as = Utilidades::retorna_datos_parte_as400($part->line_code, '', '', $part->code)) {
                echo "DATOS NO ENCONTRADOS: " . $part->line_code . " - " . $part->code;
                continue;
            }
            print_r($datos_parte_as);
            echo '<br>';
            $sistema = trim($datos_parte_as->ohclas01);
            $subsistema = trim($datos_parte_as->ohclas02);
            $datos_und_medida =  DB::table('gen_resource_details')->where('resource_id', 27)->where('code', trim($datos_parte_as->acunimed))->first();

            if (!$datos_sistema_subsistema = Utilidades::retorna_datos_sistema_subsistema($sistema, $subsistema)) {
                echo "<br>ERROR: SISTEMA Y SUBSISTEMA NO ENCONTRADOS (Sis.: $sistema - Sub.: $subsistema)";
                $subsistema_id = 1486;
                //exit;
            } else {
                $subsistema_id = $datos_sistema_subsistema->id;
            }

            //print_r($datos_und_medida);
            //print_r($datos_sistema_subsistema);
            $arrayUpdate = [
                'product_features' => utf8_encode(strtoupper(trim($datos_parte_as->caracteristicas))),
                'subsystem_id' => $subsistema_id,
                'measure_unit_id' => $datos_und_medida->id
            ];
            $arrayWhere = [
                ['id', '=', $part->id]
            ];
            //print_r($arrayUpdate);
            //print_r($arrayWhere);
            DB::table('part_parts')
                ->where($arrayWhere)
                ->update($arrayUpdate);
        }
        //exit;

        $n_offset = (int)$offset + (int)$limit;
        if ($n_offset < $cantidad) {
            echo '<a href="act_dat_pp/' . $n_offset . '">Siguiente</a>';
            return redirect('act_dat_pp/' . $n_offset);
        } else echo '<br>Fin Actualizar datos de Partes';
    }

    public function actualiza_datos_parte_detalles(Request $request)
    {
        //ACTUALIZA OBSERVACIONES, CODIGO DE FABRICA
        $offset = ($request->offset) ? intval($request->offset) : 0;
        $limit = 10000;

        $select_pp = ['vp.part_detail_id', 'vp.line_code', 'vp.origin_code', 'vp.trademark_code', 'vp.part_code', 'vp.factory_code', 'vp.product_remarks'];
        $datos_partes = DB::table('v_partes as vp')->distinct()->select($select_pp)
            ->orderBy('part_detail_id')->offset($offset)->limit($limit)->get()->toArray();

        $cantidad = DB::table('v_partes as vp')->distinct()->select($select_pp)->count();
        echo "<br>Cantidad: $cantidad";
        $i = 0;
        foreach ($datos_partes as $part) {
            $i++;
            echo "<br>Registro: $i";
            echo '<pre>';
            print_r($part);
            if (!$datos_parte_as = Utilidades::retorna_datos_parte_as400($part->line_code, $part->origin_code, $part->trademark_code, $part->part_code)) {
                echo "DATOS NO ENCONTRADOS: " . $part->line_code . " - " . $part->part_code;
                continue;
            }
            print_r($datos_parte_as);
            echo '<br>';

            $arrayUpdate = [
                'product_remarks' => utf8_encode(strtoupper(trim($datos_parte_as->soobserv))),
                'factory_code' => utf8_encode(strtoupper(trim($datos_parte_as->etcodfab))),
            ];
            $arrayWhere = [
                ['id', '=', $part->part_detail_id]
            ];
            print_r($arrayUpdate);
            //print_r($arrayWhere);
            DB::table('part_part_details')
                ->where($arrayWhere)
                ->update($arrayUpdate);
        }

        $n_offset = (int)$offset + (int)$limit;
        if ($n_offset < $cantidad) {
            echo '<a href="act_dat_ppd/' . $n_offset . '">Siguiente</a>';
            return redirect('act_dat_ppd/' . $n_offset);
        } else echo '<br>Fin Actualizar datos de Detalles de Partes';
    }

    public function imp_vehiculos()
    {
        $lineas = DB::table('gen_resource_details')->select(['id', 'code'])->where('resource_id', 7)->get()->toArray();
        $registros = DB::connection('ibmi')->table('LIBPRDDAT.MMOBREP')->get()->toArray();

        foreach ($registros as $registro) {
            $anio = intval($registro->obanomod);
            $secuencia = intval($registro->obsecuen);
            $motor = utf8_encode(strtoupper(trim($registro->obmotor)));
            $caja = utf8_encode(strtoupper(trim($registro->obcajac)));
            $categoria = utf8_encode(strtoupper(trim($registro->obcate01)));
            $hp = utf8_encode(strtoupper(trim($registro->obhp)));
            $traccion = utf8_encode(strtoupper(trim($registro->obtracc)));
            $eje_trasero = utf8_encode(strtoupper(trim($registro->obejpst)));
            $eje_delantero = utf8_encode(strtoupper(trim($registro->obejdel)));
            $reg_status = ($registro->obsts === 'A') ? 1 : 0;

            $codigo_modelo = utf8_encode(strtoupper(trim($registro->obcodmod)));
            $desc_modelo = utf8_encode(strtoupper(trim($registro->obdscmod)));

            $indice = array_search($registro->obcodlin, array_column($lineas, 'code'));
            $line_id = $lineas[$indice]->id;
            echo "<br>L: $line_id - M: $codigo_modelo";
            $datos_modelo = DB::table('veh_models')->where('model_code', $codigo_modelo)->where('line_id', $line_id)->first();
            if ($datos_modelo) {
                $id_modelo = $datos_modelo->id;
            } else {
                $arrayInsert = [
                    'line_id' => $line_id,
                    'model_code' => $codigo_modelo,
                    'model_description' => $desc_modelo,
                    'reg_status' => 1,
                    'created_at' => date("Y-m-d H:i:s")
                ];
                print_r($arrayInsert);
                VehModel::create($arrayInsert);
                $datos_modelo = DB::table('veh_models')->where('model_code', $codigo_modelo)->where('line_id', $line_id)->first();
                if ($datos_modelo) {
                    $id_modelo = $datos_modelo->id;
                } else {
                    die("Modelo no existe");
                }
            }

            $arrayInsert = [
                'model_id' => $id_modelo,
                'veh_year' => $anio,
                'veh_order' => $secuencia,
                'veh_hp' => $hp,
                'veh_traction' => $traccion,
                'veh_engine' => $motor,
                'veh_gearbox' => $caja,
                'veh_front_axle' => $eje_delantero,
                'veh_rear_axle' => $eje_trasero,
                'veh_category_code' => $categoria,
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s"),
                'reg_status' => $reg_status,
            ];

            $arrayWhere = [
                ['model_id', '=', $id_modelo],
                ['veh_year', '=', $anio],
                ['veh_order', '=', $secuencia],
            ];

            VehVehicle::updateOrCreate($arrayWhere, $arrayInsert);
            echo '<pre>';
            print_r($lineas[$indice]);
            print_r($registro);
            print_r($datos_modelo);
            //exit;
        }
    }


    public function importar_partes_x_modelo_vehiculo(Request $request)
    {
        $offset = ($request->offset) ? intval($request->offset) : 0;
        $limit = 10000;

        $cantidad = DB::connection('ibmi')->table('LIBPRDDAT.MMOCREP')->count();

        $registros = DB::connection('ibmi')->table('LIBPRDDAT.MMOCREP')
            ->orderBy('OCCODART')
            ->offset($offset)
            ->limit($limit)
            ->get()->toArray();

        echo "<br>Cantidad: $cantidad";
        echo '<pre>';
        //print_r($registros);
        $i = 0;
        foreach ($registros as $registro) {
            $i++;
            echo "<br>Registro: $i";
            $linea_modelo = trim($registro->oclinmod);
            $codigo_modelo = trim(mb_strtoupper(utf8_encode($registro->occodmod)));
            $linea_parte = trim($registro->oclinart);
            $codigo_parte = trim(mb_strtoupper(utf8_encode($registro->occodart)));
            $secuencia = trim($registro->ocsecuen);

            $datos_vehiculo = DB::table('v_vehiculos')->where('line_code', $linea_modelo)->where('model_code', $codigo_modelo)->where('veh_order', $secuencia)->first();

            $datos_parte = DB::table('part_parts as p')->select(['p.id as part_id'])
                ->join('gen_resource_details AS line', 'p.line_id', '=', 'line.id')
                ->where('line.code', $linea_parte)->where('p.code', $codigo_parte)->first();

            if ($datos_vehiculo && $datos_parte) {
                print_r($datos_parte);
                print_r($datos_vehiculo);
                $arrayInsert = [
                    'part_id' => $datos_parte->part_id,
                    'vehicle_id' => $datos_vehiculo->vehicle_id,
                    'veh_order' => $secuencia,
                    'reg_status' => 1,
                    'created_at' => date("Y-m-d H:i:s")
                ];
                $arrayWhere = [
                    ['part_id', '=', $datos_parte->part_id],
                    ['vehicle_id', '=', $datos_vehiculo->vehicle_id]
                ];
                VehPartVehicle::updateOrCreate($arrayWhere, $arrayInsert);
            } else {
                echo "<BR>VEHICULO O PARTE NO ENCONTRADA. Part:  $linea_parte - $codigo_parte | Veh: $linea_modelo - $codigo_modelo";
            }
        }
        //exit;


        $n_offset = (int)$offset + (int)$limit;
        if ($n_offset < $cantidad) {
            echo '<a href="imp_part_veh/' . $n_offset . '">Siguiente</a>';
            return redirect('imp_part_veh/' . $n_offset);
        } else echo '<br>Fin Actualizar datos de Detalles de Partes-Vehículos';
    }

    public function importar_imagenes_ecommerce_mysql(Request $request)
    {
        $offset = ($request->offset) ? intval($request->offset) : 0;
        $limit = 10000;

        $cantidad =  DB::connection('mysql')
            ->table('products AS p')
            ->join('product_images AS pi', 'p.id', '=', 'pi.product_id')
            ->select(['sku', 'pi.image', 'is_360'])->count();

        $registros = DB::connection('mysql')
            ->table('products AS p')
            ->join('product_images AS pi', 'p.id', '=', 'pi.product_id')
            ->select(['sku', 'pi.image', 'is_360'])
            ->limit($limit)
            ->offset($offset)
            ->get()->toArray();

        echo "<br>Cantidad: " . $cantidad;
        echo '<pre>';
        //print_r($registros);
        $i = 0;
        foreach ($registros as $registro) {
            $i++;
            echo "<br>Nro: $i<br>";
            $part_detail = DB::table('part_part_details')->where('sku', $registro->sku)->select(['id'])->first();
            if ($part_detail) {
                $arrayInsert = [
                    'part_detail_id' => $part_detail->id,
                    'image' => $registro->image,
                    'is_360' => $registro->is_360
                ];
                $arrayWhere = [
                    ['part_detail_id', '=', $part_detail->id],
                    ['image', '=', $registro->image]
                ];
                PartDetailImage::updateOrCreate($arrayWhere, $arrayInsert);
                print_r($arrayInsert);
            }
        }
        $n_offset = (int)$offset + (int)$limit;
        if ($n_offset < $cantidad) {
            echo '<a href="imp_img/' . $n_offset . '">Siguiente</a>';
            return redirect('imp_img/' . $n_offset);
        } else echo '<br>Fin Actualizar imagenes de partes';
    }

    public function importar_variables_logisticas(Request $request)
    {
        $offset = ($request->offset) ? intval($request->offset) : 0;
        $limit = 100;

        $cantidad = DB::connection('ibmi')->table('LIBPRDDAT.ALFUREP')->count();
        $registros = DB::connection('ibmi')->table('LIBPRDDAT.ALFUREP')->limit($limit)->offset($offset)->get()->toArray();
        echo "<br>Cantidad: " . $cantidad;
        echo '<pre>';
        //print_r($registros);
        $i = 0;
        foreach ($registros as $registro) {
            $i++;
            $sku = $registro->fucodlin . $registro->fucodori . $registro->fucodmar . utf8_encode(mb_strtoupper(trim($registro->fucodart)));
            echo "<br>Nro: $i -> SKU: $sku<br>";
            $datos_parte = DB::table('v_partes')->where('sku', $sku)->select(['part_detail_id', 'sku'])->first();
            if ($datos_parte) {
                //print_r($registro);
                //print_r($datos_parte);
                $str_espec = 'LARGO ' . trim($registro->fuuvtlar) . 'cm / ANCHO ' . trim($registro->fuuvtanc) . 'cm / ALTO ' . trim($registro->fuuvtalt) . 'cm / PESO ' . trim($registro->fuuvtpes) . 'Kg';
                //echo "<br>$str_espec ";
                $arrayWhere = [
                    ['id', '=', $datos_parte->part_detail_id]
                ];
                $arrayUpdate = [
                    'technical_spec' => $str_espec
                ];
                PartPartDetail::updateOrCreate(
                    $arrayWhere,
                    $arrayUpdate
                );
                //print_r($arrayWhere);
                //print_r($arrayUpdate);
                echo "<br>$datos_parte->part_detail_id - $datos_parte->sku - $str_espec ";
            }
        }


        $n_offset = (int)$offset + (int)$limit;
        if ($n_offset < $cantidad) {
            echo '<a href="imp_vl/' . $n_offset . '">Siguiente</a>';
            return redirect('imp_vl/' . $n_offset);
        } else echo '<br>Fin Actualizar especificaciones tecnicas';
    }


    public function sync_customer_addresses(Request $request)
    {
        ini_set('max_execution_time', '3000');

        $offset = ($request->offset) ? intval($request->offset) : 0;
        $limit = 10000;

        $cantidad = DB::connection('ibmi')->table('LIBPRDDAT.MMAKREP')
            ->where('AKSTS', 'A')
            ->where('AKCODCLI', '<>', '')
            ->count();

        $registros = DB::connection('ibmi')->table('LIBPRDDAT.MMAKREP')
            ->where('AKSTS', 'A')
            ->where('AKCODCLI', '<>', '')
            ->orderBy('AKCODCLI')->limit($limit)->offset($offset)->get()->toArray();

        echo "<br>Cantidad: " . $cantidad;
        echo '<pre>';
        //print_r($registros);
        $whereInField = 'resource_id';
        $whereInArray = array(1, 2, 3, 4, 5);
        $arraySelect = ['id', 'code', 'name', 'resource_id'];
        $array_tipos = $this->selecciona_from_tabla_where_in('gen_resource_details', $whereInField, $whereInArray, $arraySelect);

        $i = 0;
        foreach ($registros as $cliente) {
            $i++;
            echo "<br>($i) - Cliente: $cliente->akcodcli";

            if (!$datos_cliente = $this->selecciona_fila_from_tabla('customers', array(['code', '=', trim($cliente->akcodcli)]))) {
                echo '<br>CLIENTE NO EXISTE -> REGISTRAR';
                $cli = new stdClass();
                $cli->datos_consulta = $cliente;
                SyncCustomer::mmakrep_maestro_clientes($cli);
                if (!$datos_cliente = $this->selecciona_fila_from_tabla('customers', array(['code', '=', trim($cliente->akcodcli)]))) {
                    echo '<br>CLIENTE NO PUDO SER REGISTRADO';
                    continue;
                }
            }

            //INACTIVAR DIRECCIONES REGISTRADAS PARA ESE CLIENTE
            DB::table('customer_addresses')
                ->where('customer_id', $datos_cliente->id)
                ->update(['reg_status' => 0, 'updated_at' => date("Y-m-d H:i:s")]);

            $cliente->direcciones = DB::connection('ibmi')->table('LIBPRDDAT.MMALREP')
                ->where('ALSTS', 'A')
                ->where('ALCODCLI', $datos_cliente->code)
                ->orderBy('ALITEM01')->get()->toArray();

            echo " <br> Cant. Direcciones: " . sizeof($cliente->direcciones);

            foreach ($cliente->direcciones as $direccion) {
                echo "<br> Nro. Direccion: $direccion->alitem01 - Tipo Direccion: $direccion->altipdir";

                //print_r($direccion);
                //print_r($datos_cliente);

                $arrayWhere = array(
                    ['dpto_code', '=', $direccion->aldepart],
                    ['prov_code', '=', $direccion->alprovin],
                    ['dist_code', '=', $direccion->aldistri],
                );
                if ($region = $this->selecciona_fila_from_tabla('dist_prov_dpto_peru', $arrayWhere))
                    $distrito_id = $region->dist_id;
                else $distrito_id = 1807;
                if (!$tipo_direccion_id = $this->busca_datos_vector($direccion->altipdir, 3, $array_tipos)) $tipo_direccion_id = 11; //TIPO DIR LEGAL
                $tipo_via_id = $this->busca_datos_vector($direccion->alviadir, 4, $array_tipos);
                $tipo_zona_id = $this->busca_datos_vector($direccion->alzondir, 5, $array_tipos);

                $address_order = $direccion->alitem01;
                $fecha_hora = date("Y-m-d H:i:s");

                $arrayWhere = [
                    ['customer_id', '=', $datos_cliente->id],
                    ['address_order', '=', $address_order],
                ];

                $arrayInsert = [
                    'customer_id' => $datos_cliente->id,
                    'address_order' => $address_order,
                    'country_id' => $datos_cliente->country_id,
                    'address_type_id' => $tipo_direccion_id,
                    'road_type_id' => $tipo_via_id,
                    'road_name' => strtoupper(trim(utf8_encode($direccion->aldscdir))),
                    'number' => strtoupper(trim(($direccion->alnrodir))),
                    'apartment' => strtoupper(trim(utf8_encode($direccion->alnrodpt))),
                    'floor' => strtoupper(trim($direccion->alnropso)),
                    'block' => strtoupper(trim($direccion->alnrodir)),
                    'allotment' => strtoupper(trim(utf8_encode($direccion->alnrolte))),
                    'zone_type_id' => $tipo_zona_id,
                    'zone_name' => strtoupper(trim(utf8_encode($direccion->aldsczdr))),
                    'region_id' => $distrito_id,
                    'contact_name' => strtoupper(trim(utf8_encode($direccion->alnrotl2))),
                    'contact_phone' => strtoupper(trim(utf8_encode($direccion->alnrotl1))),
                    'contact_email' => strtoupper(trim(utf8_encode($direccion->alemail))),
                    'reg_status' => (strtoupper(trim($direccion->alsts)) == 'A') ? 1 : 0,
                    'updated_at' => $fecha_hora
                ];

                CustomerAddress::updateOrCreate($arrayWhere, $arrayInsert);
            }
        }
        $n_offset = (int)$offset + (int)$limit;
        if ($n_offset < $cantidad) {
            echo '<a href="sync_cust_adds/' . $n_offset . '">Siguiente</a>';
            return redirect('sync_cust_adds/' . $n_offset);
        } else echo '<br>Fin SINCRONIZAR DIRECCIONES DE CLIENTES';
    }
}
