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
    Route::get('/audits/dashboard', [AuditController::class, 'dashboard']);
    Route::put('/audits/{id}', [AuditController::class, 'update']);
    Route::put('/audits/{id}/start', [AuditController::class, 'startAudit']);
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

    // Encuestas
    Route::prefix('surveys')->group(function () {
        Route::get('/', [SurveyController::class, 'index']);
        Route::post('/', [SurveyController::class, 'store']);
        Route::get('/active', [SurveyController::class, 'active']);
        Route::get('/by-role', [SurveyController::class, 'byRole']);
        Route::get('/completed', [SurveyController::class, 'isSurveyCompleted']);
        Route::get('/{id}', [SurveyController::class, 'show']);
        Route::put('/{id}', [SurveyController::class, 'update']);
        Route::delete('/{id}', [SurveyController::class, 'destroy']);
    });

    Route::get('/surveys/{survey}/questions', [QuestionController::class, 'index']);
    Route::post('/surveys/{survey}/questions', [QuestionController::class, 'store']);

    Route::get('/questions/{id}', [QuestionController::class, 'show']);
    Route::put('/questions/{id}', [QuestionController::class, 'update']);
    Route::delete('/questions/{id}', [QuestionController::class, 'destroy']);

    // Respuestas a la encuesta
    Route::post('/surveys/{survey}/responses', [ResponseController::class, 'store']);
});
// SATISFACCION
// 🔹 Ruta de prueba para verificar API
Route::get('/test', function () {
    return response()->json(['message' => '✅ API funcionando correctamente']);
});

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

//  Reportes / estadísticas
Route::get('/reports', [ReportController::class, 'index']);
Route::get('/reports/survey/{id}', [ReportController::class, 'generate']);
Route::get('/reports/survey/{id}/pdf', [ReportController::class, 'downloadPdf']);
Route::get('/reports/survey/{id}/excel', [ReportController::class, 'downloadExcel']);
Route::get('/surveys/{id}/analysis', [SurveyController::class, 'analysis']);


