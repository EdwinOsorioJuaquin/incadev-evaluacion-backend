<?php

namespace App\Http\Controllers\Evaluacion\Satisfaccion;

use Illuminate\Http\Request;
use App\Models\Evaluacion\Satisfaccion\Survey;
use App\Models\Evaluacion\Satisfaccion\Response;
use App\Models\Evaluacion\Satisfaccion\ResponseDetail;
use App\Models\Evaluacion\Satisfaccion\Question; // Asegúrate de importar tu modelo de preguntas
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
            'teacher'      => ['teacher'],
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
        // Encuesta con preguntas y mapping (evento)
        $survey = Survey::with(['questions', 'mapping'])->findOrFail($survey_id);

        // ✅ Validar que el usuario autenticado pueda responder según el evento
        if ($resp = $this->ensureCanAnswerSurvey($request, $survey)) {
            return $resp;
        }

        $user = $request->user();

        $request->validate([
            // ❌ Ya no aceptamos user_id desde el body
            'answers' => 'required|array|min:1',
            'answers.*.question_id' => 'required|integer|exists:survey_questions,id',
            'answers.*.score' => 'required|integer|min:1|max:5',
        ]);

        DB::beginTransaction();
        try {
            // Guardar la respuesta principal
            $response = Response::create([
                'survey_id'    => $survey->id,
                'user_id'      => $user->id,          // ✅ Siempre el usuario autenticado
                'rateable_type'=> Question::class,
                'rateable_id'  => $request->answers[0]['question_id'] ?? null,
                'date' => now(),
            ]);

            // Guardar los detalles de cada respuesta
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
