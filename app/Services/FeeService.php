<?php

namespace App\Services;

use App\Models\Transaction;

class FeeService
{
    /**
     * Calcule les frais de transfert.
     */
    public function calculateTransferFee(float $amount): float
    {
        return $this->calculatePercentageFee($amount, 1);
    }

    /**
     * Calcule les frais de cash-in.
     */
    public function calculateCashInFee(float $amount): float
    {
        return $this->calculatePercentageFee($amount, 1);
    }

    /**
     * Calcule les frais de cash-out.
     */
    public function calculateCashOutFee(float $amount): float
    {
        return $this->calculatePercentageFee($amount, 1);
    }

    /**
     * Calcule les frais d'un paiement marchand.
     */
    public function calculateMerchantFee(float $amount): float
    {
        return $this->calculatePercentageFee($amount, 0);
    }

    /**
     * Calcule le montant à rembourser après l'annulation d'un transfert.
     */
    public function calculateRefundAmount(Transaction $transaction): float
    {
        return (float) $transaction->amount + (float) $transaction->fee;
    }

    /**
     * Calcule un montant de frais selon un pourcentage.
     */
    private function calculatePercentageFee(float $amount, float $percentage): float {
        return round($amount * ($percentage / 100), 2);
    }
}
