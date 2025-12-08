<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\CourseContext;
use App\Models\Message;
use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;

class ChatbotController extends Controller
{
    /**
     * Iniciar o recuperar una conversación
     */
    public function getOrCreateConversation(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|string',
            'user_id' => 'required|string',
        ]);

        $conversation = Conversation::firstOrCreate([
            'course_id' => $validated['course_id'],
            'user_id' => $validated['user_id'],
        ], [
            'title' => 'Nueva conversación',
        ]);

        return response()->json([
            'conversation' => $conversation->load('messages'),
        ]);
    }

    /**
     * Enviar un mensaje y obtener respuesta del chatbot
     */
    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'message' => 'required|string|max:2000',
        ]);

        $conversation = Conversation::findOrFail($validated['conversation_id']);

        // Guardar mensaje del usuario
        $userMessage = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $validated['message'],
        ]);

        // Obtener contexto del curso
        $courseContext = CourseContext::where('course_id', $conversation->course_id)
            ->get()
            ->pluck('content')
            ->implode("\n\n");

        // Construir historial de mensajes para OpenAI
        $messages = $this->buildMessageHistory($conversation, $courseContext);

        try {
            // Llamar a OpenAI
            $response = OpenAI::chat()->create([
                'model' => config('openai.model', 'gpt-4o-mini'),
                'messages' => $messages,
                'temperature' => 0.7,
                'max_tokens' => 500,
            ]);

            $assistantResponse = $response->choices[0]->message->content;

            // Guardar respuesta del asistente
            $assistantMessage = Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $assistantResponse,
            ]);

            return response()->json([
                'user_message' => $userMessage,
                'assistant_message' => $assistantMessage,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al procesar la solicitud: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Construir historial de mensajes para OpenAI
     */
    private function buildMessageHistory(Conversation $conversation, string $courseContext): array
    {
        $messages = [];

        // Mensaje del sistema con contexto
        $systemPrompt = "Eres un asistente educativo especializado en ayudar a estudiantes con preguntas sobre el curso. ";
        
        if (!empty($courseContext)) {
            $systemPrompt .= "Aquí está el contexto del curso:\n\n" . $courseContext . "\n\n";
        }
        
        $systemPrompt .= "Proporciona respuestas claras, concisas y útiles. Si no sabes algo, admítelo honestamente.";

        $messages[] = [
            'role' => 'system',
            'content' => $systemPrompt,
        ];

        // Añadir historial de mensajes (últimos 10)
        $history = $conversation->messages()
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->reverse();

        foreach ($history as $message) {
            $messages[] = [
                'role' => $message->role,
                'content' => $message->content,
            ];
        }

        return $messages;
    }

    /**
     * Obtener historial de una conversación
     */
    public function getHistory(Request $request, $conversationId)
    {
        $conversation = Conversation::with('messages')->findOrFail($conversationId);

        return response()->json([
            'conversation' => $conversation,
        ]);
    }

    /**
     * Cargar contexto del curso (para administradores/instructores)
     */
    public function uploadCourseContext(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|string',
            'context_type' => 'required|string',
            'content' => 'required|string',
            'source_id' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $context = CourseContext::create($validated);

        return response()->json([
            'message' => 'Contexto del curso cargado exitosamente',
            'context' => $context,
        ]);
    }
}
