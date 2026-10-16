<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE transactions
            MODIFY type ENUM(
                'transfer',
                'deposit',
                'withdraw',
                'merchant',
                'fee'
            ) NOT NULL DEFAULT 'transfer'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE transactions
            MODIFY type ENUM(
                'transfer',
                'deposit',
                'withdraw',
                'merchant'
            ) NOT NULL DEFAULT 'transfer'
        ");
    }

};
