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
    public function store(Request $request, $survey_id)
    {
        $survey = Survey::with('questions')->findOrFail($survey_id);

        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|integer|exists:survey_questions,id',
            'answers.*.score' => 'required|integer|min:1|max:5',
        ]);

        $userId = $request->input('user_id');

        DB::beginTransaction();
        try {
            // Guardar la respuesta principal
            $response = Response::create([
                'survey_id' => $survey->id,
                'user_id' => $userId,
                // Polimórfico: guardamos la primera pregunta solo como referencia
                'rateable_type' => Question::class,
                'rateable_id' => $request->answers[0]['question_id'] ?? null,
            ]);

            // Guardar los detalles de cada respuesta
            foreach ($request->answers as $answer) {
                ResponseDetail::create([
                    'survey_response_id' => $response->id,
                    'survey_question_id' => $answer['question_id'],
                    'score' => $answer['score'],
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Encuesta respondida correctamente',
                'response_id' => $response->id,
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al guardar respuestas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
