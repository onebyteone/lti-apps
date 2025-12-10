<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RubricService
{
    private string $apiUrl;
    private string $apiToken;

    public function __construct()
    {
        $this->apiUrl = config('services.canvas.api_url');
        $this->apiToken = config('services.canvas.api_token');
        
        Log::info('RubricService initialized', [
            'api_url' => $this->apiUrl,
            'has_token' => !empty($this->apiToken),
        ]);
    }

    /**
     * Obtener rúbrica de una asignación
     */
    public function getAssignmentRubric(string $courseId, string $assignmentId): ?array
    {
        try {
            // Primero obtener la asignación con su rúbrica
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiToken}",
            ])->get("{$this->apiUrl}/courses/{$courseId}/assignments/{$assignmentId}", [
                'include[]' => 'rubric',
            ]);

            if ($response->successful()) {
                $assignment = $response->json();
                
                if (isset($assignment['rubric'])) {
                    return [
                        'rubric' => $assignment['rubric'],
                        'rubric_settings' => $assignment['rubric_settings'] ?? [],
                        'use_rubric_for_grading' => $assignment['use_rubric_for_grading'] ?? false,
                    ];
                }
                
                Log::warning('Assignment has no rubric', [
                    'course_id' => $courseId,
                    'assignment_id' => $assignmentId,
                ]);
                return null;
            }

            Log::error('Failed to fetch assignment rubric', [
                'course_id' => $courseId,
                'assignment_id' => $assignmentId,
                'status' => $response->status(),
                'response_body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error fetching assignment rubric', [
                'course_id' => $courseId,
                'assignment_id' => $assignmentId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Parsear criterios de rúbrica para formato legible
     */
    public function parseCriteria(array $rubric): array
    {
        $criteria = [];
        
        foreach ($rubric as $criterion) {
            $ratings = [];
            
            // Ordenar ratings por puntos (descendente)
            $criterionRatings = $criterion['ratings'] ?? [];
            usort($criterionRatings, function($a, $b) {
                return ($b['points'] ?? 0) <=> ($a['points'] ?? 0);
            });
            
            foreach ($criterionRatings as $rating) {
                $ratings[] = [
                    'id' => $rating['id'] ?? null,
                    'description' => $rating['description'] ?? '',
                    'long_description' => $rating['long_description'] ?? '',
                    'points' => $rating['points'] ?? 0,
                ];
            }
            
            $criteria[] = [
                'id' => $criterion['id'],
                'description' => $criterion['description'] ?? '',
                'long_description' => $criterion['long_description'] ?? '',
                'points' => $criterion['points'] ?? 0,
                'ratings' => $ratings,
            ];
        }
        
        return $criteria;
    }

    /**
     * Formatear rúbrica para prompt de LLM
     */
    public function formatForLLM(array $criteria): string
    {
        $formatted = "RÚBRICA DE EVALUACIÓN:\n\n";
        
        foreach ($criteria as $index => $criterion) {
            $criterionNum = $index + 1;
            $formatted .= "Criterio {$criterionNum}: {$criterion['description']}\n";
            
            if (!empty($criterion['long_description'])) {
                $formatted .= "Descripción: {$criterion['long_description']}\n";
            }
            
            $formatted .= "Puntuación máxima: {$criterion['points']}\n";
            $formatted .= "Niveles de desempeño:\n";
            
            foreach ($criterion['ratings'] as $rating) {
                $formatted .= "  - {$rating['description']} ({$rating['points']} puntos)";
                
                if (!empty($rating['long_description'])) {
                    $formatted .= ": {$rating['long_description']}";
                }
                
                $formatted .= "\n";
            }
            
            $formatted .= "\n";
        }
        
        return $formatted;
    }

    /**
     * Validar estructura de rúbrica
     */
    public function validateRubric(array $rubric): bool
    {
        if (empty($rubric)) {
            return false;
        }

        foreach ($rubric as $criterion) {
            // Verificar que tenga criterios válidos
            if (empty($criterion['id']) || empty($criterion['ratings'])) {
                return false;
            }

            // Verificar que tenga al menos un rating
            if (count($criterion['ratings']) === 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calcular puntuación total de la rúbrica
     */
    public function getTotalPoints(array $criteria): float
    {
        $total = 0;
        
        foreach ($criteria as $criterion) {
            $total += $criterion['points'] ?? 0;
        }
        
        return $total;
    }

    /**
     * Crear una nueva rúbrica en el curso
     * 
     * @param string $courseId
     * @param array $rubricData [
     *   'title' => string,
     *   'criteria' => [
     *     [
     *       'description' => string,
     *       'points' => float,
     *       'ratings' => [
     *         ['description' => string, 'points' => float],
     *         ...
     *       ]
     *     ],
     *     ...
     *   ],
     *   'free_form_criterion_comments' => bool (optional),
     * ]
     * @return array|null
     */
    public function createRubric(string $courseId, array $rubricData): ?array
    {
        try {
            // Construir el payload según el formato de Canvas API
            $payload = [
                'rubric' => [
                    'title' => $rubricData['title'],
                    'free_form_criterion_comments' => $rubricData['free_form_criterion_comments'] ?? true,
                ],
            ];

            // Agregar criterios
            foreach ($rubricData['criteria'] as $index => $criterion) {
                $criterionKey = "rubric[criteria][{$index}]";
                
                $payload[$criterionKey . '[description]'] = $criterion['description'];
                $payload[$criterionKey . '[points]'] = $criterion['points'];
                
                // Agregar ratings para cada criterio
                if (isset($criterion['ratings'])) {
                    foreach ($criterion['ratings'] as $ratingIndex => $rating) {
                        $ratingKey = $criterionKey . "[ratings][{$ratingIndex}]";
                        $payload[$ratingKey . '[description]'] = $rating['description'];
                        $payload[$ratingKey . '[points]'] = $rating['points'];
                    }
                }
            }

            Log::info('Creating rubric', [
                'course_id' => $courseId,
                'title' => $rubricData['title'],
                'criteria_count' => count($rubricData['criteria']),
            ]);

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiToken}",
            ])->asForm()->post("{$this->apiUrl}/courses/{$courseId}/rubrics", $payload);

            if ($response->successful()) {
                $rubric = $response->json();
                
                Log::info('Rubric created successfully', [
                    'rubric_id' => $rubric['id'] ?? null,
                    'title' => $rubric['title'] ?? null,
                ]);
                
                return $rubric;
            }

            Log::error('Failed to create rubric', [
                'course_id' => $courseId,
                'status' => $response->status(),
                'response_body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error creating rubric', [
                'course_id' => $courseId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Asociar rúbrica existente a una asignación
     */
    public function associateRubricToAssignment(string $courseId, string $assignmentId, string $rubricId, array $options = []): bool
    {
        try {
            $payload = [
                'rubric_association' => [
                    'rubric_id' => $rubricId,
                    'association_id' => $assignmentId,
                    'association_type' => 'Assignment',
                    'purpose' => $options['purpose'] ?? 'grading',
                    'use_for_grading' => $options['use_for_grading'] ?? true,
                ],
            ];

            Log::info('Associating rubric to assignment', [
                'course_id' => $courseId,
                'assignment_id' => $assignmentId,
                'rubric_id' => $rubricId,
            ]);

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiToken}",
            ])->asForm()->post("{$this->apiUrl}/courses/{$courseId}/rubric_associations", $payload);

            if ($response->successful()) {
                Log::info('Rubric associated successfully');
                return true;
            }

            Log::error('Failed to associate rubric', [
                'status' => $response->status(),
                'response_body' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Error associating rubric', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Crear rúbrica y asociarla a una asignación en un solo paso
     */
    public function createAndAssociateRubric(string $courseId, string $assignmentId, array $rubricData): ?array
    {
        $rubric = $this->createRubric($courseId, $rubricData);
        
        if ($rubric && isset($rubric['id'])) {
            $associated = $this->associateRubricToAssignment($courseId, $assignmentId, (string)$rubric['id']);
            
            if ($associated) {
                return $rubric;
            }
            
            Log::warning('Rubric created but failed to associate', [
                'rubric_id' => $rubric['id'],
            ]);
        }
        
        return null;
    }

    /**
     * Obtener todas las rúbricas de un curso
     */
    public function getCourseRubrics(string $courseId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiToken}",
            ])->get("{$this->apiUrl}/courses/{$courseId}/rubrics");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to fetch course rubrics', [
                'course_id' => $courseId,
                'status' => $response->status(),
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('Error fetching course rubrics', [
                'course_id' => $courseId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}
