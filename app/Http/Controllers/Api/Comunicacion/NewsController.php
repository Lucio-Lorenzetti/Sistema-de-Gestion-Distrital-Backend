<?php

namespace App\Http\Controllers\Api\Comunicacion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\News; // <--- IMPORTANTE: Faltaba importar el modelo

class NewsController extends Controller
{
    public function index()
    {
        // Traemos las noticias más recientes
        return response()->json(News::with('autor:id,name')->latest()->get());
    }

    public function store(Request $request)
    {
        if (!$request->user()->hasAnyRole(['Director', 'Aux Comunicación'])) {
            return response()->json(['message' => 'No tienes permiso'], 403);
        }

        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'contenido' => 'required|string',
            'publicado_at' => 'nullable|date'
        ]);

        // Usamos 'autor_id' para que coincida con tu migración
        $news = News::create([
            'titulo' => $validated['titulo'],
            'contenido' => $validated['contenido'],
            'publicado_at' => $validated['publicado_at'] ?? now(),
            'autor_id' => auth()->id() 
        ]);

        return response()->json($news, 201);
    }

    public function destroy(News $news)
    {
        if (!auth()->user()->hasAnyRole(['Director', 'Aux Comunicación'])) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        $news->delete();
        return response()->json(['message' => 'Noticia eliminada']);
    }
}