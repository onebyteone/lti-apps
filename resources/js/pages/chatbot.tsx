import { router } from '@inertiajs/react';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

interface Message {
  id: number;
  role: 'user' | 'assistant' | 'system';
  content: string;
  created_at: string;
}

interface Conversation {
  id: number;
  course_id: string;
  user_id: string;
  title: string;
  messages: Message[];
}

interface User {
  user_id: string;
  name: string;
  email: string;
  role: string;
  course_id: string;
}

interface ChatbotProps {
  user: User;
  isInstructor: boolean;
}

export default function Chatbot({ user, isInstructor }: ChatbotProps) {
  const [conversation, setConversation] = useState<Conversation | null>(null);
  const [messages, setMessages] = useState<Message[]>([]);
  const [inputMessage, setInputMessage] = useState('');
  const [loading, setLoading] = useState(false);
  const [initializing, setInitializing] = useState(true);
  const messagesEndRef = useRef<HTMLDivElement>(null);

  // Inicializar conversación
  useEffect(() => {
    const initConversation = async () => {
      try {
        const response = await axios.post('/chatbot/conversation', {
          course_id: user.course_id,
          user_id: user.user_id,
        });
        setConversation(response.data.conversation);
        setMessages(response.data.conversation.messages || []);
      } catch (error) {
        console.error('Error al inicializar conversación:', error);
      } finally {
        setInitializing(false);
      }
    };

    initConversation();
  }, [user.course_id, user.user_id]);

  // Scroll automático al final
  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages]);

  const sendMessage = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!inputMessage.trim() || !conversation || loading) return;

    const userMsg = inputMessage;
    setInputMessage('');
    setLoading(true);

    // Añadir mensaje del usuario optimistamente
    const optimisticMessage: Message = {
      id: Date.now(),
      role: 'user',
      content: userMsg,
      created_at: new Date().toISOString(),
    };
    setMessages((prev) => [...prev, optimisticMessage]);

    try {
      const response = await axios.post('/chatbot/message', {
        conversation_id: conversation.id,
        message: userMsg,
      });

      // Reemplazar mensaje optimista con el real y añadir respuesta
      setMessages((prev) => [
        ...prev.filter((m) => m.id !== optimisticMessage.id),
        response.data.user_message,
        response.data.assistant_message,
      ]);
    } catch (error) {
      console.error('Error al enviar mensaje:', error);
      // Remover mensaje optimista en caso de error
      setMessages((prev) => prev.filter((m) => m.id !== optimisticMessage.id));
      alert('Error al enviar el mensaje. Por favor, intenta de nuevo.');
    } finally {
      setLoading(false);
    }
  };

  if (initializing) {
    return (
      <div className="flex h-screen items-center justify-center bg-gray-50">
        <div className="text-center">
          <div className="mx-auto h-12 w-12 animate-spin rounded-full border-b-2 border-t-2 border-blue-600"></div>
          <p className="mt-4 text-gray-600">Inicializando chatbot...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="flex h-screen flex-col bg-gray-50">
      {/* Header */}
      <div className="border-b bg-white px-6 py-4 shadow-sm">
        <div className="mx-auto max-w-4xl">
          <h1 className="text-2xl font-bold text-gray-800">
            Asistente del Curso con IA
          </h1>
          <p className="text-sm text-gray-600">
            Hola, {user.name}. Pregunta lo que necesites sobre el curso.
          </p>
        </div>
      </div>

      {/* Messages Area */}
      <div className="flex-1 overflow-y-auto px-6 py-4">
        <div className="mx-auto max-w-4xl space-y-4">
          {messages.length === 0 ? (
            <div className="rounded-lg bg-blue-50 p-8 text-center">
              <svg
                className="mx-auto h-12 w-12 text-blue-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"
                />
              </svg>
              <h3 className="mt-4 text-lg font-medium text-gray-900">
                ¡Bienvenido al asistente del curso!
              </h3>
              <p className="mt-2 text-gray-600">
                Puedes preguntar sobre el contenido del curso, tareas, fechas,
                o cualquier duda que tengas.
              </p>
            </div>
          ) : (
            messages.map((message) => (
              <div
                key={message.id}
                className={`flex ${
                  message.role === 'user' ? 'justify-end' : 'justify-start'
                }`}
              >
                <div
                  className={`max-w-[80%] rounded-lg px-4 py-3 ${
                    message.role === 'user'
                      ? 'bg-blue-600 text-white'
                      : 'bg-white text-gray-800 shadow-sm'
                  }`}
                >
                  <p className="whitespace-pre-wrap break-words">
                    {message.content}
                  </p>
                  <p
                    className={`mt-1 text-xs ${
                      message.role === 'user'
                        ? 'text-blue-200'
                        : 'text-gray-500'
                    }`}
                  >
                    {new Date(message.created_at).toLocaleTimeString('es-ES', {
                      hour: '2-digit',
                      minute: '2-digit',
                    })}
                  </p>
                </div>
              </div>
            ))
          )}
          {loading && (
            <div className="flex justify-start">
              <div className="max-w-[80%] rounded-lg bg-white px-4 py-3 shadow-sm">
                <div className="flex items-center space-x-2">
                  <div className="h-2 w-2 animate-bounce rounded-full bg-gray-400"></div>
                  <div className="h-2 w-2 animate-bounce rounded-full bg-gray-400 [animation-delay:0.2s]"></div>
                  <div className="h-2 w-2 animate-bounce rounded-full bg-gray-400 [animation-delay:0.4s]"></div>
                </div>
              </div>
            </div>
          )}
          <div ref={messagesEndRef} />
        </div>
      </div>

      {/* Input Area */}
      <div className="border-t bg-white px-6 py-4 shadow-lg">
        <div className="mx-auto max-w-4xl">
          <form onSubmit={sendMessage} className="flex gap-2">
            <input
              type="text"
              value={inputMessage}
              onChange={(e) => setInputMessage(e.target.value)}
              placeholder="Escribe tu pregunta aquí..."
              disabled={loading}
              className="flex-1 rounded-lg border border-gray-300 px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 disabled:bg-gray-100"
            />
            <button
              type="submit"
              disabled={loading || !inputMessage.trim()}
              className="rounded-lg bg-blue-600 px-6 py-3 font-medium text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
              {loading ? (
                <svg
                  className="h-5 w-5 animate-spin"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <circle
                    className="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    strokeWidth="4"
                  ></circle>
                  <path
                    className="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                  ></path>
                </svg>
              ) : (
                'Enviar'
              )}
            </button>
          </form>
        </div>
      </div>
    </div>
  );
}
