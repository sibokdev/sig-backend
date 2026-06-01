<?php

namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $password = $request->input('password');

        // Try username first, then email — whichever field was sent
        $token = null;
        if ($request->filled('username')) {
            $token = auth()->attempt(['username' => $request->input('username'), 'password' => $password]);
        }
        if (!$token && $request->filled('email')) {
            $token = auth()->attempt(['email' => $request->input('email'), 'password' => $password]);
        }

        if (!$token) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json([
            'message'      => 'Login successful',
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => config('jwt.ttl') * 60,
            'user'         => auth()->user(),
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
