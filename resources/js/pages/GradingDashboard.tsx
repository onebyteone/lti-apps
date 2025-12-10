import { Head, router } from '@inertiajs/react';
import { useState, useEffect } from 'react';

interface User {
    id: string;
    name: string;
    isInstructor: boolean;
}

interface Course {
    id: string;
}

interface Assignment {
    id: string;
    name: string;
    description: string;
    due_at: string | null;
    points_possible: number;
    has_rubric: boolean;
    use_rubric_for_grading: boolean;
    stats: {
        total: number;
        submitted: number;
        graded: number;
        needs_grading: number;
        late: number;
    };
}

interface Submission {
    id: string;
    user_id: string;
    user_name: string;
    submitted_at: string;
    body: string;
    score: number | null;
}

interface GradingResult {
    submission_id: string;
    total_score: number;
    max_score: number;
    feedback: string;
    criteria_assessments: any[];
}

interface GradingDashboardProps {
    user: User;
    course: Course;
}

export default function GradingDashboard({ user, course }: GradingDashboardProps) {
    const [assignments, setAssignments] = useState<Assignment[]>([]);
    const [loading, setLoading] = useState(true);
    const [selectedAssignment, setSelectedAssignment] = useState<Assignment | null>(null);
    const [submissions, setSubmissions] = useState<Submission[]>([]);
    const [gradingResults, setGradingResults] = useState<GradingResult[]>([]);
    const [grading, setGrading] = useState(false);
    const [submitting, setSubmitting] = useState(false);

    useEffect(() => {
        fetchAssignments();
    }, []);

    const fetchAssignments = async () => {
        setLoading(true);
        try {
            const response = await fetch(`/api/grading/assignments?course_id=${course.id}`, {
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
            });

            const data = await response.json();
            setAssignments(data.assignments || []);
        } catch (error) {
            console.error('Error fetching assignments:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleSelectAssignment = async (assignment: Assignment) => {
        setSelectedAssignment(assignment);
        setLoading(true);
        
        try {
            const response = await fetch(`/api/grading/assignments/${assignment.id}?course_id=${course.id}`, {
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
            });

            const data = await response.json();
            setSubmissions(data.submissions || []);
        } catch (error) {
            console.error('Error fetching submissions:', error);
            alert('Error al cargar las entregas');
        } finally {
            setLoading(false);
        }
    };

    const handleGradeSubmissions = async () => {
        if (!selectedAssignment) return;
        
        setGrading(true);
        try {
            const response = await fetch(`/api/grading/assignments/${selectedAssignment.id}/grade`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    course_id: course.id,
                }),
            });

            const data = await response.json();
            
            if (data.success) {
                setGradingResults(data.results || []);
                alert(`✅ ${data.results.length} entregas calificadas exitosamente por la IA`);
            } else {
                alert('Error al calificar: ' + (data.message || 'Error desconocido'));
            }
        } catch (error) {
            console.error('Error grading submissions:', error);
            alert('Error al calificar las entregas');
        } finally {
            setGrading(false);
        }
    };

    const handleSubmitGrades = async () => {
        if (gradingResults.length === 0) return;
        
        setSubmitting(true);
        try {
            const response = await fetch(`/api/grading/submit-grades`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    course_id: course.id,
                    grades: gradingResults,
                }),
            });

            const data = await response.json();
            
            if (data.success) {
                alert(`✅ ${data.summary.submitted} calificaciones enviadas a Canvas`);
                setGradingResults([]);
                setSelectedAssignment(null);
                fetchAssignments(); // Refresh
            } else {
                alert('Error al enviar calificaciones');
            }
        } catch (error) {
            console.error('Error submitting grades:', error);
            alert('Error al enviar las calificaciones');
        } finally {
            setSubmitting(false);
        }
    };

    const handleBack = () => {
        setSelectedAssignment(null);
        setSubmissions([]);
        setGradingResults([]);
    };

    return (
        <>
            <Head title="Auto-Grading Dashboard" />
            <div className="min-h-screen bg-gray-50">
                {/* Header */}
                <div className="bg-white border-b px-6 py-4 shadow-sm">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center space-x-4">
                            {selectedAssignment && (
                                <button
                                    onClick={handleBack}
                                    className="inline-flex items-center text-gray-600 hover:text-gray-900"
                                >
                                    <svg className="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                                    </svg>
                                    Volver
                                </button>
                            )}
                            <div>
                                <h1 className="text-2xl font-bold text-gray-900">
                                    🤖 Auto-Grading Dashboard
                                </h1>
                                <p className="text-sm text-gray-600 mt-1">
                                    {selectedAssignment ? selectedAssignment.name : 'Calificación automática con IA usando rúbricas'}
                                </p>
                            </div>
                        </div>
                        <div className="text-right">
                            <p className="text-sm font-medium text-gray-700">{user.name}</p>
                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Instructor
                            </span>
                        </div>
                    </div>
                </div>

                {/* Main Content */}
                <div className="max-w-7xl mx-auto px-6 py-8">
                    {!selectedAssignment ? (
                        <>
                            {/* Stats Overview */}
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                                <div className="bg-white rounded-lg shadow p-6">
                                    <div className="flex items-center">
                                        <div className="flex-shrink-0 bg-blue-100 rounded-md p-3">
                                            <svg className="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <div className="ml-4">
                                            <p className="text-sm font-medium text-gray-500">Asignaciones</p>
                                            <p className="text-2xl font-semibold text-gray-900">{assignments.length}</p>
                                        </div>
                                    </div>
                                </div>

                                <div className="bg-white rounded-lg shadow p-6">
                                    <div className="flex items-center">
                                        <div className="flex-shrink-0 bg-yellow-100 rounded-md p-3">
                                            <svg className="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div className="ml-4">
                                            <p className="text-sm font-medium text-gray-500">Pendientes</p>
                                            <p className="text-2xl font-semibold text-gray-900">
                                                {assignments.reduce((sum, a) => sum + a.stats.needs_grading, 0)}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div className="bg-white rounded-lg shadow p-6">
                                    <div className="flex items-center">
                                        <div className="flex-shrink-0 bg-green-100 rounded-md p-3">
                                            <svg className="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div className="ml-4">
                                            <p className="text-sm font-medium text-gray-500">Calificadas</p>
                                            <p className="text-2xl font-semibold text-gray-900">
                                                {assignments.reduce((sum, a) => sum + a.stats.graded, 0)}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Assignments List */}
                            <div className="bg-white rounded-lg shadow">
                                <div className="px-6 py-4 border-b border-gray-200">
                                    <h2 className="text-lg font-medium text-gray-900">
                                        Asignaciones con Rúbricas
                                    </h2>
                                    <p className="mt-1 text-sm text-gray-500">
                                        Selecciona una asignación para comenzar a calificar con IA
                                    </p>
                                </div>

                                {loading ? (
                                    <div className="p-12 text-center">
                                        <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                                        <p className="mt-2 text-sm text-gray-500">Cargando asignaciones...</p>
                                    </div>
                                ) : assignments.length === 0 ? (
                                    <div className="p-12 text-center">
                                        <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <h3 className="mt-2 text-sm font-medium text-gray-900">No hay asignaciones</h3>
                                        <p className="mt-1 text-sm text-gray-500">
                                            No se encontraron asignaciones con rúbricas.
                                        </p>
                                    </div>
                                ) : (
                                    <ul className="divide-y divide-gray-200">
                                        {assignments.map((assignment) => (
                                            <li key={assignment.id} className="hover:bg-gray-50 transition-colors">
                                                <div className="px-6 py-4">
                                                    <div className="flex items-center justify-between">
                                                        <div className="flex-1">
                                                            <h3 className="text-base font-medium text-gray-900">
                                                                {assignment.name}
                                                            </h3>
                                                            {assignment.due_at && (
                                                                <p className="mt-1 text-sm text-gray-500">
                                                                    Vence: {new Date(assignment.due_at).toLocaleDateString('es-ES')}
                                                                </p>
                                                            )}
                                                            <div className="mt-2 flex items-center space-x-4 text-sm">
                                                                <span className="text-gray-500">
                                                                    📊 {assignment.points_possible} pts
                                                                </span>
                                                                <span className="text-green-600 font-medium">
                                                                    ✓ {assignment.stats.graded} calificadas
                                                                </span>
                                                                <span className="text-yellow-600 font-medium">
                                                                    ⏳ {assignment.stats.needs_grading} pendientes
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div className="ml-6">
                                                            <button
                                                                onClick={() => handleSelectAssignment(assignment)}
                                                                className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                                                disabled={assignment.stats.submitted === 0}
                                                            >
                                                                Calificar
                                                                <svg className="ml-2 -mr-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>

                            {/* Info Box */}
                            <div className="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
                                <div className="flex">
                                    <div className="flex-shrink-0">
                                        <svg className="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                                        </svg>
                                    </div>
                                    <div className="ml-3">
                                        <h3 className="text-sm font-medium text-blue-800">
                                            Acerca de la calificación automática
                                        </h3>
                                        <div className="mt-2 text-sm text-blue-700">
                                            <ul className="list-disc pl-5 space-y-1">
                                                <li>La IA evalúa usando la rúbrica de cada asignación</li>
                                                <li>Todas las calificaciones requieren tu revisión y aprobación</li>
                                                <li>Puedes editar el feedback antes de enviar a Canvas</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </>
                    ) : (
                        /* Grading Interface */
                        <div className="space-y-6">
                            {gradingResults.length === 0 ? (
                                /* Step 1: Show submissions and grade button */
                                <div className="bg-white rounded-lg shadow p-6">
                                    <h2 className="text-lg font-medium text-gray-900 mb-4">
                                        Entregas ({submissions.length})
                                    </h2>
                                    
                                    {loading ? (
                                        <div className="text-center py-12">
                                            <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                                            <p className="mt-2 text-sm text-gray-500">Cargando entregas...</p>
                                        </div>
                                    ) : (
                                        <>
                                            <div className="space-y-4 mb-6">
                                                {submissions.map((sub) => (
                                                    <div key={sub.id} className="border rounded-lg p-4">
                                                        <div className="flex justify-between items-start mb-2">
                                                            <span className="font-medium">{sub.user_name}</span>
                                                            <span className="text-sm text-gray-500">
                                                                {new Date(sub.submitted_at).toLocaleString('es-ES')}
                                                            </span>
                                                        </div>
                                                        <div className="text-sm text-gray-700 line-clamp-3">
                                                            {sub.body?.substring(0, 200)}...
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                            
                                            <button
                                                onClick={handleGradeSubmissions}
                                                disabled={grading}
                                                className="w-full inline-flex justify-center items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                                            >
                                                {grading ? (
                                                    <>
                                                        <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-2"></div>
                                                        Calificando con IA...
                                                    </>
                                                ) : (
                                                    <>
                                                        🤖 Calificar {submissions.length} entregas con IA
                                                    </>
                                                )}
                                            </button>
                                        </>
                                    )}
                                </div>
                            ) : (
                                /* Step 2: Show results and submit button */
                                <div className="space-y-4">
                                    <div className="bg-green-50 border border-green-200 rounded-lg p-4">
                                        <p className="text-green-800">
                                            ✅ {gradingResults.length} entregas calificadas. Revisa los resultados y envía a Canvas.
                                        </p>
                                    </div>
                                    
                                    {gradingResults.map((result, idx) => (
                                        <div key={result.submission_id} className="bg-white rounded-lg shadow p-6">
                                            <div className="flex justify-between items-start mb-4">
                                                <h3 className="font-medium">Entrega #{idx + 1}</h3>
                                                <span className="text-lg font-bold text-blue-600">
                                                    {result.total_score} / {result.max_score} pts
                                                </span>
                                            </div>
                                            <div className="bg-gray-50 rounded p-4 text-sm whitespace-pre-wrap">
                                                {result.feedback}
                                            </div>
                                        </div>
                                    ))}
                                    
                                    <button
                                        onClick={handleSubmitGrades}
                                        disabled={submitting}
                                        className="w-full inline-flex justify-center items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50"
                                    >
                                        {submitting ? (
                                            <>
                                                <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-2"></div>
                                                Enviando a Canvas...
                                            </>
                                        ) : (
                                            <>
                                                📤 Enviar {gradingResults.length} calificaciones a Canvas
                                            </>
                                        )}
                                    </button>
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
