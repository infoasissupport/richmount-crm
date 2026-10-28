<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(protected DashboardService $dashboardService) {}

    /**
     * Show the main application dashboard with real database aggregates.
     */
    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();
        $period = $request->get('period', 'month');
        $pipelinePeriod = $request->get('pipeline_period', '30');

        $kpis = $this->dashboardService->getKpis($user, $period);
        $pipeline = $this->dashboardService->getPipeline($user, $pipelinePeriod);
        $recentLeads = $this->dashboardService->getRecentLeads($user, $request->only(['search', 'status', 'period']));
        $branches = Branch::active()->get();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'kpis' => $kpis,
                    'pipeline' => $pipeline,
                    'recent_leads' => $recentLeads,
                ],
            ]);
        }

        return view('dashboard.index', compact(
            'user',
            'branches',
            'kpis',
            'pipeline',
            'recentLeads',
            'period',
            'pipelinePeriod'
        ));
    }
}
