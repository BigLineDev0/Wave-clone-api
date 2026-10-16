<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TransferController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\ScheduledTransferController;
use App\Http\Controllers\Api\MerchantPaymentController;
use App\Http\Controllers\Api\DistributorController;

Route::middleware('auth:sanctum')->group(function () {

    // profil et Deconnexion
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // transfert
    Route::post('/transfers', [TransferController::class, 'store']);
    Route::post('/transfers/multiple', [TransferController::class, 'multiple']);
    Route::post('/transfers/{transaction}/cancel', [TransferController::class, 'cancel']);

    // historique des transactions
    Route::get('/transactions', [TransactionController::class, 'index']);

    // numeros favorites
    Route::apiResource('favorites', FavoriteController::class);

    // tranfert planifies
    Route::get('/scheduled-transfers', [ScheduledTransferController::class, 'index']);
    Route::post('/scheduled-transfers', [ScheduledTransferController::class, 'store']);
    Route::delete('/scheduled-transfers/{scheduledTransfer}', [ScheduledTransferController::class, 'destroy']);

    // paiement marchant
    Route::post('/payments/merchant', [MerchantPaymentController::class, 'pay']);
    Route::get('/merchants/{merchantCode}',[MerchantPaymentController::class, 'show']);

    // Distributeur
    Route::post('/distributor/cash-in', [DistributorController::class, 'cashIn']);
});

// Authentification
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');
