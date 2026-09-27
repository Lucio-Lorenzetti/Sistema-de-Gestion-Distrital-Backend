<?php

namespace App\Http\Controllers\Api\Comunicacion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Download;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Storage;

class DownloadController extends Controller
{
    public function index()
    {
        return response()->json(Download::with('user:id,name,totem')->latest()->get());
    }

    public function store(Request $request)
    {
        if (!$request->user()->hasAnyRole(['Director', 'Aux Comunicación'])) {
            return response()->json(['message' => 'No tienes permiso'], 403);
        }

        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'tipo' => 'required|in:archivo,link',
            'archivo' => 'required_if:tipo,archivo|nullable|mimes:pdf,doc,docx,xlsx,xls|max:5120',
            'link' => 'required_if:tipo,link|nullable|url',
        ]);

        $data = [
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'tipo' => $request->tipo,
            'user_id' => auth()->id(),
        ];

        if ($request->tipo === 'archivo') {
            $data['archivo_path'] = $request->file('archivo')->store('downloads', config('filesystems.uploads_disk'));
        } else {
            $data['link'] = $request->link;
        }

        $download = Download::create($data);
        
        ActivityLogger::log('download_creado', 'Se creó un nuevo download', $download->nombre);


        return response()->json($download->load('user:id,name,totem'), 201);
    }

    public function destroy(Download $download)
    {
        if (!auth()->user()->hasAnyRole(['Director', 'Aux Comunicación'])) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // Soft-delete: el archivo físico se conserva (si no, restaurar no
        // serviría de nada). Solo se borra de verdad si en el futuro se agrega
        // un "vaciar papelera"/borrado definitivo.
        $download->delete();

        ActivityLogger::log('download_eliminado', 'Se eliminó un download', $download->nombre);

        return response()->json(['message' => 'Elemento eliminado']);
    }

    /**
     * Papelera de Bibliografía eliminada — mismo criterio que destroy():
     * Director o Aux Comunicación, sin acotar por autor.
     */
    public function papelera(Request $request)
    {
        if (!$request->user()->hasAnyRole(['Director', 'Aux Comunicación'])) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $items = Download::onlyTrashed()
            ->with('user:id,name,totem')
            ->orderByDesc('deleted_at')
            ->get();

        return response()->json($items);
    }

    public function restore(Request $request, $id)
    {
        if (!$request->user()->hasAnyRole(['Director', 'Aux Comunicación'])) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $download = Download::onlyTrashed()->findOrFail($id);
        $download->restore();

        ActivityLogger::log('download_restaurado', 'Se restauró un download', $download->nombre);

        return response()->json(['message' => 'Elemento restaurado correctamente']);
    }

    public function descargar(Download $download)
    {
        $disco = Storage::disk(config('filesystems.uploads_disk'));

        if ($download->tipo !== 'archivo' || !$download->archivo_path || !$disco->exists($download->archivo_path)) {
            abort(404, 'Archivo no encontrado');
        }

        $extension = pathinfo($download->archivo_path, PATHINFO_EXTENSION);
        $nombreDescarga = $download->nombre . '.' . $extension;

        return $disco->download($download->archivo_path, $nombreDescarga);
    }
}