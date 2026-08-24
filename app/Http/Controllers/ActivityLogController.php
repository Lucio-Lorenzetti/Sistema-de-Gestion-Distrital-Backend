<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;

class ActivityLogController extends Controller
{
    public function index()
    {
        return ActivityLog::with('user:id,name,totem')
            ->orderByDesc('created_at')
            ->limit(30)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'titulo' => $log->titulo,
                'desc' => $log->descripcion
                    ? "{$log->user?->nombre_visible} · {$log->descripcion}"
                    : ($log->user?->nombre_visible ?? 'Sistema'),
                'time' => $log->created_at->diffForHumans(),
            ]);
    }
}