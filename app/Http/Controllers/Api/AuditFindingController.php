<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use IncadevUns\CoreDomain\Models\Audit;
use IncadevUns\CoreDomain\Models\AuditFinding;
use IncadevUns\CoreDomain\Enums\AuditFindingStatus;
use IncadevUns\CoreDomain\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Hallazgos de Auditoría",
 *     description="Gestión de hallazgos detectados en las auditorías."
 * )
 */
class AuditFindingController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/audits/{auditId}/findings",
     *     tags={"Hallazgos de Auditoría"},
     *     summary="Listar hallazgos de una auditoría",
     *     description="Obtiene todos los hallazgos (con sus evidencias y acciones correctivas) asociados a una auditoría específica.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="auditId",
     *         in="path",
     *         required=true,
     *         description="ID de la auditoría",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Lista de hallazgos obtenida correctamente."),
     *     @OA\Response(response=404, description="Auditoría no encontrada")
     * )
     */
    public function index($auditId)
    {
        $findings = AuditFinding::where('audit_id', $auditId)
            ->with(['evidences', 'actions'])
            ->get();

        return response()->json($findings);
    }

    /**
     * @OA\Post(
     *     path="/api/audits/{auditId}/findings",
     *     tags={"Hallazgos de Auditoría"},
     *     summary="Registrar un nuevo hallazgo",
     *     description="Crea un hallazgo asociado a una auditoría, indicando su descripción y severidad.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="auditId",
     *         in="path",
     *         required=true,
     *         description="ID de la auditoría asociada",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"description","severity"},
     *             @OA\Property(property="description", type="string", example="Falta de respaldo en los servidores de registro académico."),
     *             @OA\Property(property="severity", type="string", example="Alta")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Hallazgo registrado correctamente."),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
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
            'status' => AuditFindingStatus::Open, // 👈 Enum explícito
        ]);

        return response()->json([
            'message' => 'Hallazgo registrado correctamente.',
            'data' => $finding
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/findings/{id}/status",
     *     tags={"Hallazgos de Auditoría"},
     *     summary="Actualizar estado de un hallazgo",
     *     description="Permite cambiar el estado de un hallazgo (por ejemplo: de 'open' a 'closed').",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del hallazgo",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", example="Closed")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Estado del hallazgo actualizado correctamente."),
     *     @OA\Response(response=404, description="Hallazgo no encontrado")
     * )
     */
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|string'
        ]);

        $finding = AuditFinding::findOrFail($id);

        $finding->status = AuditFindingStatus::from($validated['status']);
        $finding->save();

        return response()->json([
            'message' => 'Estado del hallazgo actualizado correctamente.',
            'status' => $finding->status->value,
        ]);
    }


    // En AuditController.php - agregar este método
public function getAuditUsers(): JsonResponse
{
    try {
        // Obtener usuarios con roles de auditoría
        $users = \IncadevUns\CoreDomain\Models\User::where(function($query) {
                $query->where('role', 'auditor')
                      ->orWhere('role', 'audit_manager');
            })
            ->where('is_active', true)
            ->select('id', 'name', 'email', 'role')
            ->orderBy('name')
            ->get();

        return response()->json($users);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error al cargar los usuarios'
        ], 500);
    }
}
}
