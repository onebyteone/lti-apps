<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\LtiController;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');
Route::post('/lti/launch', [LtiController::class, 'launch']);
