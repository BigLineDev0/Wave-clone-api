<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\TransferRequest;
use App\Http\Requests\MultipleTransferRequest;
use App\Services\TransferService;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;

class TransferController extends Controller
{

    public function __construct(
        protected TransferService $transferService
    ) {}

    // effectuer un transfert
    public function store(TransferRequest $request): JsonResponse
    {
        $transaction = $this->transferService->transfer(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Transfert effectué avec succès.',
            'data' => new TransactionResource($transaction),
        ], 201);
    }

    // Annuler un transfert
    public function cancel(Request $request, Transaction $transaction): JsonResponse {

        $transaction = $this->transferService->cancel(
            $request->user(),
            $transaction
        );

        return response()->json([
            'success' => true,
            'message' => 'Transaction annulée avec succès.',
            'data' => new TransactionResource($transaction),
        ]);
    }

    // Transfert multiple
    public function multiple(MultipleTransferRequest $request): JsonResponse {
        $transactions = $this->transferService->transferMultiple(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Transferts multiples effectués avec succès.',
            'data' => TransactionResource::collection($transactions),
        ], 201);
    }
}
