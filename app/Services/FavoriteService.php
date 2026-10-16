<?php

namespace App\Services;

use App\Models\Favorite;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class FavoriteService
{
    public function create(User $user, array $data): Favorite
    {
        if ($user->phone === $data['phone']) {
            throw ValidationException::withMessages([
                'phone' => [
                    'Vous ne pouvez pas ajouter votre propre numéro.'
                ]
            ]);
        }

        if (Favorite::where('user_id', $user->id)
            ->where('phone', $data['phone'])
            ->exists()) {

            throw ValidationException::withMessages([
                'phone' => [
                    'Ce numéro est déjà dans vos favoris.'
                ]
            ]);
        }

        return $user->favorites()->create($data);
    }

    public function update(User $user, Favorite $favorite, array $data): Favorite {

        $this->ensureOwnership($user, $favorite);

        $favorite->update($data);

        return $favorite->refresh();
    }

    public function delete(User $user, Favorite $favorite): void {

        $this->ensureOwnership($user, $favorite);

        $favorite->delete();
    }

    private function ensureOwnership(User $user, Favorite $favorite): void {

        if ($favorite->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'favorite' => [
                    'Vous n\'êtes pas autorisé à accéder à ce favori.'
                ]
            ]);
        }
    }
}
