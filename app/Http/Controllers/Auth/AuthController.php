<?php

namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = auth()->user();

        // Guardar el token en cookie HTTP-only
        return response()->json([
            'message' => 'Login successful',
            'user' => $user
        ])->cookie('token', $token, 60, '/', null, false, true);
    }

    public function me()
    {
        return response()->json(auth()->user());
    }

    public function logout()
    {
        auth()->logout();
        return response()->json(['message' => 'Logged out'])->cookie('token', '', -1);
    }
}
