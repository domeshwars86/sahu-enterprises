<?php

namespace App\Services\Admin;

use App\Models\LoanApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateLoanApplicationStage
{
    public const STAGES = ['preparation', 'documents_pending', 'ready_for_submission', 'submitted', 'under_review', 'approved', 'rejected', 'disbursed', 'closed', 'cancelled'];

    public function update(LoanApplication $application, string $stage, string $reason, ?User $user): LoanApplication
    {
        if (! in_array($stage, self::STAGES, true)) {
            throw ValidationException::withMessages(['stage' => 'Choose a supported application stage.']);
        }

        return DB::transaction(function () use ($application, $stage, $reason, $user): LoanApplication {
            $application = LoanApplication::query()->lockForUpdate()->findOrFail($application->id);
            $oldStage = $application->stage;
            if ($oldStage === $stage) {
                return $application;
            }

            $application->update(['stage' => $stage, 'submitted_at' => $stage === 'submitted' ? now() : $application->submitted_at]);
            $application->statusHistory()->create(['changed_by' => $user?->id, 'old_stage' => $oldStage, 'new_stage' => $stage, 'reason' => $reason !== '' ? $reason : null]);
            app(AuditLogger::class)->record('application.stage_changed', $user, 'loan_application', $application->id, ['old_stage' => $oldStage, 'new_stage' => $stage]);

            return $application;
        });
    }
}
