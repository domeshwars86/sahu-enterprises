<?php

namespace App\Services\Admin;

use App\Models\CrmTask;
use App\Models\FollowUp;
use App\Models\LoanApplication;
use App\Models\LoanEnquiry;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AdminRecordScope
{
    /** @return Builder<LoanEnquiry> */
    public function enquiries(User $user): Builder
    {
        $query = LoanEnquiry::query();

        if ($this->isSalesManager($user)) {
            return $query->where(fn (Builder $builder) => $builder->whereNull('assigned_user_id')->orWhereIn('assigned_user_id', $user->operationalTeamUserIds()));
        }

        if (in_array($user->adminRole?->slug, ['dsa-agent', 'customer-support'], true)) {
            return $query->where('assigned_user_id', $user->id);
        }

        return $query;
    }

    public function canAccessEnquiry(User $user, LoanEnquiry $enquiry): bool
    {
        if ($this->isSalesManager($user)) {
            return $enquiry->assigned_user_id === null || in_array($enquiry->assigned_user_id, $user->operationalTeamUserIds(), true);
        }

        if (in_array($user->adminRole?->slug, ['dsa-agent', 'customer-support'], true)) {
            return $enquiry->assigned_user_id === $user->id;
        }

        return $user->hasAdminPermission('enquiries.view');
    }

    public function canAssignTo(User $actor, User $assignee): bool
    {
        return $actor->adminRole?->slug !== 'sales-manager'
            || in_array($assignee->id, $actor->operationalTeamUserIds(), true);
    }

    /** @return Builder<FollowUp> */
    public function followUps(User $user): Builder
    {
        $query = FollowUp::query();
        if ($this->isSalesManager($user)) {
            $query->where(fn (Builder $builder) => $builder->whereNull('assigned_user_id')->orWhereIn('assigned_user_id', $user->operationalTeamUserIds()));
        } elseif (in_array($user->adminRole?->slug, ['dsa-agent', 'customer-support'], true)) {
            $query->where('assigned_user_id', $user->id);
        }

        return $query;
    }

    public function canAccessFollowUp(User $user, FollowUp $followUp): bool
    {
        if ($this->isSalesManager($user)) {
            return $followUp->assigned_user_id === null || in_array($followUp->assigned_user_id, $user->operationalTeamUserIds(), true);
        }

        return ! in_array($user->adminRole?->slug, ['dsa-agent', 'customer-support'], true) || $followUp->assigned_user_id === $user->id;
    }

    /** @return Builder<CrmTask> */
    public function tasks(User $user): Builder
    {
        $query = CrmTask::query();
        if ($this->isSalesManager($user)) {
            $query->where(fn (Builder $builder) => $builder->whereNull('assigned_user_id')->orWhereIn('assigned_user_id', $user->operationalTeamUserIds()));
        } elseif (in_array($user->adminRole?->slug, ['dsa-agent', 'customer-support'], true)) {
            $query->where('assigned_user_id', $user->id);
        }

        return $query;
    }

    public function canAccessTask(User $user, CrmTask $task): bool
    {
        if ($this->isSalesManager($user)) {
            return $task->assigned_user_id === null || in_array($task->assigned_user_id, $user->operationalTeamUserIds(), true);
        }

        return ! in_array($user->adminRole?->slug, ['dsa-agent', 'customer-support'], true) || $task->assigned_user_id === $user->id;
    }

    /** @return Builder<LoanApplication> */
    public function applications(User $user): Builder
    {
        $query = LoanApplication::query();
        if ($this->isSalesManager($user)) {
            $query->whereIn('owner_user_id', $user->operationalTeamUserIds());
        } elseif (in_array($user->adminRole?->slug, ['dsa-agent', 'customer-support'], true)) {
            $query->where('owner_user_id', $user->id);
        }

        return $query;
    }

    private function isSalesManager(User $user): bool
    {
        return $user->adminRole?->slug === 'sales-manager';
    }
}
