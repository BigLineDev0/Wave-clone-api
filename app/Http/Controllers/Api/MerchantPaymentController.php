<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantPaymentRequest;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\MerchantResource;
use App\Services\MerchantPaymentService;
use Illuminate\Http\JsonResponse;

class MerchantPaymentController extends Controller
{
    public function __construct(
        protected MerchantPaymentService $merchantPaymentService
    ) {}

    public function pay(MerchantPaymentRequest $request): JsonResponse {

        $transaction = $this->merchantPaymentService->pay(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Paiement marchand effectué avec succès.',
            'data' => new TransactionResource($transaction),
        ], 201);
    }

    /**
     * Recuperer les details du marchant
     */
    public function show(string $merchantCode): JsonResponse
    {
        $merchant = $this->merchantPaymentService->findMerchant($merchantCode);

        return response()->json([
            'success' => true,
            'message' => 'Marchand récupéré avec succès.',
            'data' => new MerchantResource($merchant),
        ]);
    }
}
