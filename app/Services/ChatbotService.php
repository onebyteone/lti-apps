<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotService
{
    private string $apiKey;
    private string $model;
    private CanvasService $canvasService;

    public function __construct(CanvasService $canvasService)
    {
        $this->apiKey = config('services.openai.api_key');
        $this->model = config('services.openai.model', 'gpt-4o-mini');
        $this->canvasService = $canvasService;
    }

    /**
     * Generar respuesta del chatbot
     */
    public function chat(string $message, string $courseId, array $conversationHistory = []): array
    {
        try {
            // Obtener contexto del curso
            $courseContext = $this->canvasService->buildCourseContext($courseId);

            // Construir mensajes para OpenAI
            $messages = [
                [
                    'role' => 'system',
                    'content' => "Eres un asistente educativo inteligente para un curso en Canvas LMS. "
                        . "Tu objetivo es ayudar a los estudiantes con preguntas sobre el contenido del curso, "
                        . "asignaciones, fechas de entrega, y recursos del curso.\n\n"
                        . "Contexto del curso:\n{$courseContext}\n\n"
                        . "Responde de manera clara, concisa y útil. Si no tienes información específica, "
                        . "sugiere dónde el estudiante puede encontrarla en Canvas."
                ],
            ];

            // Agregar historial de conversación (últimos 5 mensajes)
            foreach (array_slice($conversationHistory, -5) as $msg) {
                $messages[] = [
                    'role' => $msg['role'],
                    'content' => $msg['content'],
                ];
            }

            // Agregar mensaje actual del usuario
            $messages[] = [
                'role' => 'user',
                'content' => $message,
            ];

            // Llamar a OpenAI API
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => 0.7,
                'max_tokens' => 500,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $assistantMessage = $data['choices'][0]['message']['content'] ?? 'Lo siento, no pude generar una respuesta.';

                return [
                    'success' => true,
                    'message' => $assistantMessage,
                    'usage' => $data['usage'] ?? null,
                ];
            }

            Log::error('OpenAI API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'message' => 'Lo siento, hubo un error al procesar tu pregunta. Por favor intenta de nuevo.',
            ];

        } catch (\Exception $e) {
            Log::error('Chatbot error', [
                'message' => $message,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Lo siento, ocurrió un error inesperado. Por favor intenta de nuevo.',
            ];
        }
    }
}
