<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    /**
     * POST /api/auth/login
     * Recibe email + password, devuelve JWT con info del usuario.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        try {
            if (! $token = Auth::guard('api')->attempt($credentials)) {
                return response()->json([
                    'error' => 'invalid_credentials',
                    'message' => 'Email o contraseña incorrectos.',
                ], 401);
            }
        } catch (JWTException $e) {
            return response()->json([
                'error' => 'could_not_create_token',
                'message' => 'No se pudo emitir el token.',
            ], 500);
        }

        return $this->tokenResponse($token);
    }

    /**
     * GET /api/auth/me
     * Devuelve info del usuario autenticado (validando JWT).
     */
    public function me(): JsonResponse
    {
        $user = Auth::guard('api')->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'institution_id' => $user->institution_id,
            ],
        ]);
    }

    /**
     * POST /api/auth/logout
     * Invalida el token actual (lo agrega a blacklist).
     */
    public function logout(): JsonResponse
    {
        Auth::guard('api')->logout();

        return response()->json([
            'message' => 'Logout exitoso.',
        ]);
    }

    /**
     * POST /api/auth/refresh
     * Renueva el JWT antes de que expire.
     */
    public function refresh(): JsonResponse
    {
        return $this->tokenResponse(
            Auth::guard('api')->refresh()
        );
    }

    /**
     * Estructura de respuesta común para login y refresh.
     */
    protected function tokenResponse(string $token): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
            'user' => [
                'id' => Auth::guard('api')->user()->id,
                'name' => Auth::guard('api')->user()->name,
                'email' => Auth::guard('api')->user()->email,
                'role' => Auth::guard('api')->user()->role,
            ],
        ]);
    }
}
