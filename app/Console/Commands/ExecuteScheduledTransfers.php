<?php

namespace App\Console\Commands;

use App\Services\ScheduledTransferService;
use Illuminate\Console\Command;

class ExecuteScheduledTransfers extends Command
{
    protected $signature = 'transfers:execute';

    protected $description = 'Exécute les transferts planifiés arrivés à échéance';

    public function handle(ScheduledTransferService $scheduledTransferService): int {

        $scheduledTransferService->executeDueTransfers();

        $this->info('Les transferts planifiés ont été traités.');

        return self::SUCCESS;
    }
}
