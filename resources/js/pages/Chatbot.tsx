import { Head } from '@inertiajs/react';
import { useState } from 'react';

interface User {
    id: string;
    name: string;
    email: string;
    roles: string[];
    isInstructor: boolean;
}

interface Course {
    id: string;
}

interface ChatbotProps {
    user: User;
    course: Course;
}

interface Message {
    role: 'user' | 'assistant';
    content: string;
}

export default function Chatbot({ user, course }: ChatbotProps) {
    const [messages, setMessages] = useState<Message[]>([
        {
            role: 'assistant',
            content: `¡Hola ${user.name}! Soy tu asistente de IA para este curso. ¿En qué puedo ayudarte?`
        }
    ]);
    const [input, setInput] = useState('');
    const [isLoading, setIsLoading] = useState(false);

    const handleSend = async () => {
        if (!input.trim()) return;

        const userMessage: Message = { role: 'user', content: input };
        setMessages(prev => [...prev, userMessage]);
        setInput('');
        setIsLoading(true);

        try {
            const conversationHistory = messages.slice(-5).map(msg => ({
                role: msg.role,
                content: msg.content,
            }));

            const response = await fetch('/api/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    message: input,
                    course_id: course.id,
                    conversation_history: conversationHistory,
                }),
            });

            const data = await response.json();
            
            setMessages(prev => [...prev, {
                role: 'assistant',
                content: data.message || 'Lo siento, no pude procesar tu pregunta.'
            }]);
        } catch (error) {
            console.error('Error:', error);
            setMessages(prev => [...prev, {
                role: 'assistant',
                content: 'Lo siento, ocurrió un error. Por favor intenta de nuevo.'
            }]);
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <>
            <Head title="AI Chatbot" />
            <div className="flex h-screen flex-col bg-gray-50">
                {/* Header */}
                <div className="bg-white border-b px-6 py-4 shadow-sm">
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-xl font-semibold text-gray-900">AI Chatbot</h1>
                            <p className="text-sm text-gray-500">Curso ID: {course.id}</p>
                        </div>
                        <div className="text-right">
                            <p className="text-sm font-medium text-gray-900">{user.name}</p>
                            <p className="text-xs text-gray-500">{user.email}</p>
                            {user.isInstructor && (
                                <span className="inline-block mt-1 px-2 py-0.5 text-xs bg-blue-100 text-blue-800 rounded">
                                    Instructor
                                </span>
                            )}
                        </div>
                    </div>
                </div>

                {/* Chat Messages */}
                <div className="flex-1 overflow-y-auto p-6 space-y-4">
                    {messages.map((message, index) => (
                        <div
                            key={index}
                            className={`flex ${message.role === 'user' ? 'justify-end' : 'justify-start'}`}
                        >
                            <div
                                className={`max-w-[70%] rounded-lg px-4 py-2 ${
                                    message.role === 'user'
                                        ? 'bg-blue-600 text-white'
                                        : 'bg-white text-gray-900 shadow-sm border'
                                }`}
                            >
                                <p className="text-sm whitespace-pre-wrap">{message.content}</p>
                            </div>
                        </div>
                    ))}
                    {isLoading && (
                        <div className="flex justify-start">
                            <div className="bg-white text-gray-900 shadow-sm border rounded-lg px-4 py-2">
                                <div className="flex space-x-2">
                                    <div className="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                                    <div className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{ animationDelay: '0.1s' }}></div>
                                    <div className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{ animationDelay: '0.2s' }}></div>
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                {/* Input Area */}
                <div className="bg-white border-t px-6 py-4">
                    <div className="flex space-x-4">
                        <input
                            type="text"
                            value={input}
                            onChange={(e) => setInput(e.target.value)}
                            onKeyPress={(e) => e.key === 'Enter' && !isLoading && handleSend()}
                            placeholder="Escribe tu pregunta..."
                            disabled={isLoading}
                            className="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100"
                        />
                        <button
                            onClick={handleSend}
                            disabled={isLoading || !input.trim()}
                            className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors"
                        >
                            {isLoading ? 'Enviando...' : 'Enviar'}
                        </button>
                    </div>
                    <p className="mt-2 text-xs text-gray-500">
                        Pregunta sobre el contenido del curso, tareas, módulos, etc.
                    </p>
                </div>
            </div>
        </>
    );
}
