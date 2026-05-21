<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GatewayAuthController
 * --------------------------------------------------------------------------
 * Responsabilidad: ser la cara pública de la autenticación.
 *
 * - login: hace PROXY a Identity (Gateway no tiene users locales).
 * - me: VALIDA el JWT localmente (mismo JWT_SECRET que Identity) y devuelve
 *       los claims que vienen dentro del token. No llama a Identity.
 * - logout: hace PROXY a Identity para invalidar el token.
 *
 * Patrón arquitectónico: API Gateway + JWT con claims federados.
 * Esto es lo que permite que el Gateway escale sin depender de Identity en
 * cada request: los claims viajan dentro del token firmado.
 */
class GatewayAuthController extends Controller
{
    /**
     * POST /api/auth/login
     * Proxy a Identity. El cliente nunca habla directo con Identity.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $identityUrl = config('services.identity.url') . '/api/auth/login';

        try {
            $response = Http::timeout(5)
                ->acceptJson()
                ->post($identityUrl, $credentials);
        } catch (\Throwable $e) {
            Log::error('Gateway → Identity (login) failed', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => 'identity_unreachable',
                'message' => 'No se pudo contactar al servicio de identidad.',
            ], 503);
        }

        return response()->json($response->json(), $response->status());
    }

    /**
 * GET /api/auth/me
 * Validación 100% LOCAL del JWT. El Gateway decodifica el token con el
 * JWT_SECRET compartido y devuelve los claims SIN hablar con Identity
 * NI consultar ninguna base de datos. Pura validación de firma + claims.
 *
 * Decisión arquitectónica: JWT stateless. El Gateway no mantiene tabla de
 * usuarios. Si el usuario fue revocado, el blacklist lo gestiona Identity
 * a través de Redis compartido en una iteración futura.
 */
public function me(Request $request): JsonResponse
{
    $payload = $request->attributes->get('jwt_payload');

    return response()->json([
        'user' => [
            'id' => $payload->get('sub'),
            'email' => $payload->get('email'),
            'name' => $payload->get('name'),
            'role' => $payload->get('role'),
            'institution_id' => $payload->get('institution_id'),
        ],
        'verified_by' => 'api-gateway (local JWT validation, stateless)',
        'jwt_meta' => [
            'issued_at' => $payload->get('iat'),
            'expires_at' => $payload->get('exp'),
            'jti' => $payload->get('jti'),
        ],
    ]);
}

    /**
     * POST /api/auth/logout
     * Proxy a Identity (necesario para que Identity ponga el token en
     * blacklist; el Gateway no mantiene blacklist propia).
     */
    public function logout(Request $request): JsonResponse
{
    $token = $request->bearerToken();
    $identityUrl = config('services.identity.url') . '/api/auth/logout';

    try {
        $response = Http::timeout(5)
            ->acceptJson()
            ->withToken($token)
            ->post($identityUrl);
    } catch (\Throwable $e) {
        \Log::error('Gateway → Identity (logout) failed', ['error' => $e->getMessage()]);
        return response()->json([
            'error' => 'identity_unreachable',
            'message' => 'No se pudo cerrar sesión en el servicio de identidad.',
        ], 503);
    }

    return response()->json($response->json(), $response->status());
}
}
