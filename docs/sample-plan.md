# Sample plan — Phase 7: Payments and Financial Tracking

This is one phase of Empire Trade School's build, published here as a representative sample of how phases were planned. For the method behind it, see [`ai-workflow.md`](ai-workflow.md).

**Place in the build:** Phases 1–6 had already shipped (379 passing tests covering roles and permissions, programs and courses, terms/sections/rooms/scheduling, enrollment workflow, attendance, and assessments/grading). Phase 7 added the financial layer.

**Outcome after Phase 7:** ~424 tests passing. Every invoice's status is computed (not persisted) from its payments. Refunds are modeled as negative payments in a single ledger. Hard separation of academic and financial roles — instructors are locked out of billing entirely.

**Status:** shipped.

---

## Context

Phases 1–6 are complete with 379 passing tests. The app has roles/permissions, programs/courses, terms/sections/rooms/scheduling, enrollment workflow, attendance tracking, and assessments/grading. Phase 7 adds the **financial layer**: tuition pricing per course, invoices auto-generated from enrollments, payments recorded against invoices, and balance tracking for students.

**Scope:** admin/registrar manages billing; students view and track their own balance and payment history. No external payment gateway — payments are recorded manually (cash, check, card-offline, ACH, refund).

## Design Decisions (locked)

1. **Tuition source**: `tuition_amount` column on `courses` table, `decimal(10, 2)`. No per-section override in this phase.
2. **Invoice granularity**: one `Invoice` per `Enrollment`. Auto-created when an enrollment is persisted with `status = Enrolled`.
3. **Payments**: many `Payment` records per invoice. Each has amount, method, reference, received_at, notes.
4. **Status derivation**: invoice status (`Unpaid` / `Partial` / `Paid` / `Overpaid` / `Voided`) is **computed** from `sum(payments.amount)` vs `amount_due`. Not persisted. `Voided` comes from a `voided_at` column (soft-void, audit-preserving).
5. **Money representation**: `decimal(10, 2)` columns. Eloquent `decimal:2` cast returns strings to avoid float rounding. Display formatted via model accessor.
6. **Refunds**: modeled as payments with a **negative amount** and `method = PaymentMethod::Refund`. Single ledger, simple math.
7. **Due date**: `due_at = enrollment.section.term.start_date`. No rolling offset.
8. **Authorization**:
   - `SuperAdmin`, `Admin`, `Registrar` — full billing access (view all, record, void, delete payments)
   - `Instructor` — **no billing access at all**
   - `Student` — view own invoices only, read-only

## Step 1: PaymentMethod Enum

`app/Enums/PaymentMethod.php`:

```php
enum PaymentMethod: string
{
    case Cash = 'cash';
    case Check = 'check';
    case CreditCard = 'credit-card';
    case ACH = 'ach';
    case Refund = 'refund';
    case Other = 'other';

    public function label(): string { /* readable labels */ }
}
```

Follow existing enum conventions (see `AssessmentType.php`, `AttendanceStatus.php`).

## Step 2: InvoiceStatus Enum

`app/Enums/InvoiceStatus.php`:

```php
enum InvoiceStatus: string
{
    case Unpaid = 'unpaid';
    case Partial = 'partial';
    case Paid = 'paid';
    case Overpaid = 'overpaid';
    case Voided = 'voided';

    public function label(): string { /* ... */ }
    public function color(): string { /* zinc/yellow/green/blue/red for Flux badges */ }
}
```

Not persisted — used as the return type of `Invoice::status()`.

## Step 3: Migrations

### 3a. `add_tuition_amount_to_courses_table`

```php
Schema::table('courses', function (Blueprint $table) {
    $table->decimal('tuition_amount', 10, 2)->default(0)->after('lab_hours');
});
```

### 3b. `create_invoices_table`

```php
Schema::create('invoices', function (Blueprint $table) {
    $table->id();
    $table->foreignId('enrollment_id')->unique()->constrained()->cascadeOnDelete();
    $table->string('invoice_number')->unique();
    $table->decimal('amount_due', 10, 2);
    $table->dateTime('issued_at');
    $table->date('due_at')->nullable();
    $table->dateTime('voided_at')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
});
```

### 3c. `create_payments_table`

```php
Schema::create('payments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
    $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
    $table->decimal('amount', 10, 2); // negative allowed for refunds
    $table->string('method');
    $table->string('reference')->nullable();
    $table->dateTime('received_at');
    $table->text('notes')->nullable();
    $table->timestamps();

    $table->index(['invoice_id', 'received_at']);
});
```

## Step 4: Models

### 4a. `Invoice` (`app/Models/Invoice.php`)

```php
#[Fillable(['enrollment_id', 'invoice_number', 'amount_due', 'issued_at', 'due_at', 'voided_at', 'notes'])]
class Invoice extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'amount_due' => 'decimal:2',
            'issued_at' => 'datetime',
            'due_at' => 'date',
            'voided_at' => 'datetime',
        ];
    }

    public function enrollment(): BelongsTo { /* ... */ }
    public function payments(): HasMany { /* ... */ }

    public function totalPaid(): string { /* sum(payments.amount), formatted 2dp */ }
    public function balance(): string { /* amount_due - totalPaid */ }
    public function status(): InvoiceStatus { /* derived per rules below */ }
    public function isVoided(): bool { return $this->voided_at !== null; }

    public function scopeOutstanding(Builder $query): void { /* status != Paid/Voided */ }
}
```

**Status derivation rules:**
- `voided_at !== null` → `Voided`
- `totalPaid == 0` → `Unpaid`
- `totalPaid == amount_due` → `Paid`
- `0 < totalPaid < amount_due` → `Partial`
- `totalPaid > amount_due` → `Overpaid`

### 4b. `Payment` (`app/Models/Payment.php`)

```php
#[Fillable(['invoice_id', 'recorded_by', 'amount', 'method', 'reference', 'received_at', 'notes'])]
class Payment extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'method' => PaymentMethod::class,
            'received_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo { /* ... */ }
    public function recorder(): BelongsTo { /* belongsTo(User::class, 'recorded_by') */ }
}
```

### 4c. Model updates

- **`Course`** — add `tuition_amount` to fillable attribute, add `'tuition_amount' => 'decimal:2'` to casts
- **`Enrollment`** — add `invoice(): HasOne` relationship
- **`User`** — add `recordedPayments(): HasMany` (payments recorded by this user); student invoices are reachable via `user.enrollments.invoice`

## Step 5: GenerateInvoiceNumber Action

`app/Actions/Billing/GenerateInvoiceNumber.php`:

```php
class GenerateInvoiceNumber
{
    public function handle(): string
    {
        $year = now()->year;
        $prefix = "INV-{$year}-";
        $last = Invoice::where('invoice_number', 'like', "{$prefix}%")
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $nextSeq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $nextSeq, 6, '0', STR_PAD_LEFT);
    }
}
```

Format: `INV-2026-000001`, `INV-2026-000002`, etc. Used by factory and auto-creation.

## Step 6: CreateInvoiceForEnrollment Action

`app/Actions/Billing/CreateInvoiceForEnrollment.php`:

```php
class CreateInvoiceForEnrollment
{
    public function __construct(private GenerateInvoiceNumber $numberGenerator) {}

    public function handle(Enrollment $enrollment): ?Invoice
    {
        if ($enrollment->status !== EnrollmentStatus::Enrolled) {
            return null;
        }

        if ($enrollment->invoice()->exists()) {
            return $enrollment->invoice;
        }

        $section = $enrollment->section()->with('course', 'term')->first();

        return Invoice::create([
            'enrollment_id' => $enrollment->id,
            'invoice_number' => $this->numberGenerator->handle(),
            'amount_due' => $section->course->tuition_amount,
            'issued_at' => now(),
            'due_at' => $section->term->start_date,
        ]);
    }
}
```

**Hook point:** call this action wherever enrollments are persisted with `status = Enrolled`. Locate existing enrollment-creation code (likely `app/Livewire/Actions/` or student registration Volt components) and inject the action there. Invoke after the enrollment is saved.

**Waitlist → Enrolled promotion:** if the app has a promotion path (waitlist to enrolled), that path must also call this action.

**Drop handling:** when an enrollment is dropped, if `invoice.payments()->count() === 0`, set `voided_at = now()`. If payments exist, leave the invoice alone — registrar processes refunds manually via the admin UI.

## Step 7: Factories

### 7a. `InvoiceFactory`

```php
public function definition(): array
{
    return [
        'enrollment_id' => Enrollment::factory(),
        'invoice_number' => fn () => app(GenerateInvoiceNumber::class)->handle(),
        'amount_due' => fake()->randomElement([450, 500, 600, 750, 900]),
        'issued_at' => now(),
        'due_at' => now()->addDays(30),
    ];
}

public function voided(): static { /* sets voided_at */ }
```

### 7b. `PaymentFactory`

```php
public function definition(): array
{
    return [
        'invoice_id' => Invoice::factory(),
        'recorded_by' => User::factory(),
        'amount' => 100.00,
        'method' => PaymentMethod::Cash,
        'received_at' => now(),
    ];
}

public function refund(): static { /* method = Refund, amount negated */ }
public function fullPayment(): static { /* amount = invoice.amount_due */ }
```

### 7c. `CourseFactory` update

Set `tuition_amount => fake()->randomElement([450, 500, 600, 750, 900])` as default.

## Step 8: Permissions

Add to the permission seeder (check existing `RolePermissionSeeder` or equivalent for the current pattern):

- `invoices.view-any` — Admin, Registrar, SuperAdmin
- `invoices.view-own` — Student (filtered to self at query level)
- `invoices.manage` — Admin, Registrar, SuperAdmin (create, update notes, void)
- `payments.create` — Admin, Registrar, SuperAdmin
- `payments.delete` — Admin, SuperAdmin (Registrar can *create* but not delete — audit safety)

**Instructor gets no billing permissions at all.**

## Step 9: Routes

Add to `routes/web.php` inside the existing `auth + verified` middleware group, following the admin/student route conventions already established (check how `admin/grades` and `student/grades` routes are set up):

```
# Admin
admin/invoices                          → admin.invoices.index
admin/invoices/{invoice}                → admin.invoices.show

# Student
student/invoices                        → student.invoices.index
student/invoices/{invoice}              → student.invoices.show
```

Role middleware matches the existing pattern in sibling route groups.

## Step 10: Sidebar Navigation

Update `resources/views/partials/*sidebar*.blade.php` (check current structure):

- **Admin/Registrar section**: add "Billing" nav item → `admin/invoices`, with banknote or credit-card icon from Heroicons
- **Student section**: add "My Billing" nav item → `student/invoices`

Use existing `<flux:navlist.item>` pattern.

## Step 11: Admin — Invoice Index Page

`resources/views/pages/admin/invoices/index.blade.php` (Volt single-file, matching sibling pages):

**Component state:**
- `#[Url] $search = ''` — searches invoice number + student name
- `#[Url] $statusFilter = null` — Unpaid/Partial/Paid/Overpaid/Voided
- `#[Url] $termFilter = null`
- `#[Url] $sortField = 'issued_at'`
- `#[Url] $sortDirection = 'desc'`

**UI:**
- Top stats strip: **Total Outstanding** (sum of balances for non-voided unpaid/partial), **Total Collected** (sum of all positive payments), **Total Refunded** (sum of absolute negative payments) — scoped to current filter set
- Filter row: search input, status select, term select
- Table (Flux table): Invoice #, Student, Section (code), Amount Due, Paid, Balance, Status badge, Issued, Due, Actions (View)
- Pagination
- Empty state

**Query strategy:** use a subquery-aware builder. Status filter translates to:
- `Unpaid` → invoices where no payments exist and not voided
- `Paid` → invoices where `sum(payments.amount) == amount_due`
- `Partial` → `0 < sum < amount_due`
- `Overpaid` → `sum > amount_due`
- `Voided` → `voided_at IS NOT NULL`

Implement with `withSum('payments', 'amount')` + `having` clauses.

## Step 12: Admin — Invoice Show Page

`resources/views/pages/admin/invoices/show.blade.php`:

**Sections:**
1. **Header** — Invoice #, status badge, void button (if permitted and no payments)
2. **Student info** — name, email, section code/name, term
3. **Amounts** — amount due, total paid, balance (large), status
4. **Payment history table** — date, method, reference, amount, recorded by, notes, delete action
5. **Record Payment form (Flux modal)** — amount, method dropdown, reference, received_at, notes
   - Validation:
     - `amount` required, numeric, not zero
     - `method` required, in PaymentMethod cases
     - If `method === Refund` → `amount` must be **negative**
     - If `method !== Refund` → `amount` must be **positive**
     - `received_at` required, `<= now()`
   - On save: `recorded_by = auth()->id()`, dispatch success toast, refresh component

**Void action:**
- Only shown if `!$invoice->isVoided()` and `$invoice->payments()->count() === 0`
- Confirmation dialog (Flux modal)
- Sets `voided_at = now()`
- If payments exist, void button is hidden and a hint is shown: "Delete all payments before voiding, or refund them instead"

**Authorization in component mount:** check `invoices.view-any` permission, 403 otherwise.

## Step 13: Student — My Invoices Index Page

`resources/views/pages/student/invoices/index.blade.php`:

- Large **Total Balance Due** banner at top (sum of non-voided balances > 0)
- Card-style list (not a table — friendlier for students) of invoices:
  - Invoice #, section code + name, amount due, amount paid, balance, status badge, due date
  - Click → show page
- Empty state: "No invoices yet"
- Query: `auth()->user()->enrollments()->with('invoice.payments')->get()->pluck('invoice')->filter()`

## Step 14: Student — Invoice Show Page

`resources/views/pages/student/invoices/show.blade.php`:

- Read-only version of admin show page
- Header with invoice #, status
- Amounts block
- Payment history table (no delete, no form)
- **Authorization:** component mount checks that `$invoice->enrollment->user_id === auth()->id()` → 403 otherwise

## Step 15: Seeders

### 15a. `CourseSeeder` update

Set realistic `tuition_amount` values for existing seeded courses (use `randomElement([450, 500, 600, 750, 900])` or hardcode per-course).

### 15b. `PaymentSeeder` (new)

For each existing non-dropped enrollment:
1. Create an invoice via `CreateInvoiceForEnrollment` action (idempotent — skips if one exists)
2. Randomly categorize:
   - ~60% **fully paid** — one payment matching `amount_due`
   - ~20% **partially paid** — one payment of 30–70% of `amount_due`
   - ~15% **unpaid** — no payments
   - ~5% **refund scenario** — full payment + a refund (negative) of ~25%
3. Wire `recorded_by` to a random admin/registrar user

### 15c. `DatabaseSeeder` update

Call `PaymentSeeder` after `GradeSeeder`.

## Step 16: Tests

Create under `tests/Feature/Billing/`. Target ~40–50 new tests.

### 16a. `InvoiceCreationTest.php`
- Invoice is auto-created when a student enrolls with `status = Enrolled`
- `amount_due` matches `course.tuition_amount`
- `due_at` matches `section.term.start_date`
- Invoice number follows `INV-YYYY-NNNNNN` format
- No invoice created for `Waitlisted` enrollment
- Re-running creation action is idempotent (returns existing invoice)
- Dropping an enrollment with zero payments voids the invoice
- Dropping an enrollment with payments leaves invoice unvoided

### 16b. `InvoiceModelTest.php`
- `totalPaid()` sums positive + negative payments correctly
- `balance()` = amount_due - totalPaid
- `status()` returns `Unpaid` with no payments
- `status()` returns `Partial` with 0 < paid < due
- `status()` returns `Paid` with paid === due
- `status()` returns `Overpaid` with paid > due
- `status()` returns `Voided` when `voided_at` is set (overrides everything)
- `scopeOutstanding()` excludes Paid and Voided
- Refund (negative payment) correctly reduces `totalPaid`

### 16c. `InvoiceNumberTest.php`
- Generator produces `INV-YYYY-NNNNNN` format
- Sequence increments across calls
- Sequence resets per year (mock year change)
- Numbers are unique under concurrent-ish creation (sequential factory calls)

### 16d. `AdminInvoiceIndexTest.php`
- Admin can view page
- Registrar can view page
- Instructor gets 403
- Student gets 403
- Stats strip shows correct totals for filtered set
- Search by student name filters results
- Search by invoice number filters results
- Status filter: each of Unpaid/Partial/Paid/Overpaid/Voided returns matching invoices
- Term filter limits results to enrollments in that term
- Sort by amount_due asc/desc works
- Pagination works

### 16e. `AdminInvoiceShowTest.php`
- Admin/Registrar can view any invoice
- Instructor/Student gets 403 on admin URL
- Record cash payment — amount positive → balance updates, status transitions
- Record refund — amount negative + method=Refund → totalPaid reduces
- Reject refund with positive amount (validation error)
- Reject cash payment with negative amount (validation error)
- Reject zero amount (validation error)
- Reject received_at in the future
- Delete payment (admin only) — recalculates balance
- Registrar cannot delete payment (403)
- Void invoice with no payments succeeds
- Void invoice hidden when payments exist
- Cannot re-void an already voided invoice
- Recorded_by field captures current user on payment creation

### 16f. `StudentInvoiceIndexTest.php`
- Student sees their own invoices only
- Student does not see other students' invoices
- Total balance banner shows correct sum
- Non-student roles redirected / 403
- Empty state renders when student has no enrollments

### 16g. `StudentInvoiceShowTest.php`
- Student can view own invoice
- Student gets 403 viewing another student's invoice
- No payment form rendered
- No delete button rendered
- No void button rendered

### 16h. `BillingPermissionTest.php`
- Each new permission exists after seeding
- Each role has the expected permission set
- Instructor role has zero billing permissions

## Step 17: Run Pint and Full Suite

```
vendor/bin/pint --dirty --format agent
./vendor/bin/sail artisan test --compact
```

Target: **all existing 379 + ~45 new tests = ~424 tests, all green.**

## Implementation Order

1. **Step 1** — PaymentMethod enum
2. **Step 2** — InvoiceStatus enum
3. **Step 3** — Migrations (all three)
4. **Step 4** — Models (Invoice, Payment) + model updates (Course, Enrollment, User)
5. **Step 5** — GenerateInvoiceNumber action
6. **Step 6** — CreateInvoiceForEnrollment action (don't wire the hook yet)
7. **Step 7** — Factories
8. **Step 16b, 16c** — Invoice model unit tests + number generator tests (TDD the math)
9. **Step 16a** — Invoice creation action tests
10. **Step 6 (hook wiring)** — Wire CreateInvoiceForEnrollment into enrollment persistence + drop handling
11. **Step 8** — Permissions seeder update
12. **Step 9** — Routes
13. **Step 10** — Sidebar navigation
14. **Step 11** — Admin invoice index page
15. **Step 16d** — Admin invoice index tests
16. **Step 12** — Admin invoice show page
17. **Step 16e** — Admin invoice show tests
18. **Step 13** — Student invoice index page
19. **Step 16f** — Student invoice index tests
20. **Step 14** — Student invoice show page
21. **Step 16g** — Student invoice show tests
22. **Step 16h** — Billing permission test
23. **Step 15** — Seeders (CourseSeeder update + PaymentSeeder + DatabaseSeeder)
24. **Step 17** — Pint + full test run

## Files Created/Modified Summary

### New files (~22)

**Enums (2):**
- `app/Enums/PaymentMethod.php`
- `app/Enums/InvoiceStatus.php`

**Migrations (3):**
- `database/migrations/XXXX_XX_XX_add_tuition_amount_to_courses_table.php`
- `database/migrations/XXXX_XX_XX_create_invoices_table.php`
- `database/migrations/XXXX_XX_XX_create_payments_table.php`

**Models (2):**
- `app/Models/Invoice.php`
- `app/Models/Payment.php`

**Actions (2):**
- `app/Actions/Billing/GenerateInvoiceNumber.php`
- `app/Actions/Billing/CreateInvoiceForEnrollment.php`

**Factories (2):**
- `database/factories/InvoiceFactory.php`
- `database/factories/PaymentFactory.php`

**Seeder (1):**
- `database/seeders/PaymentSeeder.php`

**Views (4):**
- `resources/views/pages/admin/invoices/index.blade.php`
- `resources/views/pages/admin/invoices/show.blade.php`
- `resources/views/pages/student/invoices/index.blade.php`
- `resources/views/pages/student/invoices/show.blade.php`

**Tests (8):**
- `tests/Feature/Billing/InvoiceCreationTest.php`
- `tests/Feature/Billing/InvoiceModelTest.php`
- `tests/Feature/Billing/InvoiceNumberTest.php`
- `tests/Feature/Billing/AdminInvoiceIndexTest.php`
- `tests/Feature/Billing/AdminInvoiceShowTest.php`
- `tests/Feature/Billing/StudentInvoiceIndexTest.php`
- `tests/Feature/Billing/StudentInvoiceShowTest.php`
- `tests/Feature/Billing/BillingPermissionTest.php`

### Modified files (~8)

- `app/Models/Course.php` — add tuition_amount fillable + cast
- `app/Models/Enrollment.php` — add invoice() relation
- `app/Models/User.php` — add recordedPayments() relation
- `database/factories/CourseFactory.php` — default tuition_amount
- `database/seeders/CourseSeeder.php` — realistic tuition amounts
- `database/seeders/DatabaseSeeder.php` — call PaymentSeeder
- `database/seeders/RolePermissionSeeder.php` (or equivalent) — new billing permissions
- `routes/web.php` — four new routes
- `resources/views/partials/*sidebar*.blade.php` — billing nav items
- Enrollment creation hook (location TBD in Step 6) — call CreateInvoiceForEnrollment
- Enrollment drop hook — void invoice when zero payments

## Key Design Decisions (Recap)

1. **One invoice per enrollment, auto-created.** Matches how trade schools actually bill.
2. **Derived status, not stored.** Eliminates drift between the status column and payment reality.
3. **Refunds as negative payments.** Single ledger, simpler math, no second table.
4. **Tuition on Course only.** Simplest model; section-level override can be added later if needed.
5. **Due date from term start.** Aligns with academic calendar, not enrollment date.
6. **Instructors locked out of billing.** Hard separation between academic and financial staff.
7. **Registrar can create but not delete payments.** Audit safety — only Admin/SuperAdmin can erase ledger entries.
8. **Void preserves audit trail.** Voided invoices stay visible; they aren't soft-deleted.
