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
        Schema::create('grading_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('assignment_id');
            $table->string('course_id');
            $table->string('user_id'); // Instructor who initiated the job
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->integer('submissions_count')->default(0);
            $table->integer('processed_count')->default(0);
            $table->json('submission_ids')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['course_id', 'assignment_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grading_jobs');
    }
};
