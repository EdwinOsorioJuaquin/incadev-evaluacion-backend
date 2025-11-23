<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use IncadevUns\CoreDomain\Models\Audit;
use IncadevUns\CoreDomain\Models\AuditFinding;
use IncadevUns\CoreDomain\Models\AuditAction;
use IncadevUns\CoreDomain\Enums\AuditStatus;
use IncadevUns\CoreDomain\Enums\AuditFindingStatus;
use IncadevUns\CoreDomain\Enums\AuditActionStatus;


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

    /**
     * @OA\Put(
     *     path="/api/audits/{id}",
     *     summary="Actualizar una auditoría",
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
     *             required={"audit_date","summary","auditable_type","auditable_id","status"},
     *             @OA\Property(property="audit_date", type="string", example="2023-01-01"),
     *             @OA\Property(property="summary", type="string", example="Resumen de la auditoría."),
     *             @OA\Property(property="auditable_type", type="string", example="App\\Models\\User"),
     *             @OA\Property(property="auditable_id", type="integer", example=1),
     *             @OA\Property(property="status", type="string", example="pending")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Auditoría actualizada correctamente."),
     *     @OA\Response(response=404, description="Auditoría no encontrada")
     * )
     */
    public function update(Request $request, $id)
{
    $audit = Audit::findOrFail($id);

    $validated = $request->validate([
        'audit_date' => 'nullable|date',
        'summary' => 'nullable|string',
        'auditable_type' => 'nullable|string',
        'auditable_id' => 'nullable|integer',
        'status' => 'nullable|string|in:pending,in_progress,completed,cancelled',
    ]);

    $audit->update($validated);

    return response()->json([
        'message' => 'Auditoría actualizada correctamente.',
        'data' => $audit
    ]);
}



    /**
     * @OA\Put(
     *     path="/api/audits/{id}/start",
     *     summary="Iniciar una auditoría",
     *     tags={"Auditorías"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la auditoría a iniciar",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Auditoría iniciada correctamente."),
     *     @OA\Response(response=404, description="Auditoría no encontrada")
     * )
     */
public function startAudit($id)
{
    $audit = Audit::findOrFail($id);

    // Solo audiencias pending pueden iniciar
    if ($audit->status !== 'pending') {
        return response()->json([
            'message' => 'La auditoría ya fue iniciada o completada.'
        ], 400);
    }

    $audit->status = 'in_progress';
    $audit->save();

    return response()->json([
        'message' => 'Auditoría iniciada correctamente.',
        'data' => $audit
    ]);
}


    

    /**
     * @OA\Get(
     *     path="/api/audits/dashboard",
     *     summary="Dashboard de auditorías",
     *     tags={"Auditorías"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard de auditorías obtenido correctamente."
     *     ),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    /**
     * Dashboard: estadísticas generales de Auditorías
     */
    public function dashboard()
    {
        // --- Estadísticas por mes (para el gráfico) ---
        $auditsByMonth = Audit::selectRaw("DATE_FORMAT(audit_date, '%Y-%m-01') as month, COUNT(*) as count")
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

            // Si solo hay un punto, agregamos un mes anterior con count 0
        if ($auditsByMonth->count() === 1) {
            $single = $auditsByMonth->first();
            $previousMonth = Carbon::parse($single->month)->subMonth()->format('Y-m-01');

            $auditsByMonth->prepend([
                "month" => $previousMonth,
                "count" => 0
            ]);
        }

        return response()->json([
            "audits" => [
                "total"        => Audit::count(),
                "pending"      => Audit::where('status', AuditStatus::Pending->value)->count(),
                "in_progress"  => Audit::where('status', AuditStatus::InProgress->value)->count(),
                "completed"    => Audit::where('status', AuditStatus::Completed->value)->count(),
                "cancelled"    => Audit::where('status', AuditStatus::Cancelled->value)->count(),
            ],

            "findings" => [
                "total"        => AuditFinding::count(),
                "open"         => AuditFinding::where('status', AuditFindingStatus::Open->value)->count(),
                "in_progress"  => AuditFinding::where('status', AuditFindingStatus::InProgress->value)->count(),
                "resolved"     => AuditFinding::where('status', AuditFindingStatus::Resolved->value)->count(),
                "wont_fix"     => AuditFinding::where('status', AuditFindingStatus::WontFix->value)->count(),
            ],

            "actions" => [
                "total"        => AuditAction::count(),
                "pending"      => AuditAction::where('status', AuditActionStatus::Pending->value)->count(),
                "in_progress"  => AuditAction::where('status', AuditActionStatus::InProgress->value)->count(),
                "completed"    => AuditAction::where('status', AuditActionStatus::Completed->value)->count(),
                "cancelled"    => AuditAction::where('status', AuditActionStatus::Cancelled->value)->count(),
            ],
            "audits_over_time" => $auditsByMonth,
        ]);
    }

}
