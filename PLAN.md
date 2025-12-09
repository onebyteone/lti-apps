# Plan: Chatbot IA para Canvas LMS v1.0 (LTI 1.1)

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

## 2. Backend Laravel

### 2.1 Controladores y Rutas ✅
- [x] `LtiController` con OAuth 1.0 validation
- [x] Rutas LTI simplificadas
- [x] Manejo de sesiones con cookies

### 2.2 Servicios Pendientes
- [ ] `CanvasService`
  - Obtener módulos del curso
  - Obtener páginas del curso
  - Obtener asignaciones
- [ ] `ChatbotService` (o `ChatController`)
  - Integración con gpt-4o-mini
  - Context building desde contenido del curso
  - Manejo de conversaciones
  - Endpoint `/api/chat` POST

## 3. Frontend React/Inertia ✅
- [x] Página del chatbot (`resources/js/pages/Chatbot.tsx`)
- [x] Componente de chat UI
  - Input de usuario
  - Historial de mensajes
  - Indicador de typing (loading dots)
- [x] Estado de conversación (React useState)
- [x] Badge de rol (Instructor)
- [x] Diseño responsive con Tailwind

## 4. Próximos Pasos (v1.1)
- [ ] Crear endpoint `/api/chat` 
- [ ] Implementar `CanvasService` para obtener contenido del curso
- [ ] Integrar OpenAI para respuestas inteligentes
- [ ] Persistir conversaciones en BD (opcional)
- [ ] Mejorar contexto del chatbot con datos del curso

## 4. Flujo de Datos
```
Canvas → LTI 1.1 Launch (OAuth 1.0) → Laravel → Validate Signature
                                               ↓
                                    Fetch Course Data (Canvas API) 
                                               ↓
                                    Build Context → OpenAI → Response
```

## 5. Base de Datos
- [ ] Tabla `chat_conversations` (user_id, course_id, messages JSON)
- [ ] Migración y modelos

## 6. APIs a Utilizar
**Canvas API:**
- `/api/v1/courses/:id/modules`
- `/api/v1/courses/:id/pages`
- `/api/v1/courses/:id/assignments`

**OpenAI:**
- Chat Completions API (gpt-4o-mini)

## 7. Configuración .env
```
OPENAI_API_KEY=✓
OPENAI_MODEL=gpt-4o-mini ✓
CANVAS_API_URL=✓
CANVAS_API_TOKEN=✓

# LTI 1.1 Configuration
LTI_CONSUMER_KEY=chatbot-key-2024 ✓
LTI_SHARED_SECRET=supersecret123 ✓
APP_URL=https://chatbot-test-lti-713107332501.us-central1.run.app ✓
```

## 8. Pruebas
- [x] Test de autenticación LTI 1.1 (OAuth signature)
- [x] Test de OAuth con HTTPS/proxy
- [x] Test de renderizado de interfaz Chatbot
- [ ] Test de Canvas API integration
- [ ] Test de OpenAI responses
- [ ] Test end-to-end del chatbot

## 9. Deploy en GCP Cloud Run ✅
```bash
# Build & Deploy (última versión: v7)
gcloud builds submit --tag gcr.io/aplicacionesnube-2025/mi-repo-lti/chatbot-test:v7
gcloud run deploy chatbot-test-lti \
    --image gcr.io/aplicacionesnube-2025/mi-repo-lti/chatbot-test:v7 \
    --platform managed \
    --region us-central1 \
    --allow-unauthenticated
```

**Versiones:**
- v1-v2: Configuración inicial
- v3: Fix SESSION_DRIVER=cookie
- v4-v5: Fix LTI consumer key
- v6: Fix OAuth signature con HTTPS
- v7: Agregado componente Chatbot.tsx ✅

## Diferencias LTI 1.1 vs LTI 1.3
| Aspecto | LTI 1.1 | LTI 1.3 |
|---------|---------|---------|
| **Autenticación** | OAuth 1.0 signature | OAuth 2.0 + JWT |
| **Permisos** | Instructor puede instalar | Requiere Admin |
| **Complejidad** | Simple | Compleja |
| **Setup** | Consumer Key + Secret | Developer Key + OIDC |
| **Endpoints** | 1 (launch) | 3+ (login, jwks, launch) |
