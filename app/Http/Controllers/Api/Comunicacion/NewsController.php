<?php

namespace App\Http\Controllers\Api\Comunicacion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\News;

class NewsController extends Controller
{
    // Público — Home y Noticias (sin auth)
    public function index(Request $request)
    {
        $query = News::with('autor:id,name')->latest();

        // Filtro por estado (Dashboard lo usa: ?estado=Publicada)
        if ($request->has('estado') && $request->estado !== 'Todas') {
            $query->where('estado', $request->estado);
        }

        // Solo publicadas para el front público
        if ($request->boolean('solo_publicadas')) {
            $query->where('estado', 'Publicada');
        }

        $noticias = $query->get()->map(fn($n) => $this->formatNoticia($n));

        return response()->json($noticias);
    }

    // Público — detalle para el modal de Noticias.jsx
    public function show(News $news)
    {
        // Incrementamos visitas al abrir el detalle
        $news->increment('visitas');

        return response()->json($this->formatNoticia($news->load('autor:id,name')));
    }

    public function store(Request $request)
    {
        $this->authorizeRoles($request);

        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'contenido' => 'required|string',
            'estado' => 'required|in:Publicada,Borrador,Programada',
            'categoria' => 'nullable|string',
            // AHORA ACEPTA ARCHIVOS DE IMAGEN (Máx 5MB), NO STRINGS
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'publicado_at' => 'nullable|date',
        ]);

        if ($validated['estado'] === 'Publicada' && empty($validated['publicado_at'])) {
            $validated['publicado_at'] = now();
        }

        // Lógica para guardar la imagen en el Storage
        $rutaImagen = null;
        if ($request->hasFile('imagen')) {
            // Guarda en storage/app/public/noticias
            $rutaImagen = $request->file('imagen')->store('noticias', 'public');
        }

        $news = News::create([
            'titulo' => $validated['titulo'],
            'contenido' => $validated['contenido'],
            'estado' => $validated['estado'],
            'categoria' => $validated['categoria'] ?? 'Distrital',
            'imagen' => $rutaImagen, // Guardamos la ruta del disco
            'publicado_at' => $validated['publicado_at'],
            'autor_id' => auth()->id(), // El autor ya se toma directo del backend
            'visitas' => 0,
        ]);

        return response()->json($this->formatNoticia($news->load('autor:id,name')), 201);
    }

    // Privado — editar noticia
    public function update(Request $request, News $news)
    {
        $this->authorizeRoles($request);

        $validated = $request->validate([
            'titulo' => 'sometimes|string|max:255',
            'contenido' => 'sometimes|string',
            'estado' => 'sometimes|in:Publicada,Borrador,Programada',
            'categoria' => 'nullable|string',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'publicado_at' => 'nullable|date',
        ]);

        if ($request->hasFile('imagen')) {
            // Borramos la imagen anterior si existe
            if ($news->imagen) {
                \Storage::disk('public')->delete($news->imagen);
            }
            $validated['imagen'] = $request->file('imagen')->store('noticias', 'public');
        }

        // Si cambia a Publicada y no tenía fecha, la seteamos ahora
        if (
            isset($validated['estado']) &&
            $validated['estado'] === 'Publicada' &&
            !$news->publicado_at
        ) {
            $validated['publicado_at'] = now();
        }

        $news->update($validated);

        return response()->json($this->formatNoticia($news->load('autor:id,name')));
    }

    // Privado — eliminar
    public function destroy(News $news)
    {
        if (!auth()->user()->hasAnyRole(['Director', 'Aux Comunicación'])) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $news->delete();

        return response()->json(['message' => 'Noticia eliminada']);
    }

    // -------------------------------------------------------
    // Helpers privados
    // -------------------------------------------------------

    private function formatNoticia(News $noticia): array
    {
        return [
            'id' => $noticia->id,
            'titulo' => $noticia->titulo,
            'contenido' => $noticia->contenido,
            'estado' => $noticia->estado,
            'categoria' => $noticia->categoria,
            'imagen' => $noticia->imagen ? asset('storage/' . $noticia->imagen) : null,
            'visitas' => $noticia->visitas,
            'autor' => $noticia->autor?->name ?? 'Sin asignar',
            // Fecha absoluta para Home/Noticias
            'fecha' => $noticia->publicado_at?->format('d/m/Y') ?? 'No programada',
            // ISO para que el front calcule "Hace X horas" con JS
            'fecha_iso' => $noticia->publicado_at?->toIso8601String(),
        ];
    }

    private function authorizeRoles(Request $request): void
    {
        if (!$request->user()->hasAnyRole(['Director', 'Aux Comunicación'])) {
            abort(403, 'No tenés permiso para esta acción');
        }
    }
}