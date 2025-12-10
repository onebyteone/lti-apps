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
        Schema::create('grading_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grading_job_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('submission_id');
            $table->string('student_id');
            $table->string('assignment_id');
            $table->string('course_id');
            $table->json('rubric_assessment'); // Full assessment from AI
            $table->decimal('total_score', 8, 2);
            $table->decimal('max_score', 8, 2);
            $table->text('feedback');
            $table->string('approved_by')->nullable(); // User ID of approver
            $table->boolean('submitted_to_canvas')->default(false);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            
            $table->index(['course_id', 'assignment_id']);
            $table->index('student_id');
            $table->index('submitted_to_canvas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grading_results');
    }
};
