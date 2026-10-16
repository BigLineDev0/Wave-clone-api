<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScheduledTransferRequest;
use App\Http\Resources\ScheduledTransferResource;
use App\Services\ScheduledTransferService;
use Illuminate\Http\Request;
use App\Models\ScheduledTransfer;

class ScheduledTransferController extends Controller
{
    public function __construct(
        protected ScheduledTransferService $scheduledTransferService
    ) {}

    /**
     * Liste des planifications.
     */
    public function index(Request $request)
    {
        $scheduledTransfers = $request->user()
            ->scheduledTransfers()
            ->latest()
            ->get();

        return ScheduledTransferResource::collection($scheduledTransfers);
    }

    /**
     * Créer une planification.
     */
    public function store(StoreScheduledTransferRequest $request) {

        $scheduledTransfer = $this->scheduledTransferService->create(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Transfert planifié avec succès.',
            'data' => new ScheduledTransferResource($scheduledTransfer),
        ], 201);
    }

    /**
     * Annuler planification
     */
    public function destroy(Request $request, ScheduledTransfer $scheduledTransfer) {
        $scheduledTransfer = $this->scheduledTransferService->cancel(
            $request->user(),
            $scheduledTransfer
        );

        return response()->json([
            'success' => true,
            'message' => 'Transfert planifié annulé avec succès.',
            'data' => new ScheduledTransferResource($scheduledTransfer),
        ]);
    }
}
