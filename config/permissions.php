<?php

declare(strict_types=1);

return [
    'roles' => [
        'super_admin' => 'Super Admin',
        'manager' => 'Manager',
        'sales' => 'Sales Representative',
        'developer' => 'Developer',
        'finance' => 'Finance Officer',
    ],
    'permissions' => [
        // Dashboard
        'dashboard.view' => 'View executive dashboard',
        'dashboard.financials' => 'View sensitive financial KPIs on dashboard',

        // CRM
        'crm.view' => 'View CRM records (companies, contacts, leads)',
        'crm.manage' => 'Create and edit CRM records',
        'crm.delete' => 'Delete or archive CRM records',
        'crm.import_export' => 'Import and export CRM CSV data',

        // Sales
        'sales.opportunities.manage' => 'Manage sales opportunities and pipeline',
        'sales.quotations.view' => 'View quotations',
        'sales.quotations.manage' => 'Create and edit quotations',
        'sales.quotations.approve' => 'Approve or finalize quotations',
        'sales.contracts.view' => 'View contracts',
        'sales.contracts.manage' => 'Create and manage contracts',
        'sales.convert' => 'Execute conversion (Lead -> Opp -> Quote -> Contract -> Project)',

        // Projects
        'projects.view' => 'View projects',
        'projects.manage' => 'Create and manage projects and milestones',
        'projects.tasks.manage' => 'Create and manage project tasks',
        'projects.tasks.update_status' => 'Update task status on Kanban board',
        'projects.time.log' => 'Log work hours on tasks',
        'projects.profitability.view' => 'View project costs and profitability margins',

        // Finance
        'finance.invoices.view' => 'View invoices',
        'finance.invoices.manage' => 'Create, edit, and void invoices',
        'finance.payments.record' => 'Record payments and partial payments',
        'finance.payments.reversal' => 'Reverse or void payment records',
        'finance.expenses.manage' => 'Manage business expenses and vendors',
        'finance.accounts.view' => 'View cash and bank accounts balances',
        'finance.accounts.transfer' => 'Execute inter-account cash transfers',
        'finance.reports.view' => 'View comprehensive financial and cash-flow reports',

        // Recurring
        'recurring.view' => 'View recurring contracts and retainers',
        'recurring.manage' => 'Create and manage recurring contracts',

        // Documents
        'documents.view' => 'View and download documents',
        'documents.upload' => 'Upload documents to secure vault',
        'documents.delete' => 'Delete documents from vault',

        // Administration
        'users.manage' => 'Manage user accounts and roles',
        'settings.manage' => 'Manage system settings',
        'audit.view' => 'View system audit trails',
        'backups.manage' => 'Generate and verify database backups',
    ],
    'role_permissions' => [
        'super_admin' => ['*'], // Wildcard matches all permissions
        'manager' => [
            'dashboard.view', 'dashboard.financials',
            'crm.view', 'crm.manage', 'crm.import_export',
            'sales.opportunities.manage', 'sales.quotations.view', 'sales.quotations.manage', 'sales.quotations.approve',
            'sales.contracts.view', 'sales.contracts.manage', 'sales.convert',
            'projects.view', 'projects.manage', 'projects.tasks.manage', 'projects.tasks.update_status', 'projects.time.log', 'projects.profitability.view',
            'finance.invoices.view', 'finance.invoices.manage', 'finance.payments.record', 'finance.expenses.manage', 'finance.accounts.view', 'finance.reports.view',
            'recurring.view', 'recurring.manage',
            'documents.view', 'documents.upload', 'documents.delete',
            'users.manage', 'audit.view', 'backups.manage',
        ],
        'sales' => [
            'dashboard.view',
            'crm.view', 'crm.manage', 'crm.import_export',
            'sales.opportunities.manage', 'sales.quotations.view', 'sales.quotations.manage',
            'sales.contracts.view', 'sales.convert',
            'documents.view', 'documents.upload',
        ],
        'developer' => [
            'dashboard.view',
            'projects.view', 'projects.tasks.update_status', 'projects.time.log',
            'documents.view', 'documents.upload',
        ],
        'finance' => [
            'dashboard.view', 'dashboard.financials',
            'sales.quotations.view', 'sales.contracts.view',
            'projects.view', 'projects.profitability.view',
            'finance.invoices.view', 'finance.invoices.manage', 'finance.payments.record', 'finance.payments.reversal',
            'finance.expenses.manage', 'finance.accounts.view', 'finance.accounts.transfer', 'finance.reports.view',
            'recurring.view', 'recurring.manage',
            'documents.view', 'documents.upload',
            'audit.view',
        ],
    ],
];
