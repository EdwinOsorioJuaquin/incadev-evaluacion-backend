<?php

namespace App\Http\Controllers;


/**
 * @OA\Info(
 *     title="API de Auditorías UNS",
 *     version="1.0.0",
 *     description="Documentación de los endpoints del sistema de auditorías académicas y administrativas (Auditorías → Hallazgos → Evidencias → Acciones → Reportes).",
 *     @OA\Contact(
 *         email="soporte@uns.edu.pe",
 *         name="Equipo de Desarrollo UNS"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://127.0.0.1:8000",
 *     description="Servidor local de desarrollo"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */

abstract class Controller
{
    //
}
