<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class LtiController extends Controller
{
    public function launch(Request $request)
    {
        // Canvas envía muchos datos. Aquí capturamos los más útiles.
        $data = [
            'user_id' => $request->input('custom_canvas_user_id'),
            'name' => $request->input('lis_person_name_full', 'Invitado'),
            'email' => $request->input('lis_person_contact_email_primary'),
            'role' => $request->input('roles'), // Instructor, Student, etc.
            'course_id' => $request->input('custom_canvas_course_id'),
        ];

        // Determinar si es instructor
        $isInstructor = str_contains($data['role'] ?? '', 'Instructor');

        // Retornar vista de Inertia con el chatbot
        return Inertia::render('chatbot', [
            'user' => $data,
            'isInstructor' => $isInstructor,
        ]);
    }
}
