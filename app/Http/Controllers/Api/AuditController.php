<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use IncadevUns\CoreDomain\Models\Audit;

class AuditController extends Controller
{
    /**
     * Listar auditorías del auditor autenticado.
     */
    public function index()
    {
        $audits = Audit::where('auditor_id', Auth::id())
            ->orderByDesc('audit_date')
            ->get();

        return response()->json($audits);
    }

    /**
     * Crear una nueva auditoría.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'audit_date' => 'required|date',
            'summary' => 'required|string|min:5',
            'auditable_type' => 'required|string',
            'auditable_id' => 'required|integer',
        ]);

        $audit = Audit::create([
            'auditor_id' => Auth::id(),
            'audit_date' => $validated['audit_date'],
            'summary' => $validated['summary'],
            'auditable_type' => $validated['auditable_type'],
            'auditable_id' => $validated['auditable_id'],
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Auditoría creada correctamente.',
            'data' => $audit
        ], 201);
    }

    /**
     * Ver detalles completos de una auditoría.
     */
    public function show($id)
    {
        $audit = Audit::with(['findings.evidences', 'findings.actions'])->findOrFail($id);
        return response()->json($audit);
    }

    /**
     * Actualizar recomendaciones finales.
     */
    public function updateRecommendation(Request $request, $id)
    {
        $validated = $request->validate([
            'recommendation' => 'required|string|min:10',
        ]);

        $audit = Audit::findOrFail($id);
        $audit->recommendation = $validated['recommendation'];
        $audit->save();

        return response()->json([
            'message' => 'Recomendaciones actualizadas correctamente.',
            'audit_id' => $audit->id,
        ]);
    }
}
