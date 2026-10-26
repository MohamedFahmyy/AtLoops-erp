-- AT Loops OS Database Schema
-- MySQL 8.x Compatible, InnoDB, UTF8MB4

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Migrations table
CREATE TABLE IF NOT EXISTS `migrations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `migration` VARCHAR(255) NOT NULL,
    `batch` INT UNSIGNED NOT NULL,
    `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Users table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(191) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(50) NULL,
    `job_title` VARCHAR(100) NULL,
    `avatar` VARCHAR(255) NULL,
    `hourly_cost` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `last_login_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_email` (`email`),
    INDEX `idx_users_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Roles table
CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(50) NOT NULL UNIQUE,
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Permissions table
CREATE TABLE IF NOT EXISTS `permissions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `module` VARCHAR(50) NOT NULL,
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_permissions_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Role Permissions pivot
CREATE TABLE IF NOT EXISTS `role_permissions` (
    `role_id` INT UNSIGNED NOT NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`role_id`, `permission_id`),
    CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. User Roles pivot
CREATE TABLE IF NOT EXISTS `user_roles` (
    `user_id` INT UNSIGNED NOT NULL,
    `role_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`, `role_id`),
    CONSTRAINT `fk_ur_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ur_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Password Reset Tokens
CREATE TABLE IF NOT EXISTS `password_resets` (
    `email` VARCHAR(191) NOT NULL,
    `token` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_pr_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Companies (Clients & Leads)
CREATE TABLE IF NOT EXISTS `companies` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(200) NOT NULL,
    `industry` VARCHAR(100) NULL,
    `country` VARCHAR(100) NOT NULL DEFAULT 'Egypt',
    `city` VARCHAR(100) NULL,
    `address` TEXT NULL,
    `website` VARCHAR(255) NULL,
    `phone` VARCHAR(50) NULL,
    `email` VARCHAR(191) NULL,
    `tax_number` VARCHAR(100) NULL,
    `status` ENUM('lead', 'prospect', 'active_client', 'archived') NOT NULL DEFAULT 'lead',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_companies_status` (`status`),
    INDEX `idx_companies_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Contacts
CREATE TABLE IF NOT EXISTS `contacts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT UNSIGNED NOT NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NULL,
    `email` VARCHAR(191) NULL,
    `phone` VARCHAR(50) NULL,
    `job_title` VARCHAR(100) NULL,
    `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
    `provenance` VARCHAR(100) NOT NULL DEFAULT 'direct_inquiry',
    `lawful_basis` VARCHAR(100) NOT NULL DEFAULT 'legitimate_interest',
    `opt_out` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_contacts_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
    INDEX `idx_contacts_email` (`email`),
    INDEX `idx_contacts_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Leads
CREATE TABLE IF NOT EXISTS `leads` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT UNSIGNED NULL,
    `contact_id` INT UNSIGNED NULL,
    `title` VARCHAR(255) NOT NULL,
    `source` VARCHAR(100) NOT NULL DEFAULT 'website',
    `status` ENUM('new', 'contacted', 'qualified', 'disqualified', 'converted') NOT NULL DEFAULT 'new',
    `assigned_to` INT UNSIGNED NULL,
    `estimated_value` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'EGP',
    `score` INT NOT NULL DEFAULT 10,
    `notes` TEXT NULL,
    `converted_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_leads_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_leads_contact` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_leads_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_leads_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Opportunities
CREATE TABLE IF NOT EXISTS `opportunities` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `lead_id` INT UNSIGNED NULL,
    `company_id` INT UNSIGNED NOT NULL,
    `contact_id` INT UNSIGNED NULL,
    `assigned_to` INT UNSIGNED NULL,
    `title` VARCHAR(255) NOT NULL,
    `stage` ENUM('new', 'qualified', 'contacted', 'meeting', 'proposal', 'negotiation', 'won', 'lost') NOT NULL DEFAULT 'new',
    `probability` INT NOT NULL DEFAULT 20,
    `estimated_value` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'EGP',
    `expected_close_date` DATE NULL,
    `loss_reason` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_opp_lead` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_opp_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_opp_contact` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_opp_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_opp_stage` (`stage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Sales Activities
CREATE TABLE IF NOT EXISTS `sales_activities` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `opportunity_id` INT UNSIGNED NULL,
    `lead_id` INT UNSIGNED NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `type` ENUM('call', 'meeting', 'email', 'note', 'stage_change') NOT NULL DEFAULT 'note',
    `subject` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `activity_date` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_act_opp` FOREIGN KEY (`opportunity_id`) REFERENCES `opportunities` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_act_lead` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_act_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Follow-ups
CREATE TABLE IF NOT EXISTS `follow_ups` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `opportunity_id` INT UNSIGNED NULL,
    `lead_id` INT UNSIGNED NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `scheduled_at` DATETIME NOT NULL,
    `completed_at` DATETIME NULL,
    `status` ENUM('pending', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_fu_opp` FOREIGN KEY (`opportunity_id`) REFERENCES `opportunities` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fu_lead` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fu_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    INDEX `idx_fu_scheduled` (`scheduled_at`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Quotations
CREATE TABLE IF NOT EXISTS `quotations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `quotation_number` VARCHAR(50) NOT NULL UNIQUE,
    `opportunity_id` INT UNSIGNED NULL,
    `company_id` INT UNSIGNED NOT NULL,
    `contact_id` INT UNSIGNED NULL,
    `created_by` INT UNSIGNED NOT NULL,
    `issue_date` DATE NOT NULL,
    `expiry_date` DATE NOT NULL,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'EGP',
    `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `discount_type` ENUM('fixed', 'percentage') NOT NULL DEFAULT 'fixed',
    `discount_value` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `discount_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `tax_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `tax_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `total_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('draft', 'sent', 'accepted', 'rejected', 'expired') NOT NULL DEFAULT 'draft',
    `notes` TEXT NULL,
    `terms` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_quote_opp` FOREIGN KEY (`opportunity_id`) REFERENCES `opportunities` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_quote_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_quote_contact` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_quote_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    INDEX `idx_quote_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Quotation Items
CREATE TABLE IF NOT EXISTS `quotation_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `quotation_id` INT UNSIGNED NOT NULL,
    `item_order` INT NOT NULL DEFAULT 1,
    `description` TEXT NOT NULL,
    `quantity` DECIMAL(10,2) NOT NULL DEFAULT 1.00,
    `unit_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `line_total` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_qi_quote` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Projects (Declared before Contracts for two-way linking)
CREATE TABLE IF NOT EXISTS `projects` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT UNSIGNED NOT NULL,
    `manager_id` INT UNSIGNED NOT NULL,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `status` ENUM('planning', 'active', 'on_hold', 'completed', 'cancelled') NOT NULL DEFAULT 'planning',
    `start_date` DATE NOT NULL,
    `target_end_date` DATE NOT NULL,
    `actual_end_date` DATE NULL,
    `budget_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'EGP',
    `estimated_hours` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_proj_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_proj_manager` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    INDEX `idx_proj_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Contracts
CREATE TABLE IF NOT EXISTS `contracts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `contract_number` VARCHAR(50) NOT NULL UNIQUE,
    `quotation_id` INT UNSIGNED NULL,
    `company_id` INT UNSIGNED NOT NULL,
    `project_id` INT UNSIGNED NULL,
    `title` VARCHAR(255) NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `total_value` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'EGP',
    `billing_frequency` ENUM('one_time', 'monthly', 'quarterly', 'annually') NOT NULL DEFAULT 'one_time',
    `payment_terms` VARCHAR(255) NULL,
    `scope_of_work` TEXT NULL,
    `status` ENUM('draft', 'active', 'completed', 'terminated') NOT NULL DEFAULT 'draft',
    `signed_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_contract_quote` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_contract_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_contract_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
    INDEX `idx_contract_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. Project Members
CREATE TABLE IF NOT EXISTS `project_members` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `role_in_project` VARCHAR(100) NOT NULL DEFAULT 'Developer',
    `custom_hourly_rate` DECIMAL(15,2) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_proj_user` (`project_id`, `user_id`),
    CONSTRAINT `fk_pm_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. Milestones
CREATE TABLE IF NOT EXISTS `milestones` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `due_date` DATE NOT NULL,
    `status` ENUM('pending', 'completed') NOT NULL DEFAULT 'pending',
    `completed_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ms_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. Tasks
CREATE TABLE IF NOT EXISTS `tasks` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT UNSIGNED NOT NULL,
    `milestone_id` INT UNSIGNED NULL,
    `assigned_to` INT UNSIGNED NULL,
    `created_by` INT UNSIGNED NOT NULL,
    `parent_id` INT UNSIGNED NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `priority` ENUM('low', 'medium', 'high', 'urgent') NOT NULL DEFAULT 'medium',
    `status` ENUM('backlog', 'todo', 'in_progress', 'in_review', 'blocked', 'done') NOT NULL DEFAULT 'todo',
    `estimated_hours` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `due_date` DATE NULL,
    `completed_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_task_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_task_milestone` FOREIGN KEY (`milestone_id`) REFERENCES `milestones` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_task_assignee` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_task_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_task_parent` FOREIGN KEY (`parent_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
    INDEX `idx_task_status` (`status`),
    INDEX `idx_task_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 21. Task Comments
CREATE TABLE IF NOT EXISTS `task_comments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `task_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `comment` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_tc_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 22. Time Entries
CREATE TABLE IF NOT EXISTS `time_entries` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `task_id` INT UNSIGNED NULL,
    `project_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `duration_minutes` INT UNSIGNED NOT NULL,
    `hourly_cost` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `total_cost` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `is_billable` TINYINT(1) NOT NULL DEFAULT 1,
    `log_date` DATE NOT NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_te_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_te_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_te_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    INDEX `idx_te_date` (`log_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 23. Change Requests
CREATE TABLE IF NOT EXISTS `change_requests` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT UNSIGNED NOT NULL,
    `requested_by` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `impact_hours` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `impact_cost` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'EGP',
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `approved_by` INT UNSIGNED NULL,
    `approved_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_cr_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cr_requester` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_cr_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 24. Cash Accounts
CREATE TABLE IF NOT EXISTS `cash_accounts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `account_type` ENUM('bank', 'petty_cash', 'electronic_wallet') NOT NULL DEFAULT 'bank',
    `currency` VARCHAR(3) NOT NULL DEFAULT 'EGP',
    `account_number` VARCHAR(100) NULL,
    `bank_name` VARCHAR(150) NULL,
    `opening_balance` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `current_balance` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_ca_currency` (`currency`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 25. Invoices
CREATE TABLE IF NOT EXISTS `invoices` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `invoice_number` VARCHAR(50) NOT NULL UNIQUE,
    `contract_id` INT UNSIGNED NULL,
    `project_id` INT UNSIGNED NULL,
    `company_id` INT UNSIGNED NOT NULL,
    `created_by` INT UNSIGNED NOT NULL,
    `issue_date` DATE NOT NULL,
    `due_date` DATE NOT NULL,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'EGP',
    `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `discount_type` ENUM('fixed', 'percentage') NOT NULL DEFAULT 'fixed',
    `discount_value` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `discount_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `tax_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `tax_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `total_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `paid_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('draft', 'unpaid', 'partially_paid', 'paid', 'overdue', 'void') NOT NULL DEFAULT 'draft',
    `notes` TEXT NULL,
    `terms` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_inv_contract` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_inv_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_inv_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_inv_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    INDEX `idx_inv_status` (`status`),
    INDEX `idx_inv_due_date` (`due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 26. Invoice Items
CREATE TABLE IF NOT EXISTS `invoice_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `invoice_id` INT UNSIGNED NOT NULL,
    `item_order` INT NOT NULL DEFAULT 1,
    `description` TEXT NOT NULL,
    `quantity` DECIMAL(10,2) NOT NULL DEFAULT 1.00,
    `unit_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `line_total` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ii_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 27. Payments
CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `payment_number` VARCHAR(50) NOT NULL UNIQUE,
    `company_id` INT UNSIGNED NOT NULL,
    `cash_account_id` INT UNSIGNED NOT NULL,
    `received_by` INT UNSIGNED NOT NULL,
    `payment_date` DATE NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'EGP',
    `payment_method` ENUM('bank_transfer', 'cash', 'check', 'online') NOT NULL DEFAULT 'bank_transfer',
    `reference_number` VARCHAR(100) NULL,
    `idempotency_key` VARCHAR(100) NULL UNIQUE,
    `notes` TEXT NULL,
    `status` ENUM('completed', 'reversed') NOT NULL DEFAULT 'completed',
    `reversed_at` DATETIME NULL,
    `reversed_by` INT UNSIGNED NULL,
    `reversal_reason` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_pay_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_pay_account` FOREIGN KEY (`cash_account_id`) REFERENCES `cash_accounts` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_pay_receiver` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_pay_reverser` FOREIGN KEY (`reversed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_pay_status` (`status`),
    INDEX `idx_pay_date` (`payment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 28. Payment Allocations
CREATE TABLE IF NOT EXISTS `payment_allocations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `payment_id` INT UNSIGNED NOT NULL,
    `invoice_id` INT UNSIGNED NOT NULL,
    `allocated_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_pa_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pa_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE RESTRICT,
    INDEX `idx_pa_invoice` (`invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 29. Expense Categories
CREATE TABLE IF NOT EXISTS `expense_categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(50) NOT NULL UNIQUE,
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 30. Vendors
CREATE TABLE IF NOT EXISTS `vendors` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(50) NULL,
    `email` VARCHAR(191) NULL,
    `contact_person` VARCHAR(100) NULL,
    `tax_number` VARCHAR(100) NULL,
    `address` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 31. Expenses
CREATE TABLE IF NOT EXISTS `expenses` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `expense_number` VARCHAR(50) NOT NULL UNIQUE,
    `category_id` INT UNSIGNED NOT NULL,
    `vendor_id` INT UNSIGNED NULL,
    `cash_account_id` INT UNSIGNED NOT NULL,
    `project_id` INT UNSIGNED NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `expense_date` DATE NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'EGP',
    `description` TEXT NOT NULL,
    `receipt_path` VARCHAR(255) NULL,
    `status` ENUM('approved', 'paid', 'rejected') NOT NULL DEFAULT 'paid',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_exp_cat` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_exp_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_exp_account` FOREIGN KEY (`cash_account_id`) REFERENCES `cash_accounts` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_exp_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_exp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    INDEX `idx_exp_date` (`expense_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 32. Cash Transactions
CREATE TABLE IF NOT EXISTS `cash_transactions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `cash_account_id` INT UNSIGNED NOT NULL,
    `type` ENUM('deposit', 'withdrawal', 'transfer_in', 'transfer_out') NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `balance_after` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'EGP',
    `reference_type` ENUM('payment', 'expense', 'transfer', 'opening', 'reversal') NOT NULL,
    `reference_id` INT UNSIGNED NULL,
    `transfer_link_id` VARCHAR(100) NULL,
    `description` VARCHAR(255) NOT NULL,
    `transaction_date` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ct_account` FOREIGN KEY (`cash_account_id`) REFERENCES `cash_accounts` (`id`) ON DELETE RESTRICT,
    INDEX `idx_ct_date` (`transaction_date`),
    INDEX `idx_ct_transfer_link` (`transfer_link_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 33. Recurring Contracts
CREATE TABLE IF NOT EXISTS `recurring_contracts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `contract_id` INT UNSIGNED NULL,
    `company_id` INT UNSIGNED NOT NULL,
    `service_type` ENUM('it_support', 'maintenance', 'hosting', 'server_management', 'dev_retainer') NOT NULL DEFAULT 'maintenance',
    `title` VARCHAR(255) NOT NULL,
    `monthly_fee` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'EGP',
    `billing_frequency` ENUM('monthly', 'quarterly', 'annually') NOT NULL DEFAULT 'monthly',
    `start_date` DATE NOT NULL,
    `end_date` DATE NULL,
    `next_billing_date` DATE NOT NULL,
    `auto_renew` TINYINT(1) NOT NULL DEFAULT 1,
    `service_limits` TEXT NULL,
    `status` ENUM('active', 'paused', 'cancelled') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_rc_contract` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_rc_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
    INDEX `idx_rc_next_billing` (`next_billing_date`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 34. Documents Vault
CREATE TABLE IF NOT EXISTS `documents` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `entity_type` ENUM('company', 'project', 'contract', 'invoice', 'quotation', 'general') NOT NULL DEFAULT 'general',
    `entity_id` INT UNSIGNED NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `stored_name` VARCHAR(255) NOT NULL UNIQUE,
    `file_size` BIGINT UNSIGNED NOT NULL,
    `mime_type` VARCHAR(100) NOT NULL,
    `category` VARCHAR(50) NOT NULL DEFAULT 'general',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_doc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    INDEX `idx_doc_entity` (`entity_type`, `entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 35. Audit Logs
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NULL,
    `action` VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(100) NOT NULL,
    `entity_id` INT UNSIGNED NULL,
    `old_values` JSON NULL,
    `new_values` JSON NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_audit_action` (`action`),
    INDEX `idx_audit_entity` (`entity_type`, `entity_id`),
    INDEX `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 36. Notifications
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `link` VARCHAR(255) NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `read_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    INDEX `idx_notif_user_read` (`user_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 37. System Settings
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL UNIQUE,
    `value` TEXT NULL,
    `group` VARCHAR(50) NOT NULL DEFAULT 'general',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_settings_group` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
