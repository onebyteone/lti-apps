# Plan: Auto-Grading Tool para Canvas LMS v1.0 (LTI 1.1)

## Estado: ✅ FASE 1 (MVP) COMPLETADA

## Objetivo
Implementar corrección automática de ensayos y tareas utilizando rúbricas inteligentes con LLM (OpenAI GPT-4o) integrado en Canvas LMS mediante LTI 1.1.

## 1. Configuración LTI 1.1 ✅

### 1.1 LTI Placements
- **course_navigation**: Herramienta visible en el menú del curso (solo para instructores)

### 1.2 Endpoints LTI ✅
- [x] `/lti/grading/launch` - Endpoint principal para auto-grading
- [x] `/lti/grading/config.xml` - Configuración XML
- [x] OAuth validation (heredado de chatbot)

### 1.3 Credenciales ✅
- Consumer Key: `auto-grading-key-2024` (o reusar del chatbot)
- Shared Secret: `autograding-secret-456` (o reusar del chatbot)
- Configurable vía `LTI_GRADING_CONSUMER_KEY` y `LTI_GRADING_SHARED_SECRET`

## 2. Canvas API - Endpoints Necesarios ✅

### 2.1 Assignments API ✅
- [x] `GET /api/v1/courses/:course_id/assignments` - Listar asignaciones
- [x] `GET /api/v1/courses/:course_id/assignments/:id` - Detalles de asignación
- [x] `GET /api/v1/courses/:course_id/assignments/:id/rubrics` - Obtener rúbrica

### 2.2 Submissions API ✅
- [x] `GET /api/v1/courses/:course_id/assignments/:id/submissions` - Listar entregas
- [x] `GET /api/v1/courses/:course_id/assignments/:id/submissions/:user_id` - Entrega específica
- [x] `PUT /api/v1/courses/:course_id/assignments/:id/submissions/:user_id` - Actualizar calificación
- [x] `POST` comment to submission - Agregar feedback

### 2.3 Rubrics API ✅
- [x] `GET /api/v1/courses/:course_id/assignments/:id?include[]=rubric` - Obtener rúbrica con assignment
- [x] Rubric assessment en formato Canvas

## 3. Arquitectura del Sistema ✅

### 3.1 Flujo de Calificación
```
1. Instructor accede desde Canvas → LTI Launch
   ↓
2. OAuth validation → GradingController
   ↓
3. GradingDashboard muestra:
   - Asignaciones con rúbricas
   - Estadísticas de submissions
   - Entregas pendientes de calificar
   ↓
4. Instructor selecciona asignación
   ↓
5. Backend procesa (API /api/grading/assignments/{id}/grade):
   a. Obtiene submissions con SubmissionService
   b. Extrae texto de cada submission
   c. Obtiene rúbrica con RubricService
   d. AutoGradingService llama a GPT-4o con:
      - Texto del estudiante
      - Criterios de rúbrica
      - Contexto de la asignación
   e. Procesa respuesta JSON del LLM
   f. Genera feedback formateado
   ↓
6. Muestra resultados para revisión
   ↓
7. Instructor aprueba → submitGrades() envía a Canvas
```

### 3.2 Componentes Backend ✅

#### Controllers ✅
- [x] `GradingController.php`
  - `launch()` - LTI launch con validación de instructor
  - `getAssignments()` - Lista asignaciones con rúbricas
  - `getAssignmentDetails()` - Detalles + submissions
  - `gradeSubmissions()` - Procesar calificaciones con IA
  - `submitGrades()` - Enviar a Canvas
  - `config()` - XML de configuración LTI

#### Services ✅
- [x] `RubricService.php`
  - `getAssignmentRubric()` - Obtener rúbrica
  - `parseCriteria()` - Parsear criterios
  - `formatForLLM()` - Formatear para prompt
  - `validateRubric()` - Validar estructura
  - `getTotalPoints()` - Calcular puntos totales

- [x] `SubmissionService.php`
  - `getSubmissions()` - Obtener entregas con filtros
  - `getSubmission()` - Entrega específica
  - `extractSubmissionText()` - Extraer contenido
  - `submitGrade()` - Enviar calificación a Canvas
  - `addComment()` - Agregar feedback
  - `getAssignment()` - Info de asignación
  - `getSubmissionStats()` - Estadísticas

- [x] `AutoGradingService.php`
  - `gradeSubmission()` - Calificar con LLM
  - `buildSystemPrompt()` - Prompt del sistema
  - `buildUserPrompt()` - Prompt con rúbrica
  - `processAssessment()` - Validar respuesta
  - `formatForCanvas()` - Formato Canvas rubric_assessment
  - `generateCompleteFeedback()` - Feedback completo

### 3.3 Componentes Frontend ✅

#### Páginas React ✅
- [x] `GradingDashboard.tsx` - Dashboard principal
  - Stats cards (asignaciones, pendientes, calificadas)
  - Lista de asignaciones con rúbricas
  - Botones de acción
  - Info box sobre IA

## 4. Integración con OpenAI

### 4.1 Prompt Engineering
```
System Prompt:
"Eres un asistente de calificación académica experto. Tu tarea es evaluar 
trabajos de estudiantes siguiendo estrictamente una rúbrica proporcionada.

Para cada criterio de la rúbrica:
1. Analiza el contenido del trabajo
2. Determina qué nivel de desempeño alcanza (Excelente, Bueno, Satisfactorio, Necesita Mejorar)
3. Asigna la puntuación correspondiente
4. Proporciona feedback constructivo específico

Responde en formato JSON estructurado."
```

### 4.2 Estructura de Request
```json
{
  "model": "gpt-4o",
  "messages": [
    {
      "role": "system",
      "content": "..."
    },
    {
      "role": "user",
      "content": "Rúbrica: {...}\n\nTrabajo del estudiante: {...}"
    }
  ],
  "response_format": { "type": "json_object" },
  "temperature": 0.3
}
```

### 4.3 Estructura de Response Esperada
```json
{
  "total_score": 85,
  "max_score": 100,
  "criteria_assessments": [
    {
      "criterion_id": "crit_1",
      "criterion_name": "Argumentación",
      "level_achieved": "Bueno",
      "points": 8,
      "max_points": 10,
      "feedback": "El estudiante presenta argumentos claros..."
    }
  ],
  "general_feedback": "Buen trabajo en general...",
  "strengths": ["Buena estructura", "Fuentes confiables"],
  "areas_for_improvement": ["Profundizar en análisis crítico"]
}
```

## 5. Base de Datos ✅

### 5.1 Migraciones ✅
- [x] `grading_jobs` - Trabajos de calificación
  ```php
  - id
  - assignment_id
  - course_id
  - user_id (instructor)
  - status (pending, processing, completed, failed)
  - submissions_count
  - processed_count
  - submission_ids (JSON)
  - error_message
  - created_at, updated_at
  - indexes: (course_id, assignment_id), status
  ```

- [x] `grading_results` - Resultados de calificaciones
  ```php
  - id
  - grading_job_id (FK)
  - submission_id
  - student_id
  - assignment_id
  - course_id
  - rubric_assessment (JSON)
  - total_score
  - max_score
  - feedback (TEXT)
  - approved_by
  - submitted_to_canvas (boolean)
  - submitted_at
  - created_at, updated_at
  - indexes: (course_id, assignment_id), student_id, submitted_to_canvas
  ```

### 5.2 Modelos Eloquent (Pendiente para Fase 2)
- [ ] `GradingJob.php`
- [ ] `GradingResult.php`

## 6. Configuración ✅

### 6.1 Variables de Entorno ✅
```bash
# Heredadas del chatbot
CANVAS_API_URL=https://canvas.instructure.com/api/v1
CANVAS_API_TOKEN=...
OPENAI_API_KEY=...

# Auto-grading específicas
OPENAI_GRADING_MODEL=gpt-4o
LTI_GRADING_CONSUMER_KEY=auto-grading-key-2024
LTI_GRADING_SHARED_SECRET=autograding-secret-456
```

### 6.2 Config Files ✅
- [x] `config/lti.php` - Credenciales LTI para grading
- [x] `config/services.php` - Modelo GPT-4o para grading

## 7. Integración con OpenAI ✅

### 7.1 Modelo y Configuración ✅
- **Modelo**: GPT-4o (vs gpt-4o-mini del chatbot)
- **Temperatura**: 0.3 (consistencia en evaluaciones)
- **Formato de respuesta**: JSON estructurado

### 7.2 System Prompt ✅
```
You are an expert academic grader. Your task is to evaluate student submissions against a provided rubric.

CRITICAL INSTRUCTIONS:
1. Evaluate OBJECTIVELY based on rubric criteria
2. Assign points strictly according to rubric descriptions
3. Provide SPECIFIC, ACTIONABLE feedback
4. Reference specific parts of the submission
5. Balance constructive criticism with encouragement
6. Use professional, academic language

EVALUATION PROCESS:
- Read the entire submission carefully
- For each criterion, determine the rating level that best matches
- Assign the exact points for that rating
- Explain your decision with evidence from the submission
- Suggest concrete improvements

RESPONSE FORMAT: JSON object with criteria_assessments array
```

### 7.3 User Prompt ✅
```
# Submission to Grade:
{submission_text}

# Rubric:
{rubric_formatted_for_llm}

Evaluate this submission and return a JSON response.
```

### 7.4 JSON Response Schema ✅
```json
{
  "criteria_assessments": [
    {
      "criterion_id": "string",
      "points": number,
      "rating_id": "string",
      "comments": "string"
    }
  ]
}
```

### 7.5 Post-Processing ✅
- [x] `processAssessment()` - Valida estructura JSON
- [x] `formatForCanvas()` - Convierte a formato Canvas rubric_assessment
- [x] `generateCompleteFeedback()` - Genera comentario unificado con scores

## 8. Rutas ✅

```php
// LTI Routes ✅
Route::get('/lti/grading/config.xml', [GradingController::class, 'config']);
Route::post('/lti/grading/launch', [GradingController::class, 'launch']);

// API Routes (protegidas por autenticación) ✅
Route::prefix('api/grading')->group(function () {
    Route::get('/assignments', [GradingController::class, 'getAssignments']);
    Route::get('/assignments/{id}', [GradingController::class, 'getAssignmentDetails']);
    Route::post('/assignments/{id}/grade', [GradingController::class, 'gradeSubmissions']);
    Route::post('/submit-grades', [GradingController::class, 'submitGrades']);
});
```

**Implementado**: Todas las rutas creadas en `routes/web.php` con CSRF exceptions para `/api/grading/*`

## 9. Testing

### 9.1 Testing Manual Pendiente
- [ ] Test de RubricService con assignment real de Canvas
- [ ] Test de AutoGradingService con submission de prueba
- [ ] Test de flujo completo de calificación
- [ ] Test de envío a Canvas

### 9.2 Integration Tests (Fase 2)
- [ ] `RubricServiceTest.php`
- [ ] `AutoGradingServiceTest.php`
- [ ] `SubmissionServiceTest.php`
- [ ] `GradingControllerTest.php`

## 10. Deploy

### 10.1 Preparación
```bash
# Build frontend assets
npm run build

# Run migrations
php artisan migrate

# Clear Laravel caches
php artisan config:clear
php artisan route:clear
```

### 10.2 Cloud Run Deployment
```bash
# Build image
gcloud builds submit --tag gcr.io/aplicacionesnube-2025/mi-repo-lti/auto-grading:v1

# Deploy to Cloud Run
gcloud run deploy auto-grading-lti \
  --image gcr.io/aplicacionesnube-2025/mi-repo-lti/auto-grading:v1 \
  --region us-central1 \
  --platform managed \
  --allow-unauthenticated \
  --set-env-vars "\
APP_NAME=Auto-Grading-LTI,\
APP_ENV=production,\
APP_KEY=base64:...,\
APP_DEBUG=false,\
APP_URL=https://auto-grading-lti-...,\
LOG_CHANNEL=stack,\
LOG_LEVEL=debug,\
SESSION_DRIVER=cookie,\
SESSION_LIFETIME=120,\
CANVAS_API_URL=https://canvas.instructure.com/api/v1,\
CANVAS_API_TOKEN=...,\
OPENAI_API_KEY=...,\
OPENAI_MODEL=gpt-4o-mini,\
OPENAI_GRADING_MODEL=gpt-4o,\
LTI_CONSUMER_KEY=chatbot-key-2024,\
LTI_SHARED_SECRET=supersecret123,\
LTI_GRADING_CONSUMER_KEY=auto-grading-key-2024,\
LTI_GRADING_SHARED_SECRET=autograding-secret-456"
```

### 10.3 Instalación en Canvas
1. Ir a **Course Settings** → **Apps** → **View App Configurations**
2. Click **+ App**
3. Seleccionar **Configuration Type**: By URL
4. Configurar:
   - **Name**: Auto-Grading con IA
   - **Consumer Key**: `auto-grading-key-2024`
   - **Shared Secret**: `autograding-secret-456`
   - **Config URL**: `https://auto-grading-lti-....run.app/lti/grading/config.xml`
5. Submit

## 11. Consideraciones Importantes

### 11.1 Seguridad ✅
- [x] Validar que solo instructores puedan calificar (implementado en `GradingController::launch()`)
- [x] Verificar OAuth 1.0 signature en LTI launch
- [ ] Rate limiting para prevenir abuso (Fase 2)

### 11.2 Ética y Transparencia
- [x] Calificación asistida por IA (no automática)
- [x] Revisión del instructor antes de enviar (workflow con `submitGrades()`)
- [x] Log de calificaciones en `grading_results` table
- [x] Override manual permitido (edición antes de submit)

### 11.3 Performance
- [x] Límite de 50 submissions por request en `gradeSubmissions()`
- [x] Timeout de 180s en `AutoGradingService::gradeSubmission()`
- [ ] Queue jobs para procesamiento batch (Fase 2)
- [ ] Cache de rúbricas (Fase 2)

### 11.4 UX
- [x] Preview de resultados antes de enviar a Canvas
- [ ] Progress indicators para batch grading (Fase 2)
- [ ] Edición inline de feedback (Fase 2)
- [ ] Bulk actions (Fase 2)

## 12. Roadmap

### Fase 1 - MVP ✅ COMPLETADA
- [x] Setup LTI 1.1 con OAuth 1.0
- [x] RubricService - Integración Canvas API para rúbricas
- [x] SubmissionService - Get/submit submissions y grades
- [x] AutoGradingService - GPT-4o con prompts estructurados
- [x] GradingController - LTI launch + API endpoints
- [x] Database migrations (grading_jobs, grading_results)
- [x] GradingDashboard - React UI con stats y assignment list
- [x] Routes configuradas
- [x] Frontend build exitoso

### Fase 2 - Testing y Deploy (Próximo)
- [ ] Testing manual con assignment real de Canvas
- [ ] Deploy a Cloud Run con env vars
- [ ] Instalación en Canvas como External Tool
- [ ] End-to-end test de flujo completo

### Fase 3 - Polish (Futuro)
- [ ] Eloquent models (GradingJob, GradingResult)
- [ ] Queue jobs para batch processing
- [ ] Progress indicators en UI
- [ ] Edición inline de feedback
- [ ] Bulk actions (aprobar todas, rechazar)
- [ ] Analytics dashboard

### Fase 4 - Advanced Features (Futuro)
- [ ] Detección de similitudes entre submissions
- [ ] Historial de calificaciones por estudiante
- [ ] Exportar reportes de calificación
- [ ] Integración con rubrics templates
- [ ] Multi-language support

## 13. Diferencias con Chatbot

| Aspecto | Chatbot | Auto-Grading |
|---------|---------|--------------|
| **Propósito** | Consultas/Ayuda estudiantes | Evaluación asistida por IA |
| **Usuario Principal** | Estudiantes | Instructores/TAs |
| **Canvas APIs** | Read-only (modules, pages, assignments) | Read/Write (submissions, grades, rubrics) |
| **LTI Placement** | course_navigation | course_navigation (sidebar) |
| **LTI Credentials** | chatbot-key-2024 | auto-grading-key-2024 |
| **OpenAI Model** | gpt-4o-mini (conversacional) | gpt-4o (structured JSON grading) |
| **OpenAI Temperature** | 0.7 (creativo) | 0.3 (consistente) |
| **Persistencia** | Opcional (history in session) | Requerida (grading_jobs, grading_results) |
| **Permisos** | Cualquier rol | Solo Instructors (validado en launch) |
| **Workflow** | Request-response inmediato | Batch grading → Review → Submit |
| **Database** | No requiere | Migrations para job tracking |
| **Frontend** | Chatbot.tsx (chat UI) | GradingDashboard.tsx (assignment list + stats) |

## 14. Config.xml (LTI Configuration)

El archivo de configuración XML se genera dinámicamente en `GradingController::config()`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<cartridge_basiclti_link xmlns="http://www.imsglobal.org/xsd/imslticc_v1p0"
    xmlns:blti="http://www.imsglobal.org/xsd/imsbasiclti_v1p0"
    xmlns:lticm="http://www.imsglobal.org/xsd/imslticm_v1p0"
    xmlns:lticp="http://www.imsglobal.org/xsd/imslticp_v1p0"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="...">
    
    <blti:title>Auto-Grading con IA</blti:title>
    <blti:description>
        Herramienta de calificación asistida por IA usando rúbricas inteligentes.
        Evalúa ensayos y proporciona retroalimentación detallada.
    </blti:description>
    <blti:launch_url>{APP_URL}/lti/grading/launch</blti:launch_url>
    <blti:icon>{APP_URL}/grading-icon.png</blti:icon>
    
    <blti:extensions platform="canvas.instructure.com">
        <lticm:property name="privacy_level">public</lticm:property>
        <lticm:property name="domain">{APP_DOMAIN}</lticm:property>
        
        <lticm:options name="course_navigation">
            <lticm:property name="enabled">true</lticm:property>
            <lticm:property name="text">Auto-Grading IA</lticm:property>
            <lticm:property name="visibility">admins</lticm:property>
            <lticm:property name="default">enabled</lticm:property>
        </lticm:options>
    </blti:extensions>
</cartridge_basiclti_link>
```

**Características clave:**
- Placement: `course_navigation` (aparece en sidebar del curso)
- Visibility: `admins` (solo visible para instructores)
- Launch URL: Dinámico basado en `config('app.url')`
- Privacy level: `public` (envía user_id, roles, course_id a la tool)
