<?php

namespace App\Http\Controllers\Evaluation;

use Illuminate\Http\Request;
use App\Models\Evaluation\Survey;
use App\Models\Evaluation\Question;
use App\Models\Evaluation\ResponseDetail;
use App\Models\Evaluation\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SurveyExport;
use Maatwebsite\Excel\Excel as ExcelFormat;

class ReportController extends Controller
{
    /**
     * 📋 1. Listar todas las encuestas disponibles (para dashboard)
     */
    public function index()
    {
        $surveys = Survey::select('id', 'title', 'description', 'created_at')->get();
        return response()->json($surveys);
    }

    /**
     * 📊 2. Generar datos resumidos del reporte (para gráficos en frontend)
     */
    public function generate($surveyId)
    {
        $survey = Survey::with('questions')->findOrFail($surveyId);

        $labels = [];
        $values = [];

        foreach ($survey->questions as $question) {
            $labels[] = $question->question;
            $count = ResponseDetail::where('survey_question_id', $question->id)->count();
            $values[] = $count;
        }

        return response()->json([
            'survey' => $survey,
            'labels' => $labels,
            'values' => $values,
        ]);
    }

    /**
     * 📄 3. Mostrar reporte detallado de una encuesta
     */
    public function show($surveyId)
    {
        $survey = Survey::with(['questions.details'])->findOrFail($surveyId);
        return response()->json($survey);
    }

    /**
     * 🧾 4. Descargar reporte en PDF (almacenado en /public/reports)
     */
    public function downloadPdf($surveyId)
    {
        $survey = Survey::with(['questions.details'])->findOrFail($surveyId);

        $directory = public_path('reports');
        $fileName = 'reporte_encuesta_' . $surveyId . '.pdf';
        $filePath = $directory . '/' . $fileName;

        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }

        // ✅ Carga la vista Blade formal
        $html = view('reports.template', compact('survey'))->render();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait');
        $pdf->save($filePath);

        return response()->download($filePath);
    }

    /**
     * 📊 5. Descargar reporte en Excel (almacenado en /storage/app/public/reports)
     */
    public function downloadExcel($surveyId)
    {
        $survey = Survey::findOrFail($surveyId);

        $directory = public_path('reports');
        $fileName = 'reporte_encuesta_' . $surveyId . '.xlsx';
        $filePath = $directory . '/' . $fileName;

        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }

        $content = Excel::raw(
            new SurveyExport($surveyId),
            ExcelFormat::XLSX
        );

        file_put_contents($filePath, $content);

        return response()->download($filePath);
    }
}
