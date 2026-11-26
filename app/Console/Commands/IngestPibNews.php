<?php

namespace App\Console\Commands;

use App\Models\BusinessSetting;
use App\Models\User;
use App\Notifications\CrmAlertNotification;
use App\Services\News\PibRssIngestor;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
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
            BusinessSetting::query()->updateOrCreate(['key' => 'news_last_sync_status'], ['value' => 'failed']);
            BusinessSetting::query()->updateOrCreate(['key' => 'news_last_attempt_at'], ['value' => now()->toIso8601String()]);
            if (Cache::add('news.sync.failure-alert-sent', true, now()->addHours(6))) {
                $recipients = User::query()->where('is_active', true)->with('adminRole')->get()
                    ->filter(fn (User $user): bool => $user->hasAdminPermission('news.manage') && $user->hasAdminPermission('notifications.view'));
                Notification::send($recipients, new CrmAlertNotification(['title' => 'News synchronization failed', 'message' => 'The scheduled finance news import failed. Check the server log for details.', 'url' => '/admin/news']));
            }
            $this->error('PIB news import failed. Check the application log for details.');

            return self::FAILURE;
        }

        BusinessSetting::query()->updateOrCreate(['key' => 'news_last_sync_status'], ['value' => 'succeeded']);
        BusinessSetting::query()->updateOrCreate(['key' => 'news_last_attempt_at'], ['value' => now()->toIso8601String()]);
        BusinessSetting::query()->updateOrCreate(['key' => 'news_last_success_at'], ['value' => now()->toIso8601String()]);
        BusinessSetting::query()->updateOrCreate(['key' => 'news_last_sync_count'], ['value' => (string) $processed]);
        Cache::forget('news.sync.failure-alert-sent');

        $this->info("Imported or refreshed {$processed} PIB headlines.");

        return self::SUCCESS;
    }
}
