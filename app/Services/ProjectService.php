<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProjectService
{
    /**
     * Create a project manually or from a lead.
     */
    public function createProject(array $data, User $author): Project
    {
        return DB::transaction(function () use ($data, $author) {
            $data['project_number'] = Project::nextProjectNumber();
            $data['created_by_id'] = $author->id;
            $data['status'] = !empty($data['client_po_number']) ? 'open' : 'popending';

            $project = Project::create($data);

            $project->activities()->create([
                'user_id' => $author->id,
                'kind' => 'created',
                'text' => "Project created by {$author->name}",
            ]);

            AuditService::log('created', $project, "Created project {$project->project_number} for {$project->client_name}");

            return $project;
        });
    }

    /**
     * Create project directly from an Awarded Quotation.
     */
    public function createFromAwardedQuotation(Quotation $quotation, User $author): Project
    {
        return DB::transaction(function () use ($quotation, $author) {
            $lead = $quotation->lead;

            $project = Project::create([
                'project_number' => Project::nextProjectNumber(),
                'name' => $lead ? $lead->subject : "Project for {$quotation->quote_number}",
                'lead_id' => $lead?->id,
                'quotation_id' => $quotation->id,
                'branch_id' => $quotation->branch_id,
                'engineer_id' => $lead?->assigned_engineer_id ?? $quotation->prepared_by_id,
                'created_by_id' => $author->id,
                'client_name' => $lead ? $lead->company_name : 'Customer',
                'contract_value' => $quotation->grand_total,
                'start_date' => now()->toDateString(),
                'status' => 'popending', // Awaiting PO
                'description' => $quotation->technical_description,
            ]);

            $project->activities()->create([
                'user_id' => $author->id,
                'kind' => 'created',
                'text' => "Project initiated from awarded quotation {$quotation->quote_number}",
            ]);

            AuditService::log('created', $project, "Created project {$project->project_number} from quote {$quotation->quote_number}");

            return $project;
        });
    }

    /**
     * Record client PO and transition project to 'open'.
     */
    public function recordClientPo(Project $project, array $data, User $user): Project
    {
        return DB::transaction(function () use ($project, $data, $user) {
            $update = [
                'client_po_number' => $data['client_po_number'],
                'client_po_date' => $data['client_po_date'] ?? now()->toDateString(),
                'client_po_amount' => $data['client_po_amount'] ?? $project->contract_value,
            ];

            if (!empty($data['client_po_file'])) {
                $update['client_po_file'] = $data['client_po_file'];
            }

            // Transition status to open if it was popending
            if ($project->status === 'popending') {
                $update['status'] = 'open';
            }

            $project->update($update);

            $project->activities()->create([
                'user_id' => $user->id,
                'kind' => 'po_recorded',
                'text' => "Client PO #{$data['client_po_number']} recorded. Project is now Open.",
            ]);

            AuditService::log('po_recorded', $project, "Recorded Client PO {$data['client_po_number']} on project {$project->project_number}");

            \App\Jobs\SyncProjectToZohoJob::dispatch($project);

            return $project;
        });
    }

    /**
     * Change project status (start work, put on hold, resume, close).
     */
    public function updateStatus(Project $project, string $status, ?string $reason, User $user): Project
    {
        return DB::transaction(function () use ($project, $status, $reason, $user) {
            $oldStatus = $project->status;
            $update = ['status' => $status];

            if ($status === 'onhold') {
                $update['hold_reason'] = $reason;
            } elseif ($status === 'closed') {
                $update['closed_at'] = now();
            }

            $project->update($update);

            $actionText = match ($status) {
                'inprogress' => $oldStatus === 'onhold' ? "Work resumed by {$user->name}" : "Work started by {$user->name}. Site mobilized.",
                'onhold' => "Project put on hold: {$reason}",
                'completed' => "Work completed report submitted by {$user->name}",
                'invoicepending' => "Final invoice raised. Awaiting review to close.",
                'closed' => "Project officially closed on final invoice settlement.",
                default => "Status changed from {$oldStatus} to {$status}",
            };

            $project->activities()->create([
                'user_id' => $user->id,
                'kind' => 'status',
                'text' => $actionText,
            ]);

            AuditService::log('status_changed', $project, "Updated project {$project->project_number} status to {$status}");

            return $project;
        });
    }

    /**
     * Record work completion report and certificate.
     */
    public function completeWork(Project $project, array $data, User $user): Project
    {
        return DB::transaction(function () use ($project, $data, $user) {
            $wcc = $project->workCompletionCertificates()->create([
                'certificate_number' => \App\Models\WorkCompletionCertificate::nextCertificateNumber(),
                'certificate_date' => $data['certificate_date'] ?? now()->toDateString(),
                'completion_date' => $data['completion_date'] ?? now()->toDateString(),
                'work_description' => $data['work_description'] ?? ($project->name . ' - All scope of works executed in accordance with approved specifications.'),
                'retention_pct' => $data['retention_pct'] ?? 10.00,
                'retention_amount' => $data['retention_amount'] ?? round($project->contract_value * 0.10, 2),
                'engineer_id' => $user->id,
                'client_representative_name' => $data['client_representative_name'] ?? null,
                'client_representative_title' => $data['client_representative_title'] ?? null,
                'client_signed_at' => !empty($data['client_representative_name']) ? now() : null,
                'status' => 'signed',
                'notes' => $data['notes'] ?? null,
            ]);

            $project->update([
                'status' => 'completed',
                'actual_end_date' => $data['completion_date'] ?? now()->toDateString(),
                'progress_pct' => 100,
                'completion_report' => $data['work_description'] ?? null,
            ]);

            $project->activities()->create([
                'user_id' => $user->id,
                'kind' => 'completed',
                'text' => "Work Completion Certificate {$wcc->certificate_number} issued. Ready for final billing.",
            ]);

            AuditService::log('completed', $project, "Issued WCC {$wcc->certificate_number} for project {$project->project_number}");

            return $project;
        });
    }
}
