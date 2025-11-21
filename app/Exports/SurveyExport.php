<?php

namespace App\Exports;

use App\Models\evaluacion\satisfaccion\Survey;
use Maatwebsite\Excel\Concerns\FromArray;

class SurveyExport implements FromArray
{
    protected $surveyId;

    public function __construct($surveyId)
    {
        $this->surveyId = $surveyId;
    }

    public function array(): array
    {
        // Cargamos la encuesta con preguntas y sus detalles
        $survey = Survey::with(['questions.details'])->findOrFail($this->surveyId);

        $allScores = $survey->questions->map(fn($q) => $q->details->avg('score') ?? 0);
        $avgGeneral = round($allScores->avg(), 2);

        // Tomamos las 3 preguntas con menor promedio
        $lowQuestions = $survey->questions
            ->sortBy(fn($q) => $q->details->avg('score') ?? 0)
            ->take(3);

        // Encabezado del Excel
        $data = [
            ['Encuesta', $survey->title],
            ['Descripción', $survey->description],
            ['Promedio General', $avgGeneral],
            ['Total de Preguntas', $survey->questions->count()],
            ['Total de Respuestas', $survey->questions->sum(fn($q) => $q->details->count())],
            [],
            ['Pregunta', 'Promedio', 'Total Respuestas']
        ];

        // Agregamos cada pregunta con promedio y total de respuestas
        foreach ($survey->questions as $q) {
            $avg = round($q->details->avg('score') ?? 0, 2);
            $total = $q->details->count();
            $data[] = [$q->question, $avg, $total];
        }

        // Sección de preguntas con menor puntuación
        if ($lowQuestions->count() > 0) {
            $data[] = [];
            $data[] = ['Preguntas con menor puntuación'];
            $data[] = ['Pregunta', 'Promedio'];
            foreach ($lowQuestions as $q) {
                $avg = round($q->details->avg('score') ?? 0, 2);
                $data[] = [$q->question, $avg];
            }
        }

        // Recomendaciones según promedio general
        $recommendation = '';
        if ($avgGeneral >= 4.5) {
            $recommendation = 'Excelente desempeño general. Mantén las estrategias actuales y comparte las buenas prácticas con el equipo.';
        } elseif ($avgGeneral >= 3.5) {
            $recommendation = 'Buen nivel de satisfacción. Reforzar los aspectos menos valorados para alcanzar la excelencia.';
        } elseif ($avgGeneral >= 2.5) {
            $recommendation = 'Nivel intermedio detectado. Mejorar las áreas más débiles y diseñar un plan de mejora específico.';
        } else {
            $recommendation = 'Nivel bajo de satisfacción. Urge una revisión integral de los procesos y retroalimentación directa con los participantes.';
        }

        $data[] = [];
        $data[] = ['Recomendaciones del sistema'];
        $data[] = [$recommendation];

        return $data;
    }
}
