<?php

namespace App\Http\Controllers\Api\Gestion;

use App\Http\Controllers\Controller;
use App\Models\Grupo;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * El Jefe de Grupo completa el perfil público de SU grupo (el del pivot
 * user_roles, ver User::roleScope()). Lo que cargue se muestra en /distrito.
 */
class MiGrupoController extends Controller
{
    public function show(Request $request)
    {
        return response()->json($this->grupoDelJefe($request));
    }

    public function update(Request $request)
    {
        $grupo = $this->grupoDelJefe($request);

        $validated = $request->validate([
            'numero' => ['nullable', 'regex:/^\d{1,5}$/'],
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:50',
            'telefono_whatsapp' => 'boolean',
            'instagram' => 'nullable|string|max:255',
            'facebook' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string|max:2000',
        ], [
            'numero.regex' => 'El número de grupo solo puede tener dígitos (hasta 5).',
        ]);

        // "numero" es NOT NULL en la tabla: vacío vuelve al placeholder "000".
        $validated['numero'] = $validated['numero'] ?? '000';

        $grupo->update($validated);

        ActivityLogger::log('grupo_actualizado', 'Se actualizó el perfil de un grupo', $grupo->nombre);

        return response()->json($grupo);
    }

    public function updateFoto(Request $request)
    {
        $grupo = $this->grupoDelJefe($request);

        $request->validate([
            'foto' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $disco = config('filesystems.uploads_disk');

        if ($grupo->foto) {
            Storage::disk($disco)->delete($grupo->foto);
        }

        $grupo->foto = $request->file('foto')->store('grupos', $disco);
        $grupo->save();

        return response()->json(['foto_url' => $grupo->foto_url]);
    }

    public function deleteFoto(Request $request)
    {
        $grupo = $this->grupoDelJefe($request);

        if ($grupo->foto) {
            Storage::disk(config('filesystems.uploads_disk'))->delete($grupo->foto);
            $grupo->foto = null;
            $grupo->save();
        }

        return response()->json(['message' => 'Foto del grupo eliminada correctamente']);
    }

    private function grupoDelJefe(Request $request): Grupo
    {
        $grupoId = $request->user()->roleScope('Jefe de Grupo')?->grupo_id;

        abort_unless($grupoId, 403, 'No sos Jefe de Grupo de ningún grupo.');

        return Grupo::findOrFail($grupoId);
    }
}
