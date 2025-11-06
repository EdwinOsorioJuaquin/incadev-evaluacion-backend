<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Reporte de Auditoría - {{ $audit->summary }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    @page { margin: 80px 30px 70px 30px; }
    body {
      font-family: DejaVu Sans, Arial, sans-serif;
      font-size: 12px;
      color: #334155;
      background-color: #ffffff;
      line-height: 1.5;
    }
    .header, .footer {
      position: fixed;
      left: 0; right: 0;
      height: 60px;
      padding: 8px 20px;
    }
    .header {
      top: -60px;
      background: #1e3a8a;
      color: #fff;
      border-bottom: 3px solid #3b82f6;
    }
    .footer {
      bottom: -40px;
      background: #f1f5f9;
      border-top: 2px solid #e2e8f0;
      font-size: 10px;
      color: #475569;
    }

    h2.section-title {
      background: #eff6ff;
      border-left: 5px solid #2563eb;
      color: #1e40af;
      padding: 8px 12px;
      border-radius: 4px;
      margin-top: 25px;
      font-size: 15px;
      font-weight: 600;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 12px;
    }
    th, td {
      border: 1px solid #d1d5db;
      padding: 6px 8px;
      vertical-align: top;
    }
    th { background: #f8fafc; }

    .chip {
      display: inline-block;
      padding: 3px 8px;
      border-radius: 10px;
      font-size: 10px;
      text-transform: uppercase;
      font-weight: bold;
      border: 1px solid transparent;
    }
    .chip-new { background: #fef3c7; color: #92400e; border-color: #f59e0b; }
    .chip-progress { background: #dbeafe; color: #1e40af; border-color: #3b82f6; }
    .chip-completed { background: #dcfce7; color: #166534; border-color: #16a34a; }

    .sev-low { background: #dcfce7; color: #15803d; border-color: #22c55e; }
    .sev-medium { background: #fef3c7; color: #92400e; border-color: #f59e0b; }
    .sev-high { background: #fee2e2; color: #b91c1b; border-color: #ef4444; }

    .sign-grid { width: 100%; margin-top: 25px; }
    .sign-cell { border: 1px solid #d1d5db; height: 90px; background: #f8fafc; text-align: center; }
    .sign-line { border-top: 1px solid #94a3b8; font-size: 12px; color: #1e293b; padding-top: 8px; font-weight: 600; }

    .muted { color: #6b7280; }
    .small { font-size: 10px; }
    .section-divider { height: 1px; background: #e5e7eb; margin: 20px 0; }

    .header-table, .footer-table { width: 100%; border-collapse: collapse; }
    .header-logo { height: 35px; }
  </style>
</head>
<body>

<!-- HEADER -->
<div class="header">
  <table class="header-table">
    <tr>
      <td style="width:20%;">
        @if(isset($logo))
          <img src="data:image/png;base64,{{ $logo }}" alt="Logo" class="header-logo">
        @endif
      </td>
      <td style="width:60%; text-align:center;">
        <div style="font-size:14px; font-weight:700;">INCADEV — REPORTE DE AUDITORÍA</div>
        <div style="font-size:10px; color:#cbd5e1;">Instituto de Capacitación y Desarrollo Virtual</div>
      </td>
      <td style="width:20%; text-align:right; font-size:9px;">
        <div><strong>Código:</strong> AUD{{ $audit->id }}</div>
        <div><strong>Generado:</strong> {{ now()->format('d/m/Y') }}</div>
      </td>
    </tr>
  </table>
</div>

<!-- FOOTER -->
<div class="footer">
  <table class="footer-table">
    <tr>
      <td class="small" style="width:33%;">INCADEV - Auditoría</td>
      <td class="small" style="width:34%; text-align:center;">AUD{{ $audit->id }}</td>
      <td class="small" style="width:33%; text-align:right;">Página {PAGE_NUM} de {PAGE_COUNT}</td>
    </tr>
  </table>
</div>

<!-- CONTENIDO -->
<div class="content">

  <h2 class="section-title">Información General</h2>
  <table>
    <tr>
      <td><strong>Auditor:</strong></td>
      <td>{{ $audit->auditor?->fullname ?? 'N/A' }}</td>
      <td><strong>Fecha:</strong></td>
      <td>{{ optional($audit->audit_date)->format('d/m/Y') }}</td>
    </tr>
    <tr>
      <td><strong>Resumen:</strong></td>
      <td colspan="3">{{ $audit->summary }}</td>
    </tr>
    <tr>
      <td><strong>Estado:</strong></td>
      <td colspan="3">
        @php
          $chip = 'chip';
          if($audit->status->value === 'pending') $chip .= ' chip-pending';
          elseif($audit->status->value === 'in_progress') $chip .= ' chip-in_progress';
          elseif($audit->status->value === 'completed') $chip .= ' chip-completed';
        @endphp
        <span class="{{ $chip }}">{{ strtoupper($audit->status->value) }}</span>
      </td>
    </tr>
  </table>

  <div class="section-divider"></div>

  <h2 class="section-title">Hallazgos</h2>
  @forelse($audit->findings as $finding)
    <div style="margin-bottom:15px; border:1px solid #d1d5db; border-radius:6px; padding:10px;">
      <strong>ID Hallazgo:</strong> #{{ $finding->id }}<br>
      <strong>Descripción:</strong> {{ $finding->description }}<br>
      <strong>Severidad:</strong>
      @php
        $sevClass = 'chip';
        if($finding->severity->value === 'LOW') $sevClass .= ' sev-low';
        elseif($finding->severity->value === 'MEDIUM') $sevClass .= ' sev-medium';
        elseif($finding->severity->value === 'HIGH') $sevClass .= ' sev-high';
      @endphp
      <span class="{{ $sevClass }}">{{ ucfirst(strtolower($finding->severity->value)) }}</span>
      <br>
      <strong>Estado:</strong> {{ ucfirst($finding->status->value) }}

      {{-- Evidencias --}}
      @if($finding->evidences->count())
        <h4 style="margin-top:8px;">Evidencias:</h4>
        @foreach($finding->evidences as $evidence)
          @if($evidence->type === 'text')
            <p class="small">📝 {{ $evidence->path }}</p>
          @else
            <p class="small">📎 Archivo: {{ basename($evidence->path) }}</p>
          @endif
        @endforeach
      @endif

      {{-- Acciones Correctivas --}}
      @if($finding->actions->count())
        <h4 style="margin-top:8px;">Acciones Correctivas:</h4>
        <table class="small">
          <thead>
            <tr>
              <th>Acción</th>
              <th>Responsable</th>
              <th>Fecha Límite</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody>
            @foreach($finding->actions as $action)
              <tr>
                <td>{{ $action->action_required }}</td>
                <td>{{ $action->responsible?->fullname ?? '—' }}</td>
                <td>{{ optional($action->due_date)->format('d/m/Y') }}</td>
                <td>{{ ucfirst($action->status->value) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @else
        <p class="small muted">No hay acciones correctivas para este hallazgo.</p>
      @endif
    </div>
  @empty
    <p class="muted">No se registraron hallazgos para esta auditoría.</p>
  @endforelse

  <div class="section-divider"></div>

  <h2 class="section-title">Recomendaciones Finales</h2>
  <p>{{ $audit->recommendation ?? 'No se han ingresado recomendaciones finales.' }}</p>

  <div class="section-divider"></div>

  <h2 class="section-title">Firmas</h2>
  <table class="sign-grid">
    <tr>
      <td class="sign-cell">
        <div class="sign-line">{{ $audit->auditor?->fullname ?? '____________________________' }}</div>
        <div class="small">Auditor Responsable</div>
      </td>
      <td style="width:10%; border:none;"></td>
      <td class="sign-cell">
        <div class="sign-line">{{ $audit->auditable?->name ?? '____________________________' }}</div>
        <div class="small">Área Auditada</div>
      </td>
    </tr>
  </table>
</div>
</body>
</html>
