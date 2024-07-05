<?php

namespace App\Http\Controllers\vimAPI;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vim\AsSyncVim;
use App\Models\Vim\AsSyncVimFS;
use App\Models\Vim\SyncVimCountry;
// use App\Models\SyncVimCustomer;
use App\Http\Controllers\Sync\SyncCustomer;
use App\Http\Controllers\vimAPI\ActionsVimController;

//use App\Exceptions\Handler;

use DB;
// use Maatwebsite\Excel\Concerns\ToArray;
use stdClass;

class SyncVimController extends Controller
{
    public function syncvim(Request $request)
    {
        //$tipo_proceso = 'MIGRACION_INICIAL'; //HABILITAR PARA LA MIGRACION INICIAL DE DATOS (GRAN CANTIDAD DE DATOS)
        $tipo_proceso = 'AUTOMATICO'; //HABILITAR CUANDO EL PROCESO EMPIECE A EJECUTARSE DE FORMA AUTOMATICA

        if ($tipo_proceso == 'MIGRACION_INICIAL') {
            /////////////////////////////////////// MIGRACION INICIAL ///////////////////////////////////////
            switch ($request->param) {
                case 'CLIENT': //PARAMETRO REFERENTE A LA TABLA CLIENTES
                    $registros = $this->getQueriesStoragedInDB2CLIENTMIGRATION();
        
                    if ($registros && is_array($registros)) {
                        echo "<br>Cantidad de Registros Nuevos: " . sizeof($registros);
        
                        $i = 0;
                        foreach ($registros as $registro) {
                            //Guardando en DB:POSTGRESQL, tabla "clientes"
                            ActionsVimController::mmakrep_maestro_clientes_vim_migracion($registro);
    
                            echo '<br>Registro #: '.$i.' - ID Cliente: '.trim($registro->akcodcli).' - Razón Social: '.trim($registro->akrazsoc).'<br>';
                            $i++;
                        }
                    }
    
                    break;
                case 'COUNTRY': //PARAMETRO REFERENTE A LA TABLA PAISES
                    $registros = $this->getQueriesStoragedInDB2COUNTRYMIGRATION();
    
                    if ($registros && is_array($registros)) {
                        echo "<br>Cantidad de Registros Nuevos: " . sizeof($registros);
    
                        $i = 0;
                        foreach ($registros as $registro) {
                            //Guardando en DB:POSTGRESQL, tabla "pais"
                            ActionsVimController::mmferel0_maestro_paises_vim_migracion($registro);
    
                            echo '<br>Registro #: '.$i.' - Código: '.trim($registro->fecodpai).' - País: '.trim($registro->fedsccor).'<br>';
                            $i++;
                        }
                    }
                    
                    break;
                case 'PROVIDER': //PARAMETRO REFERENTE A LA TABLA PROVEEDORES
                    $registros = $this->getQueriesStoragedInDB2PROVIDERMIGRATION();
    
                    if ($registros && is_array($registros)) {
                        echo "<br>Cantidad de Registros Nuevos: " . sizeof($registros);
    
                        $i = 0;
                        foreach ($registros as $registro) {
                            //Guardando en DB:POSTGRESQL, tabla "proveedores"
                            ActionsVimController::mmahrep_maestro_proveedores_vim_migracion($registro);
    
                            echo '<br>Registro #: '.$i.' - Código: '.trim($registro->ahcodprv).' - Razón Social: '.trim($registro->ahrazsoc).'<br>';
                            $i++;
                        }
                    }
                        
                    break;
                case 'ORIGIN': //PARAMETRO REFERENTE A LA TABLA ORIGENES
                    $registros = $this->getQueriesStoragedInDB2ORIGINMIGRATION();
        
                    if ($registros && is_array($registros)) {
                        echo "<br>Cantidad de Registros Nuevos: " . sizeof($registros);
        
                        $i = 0;
                        foreach ($registros as $registro) {
                            //Guardando en DB:POSTGRESQL, tabla "origenes"
                            ActionsVimController::mmeurel0_maestro_origenes_vim_migracion($registro);
        
                            echo '<br>Registro #: '.$i.' - Código: '.trim($registro->eucodele).' - Descripción: '.trim($registro->eudsclar).'<br>';
                            $i++;
                        }
                    }
                            
                    break;
                case 'TYPE': //PARAMETRO REFERENTE A LA TABLA TIPOS
                    $registros = $this->getQueriesStoragedInDB2TYPEMIGRATION();
            
                    if ($registros && is_array($registros)) {
                        echo "<br>Cantidad de Registros Nuevos: " . sizeof($registros);
            
                        $i = 0;
                        foreach ($registros as $registro) {
                            //Guardando en DB:POSTGRESQL, tabla "tipos"
                            ActionsVimController::comodvel0_maestro_tipos_vim_migracion($registro);
            
                            echo '<br>Registro #: '.$i.' - Código: '.trim($registro->mvcodele).' - Descripción: '.trim($registro->mvdesele).'<br>';
                            $i++;
                        }
                    }
                      
                    break;
                case 'ARTICLE': //PARAMETRO REFERENTE A LA TABLA ARTICULOS
                    $registros = $this->getQueriesStoragedInDB2ARTICLEMIGRATION();
                
                    if ($registros && is_array($registros)) {
                        echo "<br>Cantidad de Registros Nuevos: " . sizeof($registros);
                
                        $i = 0;
                        foreach ($registros as $registro) {
                            //Guardando en DB:POSTGRESQL, tabla "articulos"
                            ActionsVimController::mmacrep_maestro_articulos_vim_migracion($registro);
                
                            echo '<br>Registro #: '.$i.' - Código: '.trim($registro->accodart).' - Descripción: '.trim($registro->acdsclar).'<br>';
                            $i++;
                        }
                    }
                          
                    break;
                case 'ARTICLEDETAIL': //PARAMETRO REFERENTE A LA TABLA ARTICULO DETALLES
                    $registros = $this->getQueriesStoragedInDB2ARTICLEDETAILMIGRATION();
                    
                    if ($registros && is_array($registros)) {
                        echo "<br>Cantidad de Registros Nuevos: " . sizeof($registros);
                    
                        $i = 0;
                        foreach ($registros as $registro) {
                            //Guardando en DB:POSTGRESQL, tabla "articulo_detalles"
                            ActionsVimController::mmetrep_maestro_articulo_detalles_vim_migracion($registro);
                    
                            echo '<br>Registro #: '.$i.' - Código: '.trim($registro->etcodsuc).' - Código Articulo: '.trim($registro->etcodart).'<br>';
                            $i++;
                        }
                    }
    
                    break;
                case 'LINE': //PARAMETRO REFERENTE A LA TABLA LINEAS
                    $registros = $this->getQueriesStoragedInDB2LINEMIGRATION();
                        
                    if ($registros && is_array($registros)) {
                        echo "<br>Cantidad de Registros Nuevos: " . sizeof($registros);
                        
                        $i = 0;
                        foreach ($registros as $registro) {
                            //Guardando en DB:POSTGRESQL, tabla "lineas"
                            ActionsVimController::mmeurel0_maestro_lineas_vim_migracion($registro);
                        
                            echo '<br>Registro #: '.$i.' - Código: '.trim($registro->eucodele).' - Descripción: '.trim($registro->eudsclar).'<br>';
                            $i++;
                        }
                    }
    
                    break;
                case 'BRAND': //PARAMETRO REFERENTE A LA TABLA MARCAS
                    $registros = $this->getQueriesStoragedInDB2BRANDMIGRATION();
    
                    if ($registros && is_array($registros)) {
                        echo "<br>Cantidad de Registros Nuevos: " . sizeof($registros);
    
                        $i = 0;
                        foreach ($registros as $registro) {
                            //Guardando en DB:POSTGRESQL, tabla "marcas"
                            ActionsVimController::mmeyrel0_maestro_marcas_vim_migracion($registro);
                            
                            echo '<br>Registro #: '.$i.' - Código: '.trim($registro->eycodmar).' - Descripción: '.trim($registro->eydsclar).'<br>';
                            $i++;
                        }
                    }
    
                    break;
                case 'MODEL': //PARAMETRO REFERENTE A LA TABLA MODELOS
                    $registros = $this->getQueriesStoragedInDB2MODELMIGRATION();
        
                    if ($registros && is_array($registros)) {
                        echo "<br>Cantidad de Registros Nuevos: " . sizeof($registros);
        
                        $i = 0;
                        foreach ($registros as $registro) {
                            //Guardando en DB:POSTGRESQL, tabla "modelos"
                            ActionsVimController::mmocrel0_maestro_modelos_vim_migracion($registro);
                                
                            echo '<br>Registro #: '.$i.' - Código: '.trim($registro->occodmod).' - Código Artículo: '.trim($registro->occodart).'<br>';
                            $i++;
                        }
                    }
        
                    break;
                case 'MODELDETAIL': //PARAMETRO REFERENTE A LA TABLA MODELO DETALLES
                    $registros = $this->getQueriesStoragedInDB2MODELDETAILMIGRATION();
                
                    if ($registros && is_array($registros)) {
                        echo "<br>Cantidad de Registros Nuevos: " . sizeof($registros);
                
                        $i = 0;
                        foreach ($registros as $registro) {
                            //Guardando en DB:POSTGRESQL, tabla "modelo_detalles"
                            ActionsVimController::mmobrel0_maestro_modelo_detalles_vim_migracion($registro);
                                        
                            echo '<br>Registro #: '.$i.' - Código: '.trim($registro->obcodmod).' - Motor: '.trim($registro->obmotor).'<br>';
                            $i++;
                        }
                    }
                
                    break;
                default:
                    //$str_tablas = $request->param;
                    #code
                    break;
            }
            /////////////////////////////////////// MIGRACION INICIAL ///////////////////////////////////////
        }elseif($tipo_proceso == 'AUTOMATICO'){
            /////////////////////////////////////// AUTOMATICO ///////////////////////////////////////
            switch ($request->param) {
                case 'CLIENT': //PARAMETRO REFERENTE A LA TABLA CLIENTES
                    $registros = $this->getQueriesStoragedInDB2CLIENTAUTOMATIC();
    
                    if ($registros && is_array($registros)) {
                        echo "<br>Cantidad de Registros Nuevos: " . sizeof($registros);
    
                        $i = 0;
                        foreach ($registros as $registro) {
                            //Guardando en Tabla as_sync de BD en POSTGRESQL
                            $vector_as_sync_bd_intermedia = [
                                'sytabla' => trim($registro->sytabla),
                                'sql' => utf8_encode(trim($registro->sycadsql)),
                                'usuario' => trim($registro->syusuari),
                                'fecha_generado' => trim($registro->syfechac),
                                'hora_generado' => trim($registro->syhorac),
                                'tipo_operacion' => trim($registro->sytpoper),
                                'created_at' => date("Y-m-d H:i:s"),
                            ];
                            AsSyncVim::create($vector_as_sync_bd_intermedia);
                            
                            $sql = trim(str_replace(';', '', $registro->sycadsql));

                            //OPTIMIZAR PROCESO DE CONSULTA, VALIDANDO QUE LA CONSULTA ALMACENADA YA NO EXISTA EN LA TABLA DE PG
                            
                            //FIN DE LA OPTIMIZACION DE LA QUERY

                            //VAMOS A GENERAR UNA VARIACION DE LA QUERY QUE PROVIENE DE LA TABLA "LIBPRDDAT.AS_SYNC" DEL CAMPO "SYCADSQL" DEL DB2
                            //CON LA INTENCION DE INCLUIR A OTRAS 2 TABLAS QUE SE RELACIONAN CON LA TABLA MAESTRA DE CLIENTES
                            $cadena_sql_origen = $sql;

                            $cadena_sql_p1 = substr("$cadena_sql_origen", 0, 31);
                            // $cadena_sql_p2 = "INNER JOIN LIBPRDDAT.MMIFREL0 ON IFCODCLI = AKCODCLI INNER JOIN LIBPRDDAT.MMEUREL0 ON EUCODELE = AKTIPEMP WHERE AKSTS = 'A' AND IFSTS = 'A' AND EUSTS = 'A' AND EUCODTBL = 'BB' AND";
                            $cadena_sql_p2 = "LEFT JOIN LIBPRDDAT.MMIFREL0 ON IFCODCLI = AKCODCLI LEFT JOIN LIBPRDDAT.MMEUREL0 ON EUCODELE = AKTIPEMP WHERE AKSTS = 'A' AND EUSTS = 'A' AND EUCODTBL = 'BB' AND";
                            $cadena_sql_p3 = substr("$cadena_sql_origen", 38);

                            $cadena_sql_final = $cadena_sql_p1.' '.$cadena_sql_p2.' '.$cadena_sql_p3;
                            //FIN DE LA GENERACION DE LA VARIANTE DE LA QUERY

                            // $registro->datos_consulta = $this->consulta_tabla_db2($sql);
                            $registro->datos_consulta = $this->consulta_tabla_db2($cadena_sql_final);
    
                            if ($registro->datos_consulta) {
                                echo '<br>Registro #: '.$i.' - TABLA: '.trim($registro->sytabla).' - ACCIÓN: '.trim($registro->sytpoper).'<br>';
                                ActionsVimController::mmakrep_maestro_clientes_vim_automatico($registro);
                            } else {
                                echo '<br>La siguiente consulta SQL: '.trim($registro->sycadsql).' ¡NO! produjo resultados provenientes del DB2<br>';
                            }

                            $i++;
                        }
                    }
    
                    break;
                case 'COUNTRY': //PARAMETRO REFERENTE A LA TABLA PAISES
                    $registros = $this->getQueriesStoragedInDB2COUNTRYAUTOMATIC();
    
                    if ($registros && is_array($registros)) {
                        echo "<br>Cantidad de Registros Nuevos: " . sizeof($registros);
    
                        $i = 0;
                        foreach ($registros as $registro) {
                            //Guardando en DB:POSTGRESQL, tabla "pais"
                            ActionsVimController::mmferel0_maestro_paises_vim_automatico($registro);
    
                            echo '<br>Registro #: '.$i.' - Código: '.trim($registro->fecodpai).' - País: '.trim($registro->fedsccor).'<br>';
                            $i++;
                        }
                    }
                    
                    break;
                case 'PROVIDER': //PARAMETRO REFERENTE A LA TABLA PROVEEDORES
                    $registros = $this->getQueriesStoragedInDB2PROVIDERAUTOMATIC();
    
                    if ($registros && is_array($registros)) {
                        echo "<br>Cantidad de Registros Nuevos: " . sizeof($registros);
    
                        $i = 0;
                        foreach ($registros as $registro) {
                            //Guardando en Tabla as_sync de BD en POSTGRESQL
                            $vector_as_sync_bd_intermedia = [
                                'sytabla' => trim($registro->sytabla),
                                'sql' => utf8_encode(trim($registro->sycadsql)),
                                'usuario' => trim($registro->syusuari),
                                'fecha_generado' => trim($registro->syfechac),
                                'hora_generado' => trim($registro->syhorac),
                                'tipo_operacion' => trim($registro->sytpoper),
                                'created_at' => date("Y-m-d H:i:s"),
                            ];
                            AsSyncVim::create($vector_as_sync_bd_intermedia);

                            $sql = trim(str_replace(';', '', $registro->sycadsql));

                            //VAMOS A GENERAR UNA VARIACION DE LA QUERY QUE PROVIENE DE LA TABLA "LIBPRDDAT.AS_SYNC" DEL CAMPO "SYCADSQL" DEL DB2
                            //CON LA INTENCION DE INCLUIR A UNA TABLA QUE SE RELACIONA CON LA TABLA MAESTRA DE PROVEEDORES
                            $cadena_sql_origen = $sql;

                            $cadena_sql_p1 = substr("$cadena_sql_origen", 0, 31);
                            $cadena_sql_p2 = "LEFT JOIN LIBPRDDAT.MMIPREL0 ON IPCODCLI = AHCODPRV WHERE AHSTS = 'A' AND";
                            $cadena_sql_p3 = substr("$cadena_sql_origen", 38);

                            $cadena_sql_final = $cadena_sql_p1.' '.$cadena_sql_p2.' '.$cadena_sql_p3;
                            //FIN DE LA GENERACION DE LA VARIANTE DE LA QUERY

                            // $registro->datos_consulta = $this->consulta_tabla_db2($sql);
                            $registro->datos_consulta = $this->consulta_tabla_db2($cadena_sql_final);

                            if ($registro->datos_consulta) {
                                echo '<br>Registro #: '.$i.' - TABLA: '.trim($registro->sytabla).' - ACCIÓN: '.trim($registro->sytpoper).'<br>';
                                //Guardando en DB:POSTGRESQL, tabla "proveedores"
                                ActionsVimController::mmahrep_maestro_proveedores_vim_automatico($registro);
                            } else {
                                echo '<br>La siguiente consulta SQL: '.trim($registro->sycadsql).' ¡NO! produjo resultados provenientes del DB2<br>';
                            }

                            $i++;
                        }
                    }
                        
                    break;
                case 'ORIGIN': //PARAMETRO REFERENTE A LA TABLA ORIGENES
                    $registros = $this->getQueriesStoragedInDB2ORIGINAUTOMATIC();
        
                    if ($registros && is_array($registros)) {
                        echo "<br>Cantidad de Registros Nuevos: " . sizeof($registros);
        
                        $i = 0;
                        foreach ($registros as $registro) {
                            //Guardando en DB:POSTGRESQL, tabla "origenes"
                            ActionsVimController::mmeurel0_maestro_origenes_vim_automatico($registro);
        
                            echo '<br>Registro #: '.$i.' - Código: '.trim($registro->eucodele).' - Descripción: '.trim($registro->eudsclar).'<br>';
                            $i++;
                        }
                    }
                            
                    break;
                case 'TYPE': //PARAMETRO REFERENTE A LA TABLA TIPOS
                    $registros = $this->getQueriesStoragedInDB2TYPEAUTOMATIC();
            
                    if ($registros && is_array($registros)) {
                        echo "<br>Cantidad de Registros Nuevos: " . sizeof($registros);
            
                        $i = 0;
                        foreach ($registros as $registro) {
                            //Guardando en DB:POSTGRESQL, tabla "tipos"
                            ActionsVimController::comodvel0_maestro_tipos_vim_automatico($registro);
            
                            echo '<br>Registro #: '.$i.' - Código: '.trim($registro->mvcodele).' - Descripción: '.trim($registro->mvdesele).'<br>';
                            $i++;
                        }
                    }
                      
                    break;
                case 'ARTICLE': //PARAMETRO REFERENTE A LA TABLA ARTICULOS
                    $registros = $this->getQueriesStoragedInDB2ARTICLEAUTOMATIC();
                
                    if ($registros && is_array($registros)) {
                        echo "<br>Cantidad de Registros Nuevos: " . sizeof($registros);
                
                        $i = 0;
                        foreach ($registros as $registro) {
                            //Guardando en Tabla as_sync de BD en POSTGRESQL
                            $vector_as_sync_bd_intermedia = [
                                'sytabla' => trim($registro->sytabla),
                                'sql' => utf8_encode(trim($registro->sycadsql)),
                                'usuario' => trim($registro->syusuari),
                                'fecha_generado' => trim($registro->syfechac),
                                'hora_generado' => trim($registro->syhorac),
                                'tipo_operacion' => trim($registro->sytpoper),
                                'created_at' => date("Y-m-d H:i:s"),
                            ];
                            AsSyncVim::create($vector_as_sync_bd_intermedia);
                            
                            $sql = trim(str_replace(';', '', $registro->sycadsql));
                            $registro->datos_consulta = $this->consulta_tabla_db2($sql);

                            if ($registro->datos_consulta) {
                                echo '<br>Registro #: '.$i.' - TABLA: '.trim($registro->sytabla).' - ACCIÓN: '.trim($registro->sytpoper).'<br>';
                                //Guardando en DB:POSTGRESQL, tabla "articulos"
                                ActionsVimController::mmacrep_maestro_articulos_vim_automatico($registro);
                            } else {
                                echo '<br>La siguiente consulta SQL: '.trim($registro->sycadsql).' ¡NO! produjo resultados provenientes del DB2<br>';
                            }
                            
                            $i++;
                        }
                    }
                          
                    break;
                case 'ARTICLEDETAIL': //PARAMETRO REFERENTE A LA TABLA ARTICULO DETALLES
                    $registros = $this->getQueriesStoragedInDB2ARTICLEDETAILAUTOMATIC();
                    
                    if ($registros && is_array($registros)) {
                        echo "<br>Cantidad de Registros Nuevos: " . sizeof($registros);
                    
                        $i = 0;
                        foreach ($registros as $registro) {
                            //Guardando en Tabla as_sync de BD en POSTGRESQL
                            $vector_as_sync_bd_intermedia = [
                                'sytabla' => trim($registro->sytabla),
                                'sql' => utf8_encode(trim($registro->sycadsql)),
                                'usuario' => trim($registro->syusuari),
                                'fecha_generado' => trim($registro->syfechac),
                                'hora_generado' => trim($registro->syhorac),
                                'tipo_operacion' => trim($registro->sytpoper),
                                'created_at' => date("Y-m-d H:i:s"),
                            ];
                            AsSyncVim::create($vector_as_sync_bd_intermedia);
                            
                            $sql = trim(str_replace(';', '', $registro->sycadsql));
                            $registro->datos_consulta = $this->consulta_tabla_db2($sql);

                            if ($registro->datos_consulta) {
                                echo '<br>Registro #: '.$i.' - TABLA: '.trim($registro->sytabla).' - ACCIÓN: '.trim($registro->sytpoper).'<br>';
                                //Guardando en DB:POSTGRESQL, tabla "articulo_detalles"
                                ActionsVimController::mmetrep_maestro_articulo_detalles_vim_automatico($registro);
                            } else {
                                echo '<br>La siguiente consulta SQL: '.trim($registro->sycadsql).' ¡NO! produjo resultados provenientes del DB2<br>';
                            }
                            
                            $i++;
                        }
                    }
    
                    break;
                case 'LINE': //PARAMETRO REFERENTE A LA TABLA LINEAS
                    $registros = $this->getQueriesStoragedInDB2LINEAUTOMATIC();
                        
                    if ($registros && is_array($registros)) {
                        echo "<br>Cantidad de Registros Nuevos: " . sizeof($registros);
                        
                        $i = 0;
                        foreach ($registros as $registro) {
                            //Guardando en DB:POSTGRESQL, tabla "lineas"
                            ActionsVimController::mmeurel0_maestro_lineas_vim_automatico($registro);
                        
                            echo '<br>Registro #: '.$i.' - Código: '.trim($registro->eucodele).' - Descripción: '.trim($registro->eudsclar).'<br>';
                            $i++;
                        }
                    }
    
                    break;
                case 'BRAND': //PARAMETRO REFERENTE A LA TABLA MARCAS
                    $registros = $this->getQueriesStoragedInDB2BRANDAUTOMATIC();
    
                    if ($registros && is_array($registros)) {
                        echo "<br>Cantidad de Registros Nuevos: " . sizeof($registros);
    
                        $i = 0;
                        foreach ($registros as $registro) {
                            //Guardando en DB:POSTGRESQL, tabla "marcas"
                            ActionsVimController::mmeyrel0_maestro_marcas_vim_automatico($registro);
                            
                            echo '<br>Registro #: '.$i.' - Código: '.trim($registro->eycodmar).' - Descripción: '.trim($registro->eydsclar).'<br>';
                            $i++;
                        }
                    }
    
                    break;
                case 'MODEL': //PARAMETRO REFERENTE A LA TABLA MODELOS
                    $registros = $this->getQueriesStoragedInDB2MODELAUTOMATIC();
        
                    if ($registros && is_array($registros)) {
                        echo "<br>Cantidad de Registros Nuevos: " . sizeof($registros);
        
                        $i = 0;
                        foreach ($registros as $registro) {
                            //Guardando en DB:POSTGRESQL, tabla "modelos"
                            ActionsVimController::mmocrel0_maestro_modelos_vim_automatico($registro);
                                
                            echo '<br>Registro #: '.$i.' - Código: '.trim($registro->occodmod).' - Código Artículo: '.trim($registro->occodart).'<br>';
                            $i++;
                        }
                    }
        
                    break;
                case 'MODELDETAIL': //PARAMETRO REFERENTE A LA TABLA MODELO DETALLES
                    $registros = $this->getQueriesStoragedInDB2MODELDETAILAUTOMATIC();
                
                    if ($registros && is_array($registros)) {
                        echo "<br>Cantidad de Registros Nuevos: " . sizeof($registros);
                
                        $i = 0;
                        foreach ($registros as $registro) {
                            //Guardando en DB:POSTGRESQL, tabla "modelo_detalles"
                            ActionsVimController::mmobrel0_maestro_modelo_detalles_vim_automatico($registro);
                                        
                            echo '<br>Registro #: '.$i.' - Código: '.trim($registro->obcodmod).' - Motor: '.trim($registro->obmotor).'<br>';
                            $i++;
                        }
                    }
                
                    break;
                default:
                    //$str_tablas = $request->param;
                    #code
                    break;
            }
            /////////////////////////////////////// AUTOMATICO ///////////////////////////////////////
        }else{
            #code...
        }

    }

    //FUNCIONES INICIALES PARA OBTENER DATA DEL DB2
    public function getQueriesStoragedInDB2CLIENTMIGRATION()
    {
        // USADO PARA LA MIGRACION INICIAL EN BLOQUES
        // $sql = "SELECT * FROM LIBPRDDAT.MMAKREP 
        //         LEFT JOIN LIBPRDDAT.MMIFREL0 ON IFCODCLI = AKCODCLI 
        //         LEFT JOIN LIBPRDDAT.MMEUREL0 ON EUCODELE = AKTIPEMP
        //         WHERE AKSTS = 'A' AND EUSTS = 'A' AND EUCODTBL = 'BB' AND AKCODCLI BETWEEN '091597' AND '109479' ORDER BY AKCODCLI ASC";

        $sql = "SELECT * FROM LIBPRDDAT.MMAKREP 
                LEFT JOIN LIBPRDDAT.MMIFREL0 ON IFCODCLI = AKCODCLI 
                LEFT JOIN LIBPRDDAT.MMEUREL0 ON EUCODELE = AKTIPEMP
                WHERE AKSTS = 'A' AND EUSTS = 'A' AND EUCODTBL = 'BB' ORDER BY AKCODCLI ASC";

        $registros = DB::connection('ibmi')->select(DB::raw($sql));
        return $registros;
    }

    public function getQueriesStoragedInDB2COUNTRYMIGRATION()
    {
        $sql = "SELECT * FROM LIBPRDDAT.MMFEREL0 ORDER BY FECODPAI ASC";
        //$sql = "SELECT * FROM LIBPRDDAT.MMFEREL0 WHERE FECODPAI <> '002' ORDER BY FECODPAI ASC LIMIT 10";
        
        $registros = DB::connection('ibmi')->select(DB::raw($sql));
        return $registros;
    }

    public function getQueriesStoragedInDB2PROVIDERMIGRATION()
    {
        // $sql = "SELECT * FROM LIBPRDDAT.MMAHREP ORDER BY AHCODPRV ASC";
        $sql = "SELECT * FROM LIBPRDDAT.MMAHREP
                LEFT JOIN LIBPRDDAT.MMIPREL0 ON IPCODCLI = AHCODPRV
                WHERE AHSTS = 'A' ORDER BY AHCODPRV ASC";
        
        $registros = DB::connection('ibmi')->select(DB::raw($sql));
        return $registros;
    }

    public function getQueriesStoragedInDB2ORIGINMIGRATION()
    {
        $sql = "SELECT * FROM LIBPRDDAT.MMEUREL0 WHERE EUCODTBL = '11'";
        
        $registros = DB::connection('ibmi')->select(DB::raw($sql));
        return $registros;
    }

    public function getQueriesStoragedInDB2TYPEMIGRATION()
    {
        $sql = "SELECT * FROM LIBPRDDAT.COMODVEL0 WHERE MVTIPO='TV' ORDER BY MVCODELE ASC";
        
        $registros = DB::connection('ibmi')->select(DB::raw($sql));
        return $registros;
    }

    public function getQueriesStoragedInDB2ARTICLEMIGRATION()
    {
        // $sql = "SELECT * FROM LIBPRDDAT.MMACREP WHERE ACJDT BETWEEN 19990101 AND 20000101 ORDER BY ACJDT ASC OPTIMIZE FOR 1000 ROWS";
        $sql = "SELECT * FROM LIBPRDDAT.MMACREP ORDER BY ACJDT ASC LIMIT 25";
        
        $registros = DB::connection('ibmi')->select(DB::raw($sql));
        return $registros;
    }

    public function getQueriesStoragedInDB2ARTICLEDETAILMIGRATION()
    {
        $sql = "SELECT * FROM LIBPRDDAT.MMETREP ORDER BY ETCODSUC ASC LIMIT 25";
        
        $registros = DB::connection('ibmi')->select(DB::raw($sql));
        return $registros;
    }

    public function getQueriesStoragedInDB2LINEMIGRATION()
    {
        $sql = "SELECT * FROM LIBPRDDAT.MMEUREL0 WHERE EUCODTBL = '12'";
        
        $registros = DB::connection('ibmi')->select(DB::raw($sql));
        return $registros;
    }

    public function getQueriesStoragedInDB2BRANDMIGRATION()
    {
        $sql = "SELECT * FROM LIBPRDDAT.MMEYREL0 ORDER BY EYCODMAR ASC";
        
        $registros = DB::connection('ibmi')->select(DB::raw($sql));
        return $registros;
    }

    public function getQueriesStoragedInDB2MODELMIGRATION()
    {
        $sql = "SELECT * FROM LIBPRDDAT.MMOCREL0 ORDER BY OCCODCIA ASC";
        
        $registros = DB::connection('ibmi')->select(DB::raw($sql));
        return $registros;
    }

    public function getQueriesStoragedInDB2MODELDETAILMIGRATION()
    {
        $sql = "SELECT * FROM LIBPRDDAT.MMOBREL0 ORDER BY OBCODLIN ASC";
        
        $registros = DB::connection('ibmi')->select(DB::raw($sql));
        return $registros;
    }

    //FUNCIONES AUTOMATICAS PARA OBTENER DATA DEL DB2
    public function getQueriesStoragedInDB2CLIENTAUTOMATIC()
    {
        //$fecha_hoy = date('Ymd');

        $dia = date("d");
        $mes = date("m");
        $anio = date("Y");

        $fecha_hace_2_dias = date('Ymd', mktime(0,0,0,$mes,($dia-2),$anio));

        // $sql = "SELECT * FROM LIBPRDDAT.AS_SYNC WHERE SYTABLA = 'MMAKREP' AND SYFECHAC >= 20230101 ORDER BY SYFECHAC ASC, SYHORAC ASC LIMIT 10";
        $sql = "SELECT * FROM LIBPRDDAT.AS_SYNC WHERE SYTABLA = 'MMAKREP' AND SYFECHAC >= $fecha_hace_2_dias ORDER BY SYFECHAC ASC, SYHORAC ASC";

        $registros = DB::connection('ibmi')->select(DB::raw($sql));
        return $registros;
    }

    public function getQueriesStoragedInDB2COUNTRYAUTOMATIC()
    {
        $sql = "SELECT * FROM LIBPRDDAT.MMFEREL0 ORDER BY FECODPAI ASC";
        //$sql = "SELECT * FROM LIBPRDDAT.MMFEREL0 WHERE FECODPAI <> '002' ORDER BY FECODPAI ASC LIMIT 10";
        
        $registros = DB::connection('ibmi')->select(DB::raw($sql));
        return $registros;
    }

    public function getQueriesStoragedInDB2PROVIDERAUTOMATIC()
    {
        //$fecha_hoy = date('Ymd');

        $dia = date("d");
        $mes = date("m");
        $anio = date("Y");

        $fecha_hace_2_dias = date('Ymd', mktime(0,0,0,$mes,($dia-2),$anio));

        // $sql = "SELECT * FROM LIBPRDDAT.AS_SYNC WHERE SYTABLA = 'MMAHREP' AND SYFECHAC >= 20230101 ORDER BY SYFECHAC ASC, SYHORAC ASC";
        $sql = "SELECT * FROM LIBPRDDAT.AS_SYNC WHERE SYTABLA = 'MMAHREP' AND SYFECHAC >= $fecha_hace_2_dias ORDER BY SYFECHAC ASC, SYHORAC ASC";
        
        $registros = DB::connection('ibmi')->select(DB::raw($sql));
        return $registros;
    }

    public function getQueriesStoragedInDB2ORIGINAUTOMATIC()
    {
        $sql = "SELECT * FROM LIBPRDDAT.MMEUREL0 WHERE EUCODTBL = '11'";
        
        $registros = DB::connection('ibmi')->select(DB::raw($sql));
        return $registros;
    }

    public function getQueriesStoragedInDB2TYPEAUTOMATIC()
    {
        $sql = "SELECT * FROM LIBPRDDAT.COMODVEL0 WHERE MVTIPO='TV' ORDER BY MVCODELE ASC";
        
        $registros = DB::connection('ibmi')->select(DB::raw($sql));
        return $registros;
    }

    public function getQueriesStoragedInDB2ARTICLEAUTOMATIC()
    {
        //$fecha_hoy = date('Ymd');

        $dia = date("d");
        $mes = date("m");
        $anio = date("Y");

        $fecha_hace_2_dias = date('Ymd', mktime(0,0,0,$mes,($dia-2),$anio));

        // $sql = "SELECT * FROM LIBPRDDAT.AS_SYNC WHERE SYTABLA = 'MMACREP' AND SYFECHAC >= 20230101 ORDER BY SYFECHAC ASC, SYHORAC ASC";
        $sql = "SELECT * FROM LIBPRDDAT.AS_SYNC WHERE SYTABLA = 'MMACREP' AND SYFECHAC >= $fecha_hace_2_dias ORDER BY SYFECHAC ASC, SYHORAC ASC";
        
        $registros = DB::connection('ibmi')->select(DB::raw($sql));
        return $registros;
    }

    public function getQueriesStoragedInDB2ARTICLEDETAILAUTOMATIC()
    {
        //$fecha_hoy = date('Ymd');

        $dia = date("d");
        $mes = date("m");
        $anio = date("Y");

        $fecha_hace_2_dias = date('Ymd', mktime(0,0,0,$mes,($dia-2),$anio));

        // $sql = "SELECT * FROM LIBPRDDAT.AS_SYNC WHERE SYTABLA = 'MMETREP' AND SYFECHAC >= 20230101 ORDER BY SYFECHAC ASC, SYHORAC ASC";
        $sql = "SELECT * FROM LIBPRDDAT.AS_SYNC WHERE SYTABLA = 'MMETREP' AND SYFECHAC >= $fecha_hace_2_dias ORDER BY SYFECHAC ASC, SYHORAC ASC";
        
        $registros = DB::connection('ibmi')->select(DB::raw($sql));
        return $registros;
    }

    public function getQueriesStoragedInDB2LINEAUTOMATIC()
    {
        $sql = "SELECT * FROM LIBPRDDAT.MMEUREL0 WHERE EUCODTBL = '12'";
        
        $registros = DB::connection('ibmi')->select(DB::raw($sql));
        return $registros;
    }

    public function getQueriesStoragedInDB2BRANDAUTOMATIC()
    {
        $sql = "SELECT * FROM LIBPRDDAT.MMEYREL0 ORDER BY EYCODMAR ASC";
        
        $registros = DB::connection('ibmi')->select(DB::raw($sql));
        return $registros;
    }

    public function getQueriesStoragedInDB2MODELAUTOMATIC()
    {
        $sql = "SELECT * FROM LIBPRDDAT.MMOCREL0 ORDER BY OCCODCIA ASC";
        
        $registros = DB::connection('ibmi')->select(DB::raw($sql));
        return $registros;
    }

    public function getQueriesStoragedInDB2MODELDETAILAUTOMATIC()
    {
        $sql = "SELECT * FROM LIBPRDDAT.MMOBREL0 ORDER BY OBCODLIN ASC";
        
        $registros = DB::connection('ibmi')->select(DB::raw($sql));
        return $registros;
    }

    public function consulta_tabla_db2($sql)
    {
        $registros = DB::connection('ibmi')->select(DB::raw($sql));
        if (is_array($registros) && sizeof($registros) > 0) 
            return $registros[0];
        else 
            return false;
    }

    //AGREGADO SOLO PARA PRUEBAS
    public function insertarDatos(Request $request)
    {
        $valordata = AsSyncVim::create($request->all());
        return response($valordata,200);
    }
    //FIN FUNCIONES DE PRUEBA
}
