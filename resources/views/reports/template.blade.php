<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Reporte de Encuesta - {{ $survey->title }}</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body { 
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
      padding: 50px;
      color: #1a1a1a;
      line-height: 1.6;
      background: #ffffff;
    }

    header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 40px;
      padding-bottom: 25px;
      border-bottom: 3px solid #000000;
    }

    header img {
      height: 70px;
    }

    header h1 {
      font-size: 32px;
      font-weight: 700;
      color: #000000;
      letter-spacing: -0.5px;
    }

    h2 { 
      color: #000000;
      font-size: 26px;
      font-weight: 700;
      margin-bottom: 12px;
      margin-top: 30px;
    }

    h3 { 
      color: #000000;
      font-size: 18px;
      font-weight: 600;
      margin-bottom: 15px;
      margin-top: 25px;
    }

    p { 
      margin: 8px 0;
      font-size: 14px;
      color: #2a2a2a;
    }

    table { 
      width: 100%; 
      border-collapse: separate;
      border-spacing: 0;
      margin-top: 20px;
      font-size: 13px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      border-radius: 8px;
      overflow: hidden;
    }

    th, td { 
      padding: 14px 16px;
      text-align: left;
      border-bottom: 1px solid #e5e5e5;
    }

    th { 
      background: #000000;
      color: #ffffff;
      font-weight: 600;
      font-size: 13px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    td {
      background: #ffffff;
      color: #2a2a2a;
    }

    tbody tr:hover {
      background: #f8f8f8;
    }

    tbody tr:last-child td {
      border-bottom: none;
    }

    .summary { 
      margin-top: 35px;
      background: #f5f5f5;
      padding: 25px;
      border-left: 5px solid #000000;
      font-size: 14px;
      border-radius: 0 8px 8px 0;
      box-shadow: 0 2px 6px rgba(0,0,0,0.06);
    }

    .summary h3 {
      margin-top: 0;
      margin-bottom: 18px;
    }

    .summary p {
      margin: 10px 0;
      line-height: 1.7;
    }

    .low-questions { 
      margin-top: 30px;
      background: #fafafa;
      padding: 25px;
      border-radius: 8px;
      border: 2px solid #e0e0e0;
    }

    .low-questions h3 {
      margin-top: 0;
    }

    .low-questions ul { 
      margin: 15px 0 0 0;
      padding-left: 25px;
      list-style-type: none;
    }

    .low-questions li {
      padding: 10px 0;
      font-size: 14px;
      color: #2a2a2a;
      border-bottom: 1px solid #e5e5e5;
      position: relative;
      padding-left: 20px;
    }

    .low-questions li:before {
      content: "▸";
      position: absolute;
      left: 0;
      color: #000000;
      font-weight: bold;
    }

    .low-questions li:last-child {
      border-bottom: none;
    }

    footer { 
      margin-top: 50px;
      padding-top: 25px;
      text-align: center;
      font-size: 12px;
      color: #666666;
      border-top: 2px solid #e5e5e5;
    }

    .highlight {
      color: #000000;
      font-weight: 700;
      font-size: 16px;
      background: #f0f0f0;
      padding: 2px 8px;
      border-radius: 4px;
    }

    strong {
      font-weight: 600;
      color: #000000;
    }

    /* Estilos para números de tabla */
    td:first-child {
      font-weight: 600;
      color: #000000;
      text-align: center;
      width: 60px;
    }

    /* Mejora visual de promedios en tabla */
    td:nth-child(3) {
      font-weight: 600;
      color: #000000;
      text-align: center;
      font-size: 14px;
    }

    td:nth-child(4) {
      text-align: center;
      color: #666666;
    }
  </style>
</head>
<body>
  <header>
    <img src="{{ public_path('ISOLOGOTIPO_HORIZONTAL.svg') }}" alt="Logo Empresa">
    <h1>Reporte de Encuesta</h1>
  </header>

  <h2>{{ $survey->title }}</h2>
  @if($survey->description)
    <p>{{ $survey->description }}</p>
  @endif

  @php
      $allScores = $survey->questions->map(fn($q) => $q->details->avg('score') ?? 0);
      $avgGeneral = round($allScores->avg(), 2);
      $low = $survey->questions->sortBy(fn($q) => $q->details->avg('score') ?? 0)->take(3);
  @endphp

  <div class="summary">
    <h3>Resumen General</h3>
    <p><strong>Promedio general:</strong> <span class="highlight">{{ $avgGeneral }}</span></p>
    <p><strong>Total de preguntas:</strong> {{ $survey->questions->count() }}</p>
    <p><strong>Total de respuestas:</strong>
      {{ $survey->questions->sum(fn($q) => $q->details->count()) }}
    </p>
  </div>

  <h3>Promedio por pregunta</h3>
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Pregunta</th>
        <th>Promedio</th>
        <th>Respuestas</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($survey->questions as $index => $q)
        <tr>
          <td>{{ $index + 1 }}</td>
          <td>{{ $q->question }}</td>
          <td>{{ number_format($q->details->avg('score'), 2) }}</td>
          <td>{{ $q->details->count() }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <div class="low-questions">
    <h3>Preguntas con menor puntuación</h3>
    <ul>
      @foreach ($low as $q)
        <li>{{ $q->question }} — Promedio: <span class="highlight">{{ number_format($q->details->avg('score'), 2) }}</span></li>
      @endforeach
    </ul>
  </div>

  <div class="summary">
    <h3>Recomendaciones del sistema</h3>
    @if ($avgGeneral >= 4.5)
      <p>Excelente desempeño general. Mantén las estrategias actuales y comparte las buenas prácticas con el equipo.</p>
    @elseif ($avgGeneral >= 3.5)
      <p>Buen nivel de satisfacción. Se sugiere reforzar los aspectos menos valorados para alcanzar la excelencia.</p>
    @elseif ($avgGeneral >= 2.5)
      <p>Nivel intermedio detectado. Enfócate en mejorar las áreas más débiles y diseña un plan de mejora específico.</p>
    @else
      <p>Nivel bajo de satisfacción. Urge una revisión integral de los procesos y retroalimentación directa con los participantes.</p>
    @endif
  </div>

  <footer>
    <p>Reporte generado automáticamente el {{ now()->format('d/m/Y H:i') }}.</p>
  </footer>
</body>
</html>