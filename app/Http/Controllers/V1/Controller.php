<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1;

/**
 * @OA\Info(
 *     title="Documentation",
 *     version="1.0"
 * ),
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     in="header",
 *     name="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * ),
 * @OA\Server(
 *     description="Base URL",
 *     url="https://domain.dev/v1"
 * ),
 * @OA\Server(
 *     description="Local Base URL",
 *     url="http://127.0.0.1:8000/v1"
 * ),
 * @OA\Response(
 *     response=500,
 *     description="Server Error",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="message", type="string", example="Oops something went wrong"),
 *         @OA\Property(property="status", type="string", example="error"),
 *         @OA\Property(property="statusCode", type="integer", example=500)
 *     )
 * ),
 * @OA\Response(
 *     response=403,
 *     description="Unauthorized: Permission denied",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="message", type="string", example="Unauthorized: User Unauthorized"),
 *         @OA\Property(property="status", type="string", example="error"),
 *         @OA\Property(property="statusCode", type="integer", example=403)
 *     )
 * ),
 * @OA\Response(
 *     response=401,
 *     description="Forbidden: Unauthenticated",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="message", type="string", example="Unauthenticated"),
 *         @OA\Property(property="status", type="string", example="error"),
 *         @OA\Property(property="statusCode", type="integer", example=401)
 *     )
 * ),
 * @OA\Response(
 *     response=404,
 *     description="Resource Not Found",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="message", type="string", example="Resource Not Found"),
 *         @OA\Property(property="status", type="string", example="error"),
 *         @OA\Property(property="statusCode", type="integer", example=404)
 *     )
 * ),
 * @OA\Response(
 *     response=422,
 *     description="Validation Error",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="message", type="string", example="Validation error"),
 *         @OA\Property(property="errors", type="object", example={"field_name": {"The field_name field is required."}}),
 *         @OA\Property(property="status", type="string", example="error"),
 *         @OA\Property(property="statusCode", type="integer", example=422)
 *     )
 * )
 */
abstract class Controller {}
