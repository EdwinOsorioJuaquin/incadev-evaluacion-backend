<?php

namespace App\Http\Controllers\Evaluacion\Satisfaccion;

use Illuminate\Http\Request;
use App\Models\Evaluacion\Satisfaccion\Survey;
use App\Models\Evaluacion\Satisfaccion\Question;
use Illuminate\Support\Facades\DB;

class QuestionController extends Controller
{
    public function index($surveyId)
    {
        $survey = Survey::with('questions')->find($surveyId);

        if (!$survey) {
            return response()->json([
                'success' => false,
                'message' => 'Encuesta no encontrada.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $survey
        ]);
    }

    public function storeQuestions(Request $request, $surveyId)
    {
        $survey = Survey::find($surveyId);

        if (!$survey) {
            return response()->json([
                'success' => false,
                'message' => 'Encuesta no encontrada.'
            ], 404);
        }

        $request->validate([
            'questions' => 'required|array',
            'questions.*.text' => 'required|string|max:500',
            'questions.*.type' => 'required|string|in:opcion_multiple,texto,escala',
        ]);

        DB::beginTransaction();
        try {
            $created = [];

            foreach ($request->questions as $q) {
                $question = $survey->questions()->create([
                    'question' => $q['text'],
                    'order' => $q['order'] ?? 1,
                ]);

                $created[] = $question;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Preguntas creadas correctamente.',
                'data' => $created
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar preguntas: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $question = Question::find($id);

        if (!$question) {
            return response()->json([
                'success' => false,
                'message' => 'Pregunta no encontrada.'
            ], 404);
        }

        DB::beginTransaction();
        try {
            // eliminar relaciones si existen
            $question->delete();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pregunta eliminada correctamente.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la pregunta: ' . $e->getMessage()
            ], 500);
        }
    }
}
