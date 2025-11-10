<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use IncadevUns\CoreDomain\Enums\MediaType;
use IncadevUns\CoreDomain\Models\AuditFinding;
use IncadevUns\CoreDomain\Models\FindingEvidence;

/**
 * @OA\Tag(
 *     name="Evidencias de Hallazgos",
 *     description="Gestión de archivos (documentos, imágenes, videos) como evidencias asociadas a hallazgos."
 * )
 */
class FindingEvidenceController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/findings/{id}/evidences",
     *     tags={"Evidencias de Hallazgos"},
     *     summary="Subir evidencia para un hallazgo",
     *     description="Permite registrar una evidencia (documento, imagen, video, audio) asociada a un hallazgo.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del hallazgo asociado",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"file"},
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="Archivo de evidencia (PDF, imagen, audio o video)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Evidencia registrada correctamente."
     *     ),
     *     @OA\Response(response=404, description="Hallazgo no encontrado"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function store(Request $request, $id)
    {
        $validated = $request->validate([
            'file' => 'required|file|max:5120',
        ]);

        // Verifica que el hallazgo exista
        $finding = AuditFinding::findOrFail($id);

        // Guardar archivo en storage público
        $path = $request->file('file')->store('evidences', 'public');
        $extension = $request->file('file')->getClientOriginalExtension();

        // Asignar tipo de medio según la extensión
        $mediaType = match (strtolower($extension)) {
            'jpg', 'jpeg', 'png' => MediaType::Image,
            'mp4', 'mov', 'avi' => MediaType::Video,
            'pdf', 'doc', 'docx' => MediaType::Document,
            'mp3', 'wav' => MediaType::Audio,
            default => MediaType::Other,
        };

        // Crear registro de evidencia
        $evidence = FindingEvidence::create([
            'audit_finding_id' => $finding->id,
            'path' => $path,
            'type' => $mediaType,
        ]);

        return response()->json([
            'message' => 'Evidencia registrada correctamente.',
            'data' => [
                'id' => $evidence->id,
                'path' => Storage::url($evidence->path),
                'type' => $evidence->type->value,
            ],
        ], 201);
    }
}
