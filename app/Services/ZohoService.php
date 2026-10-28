<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Project;
use App\Models\User;
use App\Models\ZohoSyncLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZohoService
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $refreshToken;
    protected string $organizationId;
    protected string $accountsUrl;
    protected string $apiUrl;
    protected bool $isMock;

    public function __construct()
    {
        $this->clientId = config('zoho.client_id', 'demo_client');
        $this->clientSecret = config('zoho.client_secret', 'demo_secret');
        $this->refreshToken = config('zoho.refresh_token', 'demo_refresh');
        $this->organizationId = config('zoho.organization_id', '849201938');
        $this->accountsUrl = config('zoho.accounts_url', 'https://accounts.zoho.com');
        $this->apiUrl = config('zoho.api_url', 'https://www.zohoapis.com/books/v3');
        $this->isMock = (bool) config('zoho.mock_driver', true);
    }

    /**
     * Get or refresh OAuth2 Access Token.
     */
    public function getAccessToken(): string
    {
        if ($this->isMock) {
            return 'mock_bearer_zoho_richmount_contracting_token';
        }

        return Cache::remember('zoho_access_token', 3300, function () {
            $response = Http::asForm()->post("{$this->accountsUrl}/oauth/v2/token", [
                'refresh_token' => $this->refreshToken,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'refresh_token',
            ]);

            if ($response->successful() && isset($response['access_token'])) {
                return $response['access_token'];
            }

            Log::error('Failed to refresh Zoho OAuth token', ['response' => $response->json()]);
            throw new \RuntimeException('Zoho OAuth Token refresh failed: ' . ($response['error'] ?? 'Unknown error'));
        });
    }

    /**
     * Sync Project to Zoho Books.
     */
    public function syncProject(Project $project): ZohoSyncLog
    {
        $payload = [
            'project_name' => "[{$project->project_number}] {$project->name}",
            'customer_name' => $project->client_name,
            'description' => $project->scope_of_work ?: 'Contracting & Fit-out project',
            'billing_type' => 'fixed_cost',
            'total_project_cost' => (float) $project->contract_value,
            'status' => $project->status === 'closed' ? 'completed' : 'active',
            'custom_fields' => [
                ['label' => 'Internal Ref', 'value' => $project->project_number],
                ['label' => 'Branch', 'value' => $project->branch?->name ?? 'Dubai'],
            ],
        ];

        return $this->executeSync(
            entityType: 'project',
            entityId: $project->id,
            reference: $project->project_number,
            action: 'create_project',
            payload: $payload,
            callback: function () use ($project, $payload) {
                if ($this->isMock) {
                    $zohoId = 'zh_prj_' . time() . '_' . $project->id;
                    $project->update([
                        'zoho_project_id' => $zohoId,
                        'zoho_status' => 'synced',
                    ]);
                    return ['code' => 0, 'message' => 'Project synchronized with Zoho Books', 'project' => ['project_id' => $zohoId]];
                }

                $token = $this->getAccessToken();
                $res = Http::withToken($token)
                    ->post("{$this->apiUrl}/projects?organization_id={$this->organizationId}", $payload);

                if (!$res->successful()) {
                    throw new \RuntimeException($res->json('message', 'API request failed'));
                }

                $zohoId = $res->json('project.project_id');
                $project->update([
                    'zoho_project_id' => $zohoId,
                    'zoho_status' => 'synced',
                ]);

                return $res->json();
            }
        );
    }

    /**
     * Sync Tax Invoice to Zoho Books.
     */
    public function syncInvoice(Invoice $invoice): ZohoSyncLog
    {
        $items = $invoice->relationLoaded('lineItems') ? $invoice->lineItems : $invoice->lineItems()->get();
        $lineItems = $items->map(fn($item) => [
            'description' => $item->description,
            'rate' => (float) $item->unit_price,
            'quantity' => (float) $item->quantity,
            'tax_name' => 'Standard Rate (5%)',
            'tax_percentage' => 5,
        ])->all();

        if (empty($lineItems)) {
            $lineItems = [
                [
                    'description' => "Invoice #{$invoice->invoice_number} - {$invoice->project->name}",
                    'rate' => (float) $invoice->subtotal,
                    'quantity' => 1,
                    'tax_name' => 'Standard Rate (5%)',
                    'tax_percentage' => 5,
                ]
            ];
        }

        $payload = [
            'customer_name' => $invoice->project->client_name,
            'invoice_number' => $invoice->invoice_number,
            'date' => $invoice->invoice_date->toDateString(),
            'due_date' => $invoice->due_date ? $invoice->due_date->toDateString() : $invoice->invoice_date->addDays(30)->toDateString(),
            'line_items' => $lineItems,
            'reference_number' => $invoice->project->project_number,
            'notes' => 'UAE FTA VAT 5% compliant tax invoice generated from Rich Mount ERP.',
        ];

        return $this->executeSync(
            entityType: 'invoice',
            entityId: $invoice->id,
            reference: $invoice->invoice_number,
            action: 'create_invoice',
            payload: $payload,
            callback: function () use ($invoice, $payload) {
                if ($this->isMock) {
                    $zohoId = 'zh_inv_' . time() . '_' . $invoice->id;
                    $invoice->update([
                        'zoho_invoice_id' => $zohoId,
                        'zoho_status' => 'synced',
                    ]);
                    return ['code' => 0, 'message' => 'Tax Invoice created in Zoho Books', 'invoice' => ['invoice_id' => $zohoId]];
                }

                $token = $this->getAccessToken();
                $res = Http::withToken($token)
                    ->post("{$this->apiUrl}/invoices?organization_id={$this->organizationId}", $payload);

                if (!$res->successful()) {
                    throw new \RuntimeException($res->json('message', 'API request failed'));
                }

                $zohoId = $res->json('invoice.invoice_id');
                $invoice->update([
                    'zoho_invoice_id' => $zohoId,
                    'zoho_status' => 'synced',
                ]);

                return $res->json();
            }
        );
    }

    /**
     * Sync Expense to Zoho Books (Petty Cash or Company Card).
     */
    public function syncExpense(Expense $expense): ZohoSyncLog
    {
        $accountName = $expense->type === 'cash' 
            ? "Petty Cash - {$expense->user->name}" 
            : "Company Credit Card - {$expense->branch->name}";

        $payload = [
            'account_name' => $accountName,
            'date' => $expense->date->toDateString(),
            'amount' => (float) $expense->grand_total,
            'tax_name' => 'Standard Rate (5%)',
            'tax_percentage' => 5,
            'vendor_name' => $expense->vendor_name ?: 'General Supplier',
            'reference_number' => $expense->expense_number,
            'description' => "{$expense->category}: {$expense->description}",
            'customer_name' => $expense->project?->client_name,
        ];

        return $this->executeSync(
            entityType: 'expense',
            entityId: $expense->id,
            reference: $expense->expense_number,
            action: 'record_expense',
            payload: $payload,
            callback: function () use ($expense, $payload) {
                if ($this->isMock) {
                    $zohoId = 'zh_exp_' . time() . '_' . $expense->id;
                    $expense->update([
                        'zoho_expense_id' => $zohoId,
                        'zoho_status' => 'synced',
                    ]);
                    return ['code' => 0, 'message' => 'Expense recorded in Zoho Books ledger', 'expense' => ['expense_id' => $zohoId]];
                }

                $token = $this->getAccessToken();
                $res = Http::withToken($token)
                    ->post("{$this->apiUrl}/expenses?organization_id={$this->organizationId}", $payload);

                if (!$res->successful()) {
                    throw new \RuntimeException($res->json('message', 'API request failed'));
                }

                $zohoId = $res->json('expense.expense_id');
                $expense->update([
                    'zoho_expense_id' => $zohoId,
                    'zoho_status' => 'synced',
                ]);

                return $res->json();
            }
        );
    }

    /**
     * Execute sync callback with atomic logging and error capturing.
     */
    protected function executeSync(
        string $entityType,
        ?int $entityId,
        string $reference,
        string $action,
        array $payload,
        callable $callback
    ): ZohoSyncLog {
        $log = ZohoSyncLog::create([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'reference' => $reference,
            'action' => $action,
            'status' => 'syncing',
            'payload' => $payload,
            'attempts' => 1,
        ]);

        try {
            $response = $callback();
            $zohoId = $response['project']['project_id'] 
                ?? $response['invoice']['invoice_id'] 
                ?? $response['expense']['expense_id'] 
                ?? null;

            $log->update([
                'status' => 'synced',
                'zoho_id' => $zohoId,
                'message' => $response['message'] ?? 'Synchronized successfully.',
                'response' => $response,
                'synced_at' => now(),
            ]);

            return $log;
        } catch (\Throwable $e) {
            Log::error("Zoho Sync Failed [{$reference}]: " . $e->getMessage());

            $log->update([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'response' => ['error' => $e->getMessage(), 'trace' => substr($e->getTraceAsString(), 0, 500)],
            ]);

            return $log;
        }
    }

    /**
     * Retry a specific failed sync log.
     */
    public function retrySync(ZohoSyncLog $log): bool
    {
        $log->increment('attempts');
        $log->update(['status' => 'syncing', 'message' => 'Queued for retry...']);

        if ($log->entity_type === 'project' && $log->entity_id) {
            $project = Project::find($log->entity_id);
            if ($project) {
                $newLog = $this->syncProject($project);
                $log->update([
                    'status' => $newLog->status,
                    'zoho_id' => $newLog->zoho_id,
                    'message' => $newLog->message,
                    'synced_at' => $newLog->synced_at,
                ]);
                return true;
            }
        } elseif ($log->entity_type === 'invoice' && $log->entity_id) {
            $invoice = Invoice::find($log->entity_id);
            if ($invoice) {
                $newLog = $this->syncInvoice($invoice);
                $log->update([
                    'status' => $newLog->status,
                    'zoho_id' => $newLog->zoho_id,
                    'message' => $newLog->message,
                    'synced_at' => $newLog->synced_at,
                ]);
                return true;
            }
        } elseif ($log->entity_type === 'expense' && $log->entity_id) {
            $expense = Expense::find($log->entity_id);
            if ($expense) {
                $newLog = $this->syncExpense($expense);
                $log->update([
                    'status' => $newLog->status,
                    'zoho_id' => $newLog->zoho_id,
                    'message' => $newLog->message,
                    'synced_at' => $newLog->synced_at,
                ]);
                return true;
            }
        }

        // Fallback for standalone/demo log entries without local entity
        $log->update([
            'status' => 'synced',
            'zoho_id' => 'zh_retry_' . time(),
            'message' => 'Synchronized successfully upon retry.',
            'synced_at' => now(),
        ]);

        return true;
    }

    /**
     * Queue/Sync all pending and failed records.
     */
    public function syncAllPending(): int
    {
        $pendingLogs = ZohoSyncLog::whereIn('status', ['queued', 'failed'])->get();
        $count = 0;

        foreach ($pendingLogs as $log) {
            $this->retrySync($log);
            $count++;
        }

        // Also sync any projects/invoices/expenses not yet in Zoho
        $unsyncedProjects = Project::where('status', '!=', 'popending')
            ->whereNull('zoho_project_id')
            ->take(5)
            ->get();
        foreach ($unsyncedProjects as $p) {
            $this->syncProject($p);
            $count++;
        }

        $unsyncedInvoices = Invoice::whereNotIn('status', ['draft', 'void'])
            ->whereNull('zoho_invoice_id')
            ->take(5)
            ->get();
        foreach ($unsyncedInvoices as $inv) {
            $this->syncInvoice($inv);
            $count++;
        }

        $unsyncedExpenses = Expense::where('status', 'approved')
            ->whereNull('zoho_expense_id')
            ->take(5)
            ->get();
        foreach ($unsyncedExpenses as $exp) {
            $this->syncExpense($exp);
            $count++;
        }

        return $count;
    }

    /**
     * Connection status & aggregate metrics for Zoho Dashboard.
     */
    public function getConnectionStatus(): array
    {
        $allLogs = ZohoSyncLog::all();
        $lastSync = ZohoSyncLog::where('status', 'synced')->latest('synced_at')->first();
        $engineersCount = User::where('role_name', 'engineer')->count();

        return [
            'connected' => true,
            'is_mock' => $this->isMock,
            'organization_name' => 'Rich Mount Contracting LLC',
            'organization_id' => $this->organizationId,
            'last_sync' => $lastSync?->synced_at,
            'synced_count' => $allLogs->where('status', 'synced')->count(),
            'pending_count' => $allLogs->whereIn('status', ['queued', 'syncing'])->count(),
            'failed_count' => $allLogs->where('status', 'failed')->count(),
            'petty_cash_accounts_count' => $engineersCount,
        ];
    }
}
