# API de Rúbricas - Ejemplos de Uso

## 1. Crear Rúbrica (sin asociar)

```bash
curl -X POST https://autograding-test-service-713107332501.us-central1.run.app/api/grading/rubrics \
  -H "Content-Type: application/json" \
  -d '{
    "course_id": "12564766",
    "title": "Ensayo Argumentativo",
    "free_form_criterion_comments": true,
    "criteria": [
      {
        "description": "Tesis y Argumentación",
        "points": 25,
        "ratings": [
          {
            "description": "Excelente: Tesis clara, argumentos sólidos y bien fundamentados",
            "points": 25
          },
          {
            "description": "Bueno: Tesis presente, argumentos consistentes pero con mejoras posibles",
            "points": 20
          },
          {
            "description": "Suficiente: Tesis débil o argumentos poco desarrollados",
            "points": 15
          },
          {
            "description": "Insuficiente: Falta tesis o argumentos poco coherentes",
            "points": 10
          }
        ]
      },
      {
        "description": "Estructura y Organización",
        "points": 20,
        "ratings": [
          {
            "description": "Excelente: Estructura lógica, transiciones fluidas",
            "points": 20
          },
          {
            "description": "Bueno: Estructura clara con algunas transiciones abruptas",
            "points": 15
          },
          {
            "description": "Suficiente: Estructura presente pero poco cohesiva",
            "points": 10
          },
          {
            "description": "Insuficiente: Estructura desorganizada",
            "points": 5
          }
        ]
      },
      {
        "description": "Uso de Fuentes",
        "points": 20,
        "ratings": [
          {
            "description": "Excelente: Fuentes académicas relevantes, bien citadas",
            "points": 20
          },
          {
            "description": "Bueno: Fuentes confiables con citación correcta",
            "points": 15
          },
          {
            "description": "Suficiente: Pocas fuentes o citación inconsistente",
            "points": 10
          },
          {
            "description": "Insuficiente: Sin fuentes o citación incorrecta",
            "points": 5
          }
        ]
      },
      {
        "description": "Gramática y Estilo",
        "points": 15,
        "ratings": [
          {
            "description": "Excelente: Sin errores, estilo académico apropiado",
            "points": 15
          },
          {
            "description": "Bueno: Errores mínimos que no afectan comprensión",
            "points": 12
          },
          {
            "description": "Suficiente: Varios errores gramaticales o estilo informal",
            "points": 8
          },
          {
            "description": "Insuficiente: Múltiples errores que dificultan comprensión",
            "points": 4
          }
        ]
      },
      {
        "description": "Análisis Crítico",
        "points": 20,
        "ratings": [
          {
            "description": "Excelente: Análisis profundo con perspectivas múltiples",
            "points": 20
          },
          {
            "description": "Bueno: Análisis presente con algunas perspectivas",
            "points": 15
          },
          {
            "description": "Suficiente: Análisis superficial o unidimensional",
            "points": 10
          },
          {
            "description": "Insuficiente: Sin análisis crítico evidente",
            "points": 5
          }
        ]
      }
    ]
  }'
```

**Respuesta esperada:**
```json
{
  "success": true,
  "rubric": {
    "id": "123456",
    "title": "Ensayo Argumentativo",
    "context_id": "YOUR_COURSE_ID",
    "context_type": "Course",
    "points_possible": 100,
    "free_form_criterion_comments": true,
    "criteria": [...]
  }
}
```

---

## 2. Asociar Rúbrica Existente a Asignación

```bash
curl -X POST https://autograding-test-service-713107332501.us-central1.run.app/api/grading/assignments/ASSIGNMENT_ID/rubric \
  -H "Content-Type: application/json" \
  -d '{
    "course_id": "YOUR_COURSE_ID",
    "rubric_id": "123456",
    "use_for_grading": true
  }'
```

**Respuesta:**
```json
{
  "success": true,
  "message": "Rubric associated successfully"
}
```

---

## 3. Crear y Asociar Rúbrica en un Paso

```bash
curl -X POST https://autograding-test-service-713107332501.us-central1.run.app/api/grading/assignments/ASSIGNMENT_ID/rubric/create \
  -H "Content-Type: application/json" \
  -d '{
    "course_id": "YOUR_COURSE_ID",
    "title": "Proyecto Final - Análisis de Datos",
    "criteria": [
      {
        "description": "Calidad del Código",
        "points": 30,
        "ratings": [
          {
            "description": "Excelente: Código limpio, bien documentado, sigue best practices",
            "points": 30
          },
          {
            "description": "Bueno: Código funcional con documentación adecuada",
            "points": 24
          },
          {
            "description": "Suficiente: Código funciona pero poco documentado",
            "points": 18
          },
          {
            "description": "Insuficiente: Código con errores o sin documentación",
            "points": 10
          }
        ]
      },
      {
        "description": "Análisis de Resultados",
        "points": 40,
        "ratings": [
          {
            "description": "Excelente: Análisis profundo con visualizaciones claras",
            "points": 40
          },
          {
            "description": "Bueno: Análisis correcto con visualizaciones básicas",
            "points": 32
          },
          {
            "description": "Suficiente: Análisis superficial",
            "points": 24
          },
          {
            "description": "Insuficiente: Análisis incorrecto o ausente",
            "points": 15
          }
        ]
      },
      {
        "description": "Presentación",
        "points": 30,
        "ratings": [
          {
            "description": "Excelente: Presentación profesional, bien estructurada",
            "points": 30
          },
          {
            "description": "Bueno: Presentación clara y organizada",
            "points": 24
          },
          {
            "description": "Suficiente: Presentación básica",
            "points": 18
          },
          {
            "description": "Insuficiente: Presentación desorganizada",
            "points": 10
          }
        ]
      }
    ]
  }'
```

---

## 4. Ejemplo con PHP/Laravel (desde tinker o script)

```php
use Illuminate\Support\Facades\Http;

$courseId = 'YOUR_COURSE_ID';
$assignmentId = 'YOUR_ASSIGNMENT_ID';

$response = Http::post('https://autograding-test-service-713107332501.us-central1.run.app/api/grading/assignments/' . $assignmentId . '/rubric/create', [
    'course_id' => $courseId,
    'title' => 'Rúbrica de Ensayo',
    'criteria' => [
        [
            'description' => 'Contenido',
            'points' => 50,
            'ratings' => [
                ['description' => 'Excelente', 'points' => 50],
                ['description' => 'Bueno', 'points' => 40],
                ['description' => 'Regular', 'points' => 30],
                ['description' => 'Insuficiente', 'points' => 20],
            ]
        ],
        [
            'description' => 'Formato',
            'points' => 50,
            'ratings' => [
                ['description' => 'Excelente', 'points' => 50],
                ['description' => 'Bueno', 'points' => 40],
                ['description' => 'Regular', 'points' => 30],
                ['description' => 'Insuficiente', 'points' => 20],
            ]
        ],
    ]
]);

$result = $response->json();
```

---

## 5. Ejemplo con JavaScript (desde frontend)

```javascript
const createRubric = async (courseId, assignmentId) => {
  const response = await fetch('/api/grading/assignments/' + assignmentId + '/rubric/create', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({
      course_id: courseId,
      title: 'Mi Rúbrica',
      criteria: [
        {
          description: 'Criterio 1',
          points: 100,
          ratings: [
            { description: 'Excelente', points: 100 },
            { description: 'Bueno', points: 80 },
            { description: 'Regular', points: 60 },
            { description: 'Deficiente', points: 40 }
          ]
        }
      ]
    })
  });
  
  return await response.json();
};
```

---

## Notas Importantes

### Formato de Ratings
- Cada criterio debe tener **al menos 1 rating**
- Los ratings deben ordenarse de mayor a menor puntos (generalmente)
- Canvas permite hasta 5 niveles de rating por criterio típicamente

### Points
- `criteria[].points`: Puntos máximos del criterio
- `criteria[].ratings[].points`: Puntos de cada nivel
- La suma de `criteria[].points` = Puntos totales de la rúbrica

### Validaciones
- `title`: Requerido, máximo 255 caracteres
- `criteria`: Arreglo con al menos 1 elemento
- `criteria[].description`: Requerido
- `criteria[].points`: Número >= 0
- `criteria[].ratings`: Arreglo con al menos 1 elemento

### Permisos Necesarios
- El API token de Canvas debe tener permisos de **instructor** o **admin**
- Solo usuarios con rol de instructor pueden crear rúbricas en Canvas
