<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Database;
use PDO;

class DatabaseSeeder
{
    private PDO $pdo;

    public function __construct(?string $connection = null)
    {
        $this->pdo = Database::pdo($connection);
    }

    public function run(bool $seedDemoData = true, bool $seedUsers = true, bool $rolesOnly = false): void
    {
        echo "Seeding system roles, permissions, and settings...\n";
        $this->seedRolesAndPermissions();
        $this->seedSettings();
        $this->seedExpenseCategories();

        if ($rolesOnly) {
            echo "Roles-only mode: Skipped user accounts, cash accounts, and demo data.\n";
            return;
        }

        $this->seedCashAccounts();

        if ($seedUsers) {
            echo "Seeding default environment users...\n";
            $this->seedUsers();
        } else {
            echo "Skipping default users seeding (Use scripts/create_admin.php for secure admin provisioning).\n";
        }

        if ($seedDemoData) {
            echo "Seeding synthetic demonstration data...\n";
            $this->seedDemoCRMAndProjects();
            $this->seedDemoFinance();
        } else {
            echo "Skipping demonstration data (clean environment).\n";
        }

        echo "Seeding completed successfully.\n";
    }

    private function seedRolesAndPermissions(): void
    {
        $rolesConfig = config('permissions.roles', []);
        $permissionsConfig = config('permissions.permissions', []);
        $matrixConfig = config('permissions.role_permissions', []);

        // Insert Roles
        $roleStmt = $this->pdo->prepare("
            INSERT INTO `roles` (`name`, `slug`, `description`)
            VALUES (:name, :slug, :description)
            ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`)
        ");
        foreach ($rolesConfig as $slug => $name) {
            $roleStmt->execute([
                'name' => $name,
                'slug' => $slug,
                'description' => "Default {$name} role for AT Loops OS",
            ]);
        }

        // Insert Permissions
        $permStmt = $this->pdo->prepare("
            INSERT INTO `permissions` (`name`, `slug`, `module`, `description`)
            VALUES (:name, :slug, :module, :description)
            ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `module` = VALUES(`module`)
        ");
        foreach ($permissionsConfig as $slug => $name) {
            $parts = explode('.', $slug);
            $module = $parts[0] ?? 'general';
            $permStmt->execute([
                'name' => $name,
                'slug' => $slug,
                'module' => $module,
                'description' => $name,
            ]);
        }

        // Fetch role IDs
        $roles = $this->pdo->query("SELECT id, slug FROM roles")->fetchAll(PDO::FETCH_KEY_PAIR);
        $permissions = $this->pdo->query("SELECT id, slug FROM permissions")->fetchAll(PDO::FETCH_KEY_PAIR);
        $permIdBySlug = array_flip($permissions);

        // Map Role Permissions
        $rpStmt = $this->pdo->prepare("
            INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
            VALUES (:role_id, :permission_id)
        ");
        foreach ($matrixConfig as $roleSlug => $permSlugs) {
            if (!isset($roles[$roleSlug])) {
                continue;
            }
            $roleId = (int)$roles[$roleSlug];
            foreach ($permSlugs as $pSlug) {
                if ($pSlug === '*') {
                    // Give all permissions
                    foreach ($permIdBySlug as $pId) {
                        $rpStmt->execute(['role_id' => $roleId, 'permission_id' => $pId]);
                    }
                } elseif (isset($permIdBySlug[$pSlug])) {
                    $rpStmt->execute(['role_id' => $roleId, 'permission_id' => (int)$permIdBySlug[$pSlug]]);
                }
            }
        }
    }

    private function seedUsers(): void
    {
        $defaultPassword = password_hash('Password123!', PASSWORD_DEFAULT);

        $users = [
            [
                'name' => 'Mohamed Fahmy (Super Admin)',
                'email' => 'admin@atloops.com',
                'password' => $defaultPassword,
                'phone' => '+201000000001',
                'job_title' => 'Managing Director & CTO',
                'hourly_cost' => 150.00,
                'role' => 'super_admin',
            ],
            [
                'name' => 'Operations Manager',
                'email' => 'manager@atloops.com',
                'password' => $defaultPassword,
                'phone' => '+201000000002',
                'job_title' => 'General Operations Manager',
                'hourly_cost' => 100.00,
                'role' => 'manager',
            ],
            [
                'name' => 'Sales Lead',
                'email' => 'sales@atloops.com',
                'password' => $defaultPassword,
                'phone' => '+201000000003',
                'job_title' => 'Senior Account Executive',
                'hourly_cost' => 80.00,
                'role' => 'sales',
            ],
            [
                'name' => 'Lead Software Engineer',
                'email' => 'dev@atloops.com',
                'password' => $defaultPassword,
                'phone' => '+201000000004',
                'job_title' => 'Senior Full-Stack Engineer',
                'hourly_cost' => 90.00,
                'role' => 'developer',
            ],
            [
                'name' => 'Finance Controller',
                'email' => 'finance@atloops.com',
                'password' => $defaultPassword,
                'phone' => '+201000000005',
                'job_title' => 'Financial Officer',
                'hourly_cost' => 85.00,
                'role' => 'finance',
            ],
        ];

        $userStmt = $this->pdo->prepare("
            INSERT INTO `users` (`name`, `email`, `password`, `phone`, `job_title`, `hourly_cost`, `is_active`)
            VALUES (:name, :email, :password, :phone, :job_title, :hourly_cost, 1)
            ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `job_title` = VALUES(`job_title`), `hourly_cost` = VALUES(`hourly_cost`)
        ");

        $urStmt = $this->pdo->prepare("
            INSERT IGNORE INTO `user_roles` (`user_id`, `role_id`)
            VALUES (:user_id, :role_id)
        ");

        $roles = $this->pdo->query("SELECT slug, id FROM roles")->fetchAll(PDO::FETCH_KEY_PAIR);

        foreach ($users as $u) {
            $userStmt->execute([
                'name' => $u['name'],
                'email' => $u['email'],
                'password' => $u['password'],
                'phone' => $u['phone'],
                'job_title' => $u['job_title'],
                'hourly_cost' => $u['hourly_cost'],
            ]);

            $userId = (int)$this->pdo->query("SELECT id FROM users WHERE email = " . $this->pdo->quote($u['email']))->fetchColumn();
            $roleSlug = $u['role'];
            if (isset($roles[$roleSlug])) {
                $urStmt->execute([
                    'user_id' => $userId,
                    'role_id' => (int)$roles[$roleSlug],
                ]);
            }
        }
    }

    private function seedSettings(): void
    {
        $settings = [
            'company_name' => 'AT Loops for Information Technology',
            'company_name_ar' => 'شركة إيه تي لوبس لتقنية المعلومات والحلول البرمجية',
            'company_email' => 'info@atloops.com',
            'company_phone' => '+20 100 000 0000',
            'company_website' => 'https://atloops.com',
            'company_tax_number' => 'EG-TAX-987654321',
            'company_commercial_registry' => 'CR-123456',
            'company_address' => 'Nasr City, Cairo, Egypt',
            'default_currency' => 'EGP',
            'default_tax_rate' => '14.00',
            'invoice_prefix' => 'INV-',
            'quotation_prefix' => 'QT-',
            'contract_prefix' => 'CTR-',
        ];

        $stmt = $this->pdo->prepare("
            INSERT INTO `settings` (`key`, `value`, `group`)
            VALUES (:key, :value, 'company')
            ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)
        ");

        foreach ($settings as $key => $val) {
            $stmt->execute(['key' => $key, 'value' => $val]);
        }
    }

    private function seedExpenseCategories(): void
    {
        $categories = [
            ['name' => 'Hosting & Cloud Infrastructure', 'slug' => 'hosting-cloud', 'description' => 'Servers, AWS, Cloudflare, domains'],
            ['name' => 'Office Rent & Utilities', 'slug' => 'office-utilities', 'description' => 'Workspace rent, electricity, internet'],
            ['name' => 'Salaries & Contractor Fees', 'slug' => 'salaries-contractors', 'description' => 'Team compensation and freelance payments'],
            ['name' => 'Software Tools & Subscriptions', 'slug' => 'software-subscriptions', 'description' => 'SaaS tools, IDEs, code repositories'],
            ['name' => 'Marketing & Advertising', 'slug' => 'marketing-ads', 'description' => 'Google ads, social media campaigns, print'],
            ['name' => 'Legal & Governmental Fees', 'slug' => 'legal-gov', 'description' => 'Taxes, legal council, certifications'],
            ['name' => 'Office Supplies & Hospitality', 'slug' => 'supplies-hospitality', 'description' => 'Refreshments, stationery, maintenance'],
        ];

        $stmt = $this->pdo->prepare("
            INSERT INTO `expense_categories` (`name`, `slug`, `description`)
            VALUES (:name, :slug, :description)
            ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`)
        ");

        foreach ($categories as $cat) {
            $stmt->execute($cat);
        }
    }

    private function seedCashAccounts(): void
    {
        $accounts = [
            [
                'name' => 'CIB Corporate Account (EGP)',
                'account_type' => 'bank',
                'currency' => 'EGP',
                'account_number' => '1000-4567-8901',
                'bank_name' => 'Commercial International Bank (CIB)',
                'opening_balance' => 25000.00,
                'current_balance' => 25000.00,
            ],
            [
                'name' => 'Cairo Office Petty Cash (EGP)',
                'account_type' => 'petty_cash',
                'currency' => 'EGP',
                'account_number' => 'CASH-CAIRO-01',
                'bank_name' => 'Safe Box #1',
                'opening_balance' => 5000.00,
                'current_balance' => 5000.00,
            ],
            [
                'name' => 'QNB Alahli USD Account',
                'account_type' => 'bank',
                'currency' => 'USD',
                'account_number' => '2000-8899-4411',
                'bank_name' => 'QNB Alahli',
                'opening_balance' => 1500.00,
                'current_balance' => 1500.00,
            ],
        ];

        $stmt = $this->pdo->prepare("
            INSERT INTO `cash_accounts` (`name`, `account_type`, `currency`, `account_number`, `bank_name`, `opening_balance`, `current_balance`)
            VALUES (:name, :account_type, :currency, :account_number, :bank_name, :opening_balance, :current_balance)
            ON DUPLICATE KEY UPDATE `bank_name` = VALUES(`bank_name`), `current_balance` = VALUES(`current_balance`)
        ");

        $txStmt = $this->pdo->prepare("
            INSERT INTO `cash_transactions` (`cash_account_id`, `type`, `amount`, `balance_after`, `currency`, `reference_type`, `description`, `transaction_date`)
            VALUES (:cash_account_id, 'deposit', :amount, :balance_after, :currency, 'opening', 'Opening Balance Registration', NOW())
        ");

        foreach ($accounts as $acc) {
            $stmt->execute($acc);
            $accId = (int)$this->pdo->lastInsertId();
            if ($accId > 0 && $acc['opening_balance'] > 0) {
                $txStmt->execute([
                    'cash_account_id' => $accId,
                    'amount' => $acc['opening_balance'],
                    'balance_after' => $acc['opening_balance'],
                    'currency' => $acc['currency'],
                ]);
            }
        }
    }

    private function seedDemoCRMAndProjects(): void
    {
        // 1. Companies
        $companies = [
            [
                'name' => 'Al-Amal Medical Group',
                'industry' => 'Healthcare',
                'country' => 'Egypt',
                'city' => 'Cairo',
                'website' => 'https://alamal-clinic.example',
                'phone' => '+20223456789',
                'email' => 'procurement@alamal.example',
                'tax_number' => 'EG-11223344',
                'status' => 'active_client',
            ],
            [
                'name' => 'Gulf Gulf Logistics Co.',
                'industry' => 'Logistics & Supply Chain',
                'country' => 'Saudi Arabia',
                'city' => 'Riyadh',
                'website' => 'https://gulflog.example',
                'phone' => '+966112345678',
                'email' => 'sales@gulflog.example',
                'tax_number' => 'SA-99887766',
                'status' => 'active_client',
            ],
            [
                'name' => 'Nile Retail Trading',
                'industry' => 'Retail & E-Commerce',
                'country' => 'Egypt',
                'city' => 'Alexandria',
                'website' => 'https://nileretail.example',
                'phone' => '+2034567890',
                'email' => 'info@nileretail.example',
                'tax_number' => 'EG-55667788',
                'status' => 'prospect',
            ],
        ];

        $compStmt = $this->pdo->prepare("
            INSERT INTO `companies` (`name`, `industry`, `country`, `city`, `website`, `phone`, `email`, `tax_number`, `status`)
            VALUES (:name, :industry, :country, :city, :website, :phone, :email, :tax_number, :status)
        ");

        $contactStmt = $this->pdo->prepare("
            INSERT INTO `contacts` (`company_id`, `first_name`, `last_name`, `email`, `phone`, `job_title`, `is_primary`, `provenance`, `lawful_basis`)
            VALUES (:company_id, :first_name, :last_name, :email, :phone, :job_title, :is_primary, 'client_referral', 'contract')
        ");

        $adminId = (int)$this->pdo->query("SELECT id FROM users WHERE email = 'admin@atloops.com'")->fetchColumn();
        $devId = (int)$this->pdo->query("SELECT id FROM users WHERE email = 'dev@atloops.com'")->fetchColumn();
        $salesId = (int)$this->pdo->query("SELECT id FROM users WHERE email = 'sales@atloops.com'")->fetchColumn();

        foreach ($companies as $idx => $comp) {
            $compStmt->execute($comp);
            $companyId = (int)$this->pdo->lastInsertId();

            $contactStmt->execute([
                'company_id' => $companyId,
                'first_name' => 'Khaled',
                'last_name' => 'Mansour',
                'email' => 'kmansour@' . basename($comp['website']) . '.com',
                'phone' => $comp['phone'],
                'job_title' => 'Chief Technology Officer',
                'is_primary' => 1,
            ]);
            $contactId = (int)$this->pdo->lastInsertId();

            // Opportunity
            $oppStmt = $this->pdo->prepare("
                INSERT INTO `opportunities` (`company_id`, `contact_id`, `assigned_to`, `title`, `stage`, `probability`, `estimated_value`, `currency`, `expected_close_date`)
                VALUES (:company_id, :contact_id, :assigned_to, :title, :stage, :probability, :estimated_value, :currency, DATE_ADD(CURDATE(), INTERVAL 30 DAY))
            ");

            if ($idx === 0) {
                // Al-Amal: Won
                $oppStmt->execute([
                    'company_id' => $companyId,
                    'contact_id' => $contactId,
                    'assigned_to' => $salesId,
                    'title' => 'Clinic Management Portal & Mobile App',
                    'stage' => 'won',
                    'probability' => 100,
                    'estimated_value' => 75000.00,
                    'currency' => 'EGP',
                ]);
                $oppId = (int)$this->pdo->lastInsertId();

                // Project
                $projStmt = $this->pdo->prepare("
                    INSERT INTO `projects` (`company_id`, `manager_id`, `code`, `name`, `description`, `status`, `start_date`, `target_end_date`, `budget_amount`, `currency`, `estimated_hours`)
                    VALUES (:company_id, :manager_id, 'PRJ-ALAMAL-01', 'Al-Amal Clinic Portal', 'Custom patient scheduling and doctor consultation system', 'active', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 60 DAY), 75000.00, 'EGP', 160.00)
                ");
                $projStmt->execute([
                    'company_id' => $companyId,
                    'manager_id' => $adminId,
                ]);
                $projId = (int)$this->pdo->lastInsertId();

                // Project Members
                $this->pdo->exec("INSERT INTO `project_members` (`project_id`, `user_id`, `role_in_project`, `custom_hourly_rate`) VALUES ({$projId}, {$devId}, 'Lead Engineer', 90.00)");
                $this->pdo->exec("INSERT INTO `project_members` (`project_id`, `user_id`, `role_in_project`, `custom_hourly_rate`) VALUES ({$projId}, {$adminId}, 'Project Manager', 150.00)");

                // Milestones
                $this->pdo->exec("INSERT INTO `milestones` (`project_id`, `title`, `due_date`, `status`) VALUES ({$projId}, 'Phase 1: Architecture & Auth', DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'completed')");
                $this->pdo->exec("INSERT INTO `milestones` (`project_id`, `title`, `due_date`, `status`) VALUES ({$projId}, 'Phase 2: Booking Engine & Payments', DATE_ADD(CURDATE(), INTERVAL 45 DAY), 'pending')");

                // Tasks
                $this->pdo->exec("INSERT INTO `tasks` (`project_id`, `assigned_to`, `created_by`, `title`, `priority`, `status`, `estimated_hours`, `due_date`) VALUES ({$projId}, {$devId}, {$adminId}, 'Design Patient Appointments DB Schema', 'high', 'done', 12.00, CURDATE())");
                $this->pdo->exec("INSERT INTO `tasks` (`project_id`, `assigned_to`, `created_by`, `title`, `priority`, `status`, `estimated_hours`, `due_date`) VALUES ({$projId}, {$devId}, {$adminId}, 'Develop Doctor Schedule Calendar View', 'medium', 'in_progress', 20.00, DATE_ADD(CURDATE(), INTERVAL 7 DAY))");

                // Time Entry
                $this->pdo->exec("INSERT INTO `time_entries` (`task_id`, `project_id`, `user_id`, `duration_minutes`, `hourly_cost`, `total_cost`, `is_billable`, `log_date`, `notes`) VALUES (1, {$projId}, {$devId}, 480, 90.00, 720.00, 1, CURDATE(), 'Implemented schema migrations and model definitions')");
            } elseif ($idx === 1) {
                // Gulf Logistics: Proposal stage
                $oppStmt->execute([
                    'company_id' => $companyId,
                    'contact_id' => $contactId,
                    'assigned_to' => $salesId,
                    'title' => 'Fleet Tracking API Integration',
                    'stage' => 'proposal',
                    'probability' => 60,
                    'estimated_value' => 15000.00,
                    'currency' => 'SAR',
                ]);
            } else {
                // Nile Retail: Qualified
                $oppStmt->execute([
                    'company_id' => $companyId,
                    'contact_id' => $contactId,
                    'assigned_to' => $salesId,
                    'title' => 'E-Commerce Marketplace Redesign',
                    'stage' => 'qualified',
                    'probability' => 40,
                    'estimated_value' => 45000.00,
                    'currency' => 'EGP',
                ]);
            }
        }
    }

    private function seedDemoFinance(): void
    {
        $adminId = (int)$this->pdo->query("SELECT id FROM users WHERE email = 'admin@atloops.com'")->fetchColumn();
        $companyId = (int)$this->pdo->query("SELECT id FROM companies WHERE name = 'Al-Amal Medical Group'")->fetchColumn();
        $projectId = (int)$this->pdo->query("SELECT id FROM projects WHERE code = 'PRJ-ALAMAL-01'")->fetchColumn();
        $cibAccountId = (int)$this->pdo->query("SELECT id FROM cash_accounts WHERE name LIKE '%CIB%'")->fetchColumn();

        if (!$companyId || !$cibAccountId) {
            return;
        }

        // 1. Invoice
        $invStmt = $this->pdo->prepare("
            INSERT INTO `invoices` (`invoice_number`, `project_id`, `company_id`, `created_by`, `issue_date`, `due_date`, `currency`, `subtotal`, `discount_amount`, `tax_rate`, `tax_amount`, `total_amount`, `paid_amount`, `status`, `notes`)
            VALUES ('INV-2026-001', :project_id, :company_id, :created_by, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 15 DAY), 'EGP', 35000.00, 0.00, 14.00, 4900.00, 39900.00, 20000.00, 'partially_paid', 'Phase 1 Milestone Invoice (50% upfront)')
        ");
        $invStmt->execute([
            'project_id' => $projectId,
            'company_id' => $companyId,
            'created_by' => $adminId,
        ]);
        $invId = (int)$this->pdo->lastInsertId();

        // Invoice Items
        $this->pdo->exec("INSERT INTO `invoice_items` (`invoice_id`, `item_order`, `description`, `quantity`, `unit_price`, `line_total`) VALUES ({$invId}, 1, 'Architecture, System Design & Infrastructure Setup', 1.00, 20000.00, 20000.00)");
        $this->pdo->exec("INSERT INTO `invoice_items` (`invoice_id`, `item_order`, `description`, `quantity`, `unit_price`, `line_total`) VALUES ({$invId}, 2, 'Core Patient Appointment Portal Development', 1.00, 15000.00, 15000.00)");

        // 2. Partial Payment
        $payStmt = $this->pdo->prepare("
            INSERT INTO `payments` (`payment_number`, `company_id`, `cash_account_id`, `received_by`, `payment_date`, `amount`, `currency`, `payment_method`, `reference_number`, `idempotency_key`, `notes`, `status`)
            VALUES ('PAY-2026-001', :company_id, :cash_account_id, :received_by, CURDATE(), 20000.00, 'EGP', 'bank_transfer', 'CIB-TX-998822', 'SEED-PAY-001', 'Initial advance deposit received', 'completed')
        ");
        $payStmt->execute([
            'company_id' => $companyId,
            'cash_account_id' => $cibAccountId,
            'received_by' => $adminId,
        ]);
        $payId = (int)$this->pdo->lastInsertId();

        // Payment Allocation
        $this->pdo->exec("INSERT INTO `payment_allocations` (`payment_id`, `invoice_id`, `allocated_amount`) VALUES ({$payId}, {$invId}, 20000.00)");

        // Update cash account balance
        $this->pdo->exec("UPDATE `cash_accounts` SET `current_balance` = `current_balance` + 20000.00 WHERE id = {$cibAccountId}");

        // Cash transaction trail
        $newBalance = (float)$this->pdo->query("SELECT current_balance FROM cash_accounts WHERE id = {$cibAccountId}")->fetchColumn();
        $this->pdo->exec("INSERT INTO `cash_transactions` (`cash_account_id`, `type`, `amount`, `balance_after`, `currency`, `reference_type`, `reference_id`, `description`, `transaction_date`) VALUES ({$cibAccountId}, 'deposit', 20000.00, {$newBalance}, 'EGP', 'payment', {$payId}, 'Payment Collection for INV-2026-001', NOW())");

        // 3. Demo Vendor & Expense
        $this->pdo->exec("INSERT INTO `vendors` (`name`, `phone`, `email`, `contact_person`) VALUES ('DigitalOcean Inc.', '+123456789', 'billing@digitalocean.com', 'Support')");
        $vendorId = (int)$this->pdo->lastInsertId();
        $catId = (int)$this->pdo->query("SELECT id FROM expense_categories WHERE slug = 'hosting-cloud'")->fetchColumn();

        $this->pdo->exec("INSERT INTO `expenses` (`expense_number`, `category_id`, `vendor_id`, `cash_account_id`, `project_id`, `user_id`, `expense_date`, `amount`, `currency`, `description`, `status`) VALUES ('EXP-2026-001', {$catId}, {$vendorId}, {$cibAccountId}, {$projectId}, {$adminId}, CURDATE(), 1250.00, 'EGP', 'Cloud Droplet & Managed Database for Clinic Staging Server', 'paid')");
        $expId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("UPDATE `cash_accounts` SET `current_balance` = `current_balance` - 1250.00 WHERE id = {$cibAccountId}");
        $balAfterExp = (float)$this->pdo->query("SELECT current_balance FROM cash_accounts WHERE id = {$cibAccountId}")->fetchColumn();
        $this->pdo->exec("INSERT INTO `cash_transactions` (`cash_account_id`, `type`, `amount`, `balance_after`, `currency`, `reference_type`, `reference_id`, `description`, `transaction_date`) VALUES ({$cibAccountId}, 'withdrawal', 1250.00, {$balAfterExp}, 'EGP', 'expense', {$expId}, 'Hosting infrastructure payment', NOW())");

        // 4. Demo Recurring Contract
        $this->pdo->exec("INSERT INTO `recurring_contracts` (`company_id`, `service_type`, `title`, `monthly_fee`, `currency`, `billing_frequency`, `start_date`, `next_billing_date`, `auto_renew`, `status`) VALUES ({$companyId}, 'maintenance', 'Monthly Server & App Maintenance Retainer', 3500.00, 'EGP', 'monthly', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1, 'active')");
    }
}
