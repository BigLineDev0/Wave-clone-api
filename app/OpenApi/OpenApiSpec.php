<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Wave Clone API',
    description: 'API REST pour la gestion des utilisateurs, wallets, transferts, paiements marchands et opérations distributeur.'
)]
#[OA\Server(
    url: 'http://127.0.0.1:8000',
    description: 'Serveur local'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum',
    description: 'Token d authentification Sanctum'
)]
class OpenApiSpec
{
}
