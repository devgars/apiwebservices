<?php

namespace App\Http\Controllers\MMTrack;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserAs400;
use Spatie\Permission\Models\Role;
use App\Models\User;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
            
        $usuarios = UserAs400::with('user.userSistem.roles')->where('reg_status',1)
                                ->where('first_name','ILIKE','%'.$request->busqueda.'%')
                                ->orWhere('second_name','ILIKE','%'.$request->busqueda.'%')
                                ->orWhere('last_name','ILIKE','%'.$request->busqueda.'%')
                                ->orWhere('mother_last_name','ILIKE','%'.$request->busqueda.'%')
                                ->orWhere('email', 'ILIKE', '%'.$request->busqueda.'%') 
                                ->orderBy('id','desc')
                                ->paginate(10);

        $roles = Role::select('name')->get();

        $response = [
            "mensaje" => "Consulta Exitosa",
            "usuarios" =>$usuarios,
            "roles" => $roles
        ];


        return response()->json($response, 200);

    }
}
