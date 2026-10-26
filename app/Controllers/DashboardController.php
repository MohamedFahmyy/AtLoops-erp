<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\DashboardRepository;

class DashboardController extends Controller
{
    private DashboardRepository $dashboardRepo;

    public function __construct()
    {
        $this->dashboardRepo = new DashboardRepository();
    }

    public function index(): void
    {
        $currency = $_GET['currency'] ?? 'EGP';
        if (!in_array($currency, ['EGP', 'SAR', 'USD'], true)) {
            $currency = 'EGP';
        }

        $financialKPIs = $this->dashboardRepo->getFinancialKPIs($currency);
        $salesKPIs = $this->dashboardRepo->getSalesKPIs($currency);
        $projectKPIs = $this->dashboardRepo->getProjectKPIs();
        $chartData = $this->dashboardRepo->getMonthlyCashFlowChartData($currency);
        $pipelineChart = $this->dashboardRepo->getPipelineStageChartData($currency);
        $recentInvoices = $this->dashboardRepo->getRecentInvoices($currency, 5);
        $recentPayments = $this->dashboardRepo->getRecentPayments($currency, 5);
        $topOpportunities = $this->dashboardRepo->getTopOpportunities($currency, 5);

        $this->render('dashboard/index', [
            'pageTitle' => __('common.dashboard'),
            'selectedCurrency' => $currency,
            'financial' => $financialKPIs,
            'sales' => $salesKPIs,
            'projects' => $projectKPIs,
            'chartData' => $chartData,
            'pipelineChart' => $pipelineChart,
            'recentInvoices' => $recentInvoices,
            'recentPayments' => $recentPayments,
            'topOpportunities' => $topOpportunities,
        ]);
    }
}
