# Codex Stabilization Brief

Stabilize this Laravel project for rollout. Do not add product features. Focus only on observability, reproducibility, regression coverage, and critical-flow hardening.

## Latest Status Snapshot

- Core dashboard refresh paths are now fragment-based and polling-driven for vendor, logged-in client, and guest-link views.
- Vendor order-completed Telegram notifications now run through the queue instead of blocking the upload response.
- Guest-link polling is token-scoped and fail-closed, so public clients only see updates for their own link and expired/revoked links do not leak state.

## Scope

Implement a stabilization layer with:

1. request correlation IDs
2. structured logging
3. audit logs for business actions
4. 403 and authorization diagnostics
5. production error tracking hooks
6. local and staging debug tooling hooks
7. mobile resume diagnostics
8. rollout docs
9. regression tests for the highest-risk flows

## Hard Rules

- Do not add product features.
- Do not redesign business logic unless required for observability, diagnostics, or testability.
- Prefer additive, low-risk changes.
- Do not remove existing authorization checks.
- Do not change UI styling except where needed for non-production diagnostics.
- Do not log secrets, passwords, raw session contents, CSRF tokens, uploaded file contents, or payment secrets.
- Every new failure path should include a request correlation ID.
- Keep changes reviewable and split into phases.

## Required Output From Codex

For each phase, return:

1. summary of files created or changed
2. migrations added
3. middleware added
4. services or helpers added
5. tests added
6. manual setup steps still required
7. known limitations or risks

---

## Phase 1: Documentation

Create these files:

### `docs/rollout-stabilization.md`

Include:
- purpose
- severity model
- critical flows
- release blockers
- rollout batch plan
- exit criteria

### `docs/bug-register-template.md`

Create a markdown table with columns:
- ID
- Title
- Severity
- Area
- Role
- Route
- Trigger
- Expected
- Actual
- Environment
- Correlation ID
- Sentry Event
- Root Cause
- Fix Commit or PR
- Regression Test
- Status
- Verified By

### `docs/qa-critical-flows.md`

Add manual QA cases for:
- client login
- vendor and admin verification
- client upload
- claim, unclaim, start processing
- client delete restrictions
- credit accounting
- admin freeze and delete
- mobile standby and resume

### `docs/mobile-resume-debugging.md`

Document:
- how to reproduce mobile standby failures
- what logs to inspect
- what request metadata to capture
- how to interpret 403 vs 419 vs 302/303 vs 500

Acceptance criteria:
- all four docs exist
- docs are project-specific, not generic
- docs reflect current role model and order lifecycle

---

## Phase 2: Request Correlation Middleware

Create:

- `app/Http/Middleware/RequestCorrelation.php`

Behavior:
- generate a UUID per request
- store it on request attributes
- attach it to log context
- add it to the response header as `X-Request-Id`
- log request start and request finish

Log fields:
- `request_id`
- `method`
- `path`
- `route_name`
- `user_id`
- `role`
- `ip`
- `user_agent`
- `status`
- `duration_ms`

Register it globally.

Acceptance criteria:
- every request gets an `X-Request-Id` header
- every request writes start and end log lines with the same request ID

---

## Phase 3: Structured Logging Helper

Create:

- `app/Support/LogContext.php`

Add helper methods to build safe log context for:
- current user
- current route
- current order
- current client
- current account status

Use this helper from controllers and services so logs are consistent.

Acceptance criteria:
- no repeated hand-built logging arrays across files
- no sensitive values logged

---

## Phase 4: Audit Logs

Create:

- migration for `audit_logs`
- `app/Models/AuditLog.php`
- `app/Services/AuditLogger.php`

Suggested columns:
- `id`
- `request_id`
- `user_id` nullable
- `event_type`
- `subject_type` nullable
- `subject_id` nullable
- `meta` json nullable
- `ip` nullable
- `user_agent` nullable
- timestamps

Add a service method like:
- `record(string $eventType, $subject = null, array $meta = [])`

Use it first in:
- `OrderWorkflowService`
- `DeleteClientOrderService`
- `AccountManagerController`
- login/logout/auth flows if easy and safe

Record events such as:
- `order.claimed`
- `order.unclaimed`
- `order.processing_started`
- `client_order.delete_attempted`
- `client_order.delete_denied`
- `account.frozen`
- `account.deleted`
- `credits.restored`

Acceptance criteria:
- key business actions create audit entries
- denied destructive actions are recorded with reason

---

## Phase 5: Authorization and 403 Diagnostics

Add structured warning or error logs anywhere authorization can fail in high-risk flows.

Target files:
- `app/Policies/OrderPolicy.php`
- `app/Services/DeleteClientOrderService.php`
- `app/Http/Controllers/AccountManagerController.php`
- `app/Http/Controllers/AnnouncementController.php`
- `app/Http/Controllers/ClientMatrixController.php`
- `app/Http/Controllers/MatrixController.php`

When an action is denied, log:
- `request_id`
- `user_id`
- `role`
- `route_name`
- `action`
- `subject_type`
- `subject_id`
- relevant lifecycle state such as order status
- denial reason

Do not expose denial internals to end users in production responses.

Acceptance criteria:
- 403s in these flows are traceable from logs alone

---

## Phase 6: Mobile Resume Diagnostics

Add lightweight frontend instrumentation for client pages.

Target layouts or pages where client actions occur.

Capture:
- `visibilitychange`
- timestamp when page becomes hidden
- timestamp when page becomes visible
- idle duration
- last clicked action
- last failed request URL and status if available

Send this data to a small backend endpoint.

Create:
- authenticated endpoint such as `POST /client-events`
- controller to store minimal diagnostic events

Create:
- migration for `client_diagnostic_events`

Suggested fields:
- `user_id`
- `request_id` nullable
- `event_type`
- `page`
- `meta` json
- timestamps

Only include:
- page path
- idle seconds
- last action name
- request status
- request path

Do not log raw form values or uploaded content.

Acceptance criteria:
- after a mobile resume failure, there is a matching client diagnostic event

---

## Phase 7: Production Error Tracking Hooks

If Sentry is already present, configure it better.
If not present, scaffold integration points and document required env keys.

Add useful tags and context:
- request ID
- user ID
- role
- route name
- order ID if available

Do not block progress on package installation if it is outside current scope. Leave code and docs ready.

Acceptance criteria:
- exceptions in critical flows include useful context

---

## Phase 8: Local and Staging Debug Tooling

If Telescope is already installed, configure it for non-production only.
If it is not installed, document setup steps in `docs/rollout-stabilization.md`.

Do not enable Telescope in production.

Acceptance criteria:
- local or staging can inspect requests, exceptions, jobs, and queries during stabilization

---

## Phase 9: Regression Tests

Add tests for the highest-risk areas.

### Auth
- client can access client area without verification
- vendor and admin verified-only routes are protected
- role redirects land on correct dashboard

### Order Lifecycle
- claim moves Pending to Claimed
- unclaim allowed only from Claimed
- start processing allowed only from Claimed
- processing cannot be unclaimed

### Client Delete Restrictions
- client can delete unclaimed pending order
- client cannot delete claimed order
- client cannot delete processing order

### Credits
- multi-file order consumes slots by `files_count`
- account deletion restores unfinished slots by `SUM(files_count)`
- subscription usage reflects `slots_consumed`

### Session and Admin Safety
- admin account actions do not assume database sessions when not using the database session driver

Acceptance criteria:
- tests are clear and minimal
- tests fail before the fix and pass after
- high-risk flows are covered first

---

## Implementation Order

Do the work in this exact sequence:

1. create docs
2. add request correlation middleware
3. add structured logging helper
4. add audit log table, model, and service
5. instrument critical services and controllers
6. add 403 diagnostics
7. add mobile diagnostic endpoint and frontend hooks
8. add Sentry or Telescope hooks
9. add regression tests
10. update docs with usage instructions

---

## Definition of Done

This task is complete only when:

- every request gets an `X-Request-Id`
- critical business actions create audit records
- 403s in critical flows are diagnosable from logs
- mobile resume failures leave both backend and client-side traces
- docs exist for stabilization workflow and QA
- regression tests cover auth, lifecycle, delete restrictions, credits, and admin session safety

---

## How Codex Should Work

Start with only Phase 1 and Phase 2.

Before making changes:
- inspect the repo
- list the files you plan to create or change
- state any assumptions

After Phase 1 and Phase 2 are done, stop and summarize results before continuing.

Do not implement all phases in one pass.
