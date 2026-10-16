<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Transaction;
use App\Models\Wallet;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupère tous les portefeuilles
        $wallets = Wallet::all();

        // Crée 100 transactions aléatoires entre les portefeuilles
        for ($i = 0; $i < 100; $i++) {

            // Sélectionne un portefeuille aléatoire comme expéditeur
            $sender = $wallets->random();

            // Sélectionne un portefeuille aléatoire comme destinataire, en s'assurant qu'il est différent de l'expéditeur
            do {

                $receiver = $wallets->random();

            } while ($sender->id === $receiver->id);

            // Crée une transaction avec les portefeuilles sélectionnés
            Transaction::factory()->create([
                'sender_wallet_id' => $sender->id,
                'receiver_wallet_id' => $receiver->id,
            ]);

        }

    }
}
