<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Repositories\CompanyRepository;
use App\Repositories\ContactRepository;

class CompanyController extends Controller
{
    private CompanyRepository $companyRepo;
    private ContactRepository $contactRepo;

    public function __construct()
    {
        $this->companyRepo = new CompanyRepository();
        $this->contactRepo = new ContactRepository();
    }

    public function index(): void
    {
        $this->requirePermission('crm.view');

        $status = $_GET['status'] ?? null;
        $search = $_GET['search'] ?? null;

        $companies = $this->companyRepo->getAll($status, $search);

        $this->render('crm/companies/index', [
            'pageTitle' => __('common.companies'),
            'companies' => $companies,
            'selectedStatus' => $status,
            'searchQuery' => $search,
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('crm.create');

        $this->render('crm/companies/create', [
            'pageTitle' => __('common.add') . ' ' . __('common.companies'),
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('crm.create');

        $data = $this->validate($_POST, [
            'name' => 'required|min:2|max:150',
            'country' => 'required',
            'email' => 'email',
        ]);

        // Duplicate check
        $dup = $this->companyRepo->checkDuplicate(
            $data['name'],
            $data['email'] ?? null,
            $data['phone'] ?? null
        );

        if ($dup) {
            $this->redirectBack([
                'error' => "Possible duplicate detected: Company '{$dup['name']}' already exists with matching information."
            ]);
        }

        $companyId = $this->companyRepo->create($data);

        // If primary contact provided in form, insert contact
        if (!empty($_POST['contact_first_name'])) {
            $this->contactRepo->create([
                'company_id' => $companyId,
                'first_name' => $_POST['contact_first_name'],
                'last_name' => $_POST['contact_last_name'] ?? null,
                'email' => $_POST['contact_email'] ?? null,
                'phone' => $_POST['contact_phone'] ?? null,
                'job_title' => $_POST['contact_job_title'] ?? null,
                'is_primary' => 1,
            ]);
        }

        $this->redirect(url("crm/companies/{$companyId}"), [
            'success' => __('common.success')
        ]);
    }

    public function show(string $id): void
    {
        $this->requirePermission('crm.view');

        $company = $this->companyRepo->findWithContacts((int)$id);
        if (!$company) {
            $this->redirect(url('crm/companies'), ['error' => 'Company not found.']);
        }

        $this->render('crm/companies/show', [
            'pageTitle' => $company['name'],
            'company' => $company,
        ]);
    }

    public function exportCsv(): void
    {
        $this->requirePermission('crm.view');

        $companies = $this->companyRepo->getAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="atloops_companies_' . date('Y-m-d') . '.csv"');

        $out = fopen('php://output', 'w');
        // UTF-8 BOM for Excel Arabic support
        fputs($out, "\xEF\xBB\xBF");

        fputcsv($out, ['ID', 'Company Name', 'Industry', 'Country', 'City', 'Phone', 'Email', 'Tax ID', 'Status', 'Created At']);

        foreach ($companies as $c) {
            // Formula injection protection: prepend single quote if starts with =, +, -, @, \t, \r
            $safeName = $this->sanitizeForCsv($c['name']);
            fputcsv($out, [
                $c['id'],
                $safeName,
                $this->sanitizeForCsv($c['industry'] ?? ''),
                $this->sanitizeForCsv($c['country'] ?? ''),
                $this->sanitizeForCsv($c['city'] ?? ''),
                $this->sanitizeForCsv($c['phone'] ?? ''),
                $this->sanitizeForCsv($c['email'] ?? ''),
                $this->sanitizeForCsv($c['tax_number'] ?? ''),
                $c['status'],
                $c['created_at'],
            ]);
        }

        fclose($out);
        exit;
    }

    private function sanitizeForCsv(string $str): string
    {
        if (preg_match('/^[=\+\-@\t\r]/', $str)) {
            return "'" . $str;
        }
        return $str;
    }
}
