<?php

namespace App\Http\Controllers\Api\Comunicacion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Download; // <--- IMPORTANTE: Faltaba importar el modelo

class DownloadController extends Controller
{
    public function index()
    {
        // Traemos todos los archivos para que los scouts los descarguen
        return response()->json(Download::with('user:id,name')->latest()->get());
    }

    public function store(Request $request)
    {
        if (!$request->user()->hasAnyRole(['Director', 'Aux Comunicación'])) {
            return response()->json(['message' => 'No tienes permiso'], 403);
        }

        $request->validate([
            'nombre' => 'required|string',
            'descripcion' => 'nullable|string',
            'archivo' => 'required|mimes:pdf,doc,docx,xlsx|max:5120' // Max 5MB
        ]);

        $path = $request->file('archivo')->store('downloads', 'public');

        $download = Download::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'archivo_path' => $path,
            'user_id' => auth()->id()
        ]);

        return response()->json($download, 201);
    }
    
    public function destroy(Download $download)
    {
        if (!auth()->user()->hasAnyRole(['Director', 'Aux Comunicación'])) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        $download->delete();
        return response()->json(['message' => 'Archivo eliminado']);
    }
}