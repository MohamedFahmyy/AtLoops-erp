# AT Loops OS — Application Security & Hardening Architecture

## 1. Security Architecture Principles

AT Loops OS adopts a **Defense-in-Depth** security model designed to safeguard sensitive commercial data, client credentials, and financial balances:

1. **Least Privilege Principle:** Every operator is granted only the permissions required for their specific role.
2. **Zero Trust in Client-Side Input:** All form values, headers, and query strings are validated server-side. Hidden buttons or disabled form inputs are never relied upon for authorization.
3. **No Direct Web Access to Application Assets:** Backups, private contracts, customer files, application source code, and log files are stored outside the public document root (`public/`).

---

## 2. Threat Mitigation Matrix

| Threat Category | Mitigation Architecture | Implementation in AT Loops OS |
|---|---|---|
| **SQL Injection (SQLi)** | 100% Prepared Statements via PDO | `App\Core\Database` binds all parameters explicitly; emulation mode disabled (`PDO::ATTR_EMULATE_PREPARES => false`). |
| **Cross-Site Request Forgery (CSRF)** | Synchronizer Token Pattern | Cryptographic random tokens (`bin2hex(random_bytes(32))`) validated on all `POST`, `PUT`, `DELETE` via `CSRFMiddleware`. |
| **Cross-Site Scripting (XSS)** | Context-Aware Output Escaping | All view outputs escaped using localized `htmlspecialchars($val, ENT_QUOTES, 'UTF-8')` via `e()` helper. |
| **Brute Force & Credential Stuffing** | IP & Account Rate Limiting | `RateLimiter` restricts failed login attempts to 5 per 5 minutes per IP/email pair with lockouts. |
| **Broken Access Control (IDOR)** | Server-Side RBAC & Policies | Routes protected by `RoleMiddleware` and `PermissionMiddleware`. Individual entity edits verify ownership/permissions via Policies. |
| **Malicious File Uploads** | Extension & MIME Inspection | File uploads restricted to allowlist (`pdf`, `png`, `jpg`, `docx`, `xlsx`), inspected with `finfo`, renamed to UUID/hash, stored outside public web root. |
| **CSV Formula Injection** | Sanitization of Export Rows | Prepends single quotes to cell values beginning with `=, +, -, @` during spreadsheet exports. |
| **Sensitive Data Exposure** | Production Error Concealment | When `APP_DEBUG=false`, uncaught exceptions log securely to disk and display a sanitized 500 error view without stack traces or credentials. |
| **Session Hijacking** | Secure Cookie Attributes | Sessions configured with `HttpOnly`, `SameSite=Lax`, strict lifetime, and automatic session ID regeneration on login and privilege changes. |

---

## 3. Role-Based Access Control (RBAC) Permissions Catalog

Permissions are structured in dot-notation (`module.action`):

- **CRM:** `crm.view`, `crm.create`, `crm.edit`, `crm.delete`, `crm.export`
- **Projects:** `projects.view`, `projects.create`, `projects.edit`, `projects.delete`, `projects.manage_tasks`, `projects.log_time`
- **Finance:** `finance.view`, `finance.invoices`, `finance.payments`, `finance.transfers`, `finance.expenses`, `finance.manage`
- **Reports:** `reports.view`, `reports.financial`, `reports.operational`
- **Settings & Users:** `users.manage`, `settings.manage`, `audit.view`, `backup.manage`

Every controller action checks permissions using `$this->requirePermission('permission.key')`. Direct URL manipulation by unauthorized users triggers an immediate HTTP 403 Forbidden response.

---

## 4. Secure File Vault Storage

Uploaded sensitive files (such as signed PDF contracts, invoice receipts, and project deliverables) are stored in:
`D:\laragon\www\atloops-os\storage\app\documents\`

### Security Rules:
1. Files are **never saved using their original user-supplied name**. A unique hash (`bin2hex(random_bytes(16)) . '.' . $ext`) is generated upon ingestion.
2. The `documents` table records `original_name`, `stored_name`, `mime_type`, and `file_size`.
3. Files cannot be accessed directly via static URL (e.g. `http://domain.com/storage/file.pdf` is impossible because the document root is `public/`).
4. Downloads are served exclusively through the authorized controller `DocumentController::download($id)` which verifies user authentication and role permissions before streaming bytes with `readfile()`.
