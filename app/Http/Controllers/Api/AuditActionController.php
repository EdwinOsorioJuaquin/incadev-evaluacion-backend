<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use IncadevUns\CoreDomain\Models\AuditFinding;
use IncadevUns\CoreDomain\Models\AuditAction;

class AuditActionController extends Controller
{
    public function store(Request $request, $findingId)
    {
        $validated = $request->validate([
            'responsible_id' => 'required|integer',
            'action_required' => 'required|string|min:5',
            'due_date' => 'required|date|after_or_equal:today',
        ]);

        $finding = AuditFinding::findOrFail($findingId);

        $action = $finding->actions()->create([
            'responsible_id' => $validated['responsible_id'],
            'action_required' => $validated['action_required'],
            'due_date' => $validated['due_date'],
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Acción correctiva registrada correctamente.',
            'data' => $action
        ], 201);
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate(['status' => 'required|string']);
        $action = AuditAction::findOrFail($id);
        $action->status = $validated['status'];
        $action->save();

        return response()->json([
            'message' => "Estado actualizado a {$action->status}",
            'data' => $action
        ]);
    }
}
