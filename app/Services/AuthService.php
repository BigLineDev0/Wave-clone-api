<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Inscription d'un nouvel utilisateur.
     */
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {

            // Création de l'utilisateur
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name'  => $data['last_name'],
                'phone'      => $data['phone'],
                'email'      => $data['email'],
                'password'   => $data['password'], // Hash automatique grâce au cast
                'role'       => UserRole::CLIENT,
            ]);

            // Génération du token Sanctum
            $token = $user->createToken('wave-mobile')->plainTextToken;

            return [
                'user'  => $user->load('wallet'),
                'token' => $token,                                                  
            ];
        });
    }

    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->with('wallet')->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email ou mot de passe incorrect.'],
            ]);
        }

        // Révoque les anciens tokens (un seul appareil connecté)
        $user->tokens()->delete();

        // Génère un nouveau token
        $token = $user->createToken('wave-mobile')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
