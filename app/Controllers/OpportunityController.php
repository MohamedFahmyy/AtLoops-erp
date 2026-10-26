<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Repositories\CompanyRepository;
use App\Repositories\OpportunityRepository;
use App\Services\ConversionService;

class OpportunityController extends Controller
{
    private OpportunityRepository $oppRepo;
    private CompanyRepository $companyRepo;

    public function __construct()
    {
        $this->oppRepo = new OpportunityRepository();
        $this->companyRepo = new CompanyRepository();
    }

    public function index(): void
    {
        $this->requirePermission('crm.view');

        $currency = $_GET['currency'] ?? 'EGP';
        $opportunities = $this->oppRepo->getAll(null, $currency);

        $stages = [
            'new' => ['name' => 'New', 'color' => 'secondary'],
            'qualified' => ['name' => 'Qualified', 'color' => 'info'],
            'contacted' => ['name' => 'Contacted', 'color' => 'primary'],
            'meeting' => ['name' => 'Meeting', 'color' => 'warning'],
            'proposal' => ['name' => 'Proposal', 'color' => 'purple'],
            'negotiation' => ['name' => 'Negotiation', 'color' => 'dark'],
            'won' => ['name' => 'Won', 'color' => 'success'],
            'lost' => ['name' => 'Lost', 'color' => 'danger'],
        ];

        $oppsByStage = [];
        foreach ($stages as $key => $meta) {
            $oppsByStage[$key] = [];
        }

        foreach ($opportunities as $opp) {
            $stageKey = $opp['stage'];
            if (isset($oppsByStage[$stageKey])) {
                $oppsByStage[$stageKey][] = $opp;
            }
        }

        $this->render('crm/opportunities/index', [
            'pageTitle' => __('common.opportunities'),
            'opportunities' => $opportunities,
            'oppsByStage' => $oppsByStage,
            'stages' => $stages,
            'selectedCurrency' => $currency,
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('crm.create');

        $companies = $this->companyRepo->getAll();

        $this->render('crm/opportunities/create', [
            'pageTitle' => __('common.add') . ' ' . __('common.opportunities'),
            'companies' => $companies,
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('crm.create');

        $data = $this->validate($_POST, [
            'company_id' => 'required|integer',
            'title' => 'required|min:3|max:200',
            'estimated_value' => 'required|numeric|min:0',
            'currency' => 'required|in:EGP,SAR,USD',
            'stage' => 'required',
        ]);

        $oppId = $this->oppRepo->create($data);

        $this->redirect(url("crm/opportunities"), [
            'success' => __('common.success')
        ]);
    }

    public function updateStage(string $id): void
    {
        $this->requirePermission('crm.create');

        $stage = $_POST['stage'] ?? '';
        $validStages = ['new', 'qualified', 'contacted', 'meeting', 'proposal', 'negotiation', 'won', 'lost'];

        if (!in_array($stage, $validStages, true)) {
            Response::json(['error' => 'Invalid pipeline stage'], 422);
        }

        $this->oppRepo->updateStage((int)$id, $stage);
        Response::json(['success' => true, 'stage' => $stage]);
    }

    public function convertToQuotation(string $id): void
    {
        $this->requirePermission('crm.create');

        try {
            $result = ConversionService::convertOpportunityToQuotation((int)$id);
            $this->redirect(url("sales/quotations/{$result['quotation_id']}"), [
                'success' => "Opportunity converted to Quotation #{$result['quotation_number']} successfully!"
            ]);
        } catch (\Throwable $e) {
            $this->redirectBack(['error' => $e->getMessage()]);
        }
    }
}
