<?php

namespace App\Http\Controllers\Api\Gestion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Últimas notificaciones del usuario logueado (leídas y no leídas).
     */
    public function index(Request $request)
    {
        $notificaciones = $request->user()->notifications()
            ->latest()
            ->limit(30)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'tipo' => $n->data['tipo'] ?? null,
                'mensaje' => $n->data['mensaje'] ?? '',
                'url' => $n->data['url'] ?? null,
                'leida' => $n->read_at !== null,
                'fecha' => $n->created_at->toIso8601String(),
            ]);

        return response()->json($notificaciones);
    }

    public function noLeidas(Request $request)
    {
        return response()->json(['no_leidas' => $request->user()->unreadNotifications()->count()]);
    }

    public function marcarLeida(Request $request, string $id)
    {
        $notificacion = $request->user()->notifications()->findOrFail($id);
        $notificacion->markAsRead();

        return response()->json(['message' => 'Notificación marcada como leída']);
    }

    public function marcarTodasLeidas(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'Todas las notificaciones marcadas como leídas']);
    }
}
