# Plan: Chatbot IA para Canvas LMS v1.0

## 1. Configuración LTI 1.3

### 1.1 Preparar Laravel para LTI
- [ ] Instalar dependencias: `composer require firebase/php-jwt web-token/jwt-framework`
- [ ] Generar llaves RSA (público/privado)
  ```bash
  openssl genrsa -out storage/lti-private.key 4096
  openssl rsa -in storage/lti-private.key -pubout -out storage/lti-public.key
  ```
- [ ] Crear endpoints LTI:
  - `GET /lti/config.json` - Configuración para Dynamic Registration
  - `GET /.well-known/jwks.json` - Clave pública JWK
  - `POST /lti/login` - OIDC Initiation
  - `POST /lti/launch` - LTI Resource Link Request
- [ ] Implementar validación JWT en Laravel

### 1.2 Dynamic Registration en Canvas
- [ ] Acceder a Canvas: **Admin → Developer Keys → + Developer Key → + LTI Key**
- [ ] Seleccionar **"Enter URL"**
- [ ] Ingresar: `https://tu-dominio.com/lti/config.json`
- [ ] Canvas creará automáticamente el Developer Key
- [ ] Guardar **Client ID** y **Deployment ID** en `.env`
- [ ] **Activar** el Developer Key (toggle ON)
- [ ] En el curso: **Settings → Apps → View App Configurations → + App**
- [ ] Buscar "AI Chatbot" y agregar al curso

## 2. Backend Laravel

### 2.1 Configuración JSON para Dynamic Registration
- [ ] Crear `/lti/config.json` con:
  ```json
  {
    "title": "AI Chatbot",
    "description": "Chatbot con IA para consultas del curso",
    "oidc_initiation_url": "https://tu-dominio.com/lti/login",
    "target_link_uri": "https://tu-dominio.com/lti/launch",
    "scopes": [
      "https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly"
    ],
    "extensions": [{
      "platform": "canvas.instructure.com",
      "settings": {
        "platform": "canvas.instructure.com",
        "placements": [{
          "placement": "course_navigation",
          "message_type": "LtiResourceLinkRequest",
          "target_link_uri": "https://tu-dominio.com/lti/launch",
          "text": "AI Chatbot",
          "icon_url": "https://tu-dominio.com/icon.png"
        }]
      }
    }],
    "public_jwk_url": "https://tu-dominio.com/.well-known/jwks.json"
  }
  ```

### 2.2 Controladores y Servicios
- [ ] `LtiController`
  - OIDC login handler
  - Launch request handler
  - Validación JWT y claims
  - Extracción de course_id y user_id
- [ ] `CanvasService`
  - Obtener módulos del curso
  - Obtener páginas del curso
  - Obtener asignaciones
  - Usar NRPS para roster
- [ ] `ChatbotService`
  - Integración con gpt-4o-mini
  - Context building desde contenido del curso
  - Manejo de conversaciones

## 3. Frontend React/Inertia
- [ ] Página del chatbot (`resources/js/pages/Chatbot.tsx`)
- [ ] Componente de chat UI
  - Input de usuario
  - Historial de mensajes
  - Indicador de typing
- [ ] Estado de conversación (React hooks)

## 4. Flujo de Datos
```
Canvas → LTI Launch → Laravel → Fetch Course Data (Canvas API) 
                              ↓
                    Build Context → OpenAI → Response → Frontend
```

## 5. Base de Datos
- [ ] Tabla `lti_sessions` (user_id, course_id, resource_link_id)
- [ ] Tabla `chat_conversations` (session_id, messages JSON)
- [ ] Migración y modelos

## 6. APIs a Utilizar
**Canvas API:**
- `/api/v1/courses/:id/modules`
- `/api/v1/courses/:id/pages`
- `/api/v1/courses/:id/assignments`
- `/api/lti/courses/:id/names_and_roles` (NRPS)

**OpenAI:**
- Chat Completions API (gpt-4o-mini)

## 7. Configuración .env
```
OPENAI_API_KEY=✓
OPENAI_MODEL=gpt-4o-mini ✓
CANVAS_API_URL=✓
CANVAS_API_TOKEN=✓

# LTI Configuration (obtenidos tras Dynamic Registration)
LTI_CLIENT_ID= (desde Canvas Developer Key)
LTI_DEPLOYMENT_ID= (desde Canvas Developer Key)
LTI_ISSUER=https://canvas.instructure.com
LTI_AUTH_URL=https://canvas.instructure.com/api/lti/authorize_redirect
LTI_JWKS_URL=https://canvas.instructure.com/api/lti/security/jwks
APP_URL=https://tu-dominio.com (debe ser HTTPS)
```

## 8. Pruebas
- [ ] Test de autenticación LTI
- [ ] Test de Canvas API integration
- [ ] Test de OpenAI responses
- [ ] Test end-to-end del chatbot

## 9. Deploy
- [ ] Configurar HTTPS (requerido para LTI)
- [ ] Registrar tool en Canvas
- [ ] Validar en curso de prueba
