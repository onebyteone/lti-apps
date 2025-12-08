# Pasos para Probar el Chatbot Localmente

## 1. Configurar OpenAI API Key

Crea un archivo `.env` basado en `.env.example`:

```bash
cp .env.example .env
```

Edita `.env` y añade tu API key de OpenAI:

```env
OPENAI_API_KEY=sk-tu-clave-api-aqui
OPENAI_MODEL=gpt-4o-mini
```

## 2. Ejecutar Migraciones

```bash
php artisan migrate
```

## 3. Iniciar el Servidor

En una terminal:

```bash
php artisan serve
```

En otra terminal:

```bash
npm run dev
```

## 4. Probar sin Canvas (Testing Local)

### Opción A: Crear una Ruta de Prueba

Añade esto a `routes/web.php` temporalmente:

```php
Route::get('/test-chatbot', function () {
    return Inertia::render('chatbot', [
        'user' => [
            'user_id' => '12345',
            'name' => 'Usuario de Prueba',
            'email' => 'test@example.com',
            'role' => 'Student',
            'course_id' => 'TEST-101',
        ],
        'isInstructor' => false,
    ]);
});
```

Visita: `http://localhost:8000/test-chatbot`

### Opción B: Simular LTI Launch

Usa Postman o cURL para simular un POST de Canvas:

```bash
curl -X POST http://localhost:8000/lti/launch \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "custom_canvas_user_id=12345" \
  -d "lis_person_name_full=Juan Pérez" \
  -d "lis_person_contact_email_primary=juan@example.com" \
  -d "roles=Student" \
  -d "custom_canvas_course_id=COURSE-101"
```

## 5. Cargar Contexto del Curso

Para que el chatbot tenga información sobre el curso:

```bash
curl -X POST http://localhost:8000/chatbot/context \
  -H "Content-Type: application/json" \
  -d '{
    "course_id": "TEST-101",
    "context_type": "syllabus",
    "content": "Este curso cubre introducción a la programación. Temas: variables, funciones, POO. Evaluación: 3 exámenes (60%), tareas (30%), participación (10%).",
    "metadata": {
      "title": "Syllabus",
      "date": "2025-01-01"
    }
  }'
```

## 6. Testear el Chatbot

Una vez cargado el contexto, prueba preguntas como:

- "¿Cuáles son los temas del curso?"
- "¿Cómo se evalúa el curso?"
- "¿Cuántos exámenes hay?"
- "¿Qué porcentaje vale la participación?"

## 7. Verificar Base de Datos

```bash
php artisan tinker

# Ver conversaciones
>>> \App\Models\Conversation::with('messages')->get();

# Ver contextos del curso
>>> \App\Models\CourseContext::all();
```

## 8. Debugging

Si hay errores, revisa los logs:

```bash
tail -f storage/logs/laravel.log
```

## 9. Probar con Canvas (Desarrollo)

Para probar con Canvas necesitarás exponer tu servidor local:

### Usando ngrok

```bash
# Instalar ngrok
brew install ngrok  # macOS
# o descarga de https://ngrok.com/download

# Exponer puerto 8000
ngrok http 8000
```

Ngrok te dará una URL como `https://abc123.ngrok.io`. Usa esta URL en la configuración LTI de Canvas.

### Actualizar lti-config.json

Reemplaza `tu-dominio.com` con tu URL de ngrok:

```json
{
  "oidc_initiation_url": "https://abc123.ngrok.io/lti/login",
  "target_link_uri": "https://abc123.ngrok.io/lti/launch",
  ...
}
```

## 10. Ejemplos de Uso

### Estudiante preguntando:

**Usuario:** "¿Qué temas vamos a ver en este curso?"

**Chatbot:** "Según el syllabus del curso, los temas principales son:
1. Variables y tipos de datos
2. Funciones
3. Programación Orientada a Objetos (POO)

¿Te gustaría que profundice en algún tema específico?"

### Estudiante preguntando sobre evaluación:

**Usuario:** "¿Cómo se calcula mi nota final?"

**Chatbot:** "Tu nota final se calcula de la siguiente manera:
- Exámenes (3 en total): 60%
- Tareas: 30%
- Participación: 10%

Es importante mantener un buen rendimiento en todas las áreas para una nota óptima."

## 🎉 ¡Listo!

Ya puedes probar el chatbot localmente. Para desplegar en producción, sigue las instrucciones en `DEPLOYMENT.md`.
