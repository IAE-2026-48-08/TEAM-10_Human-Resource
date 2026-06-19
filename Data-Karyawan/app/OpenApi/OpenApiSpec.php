<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Data Karyawan Service API',
    description: 'REST API untuk manajemen data karyawan'
)]

#[OA\SecurityScheme(
    securityScheme: 'ApiKeyAuth',
    type: 'apiKey',
    in: 'header',
    name: 'X-IAE-KEY'
)]

final class OpenApiSpec
{
}