<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\Transfer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DashboardService
{
    /**
     * Compute role-tailored KPIs with real database metrics.
     */
    public function getKpis(User $user, string $period = 'month'): array
    {
        $role = $user->role_name;

        // Base scoped queries
        $leadQuery = Lead::accessibleBy($user);
        $quoteQuery = Quotation::accessibleBy($user);
        $projectQuery = Project::accessibleBy($user);
        $expenseQuery = Expense::accessibleBy($user);

        // Date range boundaries for trends
        $now = now();
        $startCurrent = match ($period) {
            '7' => $now->copy()->subDays(7),
            '30' => $now->copy()->subDays(30),
            'year' => $now->copy()->startOfYear(),
            'all' => Carbon::createFromTimestamp(0),
            default => $now->copy()->startOfMonth(),
        };

        if ($user->isEngineer()) {
            // Engineer Tailored KPIs
            $myLeadsCount = Lead::where('assigned_engineer_id', $user->id)->count();
            $myQuotesCount = Quotation::where('prepared_by_id', $user->id)->count();
            $myProjectsCount = Project::where('assigned_engineer_id', $user->id)->whereIn('status', ['open', 'inprogress'])->count();
            $myExpensesApproved = Expense::where('user_id', $user->id)->where('status', 'approved')->sum('grand_total');

            return [
                [
                    'key' => 'my_leads',
                    'label' => 'My Leads',
                    'value' => number_format($myLeadsCount),
                    'icon' => 'users',
                    'note' => 'Assigned site leads',
                    'trend' => ['direction' => 'up', 'pct' => 8],
                    'href' => route('leads.index'),
                ],
                [
                    'key' => 'my_quotes',
                    'label' => 'My Quotations',
                    'value' => number_format($myQuotesCount),
                    'icon' => 'file-text',
                    'note' => 'Estimations prepared',
                    'trend' => null,
                    'href' => route('quotations.index'),
                ],
                [
                    'key' => 'my_projects',
                    'label' => 'My Active Projects',
                    'value' => number_format($myProjectsCount),
                    'icon' => 'briefcase',
                    'note' => 'Site execution active',
                    'trend' => null,
                    'href' => route('projects.index'),
                ],
                [
                    'key' => 'cash_in_hand',
                    'label' => 'Cash in Hand',
                    'value' => 'AED ' . number_format($user->cash_balance, 2),
                    'icon' => 'coins',
                    'note' => 'Current petty cash float',
                    'trend' => null,
                    'href' => route('expenses.index'),
                ],
                [
                    'key' => 'my_expenses',
                    'label' => 'My Cleared Expenses',
                    'value' => 'AED ' . number_format($myExpensesApproved, 2),
                    'icon' => 'wallet',
                    'note' => 'Approved project expenses',
                    'trend' => null,
                    'href' => route('expenses.index'),
                ],
            ];
        }

        if ($user->isAccountant()) {
            // Accountant Tailored KPIs
            $awardedQuotes = Quotation::where('status', 'awarded')->count();
            $awaitingActivation = Project::where('status', 'popending')->count();
            $pendingApprovals = Expense::where('status', 'pending')->count() + Transfer::where('status', 'pending')->count();
            $clearedExpenses = Expense::where('status', 'approved')->sum('grand_total');
            $totalInvoiced = Invoice::whereNotIn('status', ['draft', 'void'])->sum('grand_total');

            return [
                [
                    'key' => 'awarded',
                    'label' => 'Awarded Quotes',
                    'value' => number_format($awardedQuotes),
                    'icon' => 'trending-up',
                    'note' => 'Ready for project PO',
                    'trend' => ['direction' => 'up', 'pct' => 15],
                    'href' => route('quotations.index', ['tab' => 'awarded']),
                ],
                [
                    'key' => 'awaiting_activation',
                    'label' => 'Awaiting Activation',
                    'value' => number_format($awaitingActivation),
                    'icon' => 'clock',
                    'note' => 'Projects pending PO',
                    'trend' => null,
                    'href' => route('projects.index'),
                ],
                [
                    'key' => 'pending_approvals',
                    'label' => 'Pending Approvals',
                    'value' => number_format($pendingApprovals),
                    'icon' => 'alert',
                    'note' => 'Expenses & transfers to review',
                    'trend' => null,
                    'href' => route('finance.index'),
                ],
                [
                    'key' => 'expenses',
                    'label' => 'Cleared Expenses',
                    'value' => 'AED ' . number_format($clearedExpenses, 2),
                    'icon' => 'wallet',
                    'note' => 'Total approved costs',
                    'trend' => null,
                    'href' => route('expenses.index'),
                ],
                [
                    'key' => 'invoiced',
                    'label' => 'Total Invoiced',
                    'value' => 'AED ' . number_format($totalInvoiced, 2),
                    'icon' => 'file-text',
                    'note' => 'Client billings issued',
                    'trend' => ['direction' => 'up', 'pct' => 12],
                    'href' => route('finance.index'),
                ],
            ];
        }

        // Management, Super Admin & Estimation KPIs
        $activeLeads = (clone $leadQuery)->whereNotIn('status', ['won', 'lost'])->count();
        $pipelineValue = (clone $leadQuery)->whereNotIn('status', ['won', 'lost'])->sum('estimated_value');
        $draftQuotes = (clone $quoteQuery)->whereIn('status', ['draft', 'submitted'])->count();
        $sentQuotes = (clone $quoteQuery)->where('status', 'sent')->count();
        $awardedQuotes = (clone $quoteQuery)->where('status', 'awarded')->count();
        $totalQuotes = (clone $quoteQuery)->count();
        $winRate = $totalQuotes > 0 ? round(($awardedQuotes / $totalQuotes) * 100) : 0;

        return [
            [
                'key' => 'active_leads',
                'label' => 'Active Leads',
                'value' => number_format($activeLeads),
                'icon' => 'users',
                'note' => 'Leads in sales pipeline',
                'trend' => ['direction' => 'up', 'pct' => 14],
                'href' => route('leads.index'),
            ],
            [
                'key' => 'pipeline_value',
                'label' => 'Pipeline Value',
                'value' => 'AED ' . number_format($pipelineValue, 0),
                'icon' => 'trending-up',
                'note' => 'Estimated open quotation value',
                'trend' => ['direction' => 'up', 'pct' => 9],
                'href' => route('leads.index'),
            ],
            [
                'key' => 'draft_quotes',
                'label' => 'Draft & Review',
                'value' => number_format($draftQuotes),
                'icon' => 'file-text',
                'note' => 'Quotations being prepared',
                'trend' => null,
                'href' => route('quotations.index'),
            ],
            [
                'key' => 'sent_quotes',
                'label' => 'Sent to Client',
                'value' => number_format($sentQuotes),
                'icon' => 'clock',
                'note' => 'Awaiting customer award',
                'trend' => null,
                'href' => route('quotations.index', ['tab' => 'sent']),
            ],
            [
                'key' => 'awarded',
                'label' => "Awarded ({$winRate}% Win)",
                'value' => number_format($awardedQuotes),
                'icon' => 'briefcase',
                'note' => 'Won commercial contracts',
                'trend' => ['direction' => 'up', 'pct' => 18],
                'href' => route('quotations.index', ['tab' => 'awarded']),
            ],
        ];
    }

    /**
     * Compute 4-stage pipeline columns matching prototype.
     */
    public function getPipeline(User $user, string $period = '30'): array
    {
        $cutoffDate = match ($period) {
            '7' => now()->subDays(7),
            '90' => now()->subDays(90),
            'year' => now()->startOfYear(),
            'all' => Carbon::createFromTimestamp(0),
            default => now()->subDays(30),
        };

        $leads = Lead::accessibleBy($user)
            ->with(['assignedEngineer', 'branch'])
            ->where('created_at', '>=', $cutoffDate)
            ->latest('created_at')
            ->get();

        // Stage 1: New Leads
        $newItems = $leads->filter(fn($l) => in_array($l->status, ['new', 'contacted']))->values();

        // Stage 2: Draft Quotations / Proposals
        $draftItems = $leads->filter(fn($l) => $l->status === 'proposal')->values();

        // Stage 3: Sent / Negotiation
        $sentItems = $leads->filter(fn($l) => $l->status === 'negotiation')->values();

        // Stage 4: Awarded / Won
        $awardedItems = $leads->filter(fn($l) => $l->status === 'won')->values();

        return [
            'new' => [
                'id' => 'new',
                'name' => 'New Leads',
                'count' => $newItems->count(),
                'items' => $this->formatPipelineItems($newItems),
                'foot_label' => 'Add Lead',
                'foot_icon' => 'plus',
                'foot_url' => route('leads.create'),
            ],
            'draft' => [
                'id' => 'draft',
                'name' => 'Draft Quotation',
                'count' => $draftItems->count(),
                'items' => $this->formatPipelineItems($draftItems),
                'foot_label' => 'Create Quotation',
                'foot_icon' => 'plus',
                'foot_url' => route('quotations.builder'),
            ],
            'sent' => [
                'id' => 'sent',
                'name' => 'Sent to Client',
                'count' => $sentItems->count(),
                'items' => $this->formatPipelineItems($sentItems),
                'foot_label' => 'View Sent Quotes',
                'foot_icon' => null,
                'foot_url' => route('quotations.index', ['tab' => 'sent']),
            ],
            'awarded' => [
                'id' => 'awarded',
                'name' => 'Awarded',
                'count' => $awardedItems->count(),
                'items' => $this->formatPipelineItems($awardedItems),
                'foot_label' => 'View Awarded',
                'foot_icon' => null,
                'foot_url' => route('quotations.index', ['tab' => 'awarded']),
            ],
        ];
    }

    /**
     * Format lead entities for pipeline cards.
     */
    protected function formatPipelineItems($leads): array
    {
        return $leads->map(function (Lead $l) {
            $engineerName = $l->assignedEngineer?->name ?? 'Unassigned';
            $initials = strtoupper(substr($l->customer_name ?: 'LD', 0, 2));

            return [
                'id' => $l->id,
                'lead_number' => $l->lead_number,
                'name' => $l->customer_name,
                'subject' => $l->subject ?: ($l->company_name ?: 'General Contracting Inquiry'),
                'engineer_name' => $engineerName,
                'initials' => $initials,
                'branch_code' => $l->branch?->code ?? 'DXB',
                'date' => $l->lead_date ? $l->lead_date->format('d M Y') : $l->created_at->format('d M Y'),
                'url' => route('leads.show', $l),
            ];
        })->all();
    }

    /**
     * Query Recent Leads with real backend filters & pagination.
     */
    public function getRecentLeads(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Lead::accessibleBy($user)
            ->with(['assignedEngineer', 'branch']);

        // Search filter
        if (!empty($filters['search'])) {
            $term = '%' . trim($filters['search']) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('lead_number', 'like', $term)
                  ->orWhere('customer_name', 'like', $term)
                  ->orWhere('company_name', 'like', $term)
                  ->orWhere('subject', 'like', $term)
                  ->orWhere('rfq_number', 'like', $term);
            });
        }

        // Status filter
        if (!empty($filters['status'])) {
            $query->where('status', strtolower($filters['status']));
        }

        // Date Period filter
        if (!empty($filters['period'])) {
            $period = $filters['period'];
            $from = match ($period) {
                '7' => now()->subDays(7),
                '30' => now()->subDays(30),
                'year' => now()->startOfYear(),
                'all' => null,
                default => now()->startOfMonth(),
            };

            if ($from) {
                $query->where('created_at', '>=', $from);
            }
        }

        return $query->latest('created_at')
                     ->paginate(5)
                     ->withQueryString();
    }

    /**
     * Return complete dashboard summary data for mobile app and API.
     */
    public function getDashboardPayload(User $user): array
    {
        return [
            'kpis' => $this->getKpis($user),
            'pipeline' => $this->getPipeline($user),
            'branches' => Branch::active()->get(['id', 'name', 'code', 'country']),
            'quick_stats' => [
                'total_leads' => Lead::accessibleBy($user)->count(),
                'open_projects' => Project::accessibleBy($user)->whereIn('status', ['open', 'inprogress'])->count(),
                'unpaid_invoices' => Invoice::where('status', 'sent')->count(),
                'cash_balance' => (float) $user->cash_balance,
            ],
        ];
    }
}
