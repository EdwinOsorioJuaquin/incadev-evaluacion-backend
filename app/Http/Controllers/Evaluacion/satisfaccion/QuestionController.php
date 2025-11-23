<?php

namespace App\Http\Controllers\Evaluacion\Satisfaccion;

use Illuminate\Http\Request;
use App\Models\Evaluacion\Satisfaccion\Survey;
use App\Models\Evaluacion\Satisfaccion\Question;
use Illuminate\Support\Facades\DB;

class QuestionController extends Controller
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
                'message' => 'No tienes permisos para gestionar preguntas de encuestas.',
            ], 403);
        }

        return null;
    }

    public function index(Request $request, $surveyId)
    {
        if ($resp = $this->ensureSurveyAdmin($request)) {
            return $resp;
        }

        $survey = Survey::with('questions')->find($surveyId);

        if (!$survey) {
            return response()->json([
                'success' => false,
                'message' => 'Encuesta no encontrada.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $survey->questions,
        ]);
    }

    public function store(Request $request, $surveyId)
    {
        if ($resp = $this->ensureSurveyAdmin($request)) {
            return $resp;
        }

        $survey = Survey::find($surveyId);

        if (!$survey) {
            return response()->json([
                'success' => false,
                'message' => 'Encuesta no encontrada.',
            ], 404);
        }

        $request->validate([
            'question'  => 'required|string|max:500',
            'order' => 'nullable|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            $question = $survey->questions()->create([
                'question' => $request->question,
                'order'    => $request->order ?? 1,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pregunta creada correctamente.',
                'data'    => $question,
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al crear la pregunta.',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, $id)
    {
        if ($resp = $this->ensureSurveyAdmin($request)) {
            return $resp;
        }

        $question = Question::find($id);

        if (!$question) {
            return response()->json([
                'success' => false,
                'message' => 'Pregunta no encontrada.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $question,
        ]);
    }

    public function update(Request $request, $id)
    {
        if ($resp = $this->ensureSurveyAdmin($request)) {
            return $resp;
        }

        $question = Question::find($id);

        if (!$question) {
            return response()->json([
                'success' => false,
                'message' => 'Pregunta no encontrada.',
            ], 404);
        }

        $request->validate([
            'question'  => 'required|string|max:500',
            'order' => 'nullable|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            $question->update([
                'question' => $request->question,
                'order'    => $request->order ?? $question->order,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pregunta actualizada correctamente.',
                'data'    => $question,
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la pregunta.',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        if ($resp = $this->ensureSurveyAdmin($request)) {
            return $resp;
        }

        $question = Question::find($id);

        if (!$question) {
            return response()->json([
                'success' => false,
                'message' => 'Pregunta no encontrada.',
            ], 404);
        }

        DB::beginTransaction();

        try {
            if (method_exists($question, 'details')) {
                $question->details()->delete();
            }

            $question->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pregunta eliminada correctamente.',
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la pregunta.',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }
}
