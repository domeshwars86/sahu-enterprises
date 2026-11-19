<?php

namespace App\Console\Commands;

use App\Services\News\PibRssIngestor;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('news:ingest-pib')]
#[Description('Import attributed finance headlines from the PIB RSS feed')]
class IngestPibNews extends Command
{
    public function handle(PibRssIngestor $ingestor): int
    {
        try {
            $processed = $ingestor->ingest();
        } catch (Throwable $exception) {
            report($exception);
            $this->error('PIB news import failed. Check the application log for details.');

            return self::FAILURE;
        }

        $this->info("Imported or refreshed {$processed} PIB headlines.");

        return self::SUCCESS;
    }
}
