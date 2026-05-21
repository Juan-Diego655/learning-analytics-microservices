<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Middleware stateless de verificación de JWT para el API Gateway.
 *
 * A diferencia de 'auth:api' (que llama internamente a Auth::user() y
 * dispara una query a la tabla 'users' local), este middleware:
 *  - Decodifica el JWT en memoria
 *  - Valida firma con JWT_SECRET y verifica expiración
 *  - No toca ninguna DB
 *  - Inyecta el payload en la request para que los controllers lo usen
 *
 * Es lo que permite que el Gateway sea verdaderamente independiente de
 * la base de datos de usuarios (que vive en Identity).
 */
class JwtVerify
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $token = JWTAuth::parseToken();
            $payload = $token->getPayload();
        } catch (TokenExpiredException $e) {
            return response()->json([
                'error' => 'token_expired',
                'message' => 'El token ha expirado.',
            ], 401);
        } catch (TokenInvalidException $e) {
            return response()->json([
                'error' => 'token_invalid',
                'message' => 'El token es inválido.',
            ], 401);
        } catch (JWTException $e) {
            return response()->json([
                'error' => 'token_absent',
                'message' => 'Token no proporcionado.',
            ], 401);
        }

        // Inyectamos el payload en la request para que el controller lo lea
        $request->attributes->set('jwt_payload', $payload);

        return $next($request);
    }
}
