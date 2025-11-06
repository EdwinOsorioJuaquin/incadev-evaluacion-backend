<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use IncadevUns\CoreDomain\Models\Audit;
use IncadevUns\CoreDomain\Models\AuditFinding;
use IncadevUns\CoreDomain\Enums\AuditFindingStatus;


class AuditFindingController extends Controller
{
    public function index($auditId)
    {
        $findings = AuditFinding::where('audit_id', $auditId)
            ->with(['evidences', 'actions'])
            ->get();

        return response()->json($findings);
    }

    public function store(Request $request, $auditId)
    {
        $validated = $request->validate([
            'description' => 'required|string|min:10',
            'severity' => 'required|string',
        ]);

        $audit = Audit::findOrFail($auditId);

        $finding = $audit->findings()->create([
            'description' => $validated['description'],
            'severity' => $validated['severity'],
            'status' => 'open',
        ]);

        return response()->json([
            'message' => 'Hallazgo registrado correctamente.',
            'data' => $finding
        ], 201);
    }

    public function updateStatus(Request $request, $id)
{
    $validated = $request->validate([
        'status' => 'required|string'
    ]);

    $finding = AuditFinding::findOrFail($id);

    // Asignar usando el Enum correcto
    $finding->status = AuditFindingStatus::from($validated['status']);
    $finding->save();

    return response()->json([
        'message' => 'Estado del hallazgo actualizado correctamente.',
        'status' => $finding->status->value, // <- evita el error de conversión
    ]);
}
}
