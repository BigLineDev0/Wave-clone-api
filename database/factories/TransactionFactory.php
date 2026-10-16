<?php

namespace Database\Factories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Enums\TransactionType;
use App\Enums\TransactionStatus;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'amount' => fake()->numberBetween(1000, 100000),
            'fee' => fake()->numberBetween(0,500),
            'type' => fake()->randomElement([
                TransactionType::TRANSFER,
                TransactionType::DEPOSIT,
                TransactionType::WITHDRAW,
            ]),

            'status' => fake()->randomElement([
                TransactionStatus::PENDING,
                TransactionStatus::COMPLETED,
                TransactionStatus::CANCELLED,
            ]),
        ];
    }
}
