<?php

namespace App\Http\Controllers\Evaluation;

use Illuminate\Http\Request;
use App\Models\Evaluation\Survey;
use App\Models\Evaluation\Response;
use App\Models\Evaluation\ResponseDetail;
use App\Models\Evaluation\Question;
use IncadevUns\CoreDomain\Models\Group;
use IncadevUns\CoreDomain\Enums\GroupStatus;

use Illuminate\Support\Facades\DB;

class ResponseController extends Controller
{
    private function ensureCanAnswerSurvey(Request $request, Survey $survey)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
            ], 401);
        }

        // Asegurarnos de tener el mapping cargado
        $survey->loadMissing('mapping');

        if (!$survey->mapping || !$survey->mapping->event) {
            return response()->json([
                'success' => false,
                'message' => 'La encuesta no tiene un evento asociado.',
            ], 400);
        }

        $event = $survey->mapping->event;

        // Roles permitidos según el tipo de evento
        $allowedRoles = match ($event) {
            'impact'       => ['student'],
            'satisfaction' => ['student'],
            'teacher'      => ['teacher', 'student'],
            default        => [],
        };

        if (empty($allowedRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de encuesta no soportado.',
            ], 400);
        }

        if (!$user->hasAnyRole($allowedRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para responder este tipo de encuesta.',
            ], 403);
        }

        // Si todo OK, devolvemos null
        return null;
    }

    public function store(Request $request, $survey_id)
    {
        $survey = Survey::with(['questions', 'mapping'])->findOrFail($survey_id);

        if ($resp = $this->ensureCanAnswerSurvey($request, $survey)) {
            return $resp;
        }

        $user = $request->user();

        $request->validate([
            'rateable_id' => 'required|integer|exists:groups,id',
            'answers' => 'required|array|min:1',
            'answers.*.question_id' => 'required|integer|exists:survey_questions,id',
            'answers.*.score' => 'required|integer|min:1|max:5',
        ]);

        $group = Group::where('id', $request->rateable_id)
            ->where('status', GroupStatus::Completed)
            ->firstOrFail();

        DB::beginTransaction();
        try {
            $response = Response::create([
                'survey_id'     => $survey->id,
                'user_id'       => $user->id,
                'rateable_id'   => $request->rateable_id,
                'rateable_type' => Group::class,
                'date'  => now(),
            ]);

            foreach ($request->answers as $answer) {
                ResponseDetail::create([
                    'survey_response_id' => $response->id,
                    'survey_question_id' => $answer['question_id'],
                    'score'              => $answer['score'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success'     => true,
                'message'     => 'Encuesta respondida correctamente',
                'response_id' => $response->id,
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar respuestas',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
