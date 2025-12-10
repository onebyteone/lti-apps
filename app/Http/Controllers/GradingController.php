<?php

namespace App\Http\Controllers;

use App\Services\RubricService;
use App\Services\SubmissionService;
use App\Services\AutoGradingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class GradingController extends Controller
{
    private RubricService $rubricService;
    private SubmissionService $submissionService;
    private AutoGradingService $gradingService;

    public function __construct(
        RubricService $rubricService,
        SubmissionService $submissionService,
        AutoGradingService $gradingService
    ) {
        $this->rubricService = $rubricService;
        $this->submissionService = $submissionService;
        $this->gradingService = $gradingService;
    }

    /**
     * LTI Launch para auto-grading
     */
    public function launch(Request $request): Response
    {
        // Validar OAuth (heredado de LtiController)
        $consumerKey = $request->input('oauth_consumer_key');
        $expectedKey = config('lti.grading_consumer_key', config('lti.consumer_key'));
        
        if ($consumerKey !== $expectedKey) {
            abort(403, 'Invalid consumer key');
        }

        // Extraer información del usuario y curso
        $userId = $request->input('user_id');
        $userName = $request->input('lis_person_name_full', 'Usuario');
        $courseId = $request->input('custom_canvas_course_id') ?? $request->input('context_id');
        $roles = $request->input('roles', '');
        
        // Verificar que sea instructor
        $isInstructor = str_contains($roles, 'Instructor') || str_contains($roles, 'Teacher');
        
        if (!$isInstructor) {
            abort(403, 'Solo instructores pueden acceder a la herramienta de calificación automática');
        }

        // Guardar en sesión
        session([
            'lti_user_id' => $userId,
            'lti_course_id' => $courseId,
            'lti_user_name' => $userName,
            'lti_roles' => $roles,
        ]);

        \Log::info('Grading LTI Launch successful', [
            'user' => $userId,
            'course' => $courseId,
        ]);

        // Renderizar dashboard de calificación
        return Inertia::render('GradingDashboard', [
            'user' => [
                'id' => $userId,
                'name' => $userName,
                'isInstructor' => $isInstructor,
            ],
            'course' => [
                'id' => $courseId,
            ],
        ]);
    }

    /**
     * Obtener asignaciones del curso con sus estadísticas
     */
    public function getAssignments(Request $request): JsonResponse
    {
        $courseId = session('lti_course_id') ?? $request->input('course_id');
        
        if (!$courseId) {
            return response()->json(['error' => 'Course ID not found'], 400);
        }

        // Obtener asignaciones desde CanvasService (heredado del chatbot)
        $canvasService = app(\App\Services\CanvasService::class);
        $assignments = $canvasService->getCourseAssignments($courseId);

        // Enriquecer con estadísticas y rúbricas
        $enrichedAssignments = array_map(function($assignment) use ($courseId) {
            $assignmentId = $assignment['id'];
            
            // Obtener estadísticas de submissions
            $stats = $this->submissionService->getSubmissionStats($courseId, $assignmentId);
            
            // Verificar si tiene rúbrica
            $hasRubric = !empty($assignment['rubric']);
            
            return [
                'id' => $assignmentId,
                'name' => $assignment['name'],
                'description' => $assignment['description'] ?? '',
                'due_at' => $assignment['due_at'] ?? null,
                'points_possible' => $assignment['points_possible'] ?? 0,
                'has_rubric' => $hasRubric,
                'use_rubric_for_grading' => $assignment['use_rubric_for_grading'] ?? false,
                'submission_types' => $assignment['submission_types'] ?? [],
                'stats' => $stats,
            ];
        }, $assignments);

        // Filtrar solo asignaciones con rúbrica y submissions pendientes
        $gradableAssignments = array_filter($enrichedAssignments, function($assignment) {
            return $assignment['has_rubric'] && $assignment['stats']['needs_grading'] > 0;
        });

        return response()->json([
            'assignments' => array_values($gradableAssignments),
        ]);
    }

    /**
     * Obtener detalles de una asignación con submissions
     */
    public function getAssignmentDetails(Request $request, string $assignmentId): JsonResponse
    {
        $courseId = session('lti_course_id') ?? $request->input('course_id');
        
        // Obtener asignación
        $assignment = $this->submissionService->getAssignment($courseId, $assignmentId);
        
        if (!$assignment) {
            return response()->json(['error' => 'Assignment not found'], 404);
        }

        // Obtener rúbrica
        $rubricData = $this->rubricService->getAssignmentRubric($courseId, $assignmentId);
        
        if (!$rubricData) {
            return response()->json(['error' => 'Assignment has no rubric'], 400);
        }

        $criteria = $this->rubricService->parseCriteria($rubricData['rubric']);

        // Obtener submissions pendientes
        $submissions = $this->submissionService->getSubmissions($courseId, $assignmentId, [
            'needs_grading' => true,
        ]);

        return response()->json([
            'assignment' => [
                'id' => $assignment['id'],
                'name' => $assignment['name'],
                'description' => $assignment['description'] ?? '',
                'points_possible' => $assignment['points_possible'] ?? 0,
            ],
            'rubric' => [
                'criteria' => $criteria,
                'total_points' => $this->rubricService->getTotalPoints($criteria),
            ],
            'submissions' => array_map(function($sub) {
                return [
                    'id' => $sub['id'],
                    'user_id' => $sub['user_id'],
                    'user_name' => $sub['user']['name'] ?? 'Unknown',
                    'submitted_at' => $sub['submitted_at'] ?? null,
                    'late' => $sub['late'] ?? false,
                    'preview_url' => $sub['preview_url'] ?? null,
                ];
            }, $submissions),
        ]);
    }

    /**
     * Calificar una o más submissions
     */
    public function gradeSubmissions(Request $request, string $assignmentId): JsonResponse
    {
        $validated = $request->validate([
            'course_id' => 'required|string',
        ]);

        $courseId = $validated['course_id'];

        // Obtener assignment y rúbrica
        $rubricData = $this->rubricService->getAssignmentRubric($courseId, $assignmentId);
        
        if (!$rubricData) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment has no rubric',
            ], 400);
        }

        $criteria = $this->rubricService->parseCriteria($rubricData['rubric']);
        
        // Obtener todas las submissions pendientes de calificar
        $submissions = $this->submissionService->getSubmissions($courseId, $assignmentId, [
            'workflow_state' => 'submitted',
        ]);

        if (empty($submissions)) {
            return response()->json([
                'success' => false,
                'message' => 'No submissions found to grade',
            ]);
        }
        
        $results = [];
        $errors = [];

        foreach ($submissions as $submission) {
            try {
                // Extraer texto
                $text = $this->submissionService->extractSubmissionText($submission);
                
                if (empty(trim($text))) {
                    $errors[] = [
                        'submission_id' => $submission['id'],
                        'user_name' => $submission['user']['name'] ?? 'Unknown',
                        'error' => 'No text content found in submission',
                    ];
                    continue;
                }

                // Calificar con IA
                $gradingResult = $this->gradingService->gradeSubmission($text, $criteria, [
                    'assignment_id' => $assignmentId,
                ]);

                if (!$gradingResult['success']) {
                    $errors[] = [
                        'submission_id' => $submission['id'],
                        'user_name' => $submission['user']['name'] ?? 'Unknown',
                        'error' => $gradingResult['error'] ?? 'Grading failed',
                    ];
                    continue;
                }

                $assessment = $gradingResult['assessment'];
                $feedback = $this->gradingService->generateCompleteFeedback($assessment);

                $results[] = [
                    'submission_id' => $submission['id'],
                    'user_id' => $submission['user_id'],
                    'user_name' => $submission['user']['name'] ?? 'Unknown',
                    'total_score' => $assessment['total_score'],
                    'max_score' => $assessment['max_score'],
                    'feedback' => $feedback,
                    'criteria_assessments' => $assessment['criteria_assessments'],
                ];

            } catch (\Exception $e) {
                \Log::error('Error grading submission', [
                    'submission_id' => $submission['id'],
                    'assignment_id' => $assignmentId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                $errors[] = [
                    'submission_id' => $submission['id'],
                    'user_name' => $submission['user']['name'] ?? 'Unknown',
                    'error' => 'Unexpected error: ' . $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => count($results) > 0,
            'results' => $results,
            'errors' => $errors,
            'summary' => [
                'total_requested' => count($submissions),
                'successfully_graded' => count($results),
                'failed' => count($errors),
            ],
        ]);
    }

    /**
     * Enviar calificaciones aprobadas a Canvas
     */
    public function submitGrades(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'course_id' => 'required|string',
            'assignment_id' => 'required|string',
            'grades' => 'required|array|min:1',
            'grades.*.user_id' => 'required|string',
            'grades.*.score' => 'required|numeric|min:0',
            'grades.*.feedback' => 'required|string',
            'grades.*.rubric_assessment' => 'array',
        ]);

        $courseId = $validated['course_id'];
        $assignmentId = $validated['assignment_id'];
        $grades = $validated['grades'];

        $submitted = [];
        $failed = [];

        foreach ($grades as $grade) {
            $success = $this->submissionService->submitGrade(
                $courseId,
                $assignmentId,
                $grade['user_id'],
                $grade['score'],
                $grade['feedback'],
                $grade['rubric_assessment'] ?? []
            );

            if ($success) {
                $submitted[] = $grade['user_id'];
            } else {
                $failed[] = $grade['user_id'];
            }
        }

        return response()->json([
            'success' => count($submitted) > 0,
            'submitted' => $submitted,
            'failed' => $failed,
            'summary' => [
                'total' => count($grades),
                'submitted' => count($submitted),
                'failed' => count($failed),
            ],
        ]);
    }

    /**
     * Crear una nueva rúbrica
     */
    public function createRubric(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'course_id' => 'required|string',
            'title' => 'required|string|max:255',
            'criteria' => 'required|array|min:1',
            'criteria.*.description' => 'required|string',
            'criteria.*.points' => 'required|numeric|min:0',
            'criteria.*.ratings' => 'required|array|min:1',
            'criteria.*.ratings.*.description' => 'required|string',
            'criteria.*.ratings.*.points' => 'required|numeric|min:0',
            'free_form_criterion_comments' => 'sometimes|boolean',
        ]);

        $rubric = $this->rubricService->createRubric(
            $validated['course_id'],
            $validated
        );

        if ($rubric) {
            return response()->json([
                'success' => true,
                'rubric' => $rubric,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to create rubric',
        ], 500);
    }

    /**
     * Asociar rúbrica a asignación
     */
    public function associateRubric(Request $request, string $assignmentId): JsonResponse
    {
        $validated = $request->validate([
            'course_id' => 'required|string',
            'rubric_id' => 'required|string',
            'use_for_grading' => 'sometimes|boolean',
        ]);

        $associated = $this->rubricService->associateRubricToAssignment(
            $validated['course_id'],
            $assignmentId,
            $validated['rubric_id'],
            [
                'use_for_grading' => $validated['use_for_grading'] ?? true,
            ]
        );

        return response()->json([
            'success' => $associated,
            'message' => $associated ? 'Rubric associated successfully' : 'Failed to associate rubric',
        ], $associated ? 200 : 500);
    }

    /**
     * Crear rúbrica y asociarla a asignación
     */
    public function createAndAssociateRubric(Request $request, string $assignmentId): JsonResponse
    {
        $validated = $request->validate([
            'course_id' => 'required|string',
            'title' => 'required|string|max:255',
            'criteria' => 'required|array|min:1',
            'criteria.*.description' => 'required|string',
            'criteria.*.points' => 'required|numeric|min:0',
            'criteria.*.ratings' => 'required|array|min:1',
            'criteria.*.ratings.*.description' => 'required|string',
            'criteria.*.ratings.*.points' => 'required|numeric|min:0',
        ]);

        $rubric = $this->rubricService->createAndAssociateRubric(
            $validated['course_id'],
            $assignmentId,
            $validated
        );

        if ($rubric) {
            return response()->json([
                'success' => true,
                'rubric' => $rubric,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to create and associate rubric',
        ], 500);
    }

    /**
     * Endpoint de configuración XML para LTI
     */
    public function config(Request $request)
    {
        $baseUrl = config('app.url');
        
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<cartridge_basiclti_link xmlns="http://www.imsglobal.org/xsd/imslticc_v1p0"
    xmlns:blti = "http://www.imsglobal.org/xsd/imsbasiclti_v1p0"
    xmlns:lticm ="http://www.imsglobal.org/xsd/imslticm_v1p0"
    xmlns:lticp ="http://www.imsglobal.org/xsd/imslticp_v1p0"
    xmlns:xsi = "http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation = "http://www.imsglobal.org/xsd/imslticc_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticc_v1p0.xsd
    http://www.imsglobal.org/xsd/imsbasiclti_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imsbasiclti_v1p0.xsd
    http://www.imsglobal.org/xsd/imslticm_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticm_v1p0.xsd
    http://www.imsglobal.org/xsd/imslticp_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticp_v1p0.xsd">
    <blti:launch_url>{$baseUrl}/lti/grading/launch</blti:launch_url>
    <blti:title>Auto-Grading Tool</blti:title>
    <blti:description>Herramienta de calificación automática con IA usando rúbricas</blti:description>
    <blti:extensions platform="canvas.instructure.com">
      <lticm:property name="privacy_level">public</lticm:property>
      <lticm:options name="course_navigation">
        <lticm:property name="url">{$baseUrl}/lti/grading/launch</lticm:property>
        <lticm:property name="text">Auto-Grading</lticm:property>
        <lticm:property name="enabled">true</lticm:property>
        <lticm:property name="visibility">admins</lticm:property>
      </lticm:options>
    </blti:extensions>
</cartridge_basiclti_link>
XML;

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
