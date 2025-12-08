<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabla de conversaciones
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('course_id')->index();
            $table->string('user_id')->index();
            $table->string('title')->nullable();
            $table->timestamps();
        });

        // Tabla de mensajes
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['user', 'assistant', 'system']);
            $table->text('content');
            $table->timestamps();
            
            $table->index(['conversation_id', 'created_at']);
        });

        // Tabla de contexto del curso (para RAG - Retrieval Augmented Generation)
        Schema::create('course_contexts', function (Blueprint $table) {
            $table->id();
            $table->string('course_id')->index();
            $table->string('context_type'); // 'syllabus', 'announcement', 'module', etc.
            $table->string('source_id')->nullable(); // ID del recurso en Canvas
            $table->text('content');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('course_contexts');
    }
};
