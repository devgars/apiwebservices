<?php

namespace App\Http\Controllers\PostVenta;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;   
use App\Models\User;
use Illuminate\Support\Facades\Validator;

use App\Models\UserAs400;
use App\Models\MMTrack\DriverAssistant;
use App\Models\MMTrack\Drivers;
use App\Models\UserSystem;
use App\Models\UserUser;
use Auth;
use Carbon\Carbon;
class UsersController extends Controller
{
    public function index(Request $request){   
        $inp=$request->input();
        $data =DB::table('users')
        ->select("*")
        ->limit(1)
        /*->skip(0)
        ->take(1)
        */
        //->where('id', $request->id)
        ->get();
        $user =  User::all();
        return json_encode($data);    
    }

    public function byIdRoles(Request $request)
    {
        $data =DB::table('users as t1')
        ->join('user_users as t2','t1.id','t2.user_id')
        ->select(
            "t1.id",
            "t1.name",
        )
        ->where('t2.code', $request->id)
        ->get();
        return response()->json($data, 200); 
    }
    public function byIdRolesPoventaTracking(Request $request)
    {
        $data =DB::table('poventa_request as t1')
        ->join('users as t2','t1.id_responsable','t2.id')
        ->select(
            "t2.id",
            "t2.name",
            DB::raw("REPLACE(lower(t2.name), ' ', '-') as name_prefix")
        )
        ->distinct()
        ->get();
        return response()->json($data, 200); 
    }

    public function Login(Request $request)
    {   $array = [];
        $data =DB::table('users')
        ->select(
            "id",
            "name as first_name",
            "name as last_name",
            "email as email",
            "email_verified_at",
            "created_at",
            "updated_at",
            DB::raw("concat('sdfsdfsd34sg456trtgfxdg') as api_token"),
            DB::raw("concat('edwin') as nombremym"),
        )
        ->where('email', $request->email)
        //->where('password', $request->password)
        ->first();
        return response()->json($data, 200); 
    }
    public function loginMym(Request $request){
        $validacion = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',  
        ]);
        if ($validacion->fails()) {
            return response()->json(["estado"=>false, "data"=>count($validacion->errors()),'mensaje'=>'Ingrese usuario y contraseña'], 200);
        }
        $credentials = [
            'email'  =>  $request->email,
            'password'  =>  $request->password,
            'status' => 1
        ];
        if (!$token = auth()->attempt($credentials)) {
            //return response()->json(['mensaje' => 'Error en acceso'], 200);
            return response()->json(["estado"=>false, "data"=>'Error de acceso', 'mensaje'=>'Error de acceso'], 200);
        }else{
            //return $this->getTokenMym($token); 
            $fecha_actual =  Carbon::now();
            $usuario_sistema = UserSystem::where('user_id',Auth::user()->id)->first();
            $usuario_AS400 = UserAs400::where('user_id',Auth::user()->id)->first();
            $user_user = UserUser::where('user_id',Auth::user()->id)->first();
            return response()->json([
                'estado'=>true,
                'status'=>1,
                'api_token' => 'lxfKDFJNGKDFGJNDKFGJDEIUKJD7474KJF',
                'token_type' => 'bearer',            
                'id' => Auth::user()->id,
                'first_name' => Auth::user()->name,
                'last_name' => $user_user->last_name,
                'email' => Auth::user()->email,
                'password' => 'CARSSDES',
                'created_at'=>'',
                'updated_at' => Auth::user()->updated_at,
                'codigo_as400' => $usuario_AS400 ? $usuario_AS400->code : '',
                'expires_in' => auth()->factory()->getTTL() * 60,
                'expires_date' => $fecha_actual->addSeconds(auth()->factory()->getTTL() * 60)->format('Y-m-d H:i:s'),
                'permisos' => $usuario_sistema->getAllPermissions(),
                'roles' => $usuario_sistema->roles,
            ], 200);
        }
    }
    public function loginMymValidate(Request $request){
        $validacion = Validator::make($request->all(), [
            'id' => 'required',
        ]);
        if ($validacion->fails()) {
            return response()->json(["estado"=>false, "data"=>count($validacion->errors()),'mensaje'=>'Ingrese usuario y contraseña'], 200);
        }
        
            /*$fecha_actual =  Carbon::now();
            $usuario_sistema = UserSystem::where('user_id',Auth::user()->id )->first();
            $usuario_AS400 = UserAs400::where('user_id',Auth::user()->id)->first();
            $user_user = UserUser::where('user_id',Auth::user()->id)->first();
            return response()->json([
                'estado'=>true,
                'api_token' => $token,
                'token_type' => 'bearer',            
                'id' => Auth::user()->id,
                'first_name' => Auth::user()->name,
                'last_name' => $user_user->last_name,
                'email' => Auth::user()->email,
                'password' => 'CARLOS',
                'created_at'=>'',
                'updated_at' => Auth::user()->updated_at,
                'codigo_as400' => $usuario_AS400 ? $usuario_AS400->code : '',
                'expires_in' => auth()->factory()->getTTL() * 60,
                'expires_date' => $fecha_actual->addSeconds(auth()->factory()->getTTL() * 60)->format('Y-m-d H:i:s'),
                'permisos' => $usuario_sistema->getAllPermissions(),
                'roles' => $usuario_sistema->roles 
            ], 200);
            */
            $users =DB::table('users')
            ->select(
                DB::raw("true as estado"),
                DB::raw("'sdfkjs' as api_token"),
                DB::raw("'bearer' as token_type"),
                "id",
                "name as first_name",
                "name as last_name",
                "name as first_name",
                "name as last_name",
                "email",
                DB::raw("'CARLOS' as password"),
                "created_at",
                "updated_at",
                DB::raw("'0999' as codigo_as400"),
                DB::raw("'' as expires_in"),
                DB::raw("'' as expires_date"),
                DB::raw("'' as permisos"),
                DB::raw("'' as roles")
            )
            ->where('id', $request->id)
            ->first();
            return response()->json($users, 200);
    }
    
    public function verifyToken(Request $request)
    {   $array=[];
        $data =DB::table('users')
        ->select(
            DB::raw("true as estado"),
            DB::raw("'sdfkjs' as api_token"),
            DB::raw("'bearer' as token_type"),
            "id",
            "name as first_name",
            "name as last_name",
            "name as first_name",
            "name as last_name",
            "email",
            "password",
            "created_at",
            "updated_at",
            DB::raw("'0999' as codigo_as400"),
            DB::raw("'' as expires_in"),
            DB::raw("'' as expires_date"),
            DB::raw("'' as permisos"),
            DB::raw("'' as roles"),
        )
        ->where('email', 'fchinchaya@mym.com.pe')
        ->first();
        /*if(count($data)>0){
            $array['id'] = $data[0]->id;
            $array['contador'] = count($data);
        }
        */
        return response()->json($data, 200);
    }
    public function verifyCompanyByUser(Request $request)
    {
        $data =DB::table('v_users_by_companies')
        ->select(
            "email",
            "company_id",
            'subsidiary_name'
        )
        ->where('email', $request->email)
        ->where('user_code', $request->password)
        ->get();
        return response()->json($data, 200);
    }
    
    public function userPermission(Request $request)
    {
        $data =DB::table('poventa_users_permission')
        ->select(
            "user_id",
            "system_id"
        )
        ->where('user_id', $request->id_user)
        ->where('status', 1)
        ->get();
        return response()->json($data, 200);
    }
}
