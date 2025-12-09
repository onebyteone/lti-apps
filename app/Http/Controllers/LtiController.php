<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;

class LtiController extends Controller
{
    /**
     * Configuración JSON para Dynamic Registration
     */
    public function config(Request $request)
    {
        $baseUrl = config('app.url');
        
        return response()->json([
            'title' => 'AI Chatbot',
            'description' => 'Chatbot con IA para consultas del curso',
            'oidc_initiation_url' => "{$baseUrl}/lti/login",
            'target_link_uri' => "{$baseUrl}/lti/launch",
            'scopes' => [
                'https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly',
            ],
            'extensions' => [[
                'platform' => 'canvas.instructure.com',
                'settings' => [
                    'platform' => 'canvas.instructure.com',
                    'placements' => [[
                        'placement' => 'course_navigation',
                        'message_type' => 'LtiResourceLinkRequest',
                        'target_link_uri' => "{$baseUrl}/lti/launch",
                        'text' => 'AI Chatbot',
                        'icon_url' => "{$baseUrl}/icon.png",
                    ]],
                ],
            ]],
            'public_jwk_url' => "{$baseUrl}/.well-known/jwks.json",
            'custom_fields' => [
                'canvas_user_id' => '$Canvas.user.id',
                'canvas_course_id' => '$Canvas.course.id',
            ],
        ]);
    }

    /**
     * JWKS endpoint - Expone la clave pública
     */
    public function jwks(Request $request)
    {
        $publicKey = file_get_contents(storage_path('lti-public.key'));
        
        // Convertir PEM a formato JWK
        $publicKeyDetails = openssl_pkey_get_details(openssl_pkey_get_public($publicKey));
        
        $jwk = [
            'kty' => 'RSA',
            'alg' => 'RS256',
            'use' => 'sig',
            'n' => rtrim(str_replace(['+', '/'], ['-', '_'], base64_encode($publicKeyDetails['rsa']['n'])), '='),
            'e' => rtrim(str_replace(['+', '/'], ['-', '_'], base64_encode($publicKeyDetails['rsa']['e'])), '='),
        ];

        return response()->json([
            'keys' => [$jwk],
        ]);
    }

    /**
     * OIDC Login - Paso 1 del LTI Launch
     */
    public function login(Request $request)
    {
        $issuer = $request->input('iss');
        $loginHint = $request->input('login_hint');
        $targetLinkUri = $request->input('target_link_uri');
        $ltiMessageHint = $request->input('lti_message_hint');

        // Generar state y nonce para seguridad
        $state = bin2hex(random_bytes(16));
        $nonce = bin2hex(random_bytes(16));

        // Guardar en sesión para validar después
        session([
            'lti_state' => $state,
            'lti_nonce' => $nonce,
        ]);

        // Construir URL de autorización de Canvas
        $authUrl = config('lti.auth_url');
        $clientId = config('lti.client_id');

        $params = http_build_query([
            'response_type' => 'id_token',
            'response_mode' => 'form_post',
            'scope' => 'openid',
            'client_id' => $clientId,
            'redirect_uri' => $targetLinkUri,
            'login_hint' => $loginHint,
            'state' => $state,
            'nonce' => $nonce,
            'prompt' => 'none',
            'lti_message_hint' => $ltiMessageHint,
        ]);

        return redirect("{$authUrl}?{$params}");
    }

    /**
     * LTI Launch - Paso 2, recibe el id_token
     */
    public function launch(Request $request)
    {
        $idToken = $request->input('id_token');
        $state = $request->input('state');

        // Validar state
        if ($state !== session('lti_state')) {
            return response('Invalid state', 403);
        }

        try {
            // Obtener JWKS de Canvas
            $jwksUrl = config('lti.jwks_url');
            $jwks = json_decode(file_get_contents($jwksUrl), true);
            
            // Decodificar y validar JWT
            $decoded = JWT::decode($idToken, JWK::parseKeySet($jwks));

            // Validar nonce
            if ($decoded->nonce !== session('lti_nonce')) {
                return response('Invalid nonce', 403);
            }

            // Extraer información del curso y usuario
            $courseId = $decoded->{'https://purl.imsglobal.org/spec/lti/claim/context'}->id ?? null;
            $userId = $decoded->sub;
            $userName = $decoded->name ?? 'Usuario';
            $userEmail = $decoded->email ?? null;
            $roles = $decoded->{'https://purl.imsglobal.org/spec/lti/claim/roles'} ?? [];

            // Limpiar sesión temporal
            session()->forget(['lti_state', 'lti_nonce']);

            // Renderizar interfaz del chatbot con Inertia
            return Inertia::render('Chatbot', [
                'user' => [
                    'id' => $userId,
                    'name' => $userName,
                    'email' => $userEmail,
                    'roles' => $roles,
                ],
                'course' => [
                    'id' => $courseId,
                ],
            ]);

        } catch (\Exception $e) {
            return response('Launch failed: ' . $e->getMessage(), 500);
        }
    }
}
