<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AutoGradingService
{
    private string $apiKey;
    private string $model;
    private RubricService $rubricService;
    private SubmissionService $submissionService;

    public function __construct(
        RubricService $rubricService,
        SubmissionService $submissionService
    ) {
        $this->apiKey = config('services.openai.api_key');
        $this->model = config('services.openai.grading_model', 'gpt-4o');
        $this->rubricService = $rubricService;
        $this->submissionService = $submissionService;
        
        Log::info('AutoGradingService initialized', [
            'has_api_key' => !empty($this->apiKey),
            'model' => $this->model,
        ]);
    }

    /**
     * Calificar una entrega usando la rúbrica
     */
    public function gradeSubmission(
        string $submissionText, 
        array $rubricCriteria, 
        array $assignmentContext = []
    ): array {
        try {
            // Construir prompt del sistema
            $systemPrompt = $this->buildSystemPrompt();
            
            // Construir prompt del usuario con rúbrica y submission
            $userPrompt = $this->buildUserPrompt($submissionText, $rubricCriteria, $assignmentContext);
            
            // Llamar a OpenAI API
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt,
                    ],
                    [
                        'role' => 'user',
                        'content' => $userPrompt,
                    ],
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.3,
                'max_tokens' => 2000,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? '{}';
                $assessment = json_decode($content, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('Failed to parse OpenAI response as JSON', [
                        'content' => $content,
                        'error' => json_last_error_msg(),
                    ]);
                    return $this->errorResponse('Error al procesar la respuesta del sistema de calificación.');
                }

                // Validar y procesar la evaluación
                $processedAssessment = $this->processAssessment($assessment, $rubricCriteria);

                return [
                    'success' => true,
                    'assessment' => $processedAssessment,
                    'usage' => $data['usage'] ?? null,
                ];
            }

            Log::error('OpenAI API error during grading', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return $this->errorResponse('Error al comunicarse con el sistema de calificación.');

        } catch (\Exception $e) {
            Log::error('Error in gradeSubmission', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse('Error inesperado durante la calificación: ' . $e->getMessage());
        }
    }

    /**
     * Construir prompt del sistema
     */
    private function buildSystemPrompt(): string
    {
        return <<<PROMPT
Eres un asistente experto en evaluación académica. Tu tarea es calificar trabajos de estudiantes 
siguiendo estrictamente una rúbrica proporcionada.

INSTRUCCIONES:
1. Analiza cuidadosamente el trabajo del estudiante
2. Para cada criterio de la rúbrica:
   - Evalúa el nivel de desempeño alcanzado
   - Asigna la puntuación correspondiente al nivel
   - Proporciona feedback específico, constructivo y detallado
3. Calcula la puntuación total sumando todos los criterios
4. Genera un feedback general que resuma fortalezas y áreas de mejora

FORMATO DE RESPUESTA (JSON):
{
  "total_score": número,
  "max_score": número,
  "criteria_assessments": [
    {
      "criterion_id": "id_del_criterio",
      "criterion_name": "nombre del criterio",
      "rating_id": "id_del_nivel_alcanzado",
      "rating_description": "descripción del nivel",
      "points_awarded": número,
      "max_points": número,
      "feedback": "feedback específico y constructivo"
    }
  ],
  "general_feedback": "resumen general del desempeño",
  "strengths": ["fortaleza 1", "fortaleza 2"],
  "areas_for_improvement": ["área 1", "área 2"]
}

IMPORTANTE:
- Sé objetivo y justo en tu evaluación
- Basa tus decisiones únicamente en la rúbrica proporcionada
- Proporciona feedback constructivo que ayude al estudiante a mejorar
- Si el trabajo no contiene suficiente información para evaluar un criterio, asigna la puntuación mínima
PROMPT;
    }

    /**
     * Construir prompt del usuario
     */
    private function buildUserPrompt(
        string $submissionText, 
        array $rubricCriteria,
        array $assignmentContext
    ): string {
        $prompt = "TRABAJO A EVALUAR:\n\n";
        
        // Agregar contexto de la asignación si existe
        if (!empty($assignmentContext['name'])) {
            $prompt .= "Asignación: {$assignmentContext['name']}\n";
        }
        if (!empty($assignmentContext['description'])) {
            $prompt .= "Descripción: " . strip_tags($assignmentContext['description']) . "\n";
        }
        
        $prompt .= "\n--- TEXTO DEL ESTUDIANTE ---\n";
        $prompt .= $submissionText;
        $prompt .= "\n--- FIN DEL TEXTO ---\n\n";
        
        // Agregar rúbrica formateada
        $prompt .= $this->rubricService->formatForLLM($rubricCriteria);
        
        $prompt .= "\nPor favor evalúa este trabajo según la rúbrica proporcionada y devuelve tu evaluación en el formato JSON especificado.";
        
        return $prompt;
    }

    /**
     * Procesar y validar la evaluación recibida
     */
    private function processAssessment(array $assessment, array $rubricCriteria): array
    {
        // Asegurar que existan los campos necesarios
        $processed = [
            'total_score' => $assessment['total_score'] ?? 0,
            'max_score' => $assessment['max_score'] ?? $this->rubricService->getTotalPoints($rubricCriteria),
            'criteria_assessments' => $assessment['criteria_assessments'] ?? [],
            'general_feedback' => $assessment['general_feedback'] ?? 'Sin feedback general.',
            'strengths' => $assessment['strengths'] ?? [],
            'areas_for_improvement' => $assessment['areas_for_improvement'] ?? [],
        ];

        // Validar que la puntuación total coincida con la suma de criterios
        $calculatedTotal = 0;
        foreach ($processed['criteria_assessments'] as $criterionAssessment) {
            $calculatedTotal += $criterionAssessment['points_awarded'] ?? 0;
        }

        // Ajustar si hay discrepancia
        if (abs($calculatedTotal - $processed['total_score']) > 0.01) {
            Log::warning('Total score mismatch, using calculated total', [
                'reported' => $processed['total_score'],
                'calculated' => $calculatedTotal,
            ]);
            $processed['total_score'] = $calculatedTotal;
        }

        return $processed;
    }

    /**
     * Generar respuesta de error
     */
    private function errorResponse(string $message): array
    {
        return [
            'success' => false,
            'error' => $message,
            'assessment' => null,
        ];
    }

    /**
     * Convertir evaluación a formato de Canvas rubric_assessment
     */
    public function formatForCanvas(array $assessment): array
    {
        $canvasFormat = [];
        
        foreach ($assessment['criteria_assessments'] as $criterionAssessment) {
            $criterionId = $criterionAssessment['criterion_id'];
            
            $canvasFormat[$criterionId] = [
                'points' => $criterionAssessment['points_awarded'],
                'rating_id' => $criterionAssessment['rating_id'] ?? null,
                'comments' => $criterionAssessment['feedback'] ?? '',
            ];
        }
        
        return $canvasFormat;
    }

    /**
     * Generar feedback completo formateado
     */
    public function generateCompleteFeedback(array $assessment): string
    {
        $feedback = "=== EVALUACIÓN AUTOMÁTICA ===\n\n";
        $feedback .= "Puntuación: {$assessment['total_score']}/{$assessment['max_score']}\n\n";
        
        $feedback .= "FEEDBACK GENERAL:\n";
        $feedback .= $assessment['general_feedback'] . "\n\n";
        
        if (!empty($assessment['strengths'])) {
            $feedback .= "FORTALEZAS:\n";
            foreach ($assessment['strengths'] as $strength) {
                $feedback .= "✓ {$strength}\n";
            }
            $feedback .= "\n";
        }
        
        if (!empty($assessment['areas_for_improvement'])) {
            $feedback .= "ÁREAS DE MEJORA:\n";
            foreach ($assessment['areas_for_improvement'] as $area) {
                $feedback .= "• {$area}\n";
            }
            $feedback .= "\n";
        }
        
        $feedback .= "EVALUACIÓN POR CRITERIO:\n";
        foreach ($assessment['criteria_assessments'] as $criterionAssessment) {
            $feedback .= "\n{$criterionAssessment['criterion_name']}: ";
            $feedback .= "{$criterionAssessment['points_awarded']}/{$criterionAssessment['max_points']} pts\n";
            $feedback .= "  → {$criterionAssessment['feedback']}\n";
        }
        
        $feedback .= "\n--- Esta calificación fue generada con asistencia de IA y revisada por el instructor ---";
        
        return $feedback;
    }
}
