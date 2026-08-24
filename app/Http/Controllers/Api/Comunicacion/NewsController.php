<?php

namespace App\Http\Controllers\Api\Comunicacion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\News;
use App\Services\ActivityLogger;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        News::where('estado', 'Programada')
            ->where('publicado_at', '<=', now())

            ->update(['estado' => 'Publicada']);

        $query = News::with('autor:id,name,totem')->latest();

        if ($request->has('estado') && $request->estado !== 'Todas') {
            $query->where('estado', $request->estado);
        }

        if ($request->boolean('solo_publicadas')) {
            $query->where('estado', 'Publicada');
        }

        $noticias = $query->get()->map(fn($n) => $this->formatNoticia($n));

        return response()->json($noticias);
    }

    public function show(News $news)
    {
        $news->increment('visitas');

        return response()->json($this->formatNoticia($news->load('autor:id,name,totem')));
    }

    public function store(Request $request)
    {
        $this->authorizeRoles($request);

        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'copete' => 'required|string|max:500', // Validación
            'contenido' => 'required|string',
            'estado' => 'required|in:Publicada,Borrador,Programada',
            'categoria' => 'nullable|string',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'publicado_at' => 'nullable|date',
        ]);

        if ($validated['estado'] === 'Publicada' && empty($validated['publicado_at'])) {
            $validated['publicado_at'] = now();
        }

        $rutaImagen = null;
        if ($request->hasFile('imagen')) {
            $rutaImagen = $request->file('imagen')->store('noticias', 'public');
        }

        $news = News::create([
            'titulo' => $validated['titulo'],
            'copete' => $validated['copete'], // <--- ESTO ES LO QUE FALTA
            'contenido' => $validated['contenido'],
            'estado' => $validated['estado'],
            'categoria' => $validated['categoria'] ?? 'Distrital',
            'imagen' => $rutaImagen,
            'publicado_at' => $validated['publicado_at'],
            'autor_id' => auth()->id(),
            'visitas' => 0,
        ]);

        ActivityLogger::log('noticia_creada', 'Se creó una nueva noticia', $news->titulo);        

        return response()->json($this->formatNoticia($news->load('autor:id,name,totem')), 201);
    }

    public function update(Request $request, News $news)
    {
        $this->authorizeRoles($request);

        $validated = $request->validate([
            'titulo' => 'sometimes|string|max:255',
            'copete' => 'required|string|max:500',
            'contenido' => 'sometimes|string',
            'estado' => 'sometimes|in:Publicada,Borrador,Programada',
            'categoria' => 'nullable|string',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'publicado_at' => 'nullable|date',
        ]);

        if ($request->hasFile('imagen')) {
            if ($news->imagen) {
                \Storage::disk('public')->delete($news->imagen);
            }
            $validated['imagen'] = $request->file('imagen')->store('noticias', 'public');
        }

        if (
            isset($validated['estado']) &&
            $validated['estado'] === 'Publicada' &&
            !$news->publicado_at
        ) {
            $validated['publicado_at'] = now();
        }

        $news->update($validated);
        
        ActivityLogger::log('noticia_actualizada', 'Se actualizó una noticia', $news->titulo);

        return response()->json($this->formatNoticia($news->load('autor:id,name,totem')));
    }

    public function destroy(News $news)
    {
        if (!auth()->user()->hasAnyRole(['Director', 'Aux Comunicación'])) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $news->delete();
        
        ActivityLogger::log('noticia_eliminada', 'Se eliminó una noticia', $news->titulo);

        return response()->json(['message' => 'Noticia eliminada']);
    }
    private function formatNoticia(News $noticia): array
    {
        return [
            'id' => $noticia->id,
            'titulo' => $noticia->titulo,
            'copete' => $noticia->copete, //
            'contenido' => $noticia->contenido,
            'estado' => $noticia->estado,
            'categoria' => $noticia->categoria,
            'imagen' => $noticia->imagen ? asset('storage/' . $noticia->imagen) : null,
            'visitas' => $noticia->visitas,
            'autor' => $noticia->autor?->nombre_visible ?? 'Sin asignar',
            'fecha' => $noticia->publicado_at?->format('d/m/Y') ?? 'No programada',
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