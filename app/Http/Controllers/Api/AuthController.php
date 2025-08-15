<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function token(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'server_name' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return response()->json([
            'token' => $user->createToken($request->server_name)->plainTextToken
        ]);
    }

    public function logout(Request $request)
    {
        // Mevcut token'ı sil
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Token deleted successfully']);
    }

    public function logoutAll(Request $request)
    {
        // Kullanıcının tüm token'larını sil
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'All tokens deleted successfully']);
    }
}
