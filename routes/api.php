<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Evaluacion\Satisfaccion\SurveyController;
use App\Http\Controllers\Evaluacion\Satisfaccion\ResponseController;
use App\Http\Controllers\Evaluacion\Satisfaccion\ReportController;
use App\Http\Controllers\Evaluacion\Satisfaccion\QuestionController;
use App\Http\Controllers\Api\{
    AuditController,
    AuditFindingController,
    FindingEvidenceController,
    AuditActionController,
    ReportAuditController
};

Route::middleware('auth:sanctum')->group(function () {
    // Auditorías
    Route::get('/audits', [AuditController::class, 'index']);
    Route::post('/audits', [AuditController::class, 'store']);
    Route::get('/audits/{id}', [AuditController::class, 'show']);
    Route::put('/audits/{id}/recommendation', [AuditController::class, 'updateRecommendation']);

    // Hallazgos
    Route::get('/audits/{id}/findings', [AuditFindingController::class, 'index']);
    Route::post('/audits/{id}/findings', [AuditFindingController::class, 'store']);
    Route::put('/findings/{id}/status', [AuditFindingController::class, 'updateStatus']);

    // Evidencias
    Route::post('/findings/{id}/evidences', [FindingEvidenceController::class, 'store']);

    // Acciones correctivas
    Route::post('/findings/{id}/actions', [AuditActionController::class, 'store']);
    Route::put('/actions/{id}/status', [AuditActionController::class, 'updateStatus']);

    // Reporte PDF
    Route::post('/audits/{id}/report/generate', [ReportAuditController::class, 'generate']);
    Route::get('/audits/{id}/report/preview', [ReportAuditController::class, 'preview']);
    Route::get('/audits/{id}/report/download', [ReportAuditController::class, 'download']);
});
// SATISFACCION
// 🔹 Ruta de prueba para verificar API
Route::get('/test', function () {
    return response()->json(['message' => '✅ API funcionando correctamente']);
});

// 🧠 CRUD de encuestas
Route::get('/surveys', [SurveyController::class, 'index']);
Route::get('/surveys/{id}', [SurveyController::class, 'show']);
Route::post('/surveys', [SurveyController::class, 'store']);
Route::put('/surveys/{id}', [SurveyController::class, 'update']);
Route::delete('/surveys/{id}', [SurveyController::class, 'destroy']);
Route::get('/student/surveys', [SurveyController::class, 'active']);

//  Obtener encuesta con preguntas (para el frontend)
//Route::get('/surveys/{id}/questions', [ResponseController::class, 'show']);

//  Enviar respuestas de encuesta
Route::post('/surveys/{survey_id}/responses', [ResponseController::class, 'store']);

//  Contar respuestas por encuesta (dashboard)
Route::get('/responses/count', function() {
    $surveys = \App\Models\Evaluacion\Satisfaccion\Survey::withCount('responses')->get();
    return response()->json([
        'data' => $surveys->map(fn($s) => [
            'id' => $s->id,
            'title' => $s->title,
            'responses_count' => $s->responses_count,
        ])
    ]);
});

//  Consultar todas las respuestas (opcional)
Route::get('/responses', [ResponseController::class, 'index']);
// Route::get('/responses/{id}', [ResponseController::class, 'showResponse']);


//  Reportes / estadísticas
Route::get('/reports', [ReportController::class, 'index']);
Route::get('/reports/survey/{id}', [ReportController::class, 'generate']);
Route::get('/reports/survey/{id}/pdf', [ReportController::class, 'downloadPdf']);
Route::get('/reports/survey/{id}/excel', [ReportController::class, 'downloadExcel']);
Route::get('/surveys/{id}/analysis', [SurveyController::class, 'analysis']);

// 🧩 Preguntas (QuestionController)
Route::get('/surveys/{surveyId}/questions', [QuestionController::class, 'index'])
    ->name('api.surveys.questions.index');
Route::post('/surveys/{surveyId}/questions', [QuestionController::class, 'storeQuestions'])
    ->name('api.surveys.questions.store');
Route::delete('/questions/{id}', [QuestionController::class, 'destroy'])
    ->name('api.questions.destroy');


