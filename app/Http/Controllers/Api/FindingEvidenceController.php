<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use IncadevUns\CoreDomain\Enums\MediaType;
use IncadevUns\CoreDomain\Models\AuditFinding;
use IncadevUns\CoreDomain\Models\FindingEvidence;


class FindingEvidenceController extends Controller
{
    public function store(Request $request, $id)
{
    $validated = $request->validate([
        'file' => 'required|file|max:5120',
    ]);

    $path = $request->file('file')->store('evidences', 'public');

    $extension = $request->file('file')->getClientOriginalExtension();

    $mediaType = match (strtolower($extension)) {
        'jpg', 'jpeg', 'png' => MediaType::Image,
        'mp4', 'mov', 'avi' => MediaType::Video,
        'pdf', 'doc', 'docx' => MediaType::Document,
        'mp3', 'wav' => MediaType::Audio,
        default => MediaType::Document,
    };

    $evidence = FindingEvidence::create([
        'audit_finding_id' => $id,
        'path' => $path,
        'type' => $mediaType,
    ]);

    return response()->json([
        'message' => 'Evidencia registrada correctamente.',
        'data' => $evidence
    ], 201);
}
}
