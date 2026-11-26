<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FollowUp;
use App\Models\LoanEnquiry;
use App\Services\Admin\AdminRecordScope;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(AdminRecordScope $scope): Response
    {
        $user = request()->user();
        $enquiries = $scope->enquiries($user);
        $followUps = $scope->followUps($user)->where('status', 'pending');
        $applications = $scope->applications($user);
        $canSeeEnquiries = $user->hasAdminPermission('enquiries.view') || $user->hasAdminPermission('enquiries.view_assigned');
        $canSeeFollowUps = $user->hasAdminPermission('followups.manage') || $user->hasAdminPermission('followups.assigned');
        $canSeeApplications = $user->hasAdminPermission('applications.manage') || $user->hasAdminPermission('applications.assigned');

        return Inertia::render('Admin/Dashboard', [
            'metrics' => [
                'totalEnquiries' => $canSeeEnquiries ? (clone $enquiries)->count() : 0,
                'newEnquiries' => $canSeeEnquiries ? (clone $enquiries)->where('status', 'new')->count() : 0,
                'pendingFollowUps' => $canSeeFollowUps ? (clone $followUps)->count() : 0,
                'activeApplications' => $canSeeApplications ? (clone $applications)->whereNotIn('stage', ['rejected', 'disbursed', 'closed'])->count() : 0,
                'approvedApplications' => $canSeeApplications ? (clone $applications)->where('stage', 'approved')->count() : 0,
                'disbursedApplications' => $canSeeApplications ? (clone $applications)->where('stage', 'disbursed')->count() : 0,
                'overdueFollowUps' => $canSeeFollowUps ? (clone $followUps)->where('scheduled_at', '<', now())->count() : 0,
            ],
            'recentEnquiries' => $canSeeEnquiries ? (clone $enquiries)->with('assignee:id,name')->latest()->limit(8)->get()
                ->map(fn (LoanEnquiry $enquiry): array => [
                    'id' => $enquiry->id,
                    'reference' => $enquiry->reference_number,
                    'customer' => $this->displayName($enquiry->customer_name, $user->hasAdminPermission('enquiries.view_sensitive')),
                    'product' => $enquiry->loan_category,
                    'amount' => $enquiry->requested_amount,
                    'status' => $enquiry->status,
                    'assignee' => $enquiry->assignee?->name,
                    'createdAt' => $enquiry->created_at?->toIso8601String(),
                ])->all() : [],
            'upcomingFollowUps' => $canSeeFollowUps ? (clone $followUps)->with('enquiry:id,reference_number')->where('scheduled_at', '>=', now())->orderBy('scheduled_at')->limit(6)->get()
                ->map(fn (FollowUp $followUp): array => ['id' => $followUp->id, 'purpose' => $followUp->purpose, 'scheduledAt' => $followUp->scheduled_at?->toIso8601String(), 'reference' => $followUp->enquiry?->reference_number])->all() : [],
            'charts' => [
                'enquiryTrends' => $canSeeEnquiries ? (clone $enquiries)->selectRaw('DATE(created_at) as label, COUNT(*) as total')->where('created_at', '>=', now()->subDays(13)->startOfDay())->groupBy('label')->orderBy('label')->get()->map(fn ($row): array => ['label' => $row->label, 'total' => (int) $row->total])->all() : [],
                'categories' => $canSeeEnquiries ? (clone $enquiries)->select('loan_category as label')->selectRaw('COUNT(*) as total')->groupBy('loan_category')->orderByDesc('total')->limit(8)->get()->map(fn ($row): array => ['label' => $row->label, 'total' => (int) $row->total])->all() : [],
                'statuses' => $canSeeEnquiries ? (clone $enquiries)->select('status as label')->selectRaw('COUNT(*) as total')->groupBy('status')->orderByDesc('total')->get()->map(fn ($row): array => ['label' => $row->label, 'total' => (int) $row->total])->all() : [],
                'sources' => $canSeeEnquiries ? (clone $enquiries)->select('enquiry_source as label')->selectRaw('COUNT(*) as total')->groupBy('enquiry_source')->orderByDesc('total')->limit(8)->get()->map(fn ($row): array => ['label' => $row->label ?: 'Unknown', 'total' => (int) $row->total])->all() : [],
                'applicationProgress' => $canSeeApplications ? (clone $applications)->select('stage as label')->selectRaw('COUNT(*) as total')->groupBy('stage')->orderByDesc('total')->get()->map(fn ($row): array => ['label' => $row->label, 'total' => (int) $row->total])->all() : [],
                'agentWorkload' => $user->hasAdminPermission('enquiries.assign') ? (clone $enquiries)->with('assignee:id,name')->whereNotNull('assigned_user_id')->select('assigned_user_id')->selectRaw('COUNT(*) as total')->groupBy('assigned_user_id')->orderByDesc('total')->limit(8)->get()->map(fn (LoanEnquiry $enquiry): array => ['label' => $enquiry->assignee?->name ?? 'Staff', 'total' => (int) $enquiry->getAttribute('total')])->all() : [],
            ],
        ]);
    }

    private function displayName(?string $name, bool $sensitive): string
    {
        if ($sensitive || ! $name) {
            return $name ?? '—';
        }

        $parts = preg_split('/\s+/', trim($name)) ?: [];

        return ($parts[0] ?? 'Customer').(isset($parts[1]) ? ' '.mb_substr($parts[1], 0, 1).'.' : '');
    }
}
