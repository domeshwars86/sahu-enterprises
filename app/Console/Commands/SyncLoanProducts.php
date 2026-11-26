<?php

namespace App\Console\Commands;

use Database\Seeders\LoanCatalogueSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('crm:sync-products')]
#[Description('Synchronize the canonical DSA loan catalogue without overwriting staff settings')]
class SyncLoanProducts extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->call(LoanCatalogueSeeder::class);

        $this->info('Categories, canonical products and generic FAQs synchronized. Existing visibility and customer records were preserved.');

        return self::SUCCESS;
    }
}
