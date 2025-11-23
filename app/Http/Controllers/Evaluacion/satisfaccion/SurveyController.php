<?php


namespace App\Http\Controllers\Evaluacion\Satisfaccion;

use Illuminate\Http\Request;
use App\Models\Evaluacion\Satisfaccion\Survey;
use App\Models\Evaluacion\Satisfaccion\Response;
use Illuminate\Support\Facades\DB;
use App\Models\Evaluacion\Satisfaccion\SurveyMapping;

class SurveyController extends Controller
{
    private function ensureSurveyAdmin(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
            ], 401);
        }

        if (!$user->hasRole('survey_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para gestionar encuestas.',
            ], 403);
        }

        return null;
    }

    public function index(Request $request)
    {
        if ($resp = $this->ensureSurveyAdmin($request)) {
            return $resp;
        }

        $perPage = (int) $request->get('per_page', 10);

        $surveys = Survey::with(['questions', 'mapping'])
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $surveys->items(),
            'meta' => [
                'current_page' => $surveys->currentPage(),
                'from'         => $surveys->firstItem(),
                'to'           => $surveys->lastItem(),
                'per_page'     => $surveys->perPage(),
                'total'        => $surveys->total(),
                'last_page'    => $surveys->lastPage(),
            ],
            'links' => [
                'first' => $surveys->url(1),
                'last'  => $surveys->url($surveys->lastPage()),
                'prev'  => $surveys->previousPageUrl(),
                'next'  => $surveys->nextPageUrl(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        if ($resp = $this->ensureSurveyAdmin($request)) {
            return $resp;
        }

        $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'event' => 'required|string|in:satisfaction,teacher,impact',
            'mapping_description' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $survey = Survey::create([
                'title' => $request->title,
                'description' => $request->description,
            ]);

            $survey->mapping()->create([
                'event' => $request->event,
                'description' => $request->mapping_description,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $survey->load(['questions', 'mapping']),
            ], 201);

        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al crear la encuesta',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, $id)
    {
        if ($resp = $this->ensureSurveyAdmin($request)) {
            return $resp;
        }

        $survey = Survey::with(['questions', 'mapping'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $survey
        ]);
    }

    public function update(Request $request, $id)
    {
        if ($resp = $this->ensureSurveyAdmin($request)) {
            return $resp;
        }

        $survey = Survey::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event' => 'required|string|in:satisfaction,teacher,impact',
            'mapping_description' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $survey->update([
                'title' => $request->title,
                'description' => $request->description,
            ]);

            $survey->mapping()->updateOrCreate(
                [],
                [
                    'event' => $request->event,
                    'description' => $request->mapping_description,
                ]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Encuesta actualizada correctamente.',
                'data' => $survey->load(['questions', 'mapping'])
            ]);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar encuesta.',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        if ($resp = $this->ensureSurveyAdmin($request)) {
            return $resp;
        }

        DB::transaction(function () use ($id) {
            $survey = Survey::findOrFail($id);

            $responses = Response::where('survey_id', $survey->id)->get();

            foreach ($responses as $response) {
                $response->details()->delete();
            }

            $survey->responses()->delete();
            $survey->questions()->delete();
            if ($survey->mapping) {
                $survey->mapping()->delete();
            }

            $survey->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Encuesta eliminada correctamente.'
        ]);
    }

    public function active(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
            ], 401);
        }

        $request->validate([
            'event' => 'required|string|in:satisfaction,teacher,impact',
        ]);

        $event = $request->input('event');

        $allowedRoles = match ($event) {
            'impact'       => ['student'],
            'satisfaction' => ['student'],
            'teacher'      => ['teacher'],
            default        => [],
        };

        if (!$user->hasAnyRole($allowedRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para este tipo de encuesta.',
            ], 403);
        }

        // ¿Ha respondido alguna survey de este evento?
        $hasResponded = Response::where('user_id', $user->id)
            ->whereHas('survey.mapping', function ($q) use ($event) {
                $q->where('event', $event);
            })
            ->exists();

        return response()->json([
            'success' => true,
            'data' => [
                'event'        => $event,
                'hasResponded' => $hasResponded,
            ],
        ]);
    }

    public function isPastTime(Request $request)
    {
        if ($resp = $this->ensureSurveyAdmin($request)) {
            return $resp;
        }

        $surveys = Survey::get(['updated_at']);
        $time = "";

        return response()->json([
            'success' => true,
            'data' => $surveys
        ]);
    }

    public function analysis($id)
    {
        try {
            // Encuesta base
            $survey = Survey::with('questions')->findOrFail($id);

            // Obtener promedios por pregunta
            $query = DB::table('response_details')
                ->join('survey_responses', 'response_details.survey_response_id', '=', 'survey_responses.id')
                ->join('survey_questions', 'response_details.survey_question_id', '=', 'survey_questions.id')
                ->where('survey_responses.survey_id', $id)
                ->select(
                    'survey_questions.id as question_id',
                    'survey_questions.question as question_text',
                    DB::raw('AVG(response_details.score) as avg_score')
                )
                ->groupBy('survey_questions.id', 'survey_questions.question')
                ->get();

            $totalResponses = DB::table('survey_responses')
                ->where('survey_id', $id)
                ->count();

            $totalQuestions = $query->count();
            $avgGeneral = $totalQuestions > 0 ? $query->avg('avg_score') : 0;

            // Preguntas con menor puntaje
            $lowestQuestions = $query
                ->sortBy('avg_score')
                ->take(3)
                ->map(fn($q) => "{$q->question_text} (Promedio: " . round($q->avg_score, 2) . ")")
                ->values()
                ->toArray();

            $recommendation = $this->generateSmartRecommendation($avgGeneral, $lowestQuestions);

            return response()->json([
                'survey' => [
                    'id' => $survey->id,
                    'title' => $survey->title,
                    'description' => $survey->description,
                ],
                'kpis' => [
                    ['label' => 'Total de Respuestas', 'value' => $totalResponses],
                    ['label' => 'Promedio General', 'value' => round($avgGeneral, 2)],
                    ['label' => 'Preguntas Totales', 'value' => $totalQuestions],
                ],
                'chartData' => $query->map(fn($item) => [
                    'label' => $item->question_text,
                    'value' => round($item->avg_score, 2),
                ]),
                'recommendation' => $recommendation,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al generar el análisis',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    private function generateSmartRecommendation($avg, $lowestQuestions)
    {
        $intro = '';
        $suggestion = '';

        if ($avg >= 4.5) {
            $intro = 'Excelente desempeño general:';
            $suggestion = 'Mantén las estrategias actuales, refuerza las buenas prácticas y comparte los resultados positivos con el equipo.';
        } elseif ($avg >= 3.5) {
            $intro = 'Buen nivel de satisfacción:';
            $suggestion = 'Ajusta pequeños detalles en los aspectos menos valorados para alcanzar un nivel de excelencia.';
        } elseif ($avg >= 2.5) {
            $intro = 'Nivel intermedio detectado:';
            $suggestion = 'Se recomienda enfocarse en las áreas más débiles y diseñar un plan de mejora específico.';
        } else {
            $intro = 'Nivel bajo de satisfacción:';
            $suggestion = 'Urge una revisión integral de los procesos y una retroalimentación directa con los participantes.';
        }

        // ✅ Formato claro con saltos de línea y viñetas
        $formatted = "{$intro}\n{$suggestion}";

        if (!empty($lowestQuestions)) {
            $formatted .= "\nPreguntas con menor puntaje:";
            foreach ($lowestQuestions as $q) {
                $formatted .= "\n- {$q}";
            }
        }

        return $formatted;
    }
}
