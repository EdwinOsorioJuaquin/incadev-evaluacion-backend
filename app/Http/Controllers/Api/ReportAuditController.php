<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use IncadevUns\CoreDomain\Models\Audit;

/**
 * @OA\Tag(
 *     name="Reportes de Auditoría",
 *     description="Generación, previsualización y descarga de reportes en PDF de auditorías."
 * )
 */
class ReportAuditController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/audits/{id}/report",
     *     tags={"Reportes de Auditoría"},
     *     summary="Generar reporte PDF de una auditoría",
     *     description="Genera un reporte PDF institucional con los hallazgos, evidencias y acciones correctivas asociadas a una auditoría.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la auditoría",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Reporte generado correctamente."),
     *     @OA\Response(response=404, description="Auditoría no encontrada")
     * )
     */
    public function generate($id)
    {
        $audit = Audit::with(['findings.evidences', 'findings.actions'])->findOrFail($id);

        // Convertir logo a Base64
        $logoPath = public_path('images/incadev-logo.png');
        $logo = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : null;

        // Generar PDF
        $pdf = Pdf::loadView('pdf.audit', compact('audit', 'logo'))
            ->setPaper('A4', 'portrait')
            ->setOptions(['isHtml5ParserEnabled' => true, 'isPhpEnabled' => true]);

        // Guardar PDF en storage
        $path = "audits/{$audit->id}/report.pdf";
        Storage::disk('public')->put($path, $pdf->output());

        // Actualizar auditoría
        $audit->update([
            'path_report' => $path,
            'status' => 'completed',
        ]);

        return response()->json([
            'message' => 'Reporte generado correctamente.',
            'report_url' => Storage::url($path),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/audits/{id}/report/preview",
     *     tags={"Reportes de Auditoría"},
     *     summary="Previsualizar reporte PDF",
     *     description="Muestra el archivo PDF del reporte directamente en el navegador.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la auditoría",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Vista previa del reporte PDF."),
     *     @OA\Response(response=404, description="Reporte no encontrado")
     * )
     */
    public function preview($id)
    {
        $audit = Audit::findOrFail($id);
        $path = $audit->path_report;

        if (!$path || !Storage::disk('public')->exists($path)) {
            return response()->json(['error' => 'Reporte no encontrado'], 404);
        }

        return response()->file(storage_path("app/public/{$path}"));
    }

    /**
     * @OA\Get(
     *     path="/api/audits/{id}/report/download",
     *     tags={"Reportes de Auditoría"},
     *     summary="Descargar reporte PDF",
     *     description="Descarga el reporte PDF de la auditoría con su información consolidada.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la auditoría",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Reporte descargado correctamente."),
     *     @OA\Response(response=404, description="Reporte no encontrado")
     * )
     */
    public function download($id)
    {
        $audit = Audit::findOrFail($id);
        $path = $audit->path_report;

        if (!$path || !Storage::disk('public')->exists($path)) {
            return response()->json(['error' => 'Reporte no encontrado'], 404);
        }

        return response()->download(storage_path("app/public/{$path}"), "auditoria_{$audit->id}.pdf");
    }
}
