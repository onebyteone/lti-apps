<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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

        // Validar lógica simple (ejemplo)
        if (str_contains($data['role'], 'Instructor')) {
            $message = "Bienvenido, Profesor " . $data['name'] . ". Aquí está su panel de control.";
        } else {
            $message = "Hola, " . $data['name'] . ". Listo para aprender?";
        }

        // Retornar una vista con estos datos
        return view('lti.app', compact('data', 'message'));
    }
}
