<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CanvasService
{
    private string $apiUrl;
    private string $apiToken;

    public function __construct()
    {
        $this->apiUrl = config('services.canvas.api_url');
        $this->apiToken = config('services.canvas.api_token');
        
        Log::info('CanvasService initialized', [
            'api_url' => $this->apiUrl,
            'has_token' => !empty($this->apiToken),
            'token_length' => $this->apiToken ? strlen($this->apiToken) : 0,
        ]);
    }

    /**
     * Obtener módulos del curso
     */
    public function getCourseModules(string $courseId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiToken}",
            ])->get("{$this->apiUrl}/courses/{$courseId}/modules", [
                'include[]' => 'items',
                'per_page' => 100,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to fetch course modules', [
                'course_id' => $courseId,
                'status' => $response->status(),
                'response_body' => $response->body(),
                'request_url' => "{$this->apiUrl}/courses/{$courseId}/modules",
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('Error fetching course modules', [
                'course_id' => $courseId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Obtener páginas del curso
     */
    public function getCoursePages(string $courseId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiToken}",
            ])->get("{$this->apiUrl}/courses/{$courseId}/pages", [
                'per_page' => 100,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to fetch course pages', [
                'course_id' => $courseId,
                'status' => $response->status(),
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('Error fetching course pages', [
                'course_id' => $courseId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Obtener asignaciones del curso
     */
    public function getCourseAssignments(string $courseId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiToken}",
            ])->get("{$this->apiUrl}/courses/{$courseId}/assignments", [
                'per_page' => 100,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to fetch course assignments', [
                'course_id' => $courseId,
                'status' => $response->status(),
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('Error fetching course assignments', [
                'course_id' => $courseId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Obtener información del curso
     */
    public function getCourse(string $courseId): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiToken}",
            ])->get("{$this->apiUrl}/courses/{$courseId}");

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error fetching course', [
                'course_id' => $courseId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Construir contexto del curso para el chatbot
     */
    public function buildCourseContext(string $courseId): string
    {
        $course = $this->getCourse($courseId);
        $modules = $this->getCourseModules($courseId);
        $assignments = $this->getCourseAssignments($courseId);
        $pages = $this->getCoursePages($courseId);

        $context = "Información del curso:\n\n";

        // Info básica del curso
        if ($course) {
            $context .= "Nombre: {$course['name']}\n";
            if (isset($course['course_code'])) {
                $context .= "Código: {$course['course_code']}\n";
            }
            $context .= "\n";
        }

        // Módulos
        if (!empty($modules)) {
            $context .= "Módulos del curso:\n";
            foreach ($modules as $module) {
                $context .= "- {$module['name']}\n";
                if (isset($module['items']) && is_array($module['items'])) {
                    foreach ($module['items'] as $item) {
                        $context .= "  • {$item['title']}\n";
                    }
                }
            }
            $context .= "\n";
        }

        // Asignaciones
        if (!empty($assignments)) {
            $context .= "Asignaciones:\n";
            foreach (array_slice($assignments, 0, 20) as $assignment) {
                $context .= "- {$assignment['name']}";
                if (isset($assignment['due_at'])) {
                    $context .= " (Entrega: {$assignment['due_at']})";
                }
                if (isset($assignment['points_possible'])) {
                    $context .= " - {$assignment['points_possible']} puntos";
                }
                $context .= "\n";
            }
            $context .= "\n";
        }

        // Páginas
        if (!empty($pages)) {
            $context .= "Páginas del curso:\n";
            foreach (array_slice($pages, 0, 15) as $page) {
                $context .= "- {$page['title']}\n";
            }
        }

        return $context;
    }
}
