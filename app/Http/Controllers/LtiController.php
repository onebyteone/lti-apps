<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class LtiController extends Controller
{
    /**
     * LTI 1.1 Launch - Recibe el POST desde Canvas
     */
    public function launch(Request $request)
    {
        // Log para debugging
        \Log::info('LTI Launch Request', [
            'method' => $request->method(),
            'url' => $request->url(),
            'consumer_key' => $request->input('oauth_consumer_key'),
            'has_signature' => $request->has('oauth_signature'),
        ]);

        // Validar OAuth 1.0 signature
        if (!$this->validateOAuthSignature($request)) {
            \Log::error('OAuth validation failed');
            return response('Invalid OAuth signature', 403);
        }

        // Extraer información del launch de LTI 1.1
        $userId = $request->input('user_id');
        $userName = $request->input('lis_person_name_full', 'Usuario');
        $userEmail = $request->input('lis_person_contact_email_primary');
        $courseId = $request->input('custom_canvas_course_id') ?? $request->input('context_id');
        $roles = $request->input('roles', '');
        
        // Determinar si es instructor
        $isInstructor = str_contains($roles, 'Instructor') || str_contains($roles, 'Teacher');

        // Guardar en sesión para uso posterior
        session([
            'lti_user_id' => $userId,
            'lti_course_id' => $courseId,
            'lti_user_name' => $userName,
            'lti_user_email' => $userEmail,
            'lti_roles' => $roles,
        ]);

        \Log::info('LTI Launch successful', ['user' => $userId, 'course' => $courseId]);

        // Renderizar interfaz del chatbot con Inertia
        return Inertia::render('Chatbot', [
            'user' => [
                'id' => $userId,
                'name' => $userName,
                'email' => $userEmail,
                'roles' => explode(',', $roles),
                'isInstructor' => $isInstructor,
            ],
            'course' => [
                'id' => $courseId,
            ],
        ]);
    }

    /**
     * Validar OAuth 1.0 signature (LTI 1.1)
     */
    private function validateOAuthSignature(Request $request): bool
    {
        $consumerKey = config('lti.consumer_key');
        $sharedSecret = config('lti.shared_secret');

        \Log::info('OAuth Validation Config', [
            'consumer_key' => $consumerKey,
            'has_secret' => !empty($sharedSecret),
        ]);

        // Verificar que tenemos consumer key
        $requestConsumerKey = $request->input('oauth_consumer_key');
        if ($requestConsumerKey !== $consumerKey) {
            \Log::error('Invalid consumer key', [
                'expected' => $consumerKey,
                'received' => $requestConsumerKey
            ]);
            return false;
        }

        // Obtener la firma recibida
        $receivedSignature = $request->input('oauth_signature');
        if (!$receivedSignature) {
            \Log::error('No OAuth signature provided');
            return false;
        }

        // Obtener signature method (debe ser HMAC-SHA1)
        $signatureMethod = $request->input('oauth_signature_method', 'HMAC-SHA1');
        if ($signatureMethod !== 'HMAC-SHA1') {
            \Log::error('Unsupported signature method', ['method' => $signatureMethod]);
            return false;
        }

        // Construir la base string para OAuth 1.0
        $method = $request->method();
        
        // Usar la URL correcta - Cloud Run puede terminar SSL
        // Necesitamos usar https:// si estamos detrás de un proxy
        $url = $request->url();
        if ($request->header('X-Forwarded-Proto') === 'https') {
            $url = str_replace('http://', 'https://', $url);
        }
        
        // Obtener todos los parámetros excepto oauth_signature
        $params = $request->all();
        unset($params['oauth_signature']);
        
        // Ordenar parámetros alfabéticamente
        ksort($params);
        
        // Construir query string normalizado
        $pairs = [];
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                sort($value);
                foreach ($value as $v) {
                    $pairs[] = rawurlencode($key) . '=' . rawurlencode($v);
                }
            } else {
                $pairs[] = rawurlencode($key) . '=' . rawurlencode($value);
            }
        }
        sort($pairs);
        $paramString = implode('&', $pairs);
        
        // Construir base string
        $baseString = implode('&', [
            strtoupper($method),
            rawurlencode($url),
            rawurlencode($paramString),
        ]);

        // Calcular signature
        $key = rawurlencode($sharedSecret) . '&';
        $signature = base64_encode(hash_hmac('sha1', $baseString, $key, true));

        // Log para debugging
        \Log::info('OAuth Signature Calculation', [
            'method' => $method,
            'url' => $url,
            'url_original' => $request->url(),
            'x_forwarded_proto' => $request->header('X-Forwarded-Proto'),
            'calculated_signature' => $signature,
            'received_signature' => $receivedSignature,
            'match' => hash_equals($signature, $receivedSignature),
        ]);

        // Comparar signatures
        return hash_equals($signature, $receivedSignature);
    }

    /**
     * Endpoint de configuración XML para LTI 1.1
     */
    public function config(Request $request)
    {
        $baseUrl = config('app.url');
        
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<cartridge_basiclti_link xmlns="http://www.imsglobal.org/xsd/imslticc_v1p0"
    xmlns:blti="http://www.imsglobal.org/xsd/imsbasiclti_v1p0"
    xmlns:lticm="http://www.imsglobal.org/xsd/imslticm_v1p0"
    xmlns:lticp="http://www.imsglobal.org/xsd/imslticp_v1p0"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://www.imsglobal.org/xsd/imslticc_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticc_v1p0.xsd
    http://www.imsglobal.org/xsd/imsbasiclti_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imsbasiclti_v1p0.xsd
    http://www.imsglobal.org/xsd/imslticm_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticm_v1p0.xsd
    http://www.imsglobal.org/xsd/imslticp_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticp_v1p0.xsd">
    <blti:title>AI Chatbot</blti:title>
    <blti:description>Chatbot con IA para consultas del curso</blti:description>
    <blti:launch_url>{$baseUrl}/lti/launch</blti:launch_url>
    <blti:extensions platform="canvas.instructure.com">
        <lticm:property name="privacy_level">public</lticm:property>
        <lticm:options name="course_navigation">
            <lticm:property name="enabled">true</lticm:property>
            <lticm:property name="text">AI Chatbot</lticm:property>
            <lticm:property name="visibility">admins</lticm:property>
        </lticm:options>
        <lticm:property name="custom_canvas_user_id">\$Canvas.user.id</lticm:property>
        <lticm:property name="custom_canvas_course_id">\$Canvas.course.id</lticm:property>
    </blti:extensions>
</cartridge_basiclti_link>
XML;

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
