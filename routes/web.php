<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\LtiController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\GradingController;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

// LTI 1.1 Endpoints - Chatbot
Route::get('/lti/config.xml', [LtiController::class, 'config'])->name('lti.config');
Route::post('/lti/launch', [LtiController::class, 'launch'])->name('lti.launch');

// LTI 1.1 Endpoints - Auto-Grading
Route::get('/lti/grading/config.xml', [GradingController::class, 'config'])->name('lti.grading.config');
Route::post('/lti/grading/launch', [GradingController::class, 'launch'])->name('lti.grading.launch');

// Chat API
Route::post('/api/chat', [ChatController::class, 'chat'])->name('api.chat');

// Grading API
Route::prefix('api/grading')->group(function () {
    Route::get('/assignments', [GradingController::class, 'getAssignments'])->name('api.grading.assignments');
    Route::get('/assignments/{assignmentId}', [GradingController::class, 'getAssignmentDetails'])->name('api.grading.assignment');
    Route::post('/assignments/{assignmentId}/grade', [GradingController::class, 'gradeSubmissions'])->name('api.grading.grade');
    Route::post('/submit-grades', [GradingController::class, 'submitGrades'])->name('api.grading.submit');
    
    // Rubric management
    Route::post('/rubrics', [GradingController::class, 'createRubric'])->name('api.grading.rubric.create');
    Route::post('/assignments/{assignmentId}/rubric', [GradingController::class, 'associateRubric'])->name('api.grading.rubric.associate');
    Route::post('/assignments/{assignmentId}/rubric/create', [GradingController::class, 'createAndAssociateRubric'])->name('api.grading.rubric.create-associate');
});

