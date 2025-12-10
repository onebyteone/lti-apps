<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SubmissionService
{
    private string $apiUrl;
    private string $apiToken;

    public function __construct()
    {
        $this->apiUrl = config('services.canvas.api_url');
        $this->apiToken = config('services.canvas.api_token');
        
        Log::info('SubmissionService initialized', [
            'api_url' => $this->apiUrl,
            'has_token' => !empty($this->apiToken),
        ]);
    }

    /**
     * Obtener todas las entregas de una asignación
     */
    public function getSubmissions(string $courseId, string $assignmentId, array $filters = []): array
    {
        try {
            $params = [
                'include[]' => ['user', 'submission_comments', 'rubric_assessment'],
                'per_page' => 100,
            ];

            // Filtrar por estado si se especifica
            if (isset($filters['workflow_state'])) {
                $params['workflow_state'] = $filters['workflow_state'];
            }

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiToken}",
            ])->get("{$this->apiUrl}/courses/{$courseId}/assignments/{$assignmentId}/submissions", $params);

            if ($response->successful()) {
                $submissions = $response->json();
                
                // Filtrar solo submissions que necesitan calificación
                if (isset($filters['needs_grading']) && $filters['needs_grading']) {
                    $submissions = array_filter($submissions, function($sub) {
                        return ($sub['workflow_state'] === 'submitted' || $sub['workflow_state'] === 'pending_review') 
                            && empty($sub['grade']);
                    });
                }
                
                return array_values($submissions);
            }

            Log::error('Failed to fetch submissions', [
                'course_id' => $courseId,
                'assignment_id' => $assignmentId,
                'status' => $response->status(),
                'response_body' => $response->body(),
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('Error fetching submissions', [
                'course_id' => $courseId,
                'assignment_id' => $assignmentId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Obtener entrega específica de un estudiante
     */
    public function getSubmission(string $courseId, string $assignmentId, string $userId): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiToken}",
            ])->get("{$this->apiUrl}/courses/{$courseId}/assignments/{$assignmentId}/submissions/{$userId}", [
                'include[]' => ['user', 'submission_comments', 'rubric_assessment'],
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to fetch submission', [
                'course_id' => $courseId,
                'assignment_id' => $assignmentId,
                'user_id' => $userId,
                'status' => $response->status(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error fetching submission', [
                'course_id' => $courseId,
                'assignment_id' => $assignmentId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Extraer texto del contenido de una entrega
     */
    public function extractSubmissionText(array $submission): string
    {
        $text = '';

        // Online text entry
        if (!empty($submission['body'])) {
            $text = strip_tags($submission['body']);
        }
        
        // URL submission
        elseif (!empty($submission['url'])) {
            $text = "URL de entrega: {$submission['url']}";
        }
        
        // File upload - retornar mensaje indicando que es archivo
        elseif (!empty($submission['attachments'])) {
            $files = array_map(fn($att) => $att['filename'] ?? 'archivo', $submission['attachments']);
            $text = "Archivos adjuntos: " . implode(', ', $files);
        }

        return trim($text);
    }

    /**
     * Enviar calificación a Canvas
     */
    public function submitGrade(
        string $courseId, 
        string $assignmentId, 
        string $userId, 
        float $score, 
        string $feedback = '',
        array $rubricAssessment = []
    ): bool {
        try {
            $data = [
                'submission' => [
                    'posted_grade' => $score,
                ],
            ];

            // Agregar comentario de feedback si existe
            if (!empty($feedback)) {
                $data['comment'] = [
                    'text_comment' => $feedback,
                ];
            }

            // Agregar evaluación de rúbrica si existe
            if (!empty($rubricAssessment)) {
                $data['rubric_assessment'] = $rubricAssessment;
            }

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiToken}",
            ])->put(
                "{$this->apiUrl}/courses/{$courseId}/assignments/{$assignmentId}/submissions/{$userId}",
                $data
            );

            if ($response->successful()) {
                Log::info('Grade submitted successfully', [
                    'course_id' => $courseId,
                    'assignment_id' => $assignmentId,
                    'user_id' => $userId,
                    'score' => $score,
                ]);
                return true;
            }

            Log::error('Failed to submit grade', [
                'course_id' => $courseId,
                'assignment_id' => $assignmentId,
                'user_id' => $userId,
                'status' => $response->status(),
                'response_body' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Error submitting grade', [
                'course_id' => $courseId,
                'assignment_id' => $assignmentId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Agregar comentario a una entrega
     */
    public function addComment(
        string $courseId, 
        string $assignmentId, 
        string $userId, 
        string $comment
    ): bool {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiToken}",
            ])->put(
                "{$this->apiUrl}/courses/{$courseId}/assignments/{$assignmentId}/submissions/{$userId}",
                [
                    'comment' => [
                        'text_comment' => $comment,
                    ],
                ]
            );

            if ($response->successful()) {
                return true;
            }

            Log::error('Failed to add comment', [
                'course_id' => $courseId,
                'assignment_id' => $assignmentId,
                'user_id' => $userId,
                'status' => $response->status(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Error adding comment', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Obtener información de la asignación
     */
    public function getAssignment(string $courseId, string $assignmentId): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiToken}",
            ])->get("{$this->apiUrl}/courses/{$courseId}/assignments/{$assignmentId}", [
                'include[]' => ['rubric', 'rubric_settings'],
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to fetch assignment', [
                'course_id' => $courseId,
                'assignment_id' => $assignmentId,
                'status' => $response->status(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error fetching assignment', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Obtener estadísticas de entregas
     */
    public function getSubmissionStats(string $courseId, string $assignmentId): array
    {
        $submissions = $this->getSubmissions($courseId, $assignmentId);
        
        $stats = [
            'total' => count($submissions),
            'submitted' => 0,
            'graded' => 0,
            'needs_grading' => 0,
            'late' => 0,
        ];

        foreach ($submissions as $sub) {
            if ($sub['workflow_state'] === 'submitted' || $sub['workflow_state'] === 'graded') {
                $stats['submitted']++;
            }

            if (!empty($sub['grade']) || !empty($sub['score'])) {
                $stats['graded']++;
            } else if ($sub['workflow_state'] === 'submitted') {
                $stats['needs_grading']++;
            }

            if ($sub['late'] ?? false) {
                $stats['late']++;
            }
        }

        return $stats;
    }
}
