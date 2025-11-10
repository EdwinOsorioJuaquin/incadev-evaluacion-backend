<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use IncadevUns\CoreDomain\Models\AuditFinding;
use IncadevUns\CoreDomain\Models\AuditAction;

/**
 * @OA\Tag(
 *     name="Acciones Correctivas",
 *     description="Gestión de acciones correctivas asociadas a hallazgos de auditoría."
 * )
 */
class AuditActionController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/findings/{findingId}/actions",
     *     tags={"Acciones Correctivas"},
     *     summary="Registrar nueva acción correctiva",
     *     description="Crea una nueva acción correctiva asociada a un hallazgo.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="findingId",
     *         in="path",
     *         required=true,
     *         description="ID del hallazgo asociado",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"responsible_id","action_required","due_date"},
     *             @OA\Property(property="responsible_id", type="integer", example=2),
     *             @OA\Property(property="action_required", type="string", example="Implementar registro automatizado de auditorías."),
     *             @OA\Property(property="due_date", type="string", format="date", example="2025-12-15")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Acción correctiva registrada correctamente"
     *     ),
     *     @OA\Response(response=404, description="Hallazgo no encontrado"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
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

    /**
     * @OA\Put(
     *     path="/api/actions/{id}/status",
     *     tags={"Acciones Correctivas"},
     *     summary="Actualizar estado de una acción correctiva",
     *     description="Permite cambiar el estado de una acción correctiva (por ejemplo: de 'pending' a 'completed').",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la acción correctiva",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", example="completed")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Estado actualizado correctamente"
     *     ),
     *     @OA\Response(response=404, description="Acción no encontrada")
     * )
     */
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
