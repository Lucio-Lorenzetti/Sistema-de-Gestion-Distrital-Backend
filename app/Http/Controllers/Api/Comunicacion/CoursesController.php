<?php

namespace App\Http\Controllers\Api\Comunicacion;

use App\Http\Controllers\Controller;
use App\Models\Course;
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

        return response()->json($course, 201);
    }

    public function update(Request $request, Course $course)
    {
        $validated = $this->validateCourse($request);

        $course->update($validated);

        return response()->json($course);
    }

    // — Actualización parcial: usada por el botón "Forzar cierre / Finalizar ahora" —
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
