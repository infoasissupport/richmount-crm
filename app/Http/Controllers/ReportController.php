<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use App\Services\ReportService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(protected ReportService $reportService) {}

    /**
     * Display Report Library and Interactive Reports.
     */
    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();
        if (!$user->can('reports.view') && !$user->isSuperAdmin() && !$user->isAccountant()) {
            abort(403, 'Unauthorized access to reports.');
        }

        $reportType = $request->get('type', 'profitability');
        $filters = [
            'search' => $request->get('search'),
            'project_id' => $request->get('project_id'),
            'branch_id' => $request->get('branch_id'),
            'client' => $request->get('client'),
            'status' => $request->get('status'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ];

        $data = match ($reportType) {
            'profitability' => $this->reportService->projectProfitability($filters),
            'vat' => $this->reportService->vatSummary(),
            'cash' => User::where('role_name', 'engineer')->get()->map(fn($e) => $this->reportService->engineerCashLedger($e)),
            default => $this->reportService->projectProfitability($filters),
        };

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'report_type' => $reportType,
                'data' => $data,
            ]);
        }

        $projectOptions = Project::orderBy('project_number')->get();
        $branchOptions = \App\Models\Branch::orderBy('name')->get();
        $projectRows = $reportType === 'profitability'
            ? $this->paginateReportRows($data, $request, 'projects_page')
            : null;
        if ($reportType === 'cash') {
            $data = $data->map(function (array $ledger) use ($request): array {
                $pageName = 'cash_page_' . $ledger['engineer']->id;
                $ledger['history'] = $this->paginateReportRows($ledger['history'], $request, $pageName);

                return $ledger;
            });
        }

        return view('reports.index', compact('reportType', 'data', 'filters', 'projectOptions', 'branchOptions', 'projectRows'));
    }

    public function project(Project $project, Request $request): View
    {
        $user = $request->user();
        if (!$user->can('reports.view') && !$user->isSuperAdmin() && !$user->isAccountant()) {
            abort(403, 'Unauthorized access to reports.');
        }

        $detail = $this->reportService->projectFinancialDetail($project);
        $detail['income_rows'] = $this->paginateReportRows($detail['income_rows'], $request, 'income_page');
        $detail['expense_rows'] = $this->paginateReportRows($detail['expense_rows'], $request, 'expense_page');

        return view('reports.project', [
            'project' => $project,
            'detail' => $detail,
        ]);
    }

    private function paginateReportRows(array $rows, Request $request, string $pageName, int $perPage = 10): LengthAwarePaginator
    {
        $currentPage = LengthAwarePaginator::resolveCurrentPage($pageName);

        return (new LengthAwarePaginator(
            array_slice($rows, ($currentPage - 1) * $perPage, $perPage),
            count($rows),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'pageName' => $pageName,
            ],
        ))->appends($request->except($pageName));
    }

    /**
     * Export Report to CSV.
     */
    public function export(Request $request): Response
    {
        $user = $request->user();
        if (!$user->can('reports.export') && !$user->isSuperAdmin() && !$user->isAccountant()) {
            abort(403, 'Unauthorized to export reports.');
        }

        $reportType = $request->get('type', 'profitability');
        $filters = [
            'search' => $request->get('search'),
            'project_id' => $request->get('project_id'),
            'branch_id' => $request->get('branch_id'),
            'client' => $request->get('client'),
            'status' => $request->get('status'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ];

        if ($reportType === 'profitability') {
            $rows = $this->reportService->projectProfitability($filters);
            $csv = "Project Number,Project Name,Client,Branch,Status,Contract Amount,Total Invoiced,Total Received,Total Expenses,Outstanding,Net Profit,Profit Margin %\n";
            foreach ($rows as $r) {
                $csv .= "\"{$r['project_number']}\",\"{$r['name']}\",\"{$r['client']}\",\"{$r['branch']}\",\"{$r['status']}\",{$r['contract_value']},{$r['total_invoiced']},{$r['total_received']},{$r['total_expenses']},{$r['outstanding_amount']},{$r['net_profit']},{$r['profit_margin']}\n";
            }
            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="Project_Financial_Report.csv"',
            ]);
        }

        return response("Report type not supported for CSV export", 400);
    }

    public function pdf(Request $request): Response
    {
        $user = $request->user();
        if (!$user->can('reports.export') && !$user->isSuperAdmin() && !$user->isAccountant()) {
            abort(403, 'Unauthorized to export reports.');
        }

        $reportType = $request->get('type', 'profitability');
        $filters = [
            'search' => $request->get('search'),
            'project_id' => $request->get('project_id'),
            'branch_id' => $request->get('branch_id'),
            'client' => $request->get('client'),
            'status' => $request->get('status'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ];

        $data = $this->reportService->projectProfitability($filters);
        $html = view('reports.pdf', [
            'reportType' => $reportType,
            'data' => $data,
            'filters' => $filters,
        ])->render();

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Project_Financial_Report.pdf"',
        ]);
    }
}
