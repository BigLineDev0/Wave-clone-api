<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUpdateFavoriteRequest;
use App\Http\Resources\FavoriteResource;
use App\Models\Favorite;
use App\Services\FavoriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function __construct(
        protected FavoriteService $favoriteService
    ) {}

    /**
     * Liste des favoris.
     */
    public function index(Request $request)
    {
        $favorites = $request->user()
            ->favorites()
            ->latest()
            ->paginate(20);

        return FavoriteResource::collection($favorites);
    }

    /**
     * Ajouter un favori.
     */
    public function store(StoreUpdateFavoriteRequest $request): JsonResponse {

        $favorite = $this->favoriteService->create(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Favori ajouté avec succès.',
            'data' => new FavoriteResource($favorite),
        ], 201);
    }

    /**
     * Afficher un favori.
     */
    public function show(Request $request, Favorite $favorite): FavoriteResource {

        abort_if(
            $favorite->user_id !== $request->user()->id,
            403,
            'Accès non autorisé.'
        );

        return new FavoriteResource($favorite);
    }

    /**
     * Modifier un favori.
     */
    public function update(StoreUpdateFavoriteRequest $request, Favorite $favorite): FavoriteResource {

        $favorite = $this->favoriteService->update(
            $request->user(),
            $favorite,
            $request->validated()
        );

        return new FavoriteResource($favorite);
    }

    /**
     * Supprimer un favori.
     */
    public function destroy(Request $request,Favorite $favorite): JsonResponse {

        $this->favoriteService->delete($request->user(),$favorite);

        return response()->json([
            'success' => true,
            'message' => 'Favori supprimé avec succès.',
        ]);
    }
}
