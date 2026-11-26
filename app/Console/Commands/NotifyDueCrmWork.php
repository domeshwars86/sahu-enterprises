<?php

namespace App\Console\Commands;

use App\Models\CrmTask;
use App\Models\FollowUp;
use App\Notifications\CrmReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotifyDueCrmWork extends Command
{
    protected $signature = 'crm:notify-due';

    protected $description = 'Send one-time database reminders for due assigned CRM work';

    public function handle(): int
    {
        $followUps = FollowUp::query()->with('assignee')->where('status', 'pending')->whereNull('reminder_sent_at')->where('scheduled_at', '<=', now())->get();
        foreach ($followUps as $followUp) {
            if ($followUp->assignee?->is_active) {
                try {
                    $followUp->assignee->notify(new CrmReminderNotification(['title' => 'Follow-up due', 'message' => $followUp->purpose.' · '.$followUp->enquiry()->value('reference_number'), 'url' => '/admin/follow-ups']));
                } catch (Throwable) {
                    Log::warning('CRM follow-up reminder email delivery failed.', ['follow_up_id' => $followUp->id]);
                }
            }
            $followUp->forceFill(['reminder_sent_at' => now()])->save();
        }

        $tasks = CrmTask::query()->with('assignee')->whereIn('status', ['pending', 'in_progress'])->whereNull('reminder_sent_at')->whereNotNull('due_at')->where('due_at', '<=', now())->get();
        foreach ($tasks as $task) {
            if ($task->assignee?->is_active) {
                try {
                    $task->assignee->notify(new CrmReminderNotification(['title' => 'Task due', 'message' => $task->title, 'url' => '/admin/tasks']));
                } catch (Throwable) {
                    Log::warning('CRM task reminder email delivery failed.', ['task_id' => $task->id]);
                }
            }
            $task->forceFill(['reminder_sent_at' => now()])->save();
        }

        $this->info('Processed '.$followUps->count().' follow-up reminders and '.$tasks->count().' task reminders.');

        return self::SUCCESS;
    }
}
