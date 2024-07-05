<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use DB;


class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $arrayRolesYPermisos =  [
                            [ 
                                "rol" =>[
                                    "name"=> "Administrador",
                                    "guard_name" => "api",
                                    "updated_at" => date('Y-m-d H:i:s'),
                                    "created_at" => date('Y-m-d H:i:s')
                                ],
                                "permisos" => [
                                    [
                                        "name" => "mostrar-dashboard",
                                        "guard_name" => "api",
                                        "updated_at" =>  date('Y-m-d H:i:s'), 
                                        "created_at" =>  date('Y-m-d H:i:s'),     
                                    ],
                                    [
                                        "name" => "mostrar-panel-cliente",
                                        "guard_name" => "api",
                                        "updated_at" =>  date('Y-m-d H:i:s'), 
                                        "created_at" =>  date('Y-m-d H:i:s'),     
                                    ],
                                    [
                                        "name" => "mostrar-asignar-carga",
                                        "guard_name" => "api",
                                        "updated_at" =>  date('Y-m-d H:i:s'), 
                                        "created_at" =>  date('Y-m-d H:i:s'),     
                                    ],
                                    [
                                        "name" => "guardar-asignar-carga",
                                        "guard_name" => "api",
                                        "updated_at" =>  date('Y-m-d H:i:s'), 
                                        "created_at" =>  date('Y-m-d H:i:s'),     
                                    ],
                                    [
                                        "name" => "mostrar-entrega-pedido",
                                        "guard_name" => "api",
                                        "updated_at" =>  date('Y-m-d H:i:s'), 
                                        "created_at" =>  date('Y-m-d H:i:s'),     
                                    ],
                                    [
                                        "name" => "guardar-entrega-pedido",
                                        "guard_name" => "api",
                                        "updated_at" =>  date('Y-m-d H:i:s'), 
                                        "created_at" =>  date('Y-m-d H:i:s'),     
                                    ],
                                    [
                                        "name" => "mostrar-pedidos",
                                        "guard_name" => "api",
                                        "updated_at" =>  date('Y-m-d H:i:s'), 
                                        "created_at" =>  date('Y-m-d H:i:s'),     
                                    ],
                                    [
                                        "name" => "mostrar-pedidos-vehiculos",
                                        "guard_name" => "api",
                                        "updated_at" =>  date('Y-m-d H:i:s'), 
                                        "created_at" =>  date('Y-m-d H:i:s'),     
                                    ],
                                    [
                                        "name" => "mostrar-clientes",
                                        "guard_name" => "api",
                                        "updated_at" =>  date('Y-m-d H:i:s'), 
                                        "created_at" =>  date('Y-m-d H:i:s'),     
                                    ],
                                    [
                                        "name" => "mostrar-vehiculos",
                                        "guard_name" => "api",
                                        "updated_at" =>  date('Y-m-d H:i:s'), 
                                        "created_at" =>  date('Y-m-d H:i:s'),     
                                    ],
                                    [
                                        "name" => "editar-vehiculos",
                                        "guard_name" => "api",
                                        "updated_at" =>  date('Y-m-d H:i:s'), 
                                        "created_at" =>  date('Y-m-d H:i:s'),     
                                    ],
                                    [
                                        "name" => "eliminar-vehiculos",
                                        "guard_name" => "api",
                                        "updated_at" =>  date('Y-m-d H:i:s'), 
                                        "created_at" =>  date('Y-m-d H:i:s'),     
                                    ],
                                    [
                                        "name" => "crear-vehiculos",
                                        "guard_name" => "api",
                                        "updated_at" =>  date('Y-m-d H:i:s'), 
                                        "created_at" =>  date('Y-m-d H:i:s'),     
                                    ],
                                    [
                                        "name" => "mostrar-conductores",
                                        "guard_name" => "api",
                                        "updated_at" =>  date('Y-m-d H:i:s'), 
                                        "created_at" =>  date('Y-m-d H:i:s'),     
                                    ],
                                    [
                                        "name" => "editar-conductores",
                                        "guard_name" => "api",
                                        "updated_at" =>  date('Y-m-d H:i:s'), 
                                        "created_at" =>  date('Y-m-d H:i:s'),     
                                    ],
                                    [
                                        "name" => "eliminar-conductores",
                                        "guard_name" => "api",
                                        "updated_at" =>  date('Y-m-d H:i:s'), 
                                        "created_at" =>  date('Y-m-d H:i:s'),     
                                    ],
                                    [
                                        "name" => "crear-conductores",
                                        "guard_name" => "api",
                                        "updated_at" =>  date('Y-m-d H:i:s'), 
                                        "created_at" =>  date('Y-m-d H:i:s'),     
                                    ],
                                    [
                                        "name" => "mostrar-seguimiento",
                                        "guard_name" => "api",
                                        "updated_at" =>  date('Y-m-d H:i:s'), 
                                        "created_at" =>  date('Y-m-d H:i:s'),     
                                    ],
                                    [
                                        "name" => "mostrar-usuarios",
                                        "guard_name" => "api",
                                        "updated_at" =>  date('Y-m-d H:i:s'), 
                                        "created_at" =>  date('Y-m-d H:i:s'),     
                                    ],
                                    [
                                        "name" => "crear-usuarios",
                                        "guard_name" => "api",
                                        "updated_at" =>  date('Y-m-d H:i:s'), 
                                        "created_at" =>  date('Y-m-d H:i:s'),     
                                    ],
                                    [
                                        "name" => "eliminar-usuarios",
                                        "guard_name" => "api",
                                        "updated_at" =>  date('Y-m-d H:i:s'), 
                                        "created_at" =>  date('Y-m-d H:i:s'),     
                                    ],
                                    [
                                        "name" => "mostrar-configuracion-gps",
                                        "guard_name" => "api",
                                        "updated_at" =>  date('Y-m-d H:i:s'), 
                                        "created_at" =>  date('Y-m-d H:i:s'),     
                                    ],
                                    [
                                        "name" => "mostrar-configuracion-ws",
                                        "guard_name" => "api",
                                        "updated_at" =>  date('Y-m-d H:i:s'), 
                                        "created_at" =>  date('Y-m-d H:i:s'),     
                                    ],
                                ]
                            ],[
                                "rol" =>[
                                    "name"=> "Supervisor",
                                    "guard_name" => "api",
                                    "updated_at" => date('Y-m-d H:i:s'),
                                    "created_at" => date('Y-m-d H:i:s')
                                ],
                                "permisos" => [
                                    [
                                        "name" => "mostrar-asignar-carga",
                                        "guard_name" => "api",
                                        "updated_at" =>  date('Y-m-d H:i:s'), 
                                        "created_at" =>  date('Y-m-d H:i:s'),     
                                    ],
                                    [
                                        "name" => "guardar-asignar-carga",
                                        "guard_name" => "api",
                                        "updated_at" =>  date('Y-m-d H:i:s'), 
                                        "created_at" =>  date('Y-m-d H:i:s'),     
                                    ],
                                ]
                            ],[
                                "rol" =>[
                                    "name"=> "Vendedor",
                                    "guard_name" => "api",
                                    "updated_at" => date('Y-m-d H:i:s'),
                                    "created_at" => date('Y-m-d H:i:s')
                                ], 
                                "permisos" => [
                                    [
                                        "name" => "mostrar-pedidos",
                                        "guard_name" => "api",
                                        "updated_at" =>  date('Y-m-d H:i:s'), 
                                        "created_at" =>  date('Y-m-d H:i:s'),     
                                    ]
                                ]
                            ],[
                                "rol" =>[
                                    "name"=> "Conductor",
                                    "guard_name" => "api",
                                    "updated_at" => date('Y-m-d H:i:s'),
                                    "created_at" => date('Y-m-d H:i:s')
                                ],
                                "permisos" => [

                                    [
                                        "name" => "mostrar-entrega-pedido",
                                        "guard_name" => "api",
                                        "updated_at" =>  date('Y-m-d H:i:s'), 
                                        "created_at" =>  date('Y-m-d H:i:s'),     
                                    ],
                                    [
                                        "name" => "guardar-entrega-pedido",
                                        "guard_name" => "api",
                                        "updated_at" =>  date('Y-m-d H:i:s'), 
                                        "created_at" =>  date('Y-m-d H:i:s'),     
                                    ],
                                ]
                            ] ,[
                                "rol" =>[
                                    "name"=> "Ayudante",
                                    "guard_name" => "api",
                                    "updated_at" => date('Y-m-d H:i:s'),
                                    "created_at" => date('Y-m-d H:i:s')
                                ],
                                "permisos" => [

                                    [
                                        "name" => "mostrar-entrega-pedido",
                                        "guard_name" => "api",
                                        "updated_at" =>  date('Y-m-d H:i:s'), 
                                        "created_at" =>  date('Y-m-d H:i:s'),     
                                    ],
                                    [
                                        "name" => "guardar-entrega-pedido",
                                        "guard_name" => "api",
                                        "updated_at" =>  date('Y-m-d H:i:s'), 
                                        "created_at" =>  date('Y-m-d H:i:s'),     
                                    ],
                                ]
                            ] 
                        ];

        foreach ($arrayRolesYPermisos as $RolYPermisos) {

            //insertar rol en la bd
            $rol_insertado =Role::create($RolYPermisos["rol"]); 

            //iterar los permisos del rol
            foreach ($RolYPermisos["permisos"] as $permiso) {
                
                //consultar permiso en la bd 
                $consultar_permiso = Permission::where('name', $permiso["name"])->first();

                //validar si el permiso existe
                if ($consultar_permiso) {
                    //si existe lo relacionamos con el rol
                    $rol_insertado->givePermissionTo($consultar_permiso->name);
                }else {
                    //si no existe lo creamos y luego lo relacionamos 
                    $crear_permiso =Permission::create($permiso); 
                    $rol_insertado->givePermissionTo($crear_permiso->name);
                }
                
                
            }
        }
       
    }
}
