<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use IncadevUns\CoreDomain\Models\Audit;

class ReportController extends Controller
{
    public function generate($id)
    {
        $audit = Audit::with(['findings.evidences', 'findings.actions'])->findOrFail($id);

        // Convertir logo a Base64
        $logoPath = public_path('images/incadev-logo.png');
        $logo = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : null;

        // Generar PDF con la vista institucional
        $pdf = Pdf::loadView('pdf.audit', compact('audit', 'logo'))
            ->setPaper('A4', 'portrait')
            ->setOptions(['isHtml5ParserEnabled' => true, 'isPhpEnabled' => true]);

        $path = "audits/{$audit->id}/report.pdf";
        Storage::disk('public')->put($path, $pdf->output());

        // Guardar ruta del reporte
        $audit->update([
            'path_report' => $path,
            'status' => 'completed',
        ]);

        return response()->json([
            'message' => 'Reporte generado correctamente.',
            'report_url' => Storage::url($path)
        ]);
    }

    public function preview($id)
    {
        $audit = Audit::findOrFail($id);
        $path = $audit->path_report;

        if (!$path || !Storage::disk('public')->exists($path)) {
            return response()->json(['error' => 'Reporte no encontrado'], 404);
        }

        return response()->file(storage_path("app/public/{$path}"));
    }

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
