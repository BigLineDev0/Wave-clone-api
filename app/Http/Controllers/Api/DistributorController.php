<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CashInRequest;
use App\Http\Resources\TransactionResource;
use App\Services\DistributorService;
use Illuminate\Http\JsonResponse;

class DistributorController extends Controller
{
    public function __construct(
        protected DistributorService $distributorService
    ) {}

    /**
     * Effectuer un cash-in.
     */
    public function cashIn(CashInRequest $request): JsonResponse {

        $transaction = $this->distributorService->cashIn(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Cash-in effectué avec succès.',
            'data' => new TransactionResource($transaction),
        ], 201);
    }
}
