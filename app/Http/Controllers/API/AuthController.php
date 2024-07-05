<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;


class AuthController extends Controller
{

    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:20',
            'email' => 'email|required|string|unique:users',
            'password' => 'required|string|min:8'
        ]);
        $validatedData['password'] = Hash::make($request->password);
        $user = User::create($validatedData);
        $accessToken = $user->createToken('authToken')->plainTextToken;
        return response(['type' => 'Bearer', 'access_token' => $accessToken], 201);
    }



    public function login(Request $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid login'
            ], 401);
        }

        $user = User::where('email', $request['email'])->firstOrFail();
        $accessToken = $user->createToken('authToken')->plainTextToken;

        return response(['type' => 'Bearer', 'access_token' => $accessToken], 201);
    }

    public function user_validate(Request $request)
    {
        //return $request->user();
        return 'El usuario ' . $request->user()->name . ' está conectado';
    }
}
