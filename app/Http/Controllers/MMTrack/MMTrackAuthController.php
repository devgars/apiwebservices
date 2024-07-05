<?php

namespace App\Http\Controllers\MMTrack;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserAs400;
use App\Models\MMTrack\DriverAssistant;
use App\Models\MMTrack\Drivers;
use App\Models\MMTrack\Vehicles;
use App\Models\UserSystem;
use Validator;
use Auth;
use Carbon\Carbon;
use DB;

class MMTrackAuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login','guardarUsuario','cambiarClave']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        //validando el datos necesarios
        $validacion = Validator::make($request->all(), [
            'correo' => 'required',
            'clave' => 'required',
             
        ]);

        
        //verificar si hay errores en la validacion
        if ($validacion->fails()) {
            return response()->json($validacion->errors(), 422);
        }


        $credentials = [
            'email'  =>  $request->correo,
            'password'  =>  $request->clave,
            'status' => 1
        ];
                
        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['mensaje' => 'Error en Credenciales','credentials' => $credentials], 401);
        }

        
        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        $fecha_actual =  Carbon::now();
        $usuario_sistema = UserSystem::where('user_id',Auth::user()->id)->first();
        $usuario_AS400 = UserAs400::where('user_id',Auth::user()->id)->first();
        $conductor = Drivers::with('vehiculo')->where('user_id',Auth::user()->id)->first();
        $ayudante = DriverAssistant::where('user_id',Auth::user()->id)->first();
        $vehiculo_id = null;
        $ayudante_id = null;

        //validar si el usuario es conductor
        if ($conductor) {
            # validar si tiene algun vehiculo asociado
            if ($conductor->vehiculo) {
                $vehiculo_id = $conductor->vehiculo->id;
            }
        }

        //validar si el usuario es ayudante
        if ($ayudante) {
            $ayudante_id = $ayudante->id;
        }

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'user' => Auth::user()->name,
            'expires_in' => auth()->factory()->getTTL() * 60,
            'expires_date' => $fecha_actual->addSeconds(auth()->factory()->getTTL() * 60)->format('Y-m-d H:i:s'),
            'permisos' => $usuario_sistema->getAllPermissions(),
            'roles' => $usuario_sistema->roles,
            'codigo_as400' => $usuario_AS400 ? $usuario_AS400->code : '',
            'vehiculo_id' => $vehiculo_id,
            'ayudante_id' => $ayudante_id,
            'usuario_id' => Auth::user()->id
        ]);
    }

    public function guardarUsuario(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'email'     =>  'required|email',
            'name'  =>  'required|string',
            'password'  =>  'required',
            'user_as400_id'  =>  'required',
            
        ]);

        if($validation->fails()){
            return response()->json($validation->errors(), 422);
        }

        DB::transaction(function () use($request) {

            //buscar existencia de usuario 
            $existeUsuario = User::where('email', $request->email)->first();
            $usuario = null;

            if ($existeUsuario) {
                $usuario = $existeUsuario;
            }else {
               //creamos usuario de laravel
                $usuario = User::create([
                    'name'  => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                ]);
            }
            
            
            if ($request->has('roles')) { 

                //relacionamos usuario de laravel con usuario de as400
                UserAs400::where('id',$request->user_as400_id)->update(["user_id" => $usuario->id]);
            
                //RELACIONAR CON USUARIOS DE SISTEMA MMTRACK
                $usuario_sistema = UserSystem::updateOrCreate(['user_id' => $usuario->id],
                                                                [   'company_id' => 1,
                                                                    'user_id' =>$usuario->id,
                                                                    'reg_status' => 1,
                                                                    'system_id' => 501
                                                                ]
                                                            );
            
                // Asignación del rol
                $usuario_sistema->syncRoles($request->roles);   
            
                //validar si existe rol conductor
                if (in_array("Conductor", $request->roles)) {
                    Drivers::updateOrCreate(['user_id' => $usuario->id],
                    [   
                        'user_id' =>$usuario->id,
                        'names' =>$request->name,
                        'status' => 1,
                    ]);
                }else{
                    //validar si el usuario alguna vez fue conductor e inactivarlo
                    $conductor = Drivers::where('user_id',$usuario->id)->where('status','1')->first();
                    
                    if ($conductor) {
                        $conductor->status = 0;
                        $conductor->save();
                        
                        Vehicles::where('driver_id',$conductor->id)->update(['driver_id'=> null]);
                    }
                    
                }

                return response()->json(['mensaje' => 'Usuario guardado con éxito'], 200);
                
            }else{

                //buscar existencia de usuario 
                $existeUsuario = User::where('email', $request->email)->first();

                if ($existeUsuario) {
                    //eliminar relacion de usuario de laravel con usuario de as400
                    User::where('id',$existeUsuario->id)->update(["status" => 0]);
                    $usuario_sistema = UserSystem::where('user_id',$existeUsuario->id)->first();
                    $usuario_sistema->reg_status =0;
                    $usuario_sistema->save();
                     
                    //quitar roles
                    $usuario_sistema->roles()->detach();
                    //relacionamos usuario de laravel con usuario de as400
                    UserAs400::where('id',$request->user_as400_id)->update(["user_id" => null]);

                    //buscar existencia de conductor
                    $editar_conductor = Drivers::where('user_id',$existeUsuario->id)->first();
                    
                    if($editar_conductor){
                        $editar_conductor->status="0";
                        $editar_conductor->save();
                    }
                }
                
            }
        });
    }
    
    public function cambiarClave(Request $request)
    {
        
        //buscar existencia de usuario 
        $existeUsuario = User::where('name', $request->nombre)->first();
        $existeUsuario->password = Hash::make($request->clave);
        $existeUsuario->save();

        return response()->json(['mensaje' => 'Cambio con éxito'], 200);
    }
}