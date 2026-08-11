<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $program->titulo }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a1a1a; }
        h1 { font-size: 20px; margin-bottom: 4px; text-align: center; }
        .meta { margin-bottom: 20px; }
        .meta-row { width: 100%; border-collapse: collapse; }
        .meta-row td { text-align: center; font-size: 10px; color: #666; padding: 2px 0; }
        .meta-row-3 td { width: 33.33%; }
        .meta-row-2 td { width: 50%; }
        h2 { font-size: 14px; margin-top: 22px; margin-bottom: 6px; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
        .bloque { margin-bottom: 10px; white-space: pre-line; }
        .dia { margin-top: 16px; }
        .dia-titulo { font-weight: bold; font-size: 13px; margin-bottom: 6px; }
        .pie-template { margin-top: 30px; padding-top: 8px; border-top: 1px solid #ddd; font-size: 9px; color: #9ca3af; }

        /* Contenido HTML generado por el editor rich-text (Campamento/Cuatrimestre y cada día de CFA) */
        .contenido-html div { min-height: 1em; }
        .contenido-html strong { font-weight: bold; }
        .contenido-html ul, .contenido-html ol { margin: 4px 0; padding-left: 22px; }
        .contenido-html ul { list-style-type: disc; }
        .contenido-html ol { list-style-type: decimal; }
        .contenido-html li { margin-bottom: 2px; }
    </style>
</head>
<body>
    <h1>{{ $program->titulo }}</h1>
    <div class="meta">
        <table class="meta-row meta-row-3">
            <tr>
                <td><strong>Grupo:</strong> {{ $program->grupo->nombre ?? '—' }}</td>
                <td><strong>Rama:</strong> {{ $program->rama->nombre ?? '—' }}</td>
                <td><strong>Autor:</strong> {{ $program->owner->name ?? '—' }}</td>
            </tr>
        </table>
        <table class="meta-row meta-row-2">
            <tr>
                <td>
                    @if($program->fecha_inicio)
                        <strong>Fechas:</strong> {{ \Carbon\Carbon::parse($program->fecha_inicio)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($program->fecha_fin)->format('d/m/Y') }}
                    @endif
                </td>
                <td><strong>Tipo:</strong> {{ ucfirst($program->tipo) }}</td>
            </tr>
        </table>
    </div>

    @if($program->educadores_a_cargo)
        <h2>Educadores a Cargo</h2>
        <div class="bloque">{{ $program->educadores_a_cargo }}</div>
    @endif

    @if($program->diagnostico)
        <h2>Diagnóstico</h2>
        <div class="bloque">{{ $program->diagnostico }}</div>
    @endif

    @if($program->objetivos)
        <h2>Objetivo</h2>
        <div class="bloque">{{ $program->objetivos }}</div>
    @endif

    <h2>Cronograma</h2>

    @if(isset($cronograma['contenidoHtml']))
        {{-- Campamento / Cuatrimestre: HTML único generado en el editor --}}
        <div class="contenido-html">{!! $cronograma['contenidoHtml'] !!}</div>
    @elseif(isset($cronograma['contenido']))
        {{-- Legacy: programas creados antes del cambio de contrato, texto plano --}}
        <div class="bloque">{!! nl2br(e($cronograma['contenido'])) !!}</div>
    @elseif(is_array($cronograma) && count($cronograma) > 0)
        {{-- CFA: array de días, cada uno con su propio HTML --}}
        @foreach($cronograma as $dia)
            @if(is_array($dia))
                <div class="dia">
                    <div class="dia-titulo">Día {{ $dia['dia'] ?? '' }} — {{ $dia['nombreDia'] ?? '' }} {{ $dia['fechaFormatted'] ?? '' }}</div>
                    <div class="contenido-html">{!! $dia['contenidoHtml'] ?? '' !!}</div>
                </div>
            @endif
        @endforeach
    @else
        <p><em>Sin contenido cargado.</em></p>
    @endif

    @if($disclaimer)
        <div class="pie-template">{{ $disclaimer }}</div>
    @endif
</body>
</html>
