<?php

namespace App\Http\Controllers\Api\Comunicacion;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CoursesController extends Controller
{
    public function index()
    {
        return Course::orderByDesc('created_at')->get();
    }

    public function show(Course $course)
    {
        return $course;
    }

    public function store(Request $request)
    {
        $validated = $this->validateCourse($request);

        $course = Course::create($validated);
        
        ActivityLogger::log('curso_creado', 'Se creó un nuevo curso', $course->titulo);

        return response()->json($course, 201);
    }

    public function update(Request $request, Course $course)
    {
        $validated = $this->validateCourse($request);

        $course->update($validated);
        
        ActivityLogger::log('curso_creado', 'Se creó un nuevo curso', $course->titulo);

        return response()->json($course);
    }

    public function patch(Request $request, Course $course)
    {
        $validated = $request->validate([
            'fecha_cierre' => ['sometimes', 'date'],
            'fecha_fin' => ['sometimes', 'date'],
        ]);

        $course->update($validated);

        return response()->json($course);
    }

    public function destroy(Course $course)
    {
        $course->delete();
        
        ActivityLogger::log('curso_eliminado', 'Se eliminó un curso', $course->titulo);

        return response()->json(['message' => 'Curso eliminado correctamente']);
    }

    private function validateCourse(Request $request): array
    {
        return $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['required', 'string'],
            'link_formulario' => ['required', 'url'],
            'categoria' => ['required', Rule::in(['Programa', 'Gestion'])],
            'ramas' => [Rule::requiredIf(fn() => $request->categoria === 'Programa'), 'nullable', 'array', 'max:5'],
            'ramas.*' => [Rule::in(['Pre-menores', 'Manada', 'Unidad', 'Caminantes', 'Rovers'])],
            'fecha_cierre' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_cierre'],
            // 👇 Agregá estas reglas para que acepte los nuevos campos del formulario
            'lugar' => ['nullable', 'string', 'max:255'],
            'costo' => ['nullable', 'numeric', 'min:0'],
            'modalidad' => ['nullable', 'string', 'max:100'],
            'formador' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
