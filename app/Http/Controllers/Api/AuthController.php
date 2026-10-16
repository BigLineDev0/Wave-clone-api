<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * Inscription.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Inscription réussie.',
            'data' => [
                'token' => $result['token'],
                'user' => new UserResource($result['user']),
            ]

        ], 201);
    }

    /**
     * Connexion.
     */

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie.',
            'data' => [
                'token' => $result['token'],
                'user' => new UserResource($result['user']),
            ]
        ], 200);
    }

    /**
     * Récupère les informations de l'utilisateur connecté.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Profil récupéré.',
            'data' => new UserResource(
                $request->user()->load('wallet')
            ),

        ], 200);
    }

    /**
     * Déconnexion.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie.',

        ], 200);
    }
}
