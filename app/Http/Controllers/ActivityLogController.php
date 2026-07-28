<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;

class ActivityLogController extends Controller
{
    public function index()
    {
        return ActivityLog::with('user:id,name')
            ->orderByDesc('created_at')
            ->limit(30)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'titulo' => $log->titulo,
                'desc' => $log->descripcion
                    ? "{$log->user?->name} · {$log->descripcion}"
                    : ($log->user?->name ?? 'Sistema'),
                'time' => $log->created_at->diffForHumans(),
            ]);
    }
}