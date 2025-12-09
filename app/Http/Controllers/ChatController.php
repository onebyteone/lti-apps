<?php

namespace App\Http\Controllers;

use App\Services\ChatbotService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ChatController extends Controller
{
    private ChatbotService $chatbotService;

    public function __construct(ChatbotService $chatbotService)
    {
        $this->chatbotService = $chatbotService;
    }

    /**
     * Procesar mensaje del chat
     */
    public function chat(Request $request): JsonResponse
    {
        \Log::info('Chat request received', [
            'has_message' => $request->has('message'),
            'has_course_id' => $request->has('course_id'),
            'env_check' => [
                'canvas_url' => config('services.canvas.api_url'),
                'has_canvas_token' => !empty(config('services.canvas.api_token')),
                'has_openai_key' => !empty(config('services.openai.api_key')),
            ],
        ]);

        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'course_id' => 'required|string',
            'conversation_history' => 'array|max:10',
        ]);

        $message = $validated['message'];
        $courseId = $validated['course_id'];
        $conversationHistory = $validated['conversation_history'] ?? [];

        // Generar respuesta
        $result = $this->chatbotService->chat($message, $courseId, $conversationHistory);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
        ]);
    }
}
