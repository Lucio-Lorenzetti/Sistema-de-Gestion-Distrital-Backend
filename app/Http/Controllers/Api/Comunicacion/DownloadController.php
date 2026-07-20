<?php

namespace App\Http\Controllers\Api\Comunicacion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Download;
use Illuminate\Support\Facades\Storage;

class DownloadController extends Controller
{
    public function index()
    {
        return response()->json(Download::with('user:id,name')->latest()->get());
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
            $data['archivo_path'] = $request->file('archivo')->store('downloads', 'public');
        } else {
            $data['link'] = $request->link;
        }

        $download = Download::create($data);

        return response()->json($download->load('user:id,name'), 201);
    }

    public function destroy(Download $download)
    {
        if (!auth()->user()->hasAnyRole(['Director', 'Aux Comunicación'])) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        if ($download->archivo_path) {
            Storage::disk('public')->delete($download->archivo_path);
        }

        $download->delete();
        return response()->json(['message' => 'Elemento eliminado']);
    }
}