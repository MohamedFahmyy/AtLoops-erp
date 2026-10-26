# AT Loops OS — Go-Live Test Evidence Log
# سجل أدلة اختبارات الجاهزية للإطلاق الفعلي وحسم التناقضات

**تاريخ وساعة التوليد:** 26 أكتوبر 2026 — 06:22:45 EEST  
**البيئة المستخدمة:** بيئة اختبار محلية معزولة (PHP 8.4.4 / MySQL 8.4.3 InnoDB UTF8MB4 / Built-in Server `127.0.0.1:8090`)  
**قاعدة بيانات الاختبار:** `atloops_os_test` (معزولة بالكامل عن قاعدة الإنتاج `atloops_os`)  
**النسخة الاحتياطية المسبقة لقاعدة الإنتاج:** `storage/backups/backup_atloops_os_20261026_051850.sql` (180 صفاً عبر 37 جدولاً)  
**تسجيل فيديو المتصفح الحي:** `browser_smoke_test_1792984034933.webp`  
**أداة التشغيل المركزية:** `php tests/run_tests.php`  
**النتيجة الإجمالية:** **جميع أجنحة الاختبار الستة واختبار المتصفح الحي نجحت بنسبة 100% (ALL PASSED)**

---

## 1. حسم تعارض الحساب المالي للفواتير (Invoice Calculation Reconciliation)

### 1.1 التناقض المكتشف والتحليل الجذري
- **الملاحظة:** في التجارب السابقة بملف `scripts/pre_production_verify.php`، تم إدخال الفاتورة يدوياً باحتساب الضريبة على الإجمالي الفرعي دون خصم الخصم التجاري أولاً:
  - الإجمالي الفرعي: 7,000 USD
  - الضريبة (خطأً على 7,000): 980 USD
  - الخصم: 200 USD
  - الإجمالي الناتج سابقاً: 7,780 USD
- **القاعدة المحاسبية والضريبية المعتمدة في النظام:**
  وفقاً لـ [`app/Services/FinancialCalculationService.php`](file:///d:/laragon/www/atloops-os/app/Services/FinancialCalculationService.php) وقوانين ضريبة القيمة المضافة المعمول بها (مثل قانون الضريبة على القيمة المضافة المصري رقم 67 لسنة 2016، ولائحة هيئة الزكاة والضريبة والجمارك ZATCA بالسعودية، وضريبة القيمة المضافة بالإمارات FTA):
  **الخصم التجاري الممنوح عند إصدار الفاتورة يقلص الوعاء الضريبي قبل احتساب الضريبة.**
  $$\text{الوعاء الضريبي (Taxable Base)} = \text{الإجمالي الفرعي} - \text{الخصم} = 7000 - 200 = 6800.00\text{ USD}$$
  $$\text{مبلغ الضريبة (14\%)} = 6800.00 \times 0.14 = 952.00\text{ USD}$$
  $$\text{إجمالي الفاتورة النهائي} = 6800.00 + 952.00 = 7752.00\text{ USD}$$

### 1.2 المعالجة البرمجية وإضافة اختبار الحماية من الانتكاس (Anti-Regression Test)
1. تم تحديث [`tests/Financial/FinancialCalculationTest.php`](file:///d:/laragon/www/atloops-os/tests/Financial/FinancialCalculationTest.php) بدالة `testTaxBaseDiscountReconciliationRegression` التي تفحص بدقة حالة:
   `Subtotal = 7000, Discount = 200, Tax = 14% -> Total = 7752 USD`
   وتؤكد فشل الاختبار إذا نتج 7,780 USD أو كانت الضريبة 980 USD.
2. تم تحديث [`scripts/pre_production_verify.php`](file:///d:/laragon/www/atloops-os/scripts/pre_production_verify.php) ليعتمد على `FinancialCalculationService::calculateDocument()` بدلاً من الجمع اليدوي، مع تعديل دفعة التسوية إلى **4,752.00 USD** لتطابق الرصيد المتبقي بدقة سنت.
3. مخرجات الاختبار الفعلي:
   ```text
   >> Running Financial Acceptance Tests (Math & Calculations):
     [Test] Multi-line invoice with discount, tax, and rounding... PASSED
     [Test] Reconciled Tax Base & Discount Order (7000 - 200 + 14% = 7752 USD)... PASSED
     [Test] Fixed discount capping and boundary conditions... PASSED
     [Test] Outstanding balance and status transitions... PASSED
      [SUITE PASSED]
   ```

---

## 2. التحقق من جدولة Cron وفصل المهام (Cron Task Modularity & Scheduling)

### 2.1 مسار الملف والتحقق من الهيكلية
- **مسار الملف الفعلي:** [`D:\laragon\www\atloops-os\scripts\cron.php`](file:///d:/laragon/www/atloops-os/scripts/cron.php).
- **التطوير المضاف:** تم تزويد السكربت بخيار `--task=` لاختيار المهام المنفردة أو تشغيل الكل:
  - `--task=recurring`: معالجة فواتير العقود الدورية فقط.
  - `--task=overdue_invoices`: فحص وتحديث الفواتير المتأخرة فقط.
  - `--task=overdue_tasks`: فحص وتنبيه مهام المشاريع المتأخرة فقط.
  - `--task=all`: تشغيل كافة المهام بالترتيب (الوضع الافتراضي).
- **نظام الأقفال الذرية المنفصلة (Scoped Mutex Locks):**
  تم تخصيص ملف قفل مستقل لكل مهمة (`storage/cache/cron_{$task}.lock`) باستخدام `flock($handle, LOCK_EX | LOCK_NB)`.

### 2.2 الأدلة التجريبية لتشغيل المهام المنفصلة وحجب التزامن
1. **اختبار مهمة العقود الدورية المنفردة:**
   `php scripts/cron.php --database=testing --task=recurring`
   - **النتيجة:** نجاح المعالجة دون تشغيل مهام الفواتير المتأخرة أو المهام، وتوليد سجل في `storage/logs/cron.log`.
2. **اختبار مهمة الفواتير المتأخرة المنفردة:**
   `php scripts/cron.php --database=testing --task=overdue_invoices`
   - **النتيجة:** فحص الفواتير وتحديث الحالات بنجاح.
3. **اختبار مهمة مهام المشاريع المنفردة:**
   `php scripts/cron.php --database=testing --task=overdue_tasks`
   - **النتيجة:** حصر المهام المتأخرة بنجاح.
4. **اختبار اعتراض التزامن:**
   تشغيل مهمتين متزامنتين على نفس القفل -> العملية الثانية خرجت فوراً برسالة:
   `[CRON SKIPPED] Another instance for task 'recurring' is currently active. Concurrency lock held.`
5. **جدولة الإنتاج المعتمدة (Recommended Crontab Configuration):**
   ```bash
   # 1. توليد فواتير العقود الدورية (مرة يومياً عند منتصف الليل)
   0 0 * * * php /var/www/atloops-os/scripts/cron.php --task=recurring >> /var/log/cron_recurring.log 2>&1

   # 2. فحص الفواتير المتأخرة وتحديث حالتها (مرة يومياً الساعة 08:00 صباحاً)
   0 8 * * * php /var/www/atloops-os/scripts/cron.php --task=overdue_invoices >> /var/log/cron_invoices.log 2>&1

   # 3. فحص تنبيهات مهام المشاريع المتأخرة (مرتان يومياً: 09:00 و 14:00)
   0 9,14 * * * php /var/www/atloops-os/scripts/cron.php --task=overdue_tasks >> /var/log/cron_tasks.log 2>&1
   ```

---

## 3. أدلة اختبار المتصفح الحي (Live Browser Smoke Test Evidence)

تم تشغيل خادم التطبيق المحلي على المنفذ `127.0.0.1:8090`، واستدعاء الوكيل الفرعي للمتصفح (`browser_subagent`) لتنفيذ جولة اختبار كاملة داخل متصفح كروم حقيقي، مع تسجيل كامل الخطوات بالفيديو `browser_smoke_test_1792984034933.webp`.

### نتائج بنود اختبار المتصفح الفعلية:

| البند المختبر | الحالة الفعلية | الدليل والملاحظة من جلسة المتصفح |
| :--- | :---: | :--- |
| **1. تحميل صفحة تسجيل الدخول** | **PASS** | فتح الرابط `http://127.0.0.1:8090/login`، تحقق من استجابة HTTP 200 وتوجيه RTL باللغة العربية مع خط Cairo وظهور علامة "AT Loops OS". |
| **2. تسجيل الدخول والمصادقة** | **PASS** | إدخال بيانات المسؤول (`admin@atloops.com` / `Password123!`)، إرسال النموذج بنجاح مع توكن CSRF، وإصدار كوكي الجلسة `atloops_session`. |
| **3. الانتقال للوحة القيادة** | **PASS** | إعادة التوجيه الفوري إلى `/dashboard`، والتحقق من بطاقات المؤشرات المالية الرئيسية (أرصدة الخزائن، التدفق النقدي، الرسوم البيانية) والقائمة الجانبية. |
| **4. التبديل إلى الإنجليزية (LTR)** | **PASS** | استدعاء `/lang?lang=en`، تحول اتجاه الصفحة فوراً إلى LTR وتبدل العناوين إلى "Executive Dashboard" بنجاح. |
| **5. العودة إلى العربية (RTL)** | **PASS** | استدعاء `/lang?lang=ar`، عودة اتجاه الصفحة إلى RTL مع خط Cairo. |
| **6. تصفح عملاء الـ CRM** | **PASS** | فتح `/crm/companies`، استعراض جدول الشركات والعملاء وتصنيفاتهم وحالاتهم. |
| **7. تصفح الفواتير المالية** | **PASS** | فتح `/finance/invoices`، استعراض قائمة الفواتير ومبالغها الإجمالية والمسددة والمتبقية وحالاتها (`paid`, `partially_paid`, `unpaid`). |
| **8. التوافق مع الشاشات الصغيرة** | **PASS** | تغيير دقة المتصفح إلى أبعاد الهاتف المحمول (`390x844`)، التكيف التام مع العرض دون أي انكسار أو فيض أفقي (Horizontal Overflow). |
| **9. تسجيل الخروج وإنهاء الجلسة** | **PASS** | فتح قائمة الملف الشخصي والضغط على "تسجيل الخروج"، تدمير الجلسة بالكامل وإعادة التوجيه إلى صفحة تسجيل الدخول `/login`. |

---

## 4. أدلة إدارة الأسرار ونظافة Git (Secrets & Git Hygiene Evidence)

1. **فحص ملفات التكوين:**
   - فحص `c:\Users\mfahm\.gemini\config\mcp_config.json`، والتأكد من عدم وجود أي تضمين له في شفرة التطبيق.
   - التأكد من خلو ملف `.env.example` تماماً من أية أسرار أو كلمات مرور أو توكنات.
2. **ملف استثناءات Git المعتمد:**
   - تم إنشاء [`.gitignore`](file:///d:/laragon/www/atloops-os/.gitignore) بجذر المشروع لحظر ملفات `.env`، وملفات النسخ الاحتياطية `storage/backups/*.sql`، وملفات الخزينة الخاصة `storage/private/*`، وسجلات النظام.
3. **خطوات تدوير المفاتيح الموصى بها (بدون نسخ أي سر):**
   - إلغاء مفتاح المستهلك القديم في منصة Composio وتوليد مفتاح جديد خاص بالمطور.
   - إلغاء توكن الوصول القديم في بوابة Context7 وتوليد توكن جديد.
   - حظر مشاركة أي مفتاح في سجلات Git العامة أو الخاصة.

---

## 5. مصفوفة التحقق الإجمالية لأجنحة الاختبار الآلية (`php tests/run_tests.php`)

```text
========================================================
   AT LOOPS OS — AUTOMATED ACCEPTANCE TEST SUITE
   Testing against isolated database: atloops_os_test
========================================================

>> Running Financial Acceptance Tests (Math & Calculations):
  [Test] Multi-line invoice with discount, tax, and rounding... PASSED
  [Test] Reconciled Tax Base & Discount Order (7000 - 200 + 14% = 7752 USD)... PASSED
  [Test] Fixed discount capping and boundary conditions... PASSED
  [Test] Outstanding balance and status transitions... PASSED
   [SUITE PASSED]

>> Running Financial Acceptance Tests (Payment Allocations & Reversals):
  [Test] Two partial payments, outstanding balance & idempotency... PASSED
  [Test] Rejection of payments exceeding outstanding balance... PASSED
  [Test] Payment reversal restores balances and preserves audit... PASSED
   [SUITE PASSED]

>> Running Financial Acceptance Tests (Cash Accounts & Atomic Transfers):
  [Test] Atomic inter-account transfer and balance reconciliation... PASSED
  [Test] Insufficient funds transfer rejection... PASSED
  [Test] Currency mismatch transfer rejection... PASSED
  [Test] Transaction failure injection & complete rollback verification... PASSED
   [SUITE PASSED]

>> Running Security Acceptance Tests (CSRF, RBAC, Uploads, Secrets):
  [Security 1/7] CSRF token enforcement on state-changing requests... PASSED
  [Security 2/7] Server-side RBAC & permission gate enforcement... PASSED
  [Security 3/7] Horizontal and vertical privilege escalation rejection... PASSED
  [Security 4/7] Dual-key rate limiting (Anti-Spraying & Anti-Distributed Brute Force)... PASSED
  [Security 5/7] Tax jurisdiction resolution (Egypt 14%, KSA 15%, UAE 5%, Export 0%)... PASSED
  [Security 6/7] Rejection of executable uploads and prohibited extensions... PASSED
  [Security 7/7] Production error concealment (no traces/passwords in production)... PASSED
   [SUITE PASSED]

>> Running Operational Acceptance Tests (Idempotency & Conversions):
  [Operational 1/3] Conversion pipeline idempotency (no duplicate conversions)... PASSED
  [Operational 2/3] Recurring invoice draft generation idempotency... PASSED
  [Operational 3/3] Arabic (RTL, Cairo) and English (LTR, Inter) dictionary integrity... PASSED
   [SUITE PASSED]

>> Running Final Go-Live Readiness Gate (All 8 Dimensions):
  [GoLive 1/8] Verifying secret management, MCP isolation & .gitignore... PASSED
  [GoLive 2/8] Verifying financial calculation order of operations & rounding... PASSED
  [GoLive 3/8] Verifying multi-currency support & tax jurisdiction separation... PASSED
  [GoLive 4/8] Verifying payment allocation, idempotency replay, overpayment & reversal... PASSED
  [GoLive 5/8] Verifying backup generation, production restore block & schema integrity... PASSED
  [GoLive 6/8] Verifying cron concurrency mutex lock & execution idempotency... PASSED
  [GoLive 7/8] Verifying RBAC permission matrix & resource policies... PASSED
  [GoLive 8/8] Verifying bilingual dictionaries (Arabic RTL & English LTR)... PASSED
   [SUITE PASSED]

========================================================
TEST RESULTS SUMMARY:
Total Suites:  6
Suites Passed: 6
Suites Failed: 0
========================================================
ALL MANDATORY ACCEPTANCE TESTS PASSED SUCCESSFULLY!
```
