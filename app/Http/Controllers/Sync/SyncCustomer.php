<?php

namespace App\Http\Controllers\Sync;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerContact;
use App\Http\Controllers\Sync\Utilidades;
use App\Models\Provider;
use App\Models\Customers\CustomerPaymentMethod;

use DB;

class SyncCustomer extends Controller
{

    public function mmakrep_maestro_clientes($fila)
    {
        $util = new Utilidades();
        $cliente = $fila->datos_consulta;
        $code = strtoupper(trim($cliente->akcodcli));
        //$datos_cliente = Customer::where('code', $cliente->akcodcli)->first();

        $whereInField = 'resource_id';
        $whereInArray = array(1, 2);
        $arraySelect = ['id', 'code', 'name', 'resource_id'];
        $array_tipos = $util->selecciona_from_tabla_where_in('gen_resource_details', $whereInField, $whereInArray, $arraySelect);

        $arrayWhere = array(
            ['ubigeo_type_id', '=', 1],
            ['code', '=', $cliente->akcodpai]
        );
        if (!$datos_pais = $util->selecciona_fila_from_tabla('ubigeos', $arrayWhere)) {
            //$pais_id = ($cliente->akcodpai === '001') ? 163 : 1;
            $pais_id = 1;
        } else $pais_id = $datos_pais->id;

        $tipo_persona_id = $util->busca_datos_vector($cliente->aktipemp, 1, $array_tipos);
        $tipo_identificacion_id = $util->busca_datos_vector($cliente->aktipide, 2, $array_tipos);
        $tipo_identificacion_id = ($tipo_identificacion_id) ? $tipo_identificacion_id : (($tipo_persona_id == 2) ? 6 : 5);
        $tipo_persona_id = ($tipo_persona_id) ? $tipo_persona_id : (($tipo_identificacion_id == 5) ? 1 : 2);
        $name_social_reason = utf8_decode(strtoupper(trim($cliente->akrazsoc)));

        $ident_natural = trim($cliente->aknroide);
        if ($datos_ruc = $util->selecciona_fila_from_tabla_db2('LIBPRDDAT.MMIFREP', array(['IFCODCLI', '=', $code]))) {
            $ident_juridica = trim($datos_ruc->ifnvoruc);
            $numero_identificacion = ($cliente->aktipide === '01' && strlen($ident_natural) > 0) ? $ident_natural : $ident_juridica;
            if (empty($numero_identificacion)) {
                $numero_identificacion = $cliente->akcodcli;
            }
        } else {
            $ident_juridica = 0;
            $numero_identificacion = ($cliente->aktipide === '01' && strlen($ident_natural) > 0) ? $ident_natural : $cliente->akcodcli;
        }


        $arrayWhere = array(
            ['code', '=', $code]
        );
        $arrayInsert = array(
            'code' => $code,
            'company_type_id' => $tipo_persona_id,
            'document_type_id' => $tipo_identificacion_id,
            'document_number' => $numero_identificacion,
            'name_social_reason' => $name_social_reason,
            'country_id' => $pais_id,
            'client_class' => $cliente->akclsclt,
            'reg_date' => $cliente->akfecins,
            'tradename' => utf8_decode(strtoupper(trim($cliente->aknomcom))),
            'ruc_code_old' => $cliente->aknroruc,
            'sales_block' => $cliente->akblqvta,
            'credit_block' => $cliente->akblqcrd,
            'reg_status' => ($cliente->aksts === 'A') ? 1 : 0,
            'tax_condition' => $cliente->akcndtrb,
            'currency_code' => $cliente->akcodmon,
            'max_credit_limit' => $cliente->akimplmt,
            'updated_at' => date("Y-m-d H:i:s")
        );
        Customer::updateOrCreate(
            $arrayWhere,
            $arrayInsert
        );
        return 1;
    }

    public function mmalrep_cliente_direcciones($fila)
    {
        $util = new Utilidades();
        $direccion = $fila->datos_consulta;
        $code = strtoupper(trim($direccion->alcodcli));
        //echo '<pre>';
        //print_r($direccion);
        if ($datos_cliente = Customer::where('code', $code)->first()) {
            $whereInField = 'resource_id';
            $whereInArray = array(3, 4, 5);
            $arraySelect = ['id', 'code', 'name', 'resource_id'];
            $array_tipos = $util->selecciona_from_tabla_where_in('gen_resource_details', $whereInField, $whereInArray, $arraySelect);

            $arrayWhere = array(
                ['dpto_code', '=', $direccion->aldepart],
                ['prov_code', '=', $direccion->alprovin],
                ['dist_code', '=', $direccion->aldistri],
            );

            if ($region = $util->selecciona_fila_from_tabla('dist_prov_dpto_peru', $arrayWhere))
                $distrito_id = $region->dist_id;
            else $distrito_id = null;
            if (!$tipo_direccion_id = $util->busca_datos_vector($direccion->altipdir, 3, $array_tipos)) $tipo_direccion_id = 11; //TIPO DIR LEGAL
            $tipo_via_id = $util->busca_datos_vector($direccion->alviadir, 4, $array_tipos);
            $tipo_zona_id = $util->busca_datos_vector($direccion->alzondir, 5, $array_tipos);
            $arrayWhere = array(
                ['customer_id', '=', $datos_cliente->id],
                ['address_order', '=', intval($direccion->alitem01)],
            );
            $arrayInsert = array(
                'customer_id' => $datos_cliente->id,
                'address_order' => intval($direccion->alitem01),
                'country_id' => $datos_cliente->country_id,
                'address_type_id' => $tipo_direccion_id,
                'road_type_id' => $tipo_via_id,
                'road_name' => utf8_decode(strtoupper(trim($direccion->aldscdir))),
                'number' => strtoupper(trim($direccion->alnrodir)),
                'apartment' => utf8_decode(strtoupper(trim($direccion->alnrodpt))),
                'floor' => utf8_decode(strtoupper(trim($direccion->alnropso))),
                'block' => utf8_decode(strtoupper(trim($direccion->alnrodir))),
                'allotment' => strtoupper(trim($direccion->alnrolte)),
                'zone_type_id' => $tipo_zona_id,
                'zone_name' => utf8_decode(strtoupper(trim($direccion->aldsczdr))),
                'region_id' => $distrito_id,
                'contact_name' => utf8_decode(strtoupper(trim($direccion->alnrotl2))),
                'contact_phone' => utf8_decode(strtoupper(trim($direccion->alnrotl1))),
                'contact_email' => strtoupper(trim($direccion->alemail)),
                'reg_status' => (strtoupper(trim($direccion->alsts)) == 'A') ? 1 : 0,
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            );
            //echo '<pre>';
            //print_r($arrayWhere);
            //print_r($arrayInsert);
            CustomerAddress::updateOrCreate(
                $arrayWhere,
                $arrayInsert
            );
            return 1;
        } else {
            echo "<br>Cliente ($code) no existe";
            return false;
        }
    }

    public function ccpcrep_cliente_contactos($fila)
    {
        $util = new Utilidades();
        $registro = $fila->datos_consulta;
        echo '<pre>';
        //die(print_r($registro));
        $arrayWhere = array(
            ['resource_id', '=', 8],
            ['code', '=', $registro->pccodcar]
        );
        echo "<br>contacto-cargo: $registro->pccodcar";
        $arraySelect = ['id'];
        $datos_cargo = $util->selecciona_fila_from_tabla('gen_resource_details', $arrayWhere, $arraySelect);
        if ($datos_cargo) {
            $cargo_id = ($datos_cargo->id !== false) ? $datos_cargo->id : null;
        } else {
            $cargo_id =  null;
        }


        if (!$datos_cliente = $util->selecciona_fila_from_tabla('customers', array(['code', '=', trim($registro->pccodcli)]))) {
            echo " - CLIENTE $registro->pccodcli NO EXISTE, -> REGISTRAR";
            $registro->datos_consulta = $util->selecciona_fila_from_tabla_db2('LIBPRDDAT.MMAKREP', array(['AKCODCLI', '=', $registro->pccodcli]));
            $resultado = $this->mmakrep_maestro_clientes($registro);
            if ($resultado) {
                echo " - CLIENTE REGISTRADO";
                if (!$datos_cliente = $util->selecciona_fila_from_tabla('customers', array(['code', '=', trim($registro->pccodcli)]))) {
                    //echo ('<br>CLIENTE NO EXISTE');
                    return false;
                }
            }
        }
        $item = ((int)$registro->pcitem01 > 0) ? (int)$registro->pcitem01 : 0;
        echo "<br>Item: $item";
        $arrayWhereC = [
            'customer_id' => $datos_cliente->id,
            'customer_contact_number' => $item
        ];
        $arrayInsertC = [
            'customer_id' => $datos_cliente->id,
            'customer_contact_number' => $item,
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
        return true;
    }

    public function mmahrep_proveedores($fila)
    {
        $codigo = $fila->datos_consulta->ahcodprv;
        echo "<br>PROCESAR PROVEEDOR: " . $codigo;
        $util = new Utilidades();
        if (!$datos_proveedor = $util->retorna_datos_proveedor_as($codigo)) {
            echo "<br>Proveedor ($codigo) no existe";
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
        return 1;
    }


    public function mmarrep_forma_pago_cliente($registro)
    {
        //ACTUALIZAR FORMAS DE PAGO CLIENTE
        //$codigo_cliente = '029397';
        $codigo_cliente = trim($registro->arcodcli);
        echo "<br> CLIENTE: $codigo_cliente";

        $datos_cliente = DB::table('customers')->where('code', $codigo_cliente)->first();
        if ($datos_cliente) {
            //INACTIVAR REGISTROS EN TABLA CUSTOMER_PAYMENT_METHODS PARA ESE CLIENTE
            DB::table('customer_payment_methods')
                ->where('customer_id', $datos_cliente->id)
                ->update(['reg_status' => 0, 'updated_at' => date("Y-m-d H:i:s")]);
        } else {
            echo " - CLIENTE $codigo_cliente NO EXISTE, -> REGISTRAR";
            if (!$registro->datos_consulta = DB::connection('ibmi')->table('LIBPRDDAT.MMAKREP')->where('AKCODCLI', $codigo_cliente)->first()) {
                echo "<br>CLIENTE NO EXISTE EN AS400 - RETORNA 1";
                return 1;
            }

            $resultado = SyncCustomer::mmakrep_maestro_clientes($registro);
            if ($resultado) {
                echo " - CLIENTE REGISTRADO";
                if (!$datos_cliente = DB::table('customers')->where('code', $codigo_cliente)->first()) {
                    //echo ('<br>CLIENTE NO EXISTE');
                    return false;
                }
            }
        }

        $select = ['a.arcodcia', 'a.arcodcli', 'a.arfrmpag', 'a.armodpag', 'a.arsts', 'b.B2CNDPAG', 'b.B2DIAPLZ', 'a.arsts', 'b.b2sts'];

        $registros = DB::connection('ibmi')->table('LIBPRDDAT.MMARREP as a')->select($select)
            ->join('LIBPRDDAT.MMB2REP AS b', function ($join) {
                $join->on('a.arfrmpag', '=', 'b.b2frmpag');
                $join->on('a.armodpag', '=', 'b.b2modpag');
            })
            ->where('a.arcodcli', $codigo_cliente)
            ->where('a.arcodcia', '10')
            ->where('a.arsts', 'A')
            ->where('b.b2sts', 'A')
            ->whereRaw("b.B2DIAPLZ = (case 
        when a.armodpag='FA' then '30'
        when a.armodpag='FC' then '30'
        when a.armodpag='BV' then '30'
        when a.armodpag='CH' then '30'
        ELSE '0'
        end)")
            ->get()->toArray();
        //->toSql();
        //die($registros);
        echo "<BR>Cantidad: " . sizeof($registros);
        $select = ['id', 'code', 'name', 'resource_id'];
        foreach ($registros as $fila) {
            $datos_forma_pago = DB::table('gen_resource_details')->select($select)->where('resource_id', 31)->where('code', $fila->arfrmpag)->first();
            $datos_modalidad_pago = DB::table('gen_resource_details')->select($select)->where('resource_id', 32)->where('code', $fila->armodpag)->first();
            $datos_condicion_pago = DB::table('gen_resource_details')->select($select)->where('resource_id', 33)->where('code', $fila->b2cndpag)->first();

            if (!$datos_condicion_pago) {
                echo "<br>CONDICIÓN NO ENCONTRADA -> Cliente: $fila->arcodcli - Cond. Pago: $fila->b2cndpag";
                exit;
            }

            if ($datos_forma_pago && $datos_modalidad_pago && $datos_condicion_pago) {

                $tipo_documento = null;
                switch ($fila->armodpag) {
                    case 'FA':
                        $tipo_documento = '01';
                        break;

                    case 'BV':
                        $tipo_documento = '03';
                        break;

                    case 'FC':
                        $tipo_documento = '04';
                        break;

                    default:
                        $tipo_documento = '01';
                        break;
                }

                $arrayWhere = array(
                    ['customer_id', '=', $datos_cliente->id],
                    ['payment_method_id', '=', $datos_forma_pago->id],
                    ['payment_modality_id', '=', $datos_modalidad_pago->id],
                    ['payment_condition_id', '=', $datos_condicion_pago->id]
                );
                $arrayInsert = array(
                    'customer_id' => $datos_cliente->id,
                    'payment_method_id' => $datos_forma_pago->id,
                    'payment_modality_id' => $datos_modalidad_pago->id,
                    'payment_condition_id' => $datos_condicion_pago->id,
                    'document_type' => $tipo_documento,
                    'reg_status' => 1,
                    'created_at' => date("Y-m-d H:i:s"),
                    'updated_at' => date("Y-m-d H:i:s"),
                );

                CustomerPaymentMethod::updateOrCreate(
                    $arrayWhere,
                    $arrayInsert
                );
                echo "<br>Forma de pago agregada -> {$datos_cliente->code}: {$datos_forma_pago->code}-{$datos_modalidad_pago->code}-{$datos_condicion_pago->code}";
                $actualizar = 1;
            } else {
                echo "<br>Error:";
                print_r($datos_forma_pago);
                print_r($datos_modalidad_pago);
                print_r($datos_condicion_pago);
                $actualizar = 0;
            }
        }

        return $actualizar;
    }
}
