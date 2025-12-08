# 🏗️ Arquitectura del Chatbot con IA para Canvas LMS

## Diagrama de Flujo

```
┌─────────────────────────────────────────────────────────────────┐
│                          CANVAS LMS                              │
│                                                                  │
│  ┌────────────────┐         ┌──────────────────────┐           │
│  │   Estudiante   │         │     Instructor       │           │
│  │   hace clic    │         │  carga contenido     │           │
│  │  en Chatbot    │         │     del curso        │           │
│  └────────┬───────┘         └──────────┬───────────┘           │
│           │                            │                        │
│           │ LTI Launch                 │ Canvas API (futuro)    │
│           │ POST /lti/launch           │                        │
│           │                            │                        │
└───────────┼────────────────────────────┼────────────────────────┘
            │                            │
            ▼                            ▼
┌─────────────────────────────────────────────────────────────────┐
│                    APLICACIÓN LARAVEL                            │
│                                                                  │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │                    LtiController                          │  │
│  │  - Recibe datos de Canvas (user_id, course_id, role)    │  │
│  │  - Valida LTI launch                                     │  │
│  │  - Renderiza interfaz Inertia con React                  │  │
│  └───────────────────────┬──────────────────────────────────┘  │
│                          │                                       │
│                          ▼                                       │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │              INTERFAZ REACT (Inertia.js)                 │  │
│  │                                                           │  │
│  │  ┌─────────────────────────────────────────────────┐    │  │
│  │  │         Componente chatbot.tsx                  │    │  │
│  │  │  - Input de mensajes                            │    │  │
│  │  │  - Historial de chat                            │    │  │
│  │  │  - Animaciones de carga                         │    │  │
│  │  │  - Auto-scroll                                  │    │  │
│  │  └────────────┬───────────────────┬────────────────┘    │  │
│  │               │                   │                      │  │
│  └───────────────┼───────────────────┼──────────────────────┘  │
│                  │                   │                          │
│                  │ POST              │ GET                      │
│                  │ /chatbot/message  │ /chatbot/conversation    │
│                  │                   │                          │
│  ┌───────────────▼───────────────────▼──────────────────────┐  │
│  │              ChatbotController                           │  │
│  │                                                           │  │
│  │  1. getOrCreateConversation()                           │  │
│  │     - Busca o crea conversación para user/course       │  │
│  │                                                           │  │
│  │  2. sendMessage()                                        │  │
│  │     - Guarda mensaje del usuario                        │  │
│  │     - Obtiene contexto del curso                        │  │
│  │     - Construye historial para OpenAI                   │  │
│  │     - Llama a OpenAI API                                │  │
│  │     - Guarda respuesta del asistente                    │  │
│  │     - Retorna ambos mensajes                            │  │
│  │                                                           │  │
│  │  3. uploadCourseContext()                               │  │
│  │     - Permite a instructores cargar contexto           │  │
│  │                                                           │  │
│  └───────┬───────────────────────────┬──────────────────────┘  │
│          │                           │                          │
│          ▼                           ▼                          │
│  ┌─────────────────┐        ┌──────────────────┐              │
│  │   BASE DE       │        │   OPENAI API     │              │
│  │    DATOS        │        │                  │              │
│  │                 │        │  - GPT-4o-mini   │              │
│  │ ┌─────────────┐ │        │  - Genera        │              │
│  │ │Conversations│ │        │    respuestas    │              │
│  │ └─────────────┘ │        │  - Usa contexto  │              │
│  │ ┌─────────────┐ │        │                  │              │
│  │ │  Messages   │ │        └──────────────────┘              │
│  │ └─────────────┘ │                                           │
│  │ ┌─────────────┐ │                                           │
│  │ │Course       │ │                                           │
│  │ │Contexts     │ │                                           │
│  │ └─────────────┘ │                                           │
│  └─────────────────┘                                           │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

## Flujo de Datos Detallado

### 1. Inicio de Sesión (LTI Launch)

```
Canvas → POST /lti/launch
  ├─ Headers: Content-Type: application/x-www-form-urlencoded
  └─ Body:
      ├─ custom_canvas_user_id: "12345"
      ├─ lis_person_name_full: "Juan Pérez"
      ├─ lis_person_contact_email_primary: "juan@example.com"
      ├─ roles: "Student" (o "Instructor")
      └─ custom_canvas_course_id: "COURSE-101"

LtiController::launch()
  ├─ Extrae datos del request
  ├─ Valida datos
  └─ Renderiza Inertia::render('chatbot', [user, isInstructor])

React chatbot.tsx
  ├─ Recibe props: user, isInstructor
  ├─ useEffect → POST /chatbot/conversation
  │   └─ Crea o recupera conversación existente
  └─ Renderiza interfaz de chat
```

### 2. Enviar Mensaje

```
Usuario escribe: "¿Cuáles son los temas del curso?"

React chatbot.tsx
  └─ onSubmit → POST /chatbot/message
      ├─ conversation_id: 1
      └─ message: "¿Cuáles son los temas del curso?"

ChatbotController::sendMessage()
  ├─ 1. Guardar mensaje del usuario
  │   └─ Message::create([
  │       'conversation_id' => 1,
  │       'role' => 'user',
  │       'content' => '¿Cuáles son los temas...'
  │     ])
  │
  ├─ 2. Obtener contexto del curso
  │   └─ CourseContext::where('course_id', 'COURSE-101')->get()
  │       Resultado: "Este curso cubre programación. Temas: variables..."
  │
  ├─ 3. Construir mensajes para OpenAI
  │   └─ [
  │       {
  │         role: 'system',
  │         content: 'Eres un asistente... Contexto: [contexto aquí]'
  │       },
  │       {
  │         role: 'user',
  │         content: '¿Cuáles son los temas del curso?'
  │       }
  │     ]
  │
  ├─ 4. Llamar a OpenAI
  │   └─ OpenAI::chat()->create([
  │       'model' => 'gpt-4o-mini',
  │       'messages' => [...],
  │       'temperature' => 0.7,
  │       'max_tokens' => 500
  │     ])
  │     Respuesta: "Los temas principales del curso son..."
  │
  ├─ 5. Guardar respuesta
  │   └─ Message::create([
  │       'conversation_id' => 1,
  │       'role' => 'assistant',
  │       'content' => 'Los temas principales...'
  │     ])
  │
  └─ 6. Retornar respuesta
      └─ response()->json([
          'user_message' => {...},
          'assistant_message' => {...}
        ])

React chatbot.tsx
  ├─ Recibe respuesta
  ├─ Actualiza estado con nuevos mensajes
  └─ Renderiza mensajes en la UI
```

### 3. Cargar Contexto del Curso

```
Instructor → POST /chatbot/context
  ├─ course_id: "COURSE-101"
  ├─ context_type: "syllabus"
  ├─ content: "Este curso cubre..."
  └─ metadata: { title: "Syllabus", date: "2025-01-01" }

ChatbotController::uploadCourseContext()
  ├─ Valida datos
  ├─ CourseContext::create([...])
  └─ response()->json(['message' => 'Contexto cargado'])

Base de Datos (course_contexts)
  └─ Nuevo registro guardado
      Este contenido se usará en futuras consultas
```

## Estructura de Base de Datos

```sql
-- conversations
┌────┬───────────┬─────────┬─────────────────┬─────────────┬─────────────┐
│ id │ course_id │ user_id │ title           │ created_at  │ updated_at  │
├────┼───────────┼─────────┼─────────────────┼─────────────┼─────────────┤
│ 1  │ COURSE-101│ 12345   │ Nueva conversa  │ 2025-12-08  │ 2025-12-08  │
└────┴───────────┴─────────┴─────────────────┴─────────────┴─────────────┘

-- messages
┌────┬──────────────────┬───────────┬─────────────────────────┬─────────────┐
│ id │ conversation_id  │ role      │ content                 │ created_at  │
├────┼──────────────────┼───────────┼─────────────────────────┼─────────────┤
│ 1  │ 1                │ user      │ ¿Cuáles son los temas...│ 2025-12-08  │
│ 2  │ 1                │ assistant │ Los temas principales...│ 2025-12-08  │
└────┴──────────────────┴───────────┴─────────────────────────┴─────────────┘

-- course_contexts
┌────┬───────────┬──────────────┬─────────────┬──────────────────┬──────────┐
│ id │ course_id │ context_type │ source_id   │ content          │ metadata │
├────┼───────────┼──────────────┼─────────────┼──────────────────┼──────────┤
│ 1  │ COURSE-101│ syllabus     │ NULL        │ Este curso...    │ {...}    │
└────┴───────────┴──────────────┴─────────────┴──────────────────┴──────────┘
```

## Tecnologías y Responsabilidades

```
┌─────────────────────┬────────────────────────────────────────────┐
│   Tecnología        │              Responsabilidad               │
├─────────────────────┼────────────────────────────────────────────┤
│ Canvas LMS          │ - Autenticación de usuarios                │
│                     │ - Lanzamiento LTI                          │
│                     │ - Contexto educativo                       │
├─────────────────────┼────────────────────────────────────────────┤
│ Laravel 12          │ - Backend API                              │
│                     │ - Lógica de negocio                        │
│                     │ - Autenticación LTI                        │
│                     │ - ORM (Eloquent)                           │
├─────────────────────┼────────────────────────────────────────────┤
│ Inertia.js          │ - Bridge entre Laravel y React             │
│                     │ - SSR (Server-Side Rendering)              │
│                     │ - Routing sin API REST tradicional         │
├─────────────────────┼────────────────────────────────────────────┤
│ React 19            │ - UI Components                            │
│                     │ - Estado de la aplicación                  │
│                     │ - Interactividad                           │
├─────────────────────┼────────────────────────────────────────────┤
│ TypeScript          │ - Type safety                              │
│                     │ - Autocomplete                             │
│                     │ - Mejor DX                                 │
├─────────────────────┼────────────────────────────────────────────┤
│ Tailwind CSS 4      │ - Estilos                                  │
│                     │ - Responsive design                        │
│                     │ - Utilities                                │
├─────────────────────┼────────────────────────────────────────────┤
│ OpenAI API          │ - Generación de respuestas                 │
│                     │ - Comprensión de lenguaje natural         │
│                     │ - Contexto y memoria                       │
├─────────────────────┼────────────────────────────────────────────┤
│ SQLite/MySQL        │ - Persistencia de datos                    │
│                     │ - Historial de conversaciones              │
│                     │ - Contexto del curso                       │
└─────────────────────┴────────────────────────────────────────────┘
```

## Patrones de Diseño Utilizados

1. **MVC (Model-View-Controller)**
   - Model: Eloquent Models (Conversation, Message, CourseContext)
   - View: React Components (chatbot.tsx)
   - Controller: Laravel Controllers (ChatbotController, LtiController)

2. **Repository Pattern** (Implícito en Eloquent ORM)
   - Abstracción de acceso a datos
   - Facilita testing y mantenimiento

3. **Service Layer**
   - ChatbotController actúa como servicio
   - Encapsula lógica de negocio

4. **RAG (Retrieval Augmented Generation)**
   - CourseContext almacena conocimiento
   - OpenAI usa este contexto para generar respuestas

## Seguridad

```
┌────────────────────┬──────────────────────────────────────────┐
│   Capa             │            Medidas de Seguridad          │
├────────────────────┼──────────────────────────────────────────┤
│ LTI Launch         │ - CSRF exemption solo para /lti/launch   │
│                    │ - Validación de firma (TODO: LTI 1.3)    │
├────────────────────┼──────────────────────────────────────────┤
│ API Endpoints      │ - Validación de input (Request validation│
│                    │ - Rate limiting (TODO)                   │
│                    │ - Sanitización de datos                  │
├────────────────────┼──────────────────────────────────────────┤
│ Base de Datos      │ - Query builder/ORM (previene SQL inject)│
│                    │ - Prepared statements                    │
│                    │ - Mass assignment protection             │
├────────────────────┼──────────────────────────────────────────┤
│ OpenAI             │ - API Key en .env (no expuesta)          │
│                    │ - Max tokens limitado                    │
│                    │ - Moderación de contenido (TODO)         │
├────────────────────┼──────────────────────────────────────────┤
│ Frontend           │ - XSS protection (React escaping)        │
│                    │ - HTTPS obligatorio en producción        │
│                    │ - CSP headers (TODO)                     │
└────────────────────┴──────────────────────────────────────────┘
```

## Escalabilidad

### Horizontal Scaling

```
┌─────────────────────────────────────────────────────┐
│                  Load Balancer                       │
└──────────┬────────────┬────────────┬─────────────────┘
           │            │            │
    ┌──────▼────┐ ┌─────▼─────┐ ┌───▼────────┐
    │  Laravel  │ │  Laravel  │ │  Laravel   │
    │  Server 1 │ │  Server 2 │ │  Server 3  │
    └──────┬────┘ └─────┬─────┘ └───┬────────┘
           │            │            │
           └────────────┼────────────┘
                        ▼
              ┌─────────────────┐
              │  MySQL/Postgres │
              │   (Primary)     │
              └─────────────────┘
                        │
                ┌───────┴────────┐
                ▼                ▼
         ┌───────────┐    ┌───────────┐
         │  Replica  │    │  Replica  │
         └───────────┘    └───────────┘
```

### Caching Strategy

```
┌──────────────┐
│   Redis      │ ← Session Storage
├──────────────┤
│   Redis      │ ← Query Cache
├──────────────┤
│   Redis      │ ← Rate Limiting
└──────────────┘
```

### Queue Workers

```
┌──────────────────────────────────────┐
│     Queue (Redis/Database)           │
└──────────────────────────────────────┘
     ↓           ↓           ↓
┌─────────┐ ┌─────────┐ ┌─────────┐
│Worker 1 │ │Worker 2 │ │Worker 3 │
└─────────┘ └─────────┘ └─────────┘
     ↓           ↓           ↓
  ┌────────────────────────────┐
  │    OpenAI API Calls        │
  │    (Async Processing)      │
  └────────────────────────────┘
```

## Métricas y Monitoreo

```
┌────────────────────┬─────────────────────────────────┐
│   Métrica          │         Herramienta             │
├────────────────────┼─────────────────────────────────┤
│ Errores            │ Sentry / Laravel Log           │
│ Performance        │ New Relic / Scout              │
│ Uptime             │ Pingdom / UptimeRobot          │
│ OpenAI Usage       │ OpenAI Dashboard               │
│ User Analytics     │ Google Analytics / Plausible   │
│ Queue Status       │ Laravel Horizon                │
└────────────────────┴─────────────────────────────────┘
```
