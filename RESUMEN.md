# 🤖 Chatbot con IA para Canvas LMS - Resumen del Proyecto

## ✅ Configuración Completada

El proyecto ha sido configurado exitosamente como una External Tool de Canvas LMS con las siguientes características:

### 🎯 Funcionalidades Implementadas

1. **Integración LTI con Canvas**
   - Soporte para LTI 1.1 y LTI 1.3
   - CSRF exemption configurado para `/lti/launch`
   - Captura de datos del usuario y curso desde Canvas

2. **Chatbot con IA**
   - Integración con OpenAI GPT-4o-mini (configurable)
   - Respuestas contextuales basadas en el contenido del curso
   - Historial de conversaciones por usuario y curso
   - Interfaz de chat moderna y responsive

3. **Base de Datos**
   - Tabla `conversations`: Almacena conversaciones por usuario/curso
   - Tabla `messages`: Historial de mensajes (usuario y asistente)
   - Tabla `course_contexts`: Contenido del curso para RAG (Retrieval Augmented Generation)

4. **Interfaz de Usuario**
   - Componente React con TypeScript
   - Diseño responsive con Tailwind CSS 4
   - Experiencia de chat en tiempo real
   - Animaciones de carga
   - Auto-scroll a nuevos mensajes

### 📁 Archivos Creados/Modificados

#### Backend (Laravel)
- `app/Http/Controllers/ChatbotController.php` - Lógica del chatbot
- `app/Http/Controllers/LtiController.php` - Manejo de LTI launch
- `app/Models/Conversation.php` - Modelo de conversaciones
- `app/Models/Message.php` - Modelo de mensajes
- `app/Models/CourseContext.php` - Modelo de contexto del curso
- `database/migrations/*_create_chatbot_tables.php` - Migraciones
- `routes/web.php` - Rutas API del chatbot
- `config/openai.php` - Configuración de OpenAI

#### Frontend (React + TypeScript)
- `resources/js/pages/chatbot.tsx` - Componente principal del chat
- `tailwind.config.ts` - Configuración de Tailwind CSS

#### Configuración LTI
- `lti-config.json` - Configuración LTI 1.3 para Canvas
- `lti-config.xml` - Configuración LTI 1.1 para Canvas

#### Documentación
- `README.md` - Documentación principal
- `DEPLOYMENT.md` - Guía de despliegue en producción
- `TESTING.md` - Guía para pruebas locales
- `RESUMEN.md` - Este archivo
- `.env.example` - Variables de entorno actualizadas

### 🔑 Variables de Entorno Necesarias

```env
# OpenAI (REQUERIDO)
OPENAI_API_KEY=sk-tu-clave-api-aqui
OPENAI_MODEL=gpt-4o-mini

# Canvas LMS (Opcional, para integraciones avanzadas)
CANVAS_API_URL=https://your-canvas-instance.instructure.com
CANVAS_API_TOKEN=tu-token-de-canvas

# Base de datos (SQLite por defecto)
DB_CONNECTION=sqlite
```

### 🛣️ Rutas Implementadas

```
POST /lti/launch                     # LTI launch desde Canvas
POST /chatbot/conversation           # Crear/obtener conversación
POST /chatbot/message                # Enviar mensaje y obtener respuesta
GET  /chatbot/conversation/{id}      # Obtener historial
POST /chatbot/context                # Cargar contexto del curso (instructores)
```

### 🏗️ Arquitectura

```
Canvas LMS
    │
    ├─> LTI Launch (POST /lti/launch)
    │   └─> LtiController
    │       └─> Renderiza chatbot.tsx con datos del usuario
    │
    └─> Chatbot Interface (React)
        │
        ├─> POST /chatbot/conversation (inicializar)
        ├─> POST /chatbot/message (enviar mensaje)
        │   └─> ChatbotController
        │       ├─> Guardar mensaje del usuario
        │       ├─> Obtener contexto del curso
        │       ├─> Llamar a OpenAI API
        │       └─> Guardar y retornar respuesta
        │
        └─> Renderizar mensajes en tiempo real
```

### 🔄 Flujo de Uso

1. **Estudiante accede desde Canvas**
   - Canvas envía POST a `/lti/launch` con datos del usuario
   - LtiController valida y renderiza la interfaz del chatbot
   - React carga y crea/obtiene la conversación del usuario

2. **Estudiante hace una pregunta**
   - Frontend envía mensaje a `/chatbot/message`
   - Backend obtiene contexto del curso de `course_contexts`
   - Backend construye prompt con historial y contexto
   - OpenAI genera respuesta
   - Backend guarda ambos mensajes en la BD
   - Frontend muestra la respuesta

3. **Instructor carga contexto** (Opcional)
   - Usa endpoint `/chatbot/context` para añadir contenido
   - Tipos: syllabus, announcement, module, assignment, etc.
   - El chatbot usa este contexto para respuestas más precisas

### 📦 Dependencias Instaladas

**PHP (Composer):**
- `openai-php/laravel` - Cliente de OpenAI para Laravel
- Laravel 12.41.1
- Inertia.js 2.0.11

**JavaScript (npm):**
- React 19
- TypeScript 5.7
- Tailwind CSS 4.1
- Axios (para peticiones HTTP)
- Inertia.js React adapter

### 🚀 Próximos Pasos

1. **Configurar API Key de OpenAI**
   ```bash
   cp .env.example .env
   # Editar .env y añadir OPENAI_API_KEY
   ```

2. **Ejecutar Migraciones**
   ```bash
   php artisan migrate
   ```

3. **Compilar Assets**
   ```bash
   npm install
   npm run dev  # o npm run build para producción
   ```

4. **Probar Localmente**
   - Ver `TESTING.md` para instrucciones detalladas
   - Usar ngrok para exponer servidor local a Canvas

5. **Configurar en Canvas**
   - Ver `README.md` sección "Configuración en Canvas LMS"
   - Usar `lti-config.json` o `lti-config.xml`
   - Reemplazar `tu-dominio.com` con tu dominio real

6. **Desplegar en Producción**
   - Ver `DEPLOYMENT.md` para opciones de despliegue
   - Configurar HTTPS (Let's Encrypt)
   - Optimizar con cache y queues

### 🎨 Personalización

#### Cambiar el Modelo de IA
```env
# En .env
OPENAI_MODEL=gpt-4  # Para GPT-4 (más caro pero mejor)
OPENAI_MODEL=gpt-4o-mini  # Por defecto, más económico
```

#### Personalizar el Prompt del Sistema
Editar `ChatbotController.php`, método `buildMessageHistory()`:

```php
$systemPrompt = "Personaliza aquí el comportamiento del chatbot...";
```

#### Cambiar el Diseño
Editar `resources/js/pages/chatbot.tsx` para modificar:
- Colores (clases de Tailwind)
- Layout
- Mensajes de bienvenida
- Iconos

#### Añadir Autenticación/Autorización
Implementar middleware para verificar:
- Roles de Canvas (Instructor vs Student)
- Permisos específicos
- Rate limiting por usuario

### 📊 Métricas y Monitoreo

Para producción, considera implementar:

1. **Laravel Telescope** (desarrollo)
   ```bash
   composer require laravel/telescope --dev
   php artisan telescope:install
   ```

2. **Sentry** (errores en producción)
   ```bash
   composer require sentry/sentry-laravel
   ```

3. **Logs personalizados**
   - Costos de API de OpenAI
   - Preguntas más frecuentes
   - Satisfacción del usuario

### 🔐 Consideraciones de Seguridad

- ✅ CSRF exemption solo para `/lti/launch`
- ✅ Variables de entorno para secrets
- ⚠️ Pendiente: Validación de firma LTI 1.3
- ⚠️ Pendiente: Rate limiting por usuario
- ⚠️ Pendiente: Sanitización de contexto del curso
- ⚠️ Pendiente: Moderación de contenido

### 💰 Estimación de Costos (OpenAI)

Con GPT-4o-mini:
- ~$0.15 por 1M tokens de entrada
- ~$0.60 por 1M tokens de salida
- Promedio: ~$0.002 por conversación (10 mensajes)
- 1000 estudiantes x 5 conversaciones/mes = ~$10/mes

### 🆘 Soporte

Para problemas o preguntas:

1. Revisar `TESTING.md` para pruebas locales
2. Revisar `DEPLOYMENT.md` para problemas de producción
3. Revisar logs: `tail -f storage/logs/laravel.log`
4. Verificar configuración: `php artisan config:show openai`

### 📚 Referencias

- [Canvas LTI Documentation](https://canvas.instructure.com/doc/api/file.tools_intro.html)
- [LTI 1.3 Core Specification](https://www.imsglobal.org/spec/lti/v1p3/)
- [OpenAI API Documentation](https://platform.openai.com/docs)
- [Laravel 12 Documentation](https://laravel.com/docs/12.x)
- [Inertia.js Documentation](https://inertiajs.com/)

---

## ✨ ¡El proyecto está listo para probar!

Sigue las instrucciones en `TESTING.md` para comenzar.
