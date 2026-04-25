# AI-Augmented Workflow

This project was built with Claude Code as an orchestration tool: architecture, planning, and review by hand; implementation delegated to AI within tight constraints. The artifacts of that process are the planning documents themselves.

## The method, in five rules

1. **Plan before build.** Every phase opens with a written plan stored outside the repo. The plan has a Context section, a numbered list of *locked* design decisions, a step-by-step implementation order, an explicit test target per step, and a Files Created/Modified summary.

2. **Decisions are locked, with rationale.** "Decisions (locked)" is a fixed section in every phase plan. Each decision is one sentence of *what* and one sentence of *why*. A decision in the plan is binding for the implementation; a change to the plan is a deliberate edit, not a drift.

3. **Invariants per commit.** Every commit must leave main green: `php artisan test --compact` passes, `vendor/bin/pint --dirty --format agent` is clean, dark mode parity holds, no new dependencies. Phase 9 of the public UI overhaul was a zero-commit clean run because nothing needed fixing — that is the bar.

4. **One commit per purpose.** Feature, test, and any docs co-located in the same commit. Main stays green between commits. The public UI overhaul shipped in 54 commits across 8 phases — small, reviewable units, not god-commits.

5. **Lessons feed forward.** When a phase surfaces a gotcha — Flux dropdown text colliding with a test assertion, voided invoices leaking into financial totals, division-by-zero when an assessment's `max_points` is 0, an empty-string filter producing `where('term_id', '')` — it gets codified as a "Bug prevention rules" section at the top of the next plan, applied before code is written.

## Evidence — a representative excerpt

From [`sample-plan.md`](sample-plan.md), Phase 7 (Payments), Decisions section:

> 4. **Status derivation:** invoice status (`Unpaid` / `Partial` / `Paid` / `Overpaid` / `Voided`) is **computed** from `sum(payments.amount)` vs `amount_due`. Not persisted. `Voided` comes from a `voided_at` column (soft-void, audit-preserving).
>
> 6. **Refunds:** modeled as payments with a **negative amount** and `method = PaymentMethod::Refund`. Single ledger, simple math.
>
> 7. **Authorization:** `SuperAdmin`, `Admin`, `Registrar` — full billing access. `Instructor` — no billing access at all. `Student` — view own invoices only, read-only.

Each decision is locked before implementation begins. The "why" is one sentence; the implementation rules then follow from it without re-litigation.

## What is included here vs. what is in the archive

In this repo: the method (this document) plus one full sample plan ([`sample-plan.md`](sample-plan.md), Phase 7 — Payments).

In the archive (available on request):

- **Phase 7 — Payments** (this sample): invoices auto-generated from enrollments, payments with refunds as negative entries, derived status, registrar/admin authorization split.
- **Phase 8 — Documents and certifications**: program completion logic, certificate issuance and revocation, student transcripts.
- **Phase 9 — Announcements, notifications, dashboard**: database notifications without a queue dependency, role-aware dashboard widgets, first-publish-only notification dispatch.
- **Phase 10 — Reporting and analytics**: five admin reports, with an explicit "Bug prevention rules applied throughout" section codifying lessons from Phases 7–9.
- **Public UI overhaul**: a 54-commit shipped overhaul (marketing layout, public catalog, branded error pages, auth polish, contact flow, SEO/sitemap), with quantified outcomes (689 → 945 tests, 256 new tests, 2040 assertions).

The archive is not published in full because more documents would dilute the signal. The sample plan demonstrates the discipline; the rest is consistent with it.
