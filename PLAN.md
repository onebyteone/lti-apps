# Plan: Chatbot IA para Canvas LMS v1.0 (LTI 1.1)

## Estado: ✅ VERSIÓN 1.0 COMPLETADA

## 1. Configuración LTI 1.1 ✅

### 1.1 Instalación en Canvas (Como Instructor)
**Opción A: Vía Interfaz Web**
1. Ir a tu curso → **Settings** → **Apps** → **+ App**
2. En "Configuration Type" seleccionar: **By URL** o **Paste XML**
3. Si usas **By URL**: `https://chatbot-test-lti-713107332501.us-central1.run.app/lti/config.xml`
4. Si usas **Manual Entry**:
   - **Name**: AI Chatbot
   - **Consumer Key**: `chatbot-key-2024`
   - **Shared Secret**: `supersecret123`
   - **Launch URL**: `https://chatbot-test-lti-713107332501.us-central1.run.app/lti/launch`
   - **Domain**: `chatbot-test-lti-713107332501.us-central1.run.app`
   - **Privacy**: Public
   - **Course Navigation**: ✓ Enabled

**Opción B: Vía API**
```bash
curl -X POST 'https://canvas.instructure.com/api/v1/courses/12564766/external_tools' \
  -H "Authorization: Bearer 7~cG9EBezKK8xLTHCUYezXkNNcKx9zJQ7w4WQfkhytJwPwzY7rTMC8QZCwWCGUwV7v" \
  -F 'name=AI Chatbot' \
  -F 'consumer_key=chatbot-key-2024' \
  -F 'shared_secret=supersecret123' \
  -F 'url=https://chatbot-test-lti-713107332501.us-central1.run.app/lti/launch' \
  -F 'privacy_level=public' \
  -F 'course_navigation[text]=AI Chatbot' \
  -F 'course_navigation[enabled]=true'
```

### 1.2 Backend Laravel ✅
- [x] Implementar validación OAuth 1.0 signature
- [x] Endpoint `/lti/launch` para recibir POST de Canvas
- [x] Endpoint `/lti/config.xml` con configuración XML
- [x] Extracción de user_id, course_id, roles
- [x] Configuración en `config/lti.php`
- [x] Fix OAuth signature con X-Forwarded-Proto (HTTPS)
- [x] Logging detallado para debugging

## 2. Backend Laravel ✅

### 2.1 Controladores y Rutas ✅
- [x] `LtiController` con OAuth 1.0 validation
- [x] `ChatController` con endpoint `/api/chat`
- [x] Rutas LTI simplificadas
- [x] Manejo de sesiones con cookies
- [x] CSRF exception para `/api/*`

### 2.2 Servicios ✅
- [x] `CanvasService`
  - Obtener módulos del curso
  - Obtener páginas del curso
  - Obtener asignaciones
  - Construcción de contexto del curso
- [x] `ChatbotService`
  - Integración con gpt-4o-mini
  - Context building desde contenido del curso
  - Manejo de historial de conversación (últimos 5 mensajes)
  - Llamada a OpenAI Chat Completions API

### 2.3 Configuración de Servicios ✅
- [x] `config/services.php` con Canvas y OpenAI
- [x] Logging detallado en ambos servicios

## 3. Frontend React/Inertia ✅
- [x] Página del chatbot (`resources/js/pages/Chatbot.tsx`)
- [x] Componente de chat UI
  - Input de usuario
  - Historial de mensajes
  - Indicador de typing (loading dots)
  - Envío de conversación histórica al backend
- [x] Estado de conversación (React useState)
- [x] Badge de rol (Instructor)
- [x] Diseño responsive con Tailwind
- [x] Manejo de errores

## 4. Flujo de Datos ✅
```
Canvas → LTI 1.1 Launch (OAuth 1.0) → Laravel → Validate Signature
                                               ↓
                                    Render Chatbot UI (Inertia/React)
                                               ↓
Usuario escribe mensaje → POST /api/chat → ChatController
                                               ↓
                                    CanvasService.buildCourseContext()
                                    (módulos, páginas, asignaciones)
                                               ↓
                                    ChatbotService.chat()
                                    (OpenAI con contexto del curso)
                                               ↓
                                    Respuesta AI → Frontend
```

## 5. APIs Utilizadas ✅
**Canvas API:**
- ✅ `/api/v1/courses/:id/modules`
- ✅ `/api/v1/courses/:id/pages`
- ✅ `/api/v1/courses/:id/assignments`
- ✅ `/api/v1/courses/:id` (info del curso)

**OpenAI:**
- ✅ Chat Completions API (gpt-4o-mini)
- ✅ System prompt con contexto del curso
- ✅ Historial de conversación (últimos 5 mensajes)

## 6. Configuración .env ✅
```bash
# Variables configuradas en Cloud Run
APP_KEY=base64:FL4hEOMlduU7OiuhfawIaEivi5DSwScTFweoyUEE+AY= ✅
APP_ENV=production ✅
APP_DEBUG=false ✅
SESSION_DRIVER=cookie ✅
SESSION_ENCRYPT=true ✅
CACHE_STORE=array ✅
QUEUE_CONNECTION=sync ✅
LOG_CHANNEL=stderr ✅

# OpenAI
OPENAI_API_KEY=sk-proj-... ✅
OPENAI_MODEL=gpt-4o-mini ✅

# Canvas API
CANVAS_API_URL=https://canvas.instructure.com/api/v1 ✅
CANVAS_API_TOKEN=7~cG9E... ✅

# LTI 1.1
LTI_CONSUMER_KEY=chatbot-key-2024 ✅
LTI_SHARED_SECRET=supersecret123 ✅
```

## 7. Pruebas ✅
- [x] Test de autenticación LTI 1.1 (OAuth signature)
- [x] Test de OAuth con HTTPS/proxy
- [x] Test de renderizado de interfaz Chatbot
- [x] Test de Canvas API integration
- [x] Test de OpenAI responses
- [x] Test end-to-end del chatbot

## 8. Deploy en GCP Cloud Run ✅
```bash
# Build & Deploy (última versión: latest)
gcloud builds submit --tag gcr.io/aplicacionesnube-2025/mi-repo-lti/chatbot-test:latest

gcloud run deploy chatbot-test-lti \
    --image gcr.io/aplicacionesnube-2025/mi-repo-lti/chatbot-test:latest \
    --platform managed \
    --region us-central1 \
    --allow-unauthenticated \
    --set-env-vars "APP_KEY=base64:FL4hEOMlduU7OiuhfawIaEivi5DSwScTFweoyUEE+AY=,APP_ENV=production,APP_DEBUG=false,SESSION_DRIVER=cookie,SESSION_ENCRYPT=true,CACHE_STORE=array,QUEUE_CONNECTION=sync,LOG_CHANNEL=stderr,LTI_CONSUMER_KEY=chatbot-key-2024,LTI_SHARED_SECRET=supersecret123,CANVAS_API_URL=https://canvas.instructure.com/api/v1,CANVAS_API_TOKEN=[YOUR_CANVAS_API_TOKEN_HERE],OPENAI_API_KEY=[YOUR_OPENAI_API_KEY_HERE],OPENAI_MODEL=gpt-4o-mini"
```

**Historial de Versiones:**
- v1-v2: Configuración inicial
- v3: Fix SESSION_DRIVER=cookie
- v4-v5: Fix LTI consumer key
- v6: Fix OAuth signature con HTTPS
- v7: Agregado componente Chatbot.tsx
- v8: Agregados servicios Canvas y OpenAI
- v9: Fix config:clear en runtime
- v10: Logging de variables de entorno
- v11: Eliminado .env del build, todas las vars en Cloud Run ✅

**Problemas Resueltos:**
1. ✅ SQLite session error → SESSION_DRIVER=cookie
2. ✅ OAuth signature mismatch → X-Forwarded-Proto HTTPS handling
3. ✅ CSRF 419 error → Excepción para `/api/*`
4. ✅ Variables de entorno no cargadas → Eliminado .env del Dockerfile, usar solo env vars de Cloud Run

## 9. Arquitectura de Archivos
```
app/
├── Http/
│   ├── Controllers/
│   │   ├── LtiController.php      # LTI 1.1 OAuth + Launch
│   │   └── ChatController.php      # Endpoint /api/chat
│   └── Middleware/
│       └── VerifyCsrfToken.php     # CSRF exceptions
├── Services/
│   ├── CanvasService.php           # Canvas API client
│   └── ChatbotService.php          # OpenAI integration
config/
├── lti.php                         # LTI 1.1 credentials
└── services.php                    # Canvas + OpenAI config
resources/js/pages/
└── Chatbot.tsx                     # React chat UI
routes/
└── web.php                         # LTI + API routes
```

## 10. Próximos Pasos (v1.1)
- [ ] Persistir conversaciones en BD (tabla `chat_conversations`)
- [ ] Implementar rate limiting para API
- [ ] Mejorar prompts del sistema con más contexto
- [ ] Agregar soporte para archivos adjuntos del curso
- [ ] Implementar caché de contenido del curso
- [ ] Panel de administración para instructores
- [ ] Métricas de uso (preguntas más frecuentes)
- [ ] Soporte multilenguaje

## Diferencias LTI 1.1 vs LTI 1.3
| Aspecto | LTI 1.1 | LTI 1.3 |
|---------|---------|---------|
| **Autenticación** | OAuth 1.0 signature | OAuth 2.0 + JWT |
| **Permisos** | Instructor puede instalar | Requiere Admin |
| **Complejidad** | Simple | Compleja |
| **Setup** | Consumer Key + Secret | Developer Key + OIDC |
| **Endpoints** | 1 (launch) | 3+ (login, jwks, launch) |
