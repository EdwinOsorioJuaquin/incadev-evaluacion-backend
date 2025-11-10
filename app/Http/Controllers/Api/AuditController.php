<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use IncadevUns\CoreDomain\Models\Audit;

/**
 * @OA\Tag(
 *     name="Auditorías",
 *     description="Gestión principal de auditorías internas y académicas."
 * )
 */
class AuditController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/audits",
     *     summary="Listar auditorías del auditor autenticado",
     *     tags={"Auditorías"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de auditorías recuperada correctamente."
     *     ),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function index()
    {
        $audits = Audit::where('auditor_id', Auth::id())
            ->orderByDesc('audit_date')
            ->get();

        return response()->json($audits);
    }

    /**
     * @OA\Post(
     *     path="/api/audits",
     *     summary="Registrar nueva auditoría",
     *     tags={"Auditorías"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"audit_date","summary","auditable_type","auditable_id"},
     *             @OA\Property(property="audit_date", type="string", format="date", example="2025-11-10"),
     *             @OA\Property(property="summary", type="string", example="Evaluación general del proceso académico."),
     *             @OA\Property(property="auditable_type", type="string", example="Departamento"),
     *             @OA\Property(property="auditable_id", type="integer", example=12)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Auditoría creada correctamente."
     *     ),
     *     @OA\Response(response=422, description="Error de validación")
     * )
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
     * @OA\Get(
     *     path="/api/audits/{id}",
     *     summary="Ver detalles de una auditoría",
     *     tags={"Auditorías"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la auditoría",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalles de auditoría obtenidos correctamente."
     *     ),
     *     @OA\Response(response=404, description="Auditoría no encontrada")
     * )
     */
    public function show($id)
    {
        $audit = Audit::with(['findings.evidences', 'findings.actions'])->findOrFail($id);
        return response()->json($audit);
    }

    /**
     * @OA\Put(
     *     path="/api/audits/{id}/recommendation",
     *     summary="Actualizar recomendaciones finales de la auditoría",
     *     tags={"Auditorías"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la auditoría a actualizar",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"recommendation"},
     *             @OA\Property(property="recommendation", type="string", example="Se recomienda optimizar los procesos administrativos de registro académico.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Recomendaciones actualizadas correctamente."
     *     ),
     *     @OA\Response(response=404, description="Auditoría no encontrada")
     * )
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
