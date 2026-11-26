<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EnquiryStatusHistory;
use App\Models\LoanApplication;
use App\Models\LoanCategory;
use App\Models\LoanEnquiry;
use App\Models\User;
use App\Services\Admin\AdminRecordScope;
use App\Services\Admin\AuditLogger;
use App\Services\Admin\UpdateEnquiryStatus;
use App\Services\Admin\UpdateLoanApplicationStage;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request, AdminRecordScope $scope): Response
    {
        $user = $request->user();
        abort_unless($user->hasAdminPermission('reports.view'), 403);
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'category' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', Rule::in(UpdateEnquiryStatus::STATUSES)],
            'application_stage' => ['nullable', Rule::in(UpdateLoanApplicationStage::STAGES)],
            'source' => ['nullable', 'string', 'max:80'],
            'lender' => ['nullable', 'string', 'max:160'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);
        $filters['from'] ??= now()->subDays(29)->toDateString();
        $filters['to'] ??= now()->toDateString();
        $enquiries = $this->enquiryQuery($user, $filters, $scope);
        $applications = $this->applicationQuery($user, $filters, $scope);
        $total = (clone $enquiries)->count();
        $byStatus = (clone $enquiries)->select('status as label')->selectRaw('COUNT(*) as total')->groupBy('status')->orderByDesc('total')->get()->map(fn ($row): array => ['label' => $row->label, 'total' => (int) $row->total])->all();
        $byCategory = (clone $enquiries)->select('loan_category as label')->selectRaw('COUNT(*) as total')->groupBy('loan_category')->orderByDesc('total')->get()->map(fn ($row): array => ['label' => LoanCategory::query()->where('slug', $row->label)->value('name') ?? $row->label, 'total' => (int) $row->total])->all();
        $bySource = (clone $enquiries)->select('enquiry_source as label')->selectRaw('COUNT(*) as total')->groupBy('enquiry_source')->orderByDesc('total')->get()->map(fn ($row): array => ['label' => $row->label ?: 'Unknown', 'total' => (int) $row->total])->all();
        $daily = (clone $enquiries)->selectRaw('DATE(created_at) as day, COUNT(*) as total')->groupBy('day')->orderBy('day')->get()->map(fn ($row): array => ['day' => $row->day, 'total' => (int) $row->total])->all();
        $weekly = collect($daily)->groupBy(fn (array $row): string => CarbonImmutable::parse($row['day'])->startOfWeek()->toDateString())->map(fn ($rows, string $week): array => ['label' => 'Week of '.$week, 'total' => $rows->sum('total')])->values()->all();
        $monthly = collect($daily)->groupBy(fn (array $row): string => substr($row['day'], 0, 7))->map(fn ($rows, string $month): array => ['label' => $month, 'total' => $rows->sum('total')])->values()->all();
        $applicationStages = (clone $applications)->select('stage as label')->selectRaw('COUNT(*) as total')->groupBy('stage')->orderByDesc('total')->get()->map(fn ($row): array => ['label' => $row->label, 'total' => (int) $row->total])->all();
        $followUps = $scope->followUps($user)->whereDate('scheduled_at', '>=', $filters['from'])->whereDate('scheduled_at', '<=', $filters['to']);
        $followUpCompletion = (clone $followUps)->select('status as label')->selectRaw('COUNT(*) as total')->groupBy('status')->orderByDesc('total')->get()->map(fn ($row): array => ['label' => $row->label, 'total' => (int) $row->total])->all();
        $lenderSubmissions = (clone $applications)->whereNotNull('submitted_at')->selectRaw("COALESCE(lender_name, 'Lender not recorded') as label, COUNT(*) as total")->groupBy('lender_name')->orderByDesc('total')->get()->map(fn ($row): array => ['label' => $row->label, 'total' => (int) $row->total])->all();
        $lenderSubmissionStages = (clone $applications)->whereNotNull('submitted_at')->select('stage as label')->selectRaw('COUNT(*) as total')->groupBy('stage')->orderByDesc('total')->get()->map(fn ($row): array => ['label' => $row->label, 'total' => (int) $row->total])->all();
        $verifiedDisbursements = (clone $applications)->whereNotNull('verified_disbursement_amount');
        $disbursementByLender = (clone $verifiedDisbursements)->selectRaw("COALESCE(lender_name, 'Lender not recorded') as label, COUNT(*) as total, SUM(verified_disbursement_amount) as amount")->groupBy('lender_name')->orderByDesc('total')->get()->map(fn ($row): array => ['label' => $row->label, 'total' => (int) $row->total, 'amount' => (float) $row->amount])->all();
        $funnel = EnquiryStatusHistory::query()->whereIn('loan_enquiry_id', (clone $enquiries)->select('loan_enquiries.id'))->select('new_status as label')->selectRaw('COUNT(DISTINCT loan_enquiry_id) as total')->groupBy('new_status')->orderByDesc('total')->get()->map(fn ($row): array => ['label' => $row->label, 'total' => (int) $row->total])->all();
        $agentPerformance = $user->hasAdminPermission('enquiries.assign')
            ? (clone $enquiries)->whereNotNull('assigned_user_id')->with('assignee:id,name')->select('assigned_user_id')->selectRaw('COUNT(*) as total')->groupBy('assigned_user_id')->orderByDesc('total')->get()->map(fn (LoanEnquiry $enquiry): array => ['label' => $enquiry->assignee?->name ?? 'Staff', 'total' => (int) $enquiry->getAttribute('total')])->all()
            : [];
        $categories = (clone $scope->enquiries($user))->distinct()->orderBy('loan_category')->pluck('loan_category')->filter()->values()->all();
        $sources = (clone $scope->enquiries($user))->distinct()->orderBy('enquiry_source')->pluck('enquiry_source')->filter()->values()->all();
        $staff = User::query()->where('is_active', true)->whereNotNull('admin_role_id')
            ->when($user->adminRole?->slug === 'sales-manager', fn ($query) => $query->whereIn('id', $user->operationalTeamUserIds()))
            ->orderBy('name')->get(['id', 'name'])->map(fn (User $member): array => ['id' => $member->id, 'name' => $member->name])->all();
        $lenders = (clone $applications)->whereNotNull('lender_name')->distinct()->orderBy('lender_name')->pluck('lender_name')->values()->all();

        return Inertia::render('Admin/Reports/Index', [
            'summary' => [
                'enquiries' => $total,
                'applications' => (clone $applications)->count(),
                'verifiedDisbursements' => (clone $verifiedDisbursements)->count(),
                'verifiedDisbursementAmount' => (float) (clone $verifiedDisbursements)->sum('verified_disbursement_amount'),
                'conversionRate' => $total > 0 ? round((clone $verifiedDisbursements)->count() / $total * 100, 1) : 0,
                'pendingFollowUps' => (clone $followUps)->where('status', 'pending')->count(),
            ],
            'byStatus' => $byStatus,
            'byCategory' => $byCategory,
            'bySource' => $bySource,
            'daily' => $daily,
            'weekly' => $weekly,
            'monthly' => $monthly,
            'applicationStages' => $applicationStages,
            'followUpCompletion' => $followUpCompletion,
            'lenderSubmissions' => $lenderSubmissions,
            'lenderSubmissionStages' => $lenderSubmissionStages,
            'disbursementByLender' => $disbursementByLender,
            'funnel' => $funnel,
            'agentPerformance' => $agentPerformance,
            'filters' => [
                'from' => $filters['from'], 'to' => $filters['to'], 'category' => $filters['category'] ?? '',
                'status' => $filters['status'] ?? '', 'application_stage' => $filters['application_stage'] ?? '', 'source' => $filters['source'] ?? '',
                'lender' => $filters['lender'] ?? '',
                'assigned_user_id' => (string) ($filters['assigned_user_id'] ?? ''),
            ],
            'options' => ['categories' => $categories, 'sources' => $sources, 'staff' => $staff, 'lenders' => $lenders, 'statuses' => UpdateEnquiryStatus::STATUSES, 'applicationStages' => UpdateLoanApplicationStage::STAGES],
            'canExport' => $user->hasAdminPermission('reports.export'),
        ]);
    }

    public function export(Request $request, AuditLogger $auditLogger, AdminRecordScope $scope): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user->hasAdminPermission('reports.export'), 403);
        $filters = $request->validate([
            'from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from'],
            'category' => ['nullable', 'string', 'max:80'], 'status' => ['nullable', Rule::in(UpdateEnquiryStatus::STATUSES)],
            'application_stage' => ['nullable', Rule::in(UpdateLoanApplicationStage::STAGES)], 'source' => ['nullable', 'string', 'max:80'],
            'lender' => ['nullable', 'string', 'max:160'], 'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);
        $query = $this->enquiryQuery($user, $filters, $scope)->with('assignee:id,name')->orderBy('created_at')->orderBy('id');
        $auditLogger->record('report.enquiries_exported', $user, metadata: ['filters' => $filters, 'rows' => (clone $query)->count()]);

        return response()->streamDownload(function () use ($query): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                return;
            }
            fputcsv($output, ['Reference', 'Received at', 'Category', 'Requested amount', 'Status', 'Source', 'Assigned staff'], ',', '"', '');
            $query->chunk(500, function ($enquiries) use ($output): void {
                foreach ($enquiries as $enquiry) {
                    fputcsv($output, [
                        $this->safeCsvCell($enquiry->reference_number), $this->safeCsvCell($enquiry->created_at?->toIso8601String()),
                        $this->safeCsvCell($enquiry->loan_category), $this->safeCsvCell($enquiry->requested_amount),
                        $this->safeCsvCell($enquiry->status), $this->safeCsvCell($enquiry->enquiry_source),
                        $this->safeCsvCell($enquiry->assignee?->name),
                    ], ',', '"', '');
                }
            });
            fclose($output);
        }, 'sahu-enquiry-report.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @param array<string, mixed> $filters
     * @return Builder<LoanEnquiry>
     */
    private function enquiryQuery(User $user, array $filters, AdminRecordScope $scope): Builder
    {
        return $scope->enquiries($user)
            ->when($filters['from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->when($filters['category'] ?? null, fn ($query, $value) => $query->where('loan_category', $value))
            ->when($filters['status'] ?? null, fn ($query, $value) => $query->where('status', $value))
            ->when($filters['source'] ?? null, fn ($query, $value) => $query->where('enquiry_source', $value))
            ->when($filters['assigned_user_id'] ?? null, fn ($query, $id) => $query->where('assigned_user_id', $id));
    }

    /** @param array<string, mixed> $filters
     * @return Builder<LoanApplication>
     */
    private function applicationQuery(User $user, array $filters, AdminRecordScope $scope): Builder
    {
        return $scope->applications($user)
            ->when($filters['from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->when($filters['category'] ?? null, fn ($query, $value) => $query->where(function (Builder $builder) use ($value): void {
                $builder->where('product_slug', $value)
                    ->orWhereHas('product.categories', fn ($categories) => $categories->where('slug', $value))
                    ->orWhereHas('enquiry', fn ($enquiries) => $enquiries->where('loan_category', $value));
            }))
            ->when($filters['application_stage'] ?? null, fn ($query, $value) => $query->where('stage', $value))
            ->when($filters['assigned_user_id'] ?? null, fn ($query, $id) => $query->where('owner_user_id', $id))
            ->when($filters['lender'] ?? null, fn ($query, $value) => $query->where('lender_name', $value))
            ->when($filters['source'] ?? null, fn ($query, $value) => $query->whereHas('enquiry', fn ($enquiries) => $enquiries->where('enquiry_source', $value)));
    }

    private function safeCsvCell(mixed $value): string
    {
        $cell = (string) ($value ?? '');

        return preg_match('/^[\t\r\n ]*[=+\-@]/', $cell) ? "'".$cell : $cell;
    }
}
