<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(protected ReportService $reportService) {}

    /**
     * Display Report Library and Interactive Reports.
     */
    public function index(Request $request): View|JsonResponse
    {
        $reportType = $request->get('type', 'profitability');

        $data = match ($reportType) {
            'profitability' => $this->reportService->projectProfitability(),
            'vat' => $this->reportService->vatSummary(),
            'cash' => User::where('role_name', 'engineer')->get()->map(fn($e) => $this->reportService->engineerCashLedger($e)),
            default => $this->reportService->projectProfitability(),
        };

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'report_type' => $reportType,
                'data' => $data,
            ]);
        }

        return view('reports.index', compact('reportType', 'data'));
    }

    /**
     * Export Report to CSV.
     */
    public function export(Request $request): Response
    {
        $reportType = $request->get('type', 'profitability');

        if ($reportType === 'profitability') {
            $rows = $this->reportService->projectProfitability();
            $csv = "Project Number,Project Name,Client,Branch,Status,Contract Value,Invoiced,Total Cost,Profit,Margin %\n";
            foreach ($rows as $r) {
                $csv .= "\"{$r['project_number']}\",\"{$r['name']}\",\"{$r['client']}\",\"{$r['branch']}\",\"{$r['status']}\",{$r['contract_value']},{$r['invoiced']},{$r['total_cost']},{$r['profit']},{$r['margin_pct']}%\n";
            }
            return response($csv, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="Project_Profitability_Report.csv"',
            ]);
        }

        return response("Report type not supported for CSV export", 400);
    }
}
