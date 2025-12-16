<?php

namespace App\Http\Controllers;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Sindra API Documentation",
 *     description="Dokumentasi API untuk sistem tiket & knowledge base",
 *     @OA\Contact(
 *         email="support@sindra.local"
 *     )
 * )
 *
 * @OA\Server(
 *     url="https://api-sindra.okkyprojects.com",
 *     description="Main API Server"
 * )
 */
class SwaggerController extends Controller
{
    //
}
