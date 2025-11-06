<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    AuditController,
    AuditFindingController,
    FindingEvidenceController,
    AuditActionController,
    ReportController
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
    Route::post('/audits/{id}/report/generate', [ReportController::class, 'generate']);
    Route::get('/audits/{id}/report/preview', [ReportController::class, 'preview']);
    Route::get('/audits/{id}/report/download', [ReportController::class, 'download']);
});


