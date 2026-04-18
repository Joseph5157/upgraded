# Staging Smoke-Test Checklist

**Purpose:** Release-readiness gate before staged rollout.  
**Scope:** Critical user flows, credit accounting, lifecycle guards, and observability signals.  
**Not in scope:** Performance, load testing, new features, refactoring.

---

## How to Use

- Run each case in a staging environment with a real database and real storage.
- On any failure, record: **X-Request-Id header**, route name, role, timestamp, and exact error.
- Mark severity with **[BLOCKER]**, **[HIGH]**, **[MEDIUM]**, or **[LOW]** based on the definitions in `rollout-stabilization.md`.
- Do not proceed to production rollout if any **[BLOCKER]** or **[HIGH]** cases fail.

---

## Section A — Client Flows

### A-1 · Client login and dashboard access

| Field | Value |
|---|---|
| **Role** | Client |
| **Steps** | 1. Navigate to `/login`. 2. Submit valid client credentials. 3. Confirm redirect lands on `/dashboard` (client). 4. Confirm no verification prompt appears. 5. Log out and confirm redirect to `/login`. |
| **Expected** | Login succeeds; client dashboard loads with orders and subscription data visible; logout clears session. |
| **Blocker if fails** | **[BLOCKER]** — login failure blocks all client usage. |

---

### A-2 · Client single-file upload

| Field | Value |
|---|---|
| **Role** | Client (with ≥ 1 available slot) |
| **Steps** | 1. Log in as client. 2. Submit upload form with one PDF file and optional notes. 3. Confirm success flash message with tracking ID. 4. Confirm new order appears on the dashboard with status `pending`. 5. Confirm `slots_consumed` incremented by 1 on the subscription screen. |
| **Expected** | Order created; file stored; credit decremented; dashboard reflects new order. |
| **Blocker if fails** | **[BLOCKER]** — core product function broken. |

---

### A-3 · Client multi-file upload with credit deduction

| Field | Value |
|---|---|
| **Role** | Client (with ≥ 3 available slots) |
| **Steps** | 1. Note current `slots_consumed` value from subscription screen. 2. Upload 3 files in a single order submission. 3. Confirm success flash. 4. Confirm `slots_consumed` increased by exactly 3. 5. Confirm the new order shows `files_count = 3`. |
| **Expected** | Credits deducted per file, not per order; order record has correct `files_count`. |
| **Blocker if fails** | **[BLOCKER]** — credit corruption is a blocker. |

---

### A-4 · Client upload failure path (intentional)

| Field | Value |
|---|---|
| **Role** | Client with 0 remaining slots (or use a suspended account) |
| **Steps** | 1. Log in as a client whose `slots_consumed >= slots` (zero credits). 2. Attempt to submit an upload. 3. Confirm the flash error message is shown. 4. Check application logs for a `order.create_failed` warning entry containing `client_id`, `file_count`, `exception`, and `message` fields. |
| **Expected** | Upload fails gracefully with a user-visible error; no order row is created; `slots_consumed` is unchanged; `order.create_failed` warning appears in logs with safe structured context (no raw file contents). |
| **Note** | `order.create_failed` is logged to the **application log only** — it does not write to `audit_logs`. Verify in the log file or log aggregator, not the audit_logs table. |
| **Blocker if fails** | **[HIGH]** if error is not shown to user or order is partially created. **[MEDIUM]** if log entry is missing but flow is otherwise correct. |

---

### A-5 · Client deletes an unclaimed pending order (credit restore)

| Field | Value |
|---|---|
| **Role** | Client |
| **Steps** | 1. Create an order (single or multi-file) and confirm it is `pending` and not yet claimed. 2. Note `slots_consumed` value. 3. Delete the order from the dashboard. 4. Confirm success message. 5. Confirm the order no longer appears on the dashboard. 6. Confirm `slots_consumed` decreased by the order's `files_count`. 7. Query `audit_logs` for `event_type = credits.restored` with the correct `order_id` in `meta`. |
| **Expected** | Order deleted; credits restored exactly equal to `files_count`; audit log row present. |
| **Blocker if fails** | **[BLOCKER]** — credit accounting inconsistency. |

---

### A-6 · Client cannot delete a claimed order

| Field | Value |
|---|---|
| **Role** | Client |
| **Steps** | 1. Create an order, then claim it as a vendor (or set status to `claimed` via fixture). 2. As the client, attempt to delete the order via the dashboard UI or a direct `DELETE` request. 3. Confirm the deletion is rejected (403 or error flash). 4. Query `audit_logs` for `event_type = client_order.delete_denied` with `reason = order_not_unclaimed_pending`. |
| **Expected** | Deletion denied; order status unchanged; audit log row present with `claimed_by` recorded. |
| **Blocker if fails** | **[BLOCKER]** — lifecycle integrity and data integrity broken. |

---

### A-7 · Client cannot delete a processing order

| Field | Value |
|---|---|
| **Role** | Client |
| **Steps** | 1. Advance an order to `processing` status. 2. As the client, attempt to delete the order. 3. Confirm rejection (403 or error). 4. Confirm `client_order.delete_denied` audit log entry with `order_status = processing`. |
| **Expected** | Deletion denied; audit log written. |
| **Blocker if fails** | **[BLOCKER]** |

---

## Section B — Vendor Flows

### B-1 · Vendor login and dashboard access

| Field | Value |
|---|---|
| **Role** | Vendor (verified) |
| **Steps** | 1. Log in with verified vendor credentials. 2. Confirm redirect to vendor dashboard. 3. Confirm pending orders list is visible. |
| **Expected** | Login and dashboard load without errors. |
| **Blocker if fails** | **[BLOCKER]** |

---

### B-2 · Vendor claims a pending order

| Field | Value |
|---|---|
| **Role** | Vendor |
| **Steps** | 1. Identify a `pending` unclaimed order in the vendor dashboard. 2. Click "Claim". 3. Confirm the order status changes to `claimed` in the dashboard. 4. Confirm the order is no longer in the unclaimed pool for other vendors. 5. Query `audit_logs` for `event_type = order.claimed` with the correct `order_id` and `user_id`. |
| **Expected** | Order moves to `claimed`; claimed_by set to vendor's user_id; audit log row written. |
| **Blocker if fails** | **[BLOCKER]** — core vendor workflow broken. |

---

### B-3 · Vendor starts processing a claimed order

| Field | Value |
|---|---|
| **Role** | Vendor (who owns the claimed order) |
| **Steps** | 1. Locate a `claimed` order owned by this vendor. 2. Click "Start Processing". 3. Confirm status changes to `processing`. 4. Query `audit_logs` for `event_type = order.processing_started`. |
| **Expected** | Status transitions from `claimed` → `processing`; audit log written; no status skipping. |
| **Blocker if fails** | **[BLOCKER]** |

---

### B-4 · Vendor cannot transition an order from pending directly to processing

| Field | Value |
|---|---|
| **Role** | Vendor |
| **Steps** | 1. Find or create a `pending` unclaimed order. 2. Attempt to call the "Start Processing" action on it directly (fabricate a form POST if the UI doesn't show the button). 3. Confirm the attempt is rejected with an error message. |
| **Expected** | Transition denied with message referencing required `claimed` status; order status unchanged. |
| **Blocker if fails** | **[HIGH]** — lifecycle guard missing. |

---

## Section C — Admin Flows

### C-1 · Admin dashboard loads

| Field | Value |
|---|---|
| **Role** | Admin (verified) |
| **Steps** | 1. Log in as admin. 2. Navigate to `/admin/dashboard`. 3. Confirm the page renders without 500 errors. 4. Confirm key stats blocks (active orders, vendors, clients) are visible. |
| **Expected** | Dashboard renders; no errors. |
| **Blocker if fails** | **[HIGH]** |

---

### C-2 · Admin freezes a client account

| Field | Value |
|---|---|
| **Role** | Admin |
| **Steps** | 1. Navigate to account manager. 2. Select a client account. 3. Submit the "Freeze" action with a reason string. 4. Confirm the account status changes to `frozen`. 5. Confirm the associated `client.status` becomes `suspended`. 6. Query `audit_logs` for `event_type = account.frozen` with the `reason` in `meta`. 7. Log in as the frozen client and confirm access is denied. |
| **Expected** | Account frozen; client status suspended; session invalidated; audit log present. |
| **Blocker if fails** | **[BLOCKER]** — access control critical path. |

---

### C-3 · Admin soft-deletes a client account with unfinished orders

| Field | Value |
|---|---|
| **Role** | Admin |
| **Steps** | 1. Identify a client with at least one `pending` or `claimed` order. Note the total `files_count` across those orders. 2. Submit the soft-delete action (requires admin password confirmation). 3. Confirm: unfinished orders are set to `cancelled`, `claimed_by` is cleared. 4. Confirm `slots_consumed` decreased by the total `files_count` of cancelled orders. 5. Query `audit_logs` for `event_type = credits.restored` with `reason = account_deleted` and the correct `credits_restored` value. 6. Query `audit_logs` for `event_type = account.deleted`. |
| **Expected** | Orders cancelled; credits correctly restored; both audit log entries present. |
| **Blocker if fails** | **[BLOCKER]** — credit accounting and data integrity. |

---

### C-4 · Admin force-deletes a soft-deleted account

| Field | Value |
|---|---|
| **Role** | Admin |
| **Steps** | 1. First soft-delete a user (see C-3). 2. Submit force-delete with admin password confirmation. 3. Confirm the user is permanently removed from the database (`withTrashed()` returns nothing). 4. Query `audit_logs` for `event_type = account.deleted` with `force_deleted = true`. |
| **Expected** | User permanently removed; audit log entry with `force_deleted: true`. |
| **Blocker if fails** | **[HIGH]** |

---

### C-5 · Admin delete denied on wrong password

| Field | Value |
|---|---|
| **Role** | Admin |
| **Steps** | 1. Attempt account deletion with an incorrect admin password. 2. Confirm the error response (form error for wrong password). 3. Confirm the user record still exists. 4. Query `audit_logs` for `event_type = account.delete_denied` with `reason = password_confirmation_failed`. |
| **Expected** | Deletion blocked; audit log denial entry written. |
| **Blocker if fails** | **[HIGH]** — denial pathway must be auditable. |

---

## Section D — Mobile / Resume Flows

### D-1 · Client opens dashboard on mobile

| Field | Value |
|---|---|
| **Role** | Client |
| **Steps** | 1. Open the client dashboard URL in a mobile browser (or browser devtools mobile emulation). 2. Confirm the page renders without layout overflow or broken UI. 3. Confirm the upload form is usable (file picker accessible). |
| **Expected** | Page renders; core actions reachable. |
| **Blocker if fails** | **[HIGH]** if core actions are broken; **[MEDIUM]** for cosmetic issues only. |

---

### D-2 · App/browser goes to background then resumes

| Field | Value |
|---|---|
| **Role** | Client |
| **Steps** | 1. Log in on mobile browser. 2. Background the browser tab (switch apps or lock screen) for at least 5 minutes. 3. Return to the tab. 4. Attempt the last action (e.g., submit an upload, navigate to a page). 5. Record the HTTP status code from the response and the `X-Request-Id` header. |
| **Expected** | Action succeeds, or if the session has expired, the app redirects to login cleanly (302/303) without a 500 error or a silent CSRF loop. |
| **Blocker if fails** | **[BLOCKER]** if a 500 is returned with no diagnostic information. **[HIGH]** if session expiry causes a CSRF error loop rather than a clean redirect. |

---

### D-3 · Resumed session failure produces a usable correlation ID

| Field | Value |
|---|---|
| **Role** | Client (or Vendor) |
| **Steps** | 1. Reproduce a mobile resume failure (419 CSRF, 302 loop, or 500). 2. Capture the `X-Request-Id` from the response header. 3. Search application logs for that `request_id`. 4. Confirm `request.started` and `request.finished` (or `request.failed`) log entries exist for that ID. |
| **Expected** | Every failing response carries an `X-Request-Id`; that ID resolves to correlated log entries. |
| **Blocker if fails** | **[BLOCKER]** — observability requirement for all production failures. |

---

## Section E — Observability Checks

### E-1 · X-Request-Id present on all responses

| Field | Value |
|---|---|
| **Role** | Any |
| **Steps** | 1. Using browser devtools or a tool like `curl -I`, inspect response headers for any authenticated request (dashboard, upload, claim). 2. Confirm `X-Request-Id` header is present with a UUID v4 value. 3. Repeat for at least one unauthenticated public request (e.g., public order tracking). |
| **Expected** | `X-Request-Id` header is present on every response, including public routes, error responses, and redirects. |
| **Blocker if fails** | **[BLOCKER]** — prerequisite for all log correlation. |

---

### E-2 · Audit log entry written for order.claimed

| Field | Value |
|---|---|
| **Role** | Admin (DB access) |
| **Steps** | 1. Claim an order as vendor (see B-2). 2. Query: `SELECT * FROM audit_logs WHERE event_type = 'order.claimed' ORDER BY created_at DESC LIMIT 1;` 3. Confirm columns: `request_id` (non-null UUID), `user_id` (vendor), `subject_type` = `App\Models\Order`, `subject_id` (order ID), `meta` contains `old_status` and `new_status`. |
| **Expected** | Row present with all expected fields populated. |
| **Blocker if fails** | **[HIGH]** — audit trail broken for a critical action. |

---

### E-3 · Audit log entry written for order.processing_started

| Field | Value |
|---|---|
| **Role** | Admin (DB access) |
| **Steps** | 1. Start processing an order (see B-3). 2. Query `audit_logs` for `event_type = 'order.processing_started'`. 3. Confirm `request_id`, `user_id`, `subject_id`, and `meta.old_status = 'claimed'`, `meta.new_status = 'processing'`. |
| **Expected** | Row present with correct status transition in `meta`. |
| **Blocker if fails** | **[HIGH]** |

---

### E-4 · Audit log entry written for client_order.delete_denied

| Field | Value |
|---|---|
| **Role** | Admin (DB access) |
| **Steps** | 1. Trigger a denied delete (see A-6 or A-7). 2. Query: `SELECT * FROM audit_logs WHERE event_type = 'client_order.delete_denied' ORDER BY created_at DESC LIMIT 1;` 3. Confirm `reason`, `order_status`, and `claimed_by` are in `meta`. |
| **Expected** | Row present; denial reason machine-readable. |
| **Blocker if fails** | **[HIGH]** |

---

### E-5 · Audit log entry written for credits.restored

| Field | Value |
|---|---|
| **Role** | Admin (DB access) |
| **Steps** | 1. Delete an eligible pending order as client (see A-5) or delete a client account with unfinished orders (see C-3). 2. Query: `SELECT * FROM audit_logs WHERE event_type = 'credits.restored' ORDER BY created_at DESC LIMIT 1;` 3. Confirm `credits_restored`, `slots_consumed_after` (or `reason = account_deleted`) in `meta`. |
| **Expected** | Row present with numeric credit values in `meta`. |
| **Blocker if fails** | **[HIGH]** — credit restoration must be auditable. |

---

### E-6 · order.create_failed warning appears in logs (not audit_logs)

| Field | Value |
|---|---|
| **Role** | Admin (log access) |
| **Steps** | 1. Trigger a failed upload (see A-4 — use a client with zero credits). 2. Search the application log file (or log aggregator) for `order.create_failed`. 3. Confirm the log entry contains: `client_id`, `file_count`, `exception` (class name only), `message` (error message). 4. Confirm no raw file contents, tokens, or passwords appear in the log entry. |
| **Expected** | Warning entry present with safe structured context; no sensitive data leaked. |
| **Note** | This event is written to the **application log only** — it is NOT stored in `audit_logs`. If audit_log coverage of this event is required, that is a post-rollout improvement. |
| **Blocker if fails** | **[MEDIUM]** if log entry is missing but flow is otherwise clean. **[HIGH]** if sensitive data leaks into the log. |

---

### E-7 · Request correlation traceable end-to-end

| Field | Value |
|---|---|
| **Role** | Any |
| **Steps** | 1. Perform any request (e.g., submit a form). 2. Copy the `X-Request-Id` from the response header. 3. Search application logs for that UUID. 4. Confirm you find `request.started`, the relevant business-level log entry (e.g., `order.claimed`), and `request.finished`, all sharing the same `request_id` context field. |
| **Expected** | Full request lifecycle traceable from a single ID. |
| **Blocker if fails** | **[BLOCKER]** |

---

## Known Gaps (Post-Rollout, Not Blockers)

| Gap | Severity | Notes |
|---|---|---|
| `order.create_failed` is log-only, not written to `audit_logs` table | Low | Diagnosable via logs; upgrade to audit_log entry is a clean improvement for a future batch. |
| No automated smoke-test script (all manual) | Low | Manual sign-off is sufficient for this rollout phase. |
| Delivery step not in scope for this checklist | — | Deliver + download flows are stable per test suite; add to checklist if staging shows gaps. |

---

## Sign-Off

| Tester | Date | Environment | Overall Result | Notes |
|---|---|---|---|---|
| | | staging | PASS / FAIL | |

> Record the staging URL, PHP version, and Laravel version at time of sign-off.
