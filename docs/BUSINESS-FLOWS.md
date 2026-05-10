# Business Flows & Workflows - PlagExpert (Portal)

This document outlines the main features and user journeys for PlagExpert (Portal), detailing the workflows for Clients, Vendors, and Administrators. These flows represent the core business processes of the plagiarism checking and document processing service.

## Main Features

PlagExpert is designed to facilitate a seamless document processing ecosystem with distinct roles and responsibilities:

- **Clients**: Upload PDF/DOC files for plagiarism checking, track order status, manage credits, request top-ups or refunds.
- **Vendors**: Claim client orders, process documents, upload AI and plagiarism reports, track earnings, and request payouts.
- **Administrators**: Oversee the platform, manage users, set pricing, handle billing, monitor system health, and resolve issues like refunds or account freezes.
- **Integrations**: Telegram for notifications and OTP login, Cloudflare R2 for file storage, and Cloudflare Turnstile for bot protection on public endpoints.

## Client Journey

Clients interact with the system to submit documents for processing and retrieve reports. Their journey includes both dashboard-based and public link interactions.

### Workflow Diagram (Client)

```
[Start] --> [Login via OTP] --> [Dashboard Access]
Dashboard Access --> [Upload Files] --> [Order Created] --> [Credit Deduction]
Order Created --> [Track Status] --> [Order Delivered] --> [Download Reports]
Dashboard Access --> [Request Topup] --> [Admin Approval] --> [Credits Added]
Dashboard Access --> [Submit Refund Request] --> [Admin Review] --> [Credit Slot Returned or Denied]
```

### Key Steps

1. **Authentication**: Clients log in using OTP (delivered via Telegram) at `/login`. The 6-digit code is hashed before storage and compared on the server — the plaintext code is never persisted.
2. **Dashboard**: Access at `/client/dashboard` to view orders, credits, and subscription details.
3. **Upload (Dashboard)**: Upload files via dashboard (`/client/dashboard/upload`), consuming slot credits per file.
4. **Upload (Public Link)**: Use client-specific links (`/u/{token}`) for uploads without dashboard access.
5. **Order Tracking**: Track order status via dashboard or public tracking URL (`/track/{token_view}`).
6. **Report Download**: Download reports once delivered (`/download/{token_view}` or via dashboard). Reports remain available until downloaded — they are not purged while `is_downloaded=false`.
7. **Credit Management**:
   - Delete unclaimed orders to restore credits.
   - Request top-ups (`/client/topup`) for additional slots, optionally recording the rupee amount paid (`amount_paid`).
   - Submit refund requests (`POST /client/refunds`) for orders in claimed, processing, or delivered status. One pending refund per order at a time.
8. **Telegram Notifications**: Receive updates on order acceptance and completion if linked.

### Key Constraints

- Credits are deducted per file uploaded, not per order.
- Clients cannot delete orders once claimed by vendors or in processing status.
- Public upload links are throttled (30 requests/minute) to prevent abuse.
- Only one pending refund request is allowed per order at a time.

## Vendor Journey

Vendors are responsible for processing client orders, transitioning them through the workflow stages, uploading reports, and managing their earnings.

### Workflow Diagram (Vendor)

```
[Start] --> [Login] --> [Dashboard Access]
Dashboard Access --> [View Pending Orders] --> [Claim Order]
Claim Order --> [Start Processing] --> [Upload Reports] --> [Order Delivered] --> [Earnings Updated]
Dashboard Access --> [View Earnings] --> [Request Payout]
```

### Key Steps

1. **Authentication**: Login at `/login`, redirected to `/dashboard` for vendor role.
2. **Dashboard**: View pending orders available for claiming.
3. **Claim Order**: Claim a pending order (`/orders/{order}/claim`), changing status to `claimed`.
4. **Start Processing**: Transition to `processing` status (`/orders/{order}/status`) once work begins.
5. **Upload Reports**: Upload AI and plagiarism reports (`/orders/{order}/report`). AI report can be skipped with a reason. Uploading reports also auto-delivers the order in a single step.
6. **Deliver Order**: Can also be explicitly triggered via status update if reports were uploaded separately.
7. **Earnings**: Track delivered order count and balance at `/earnings`.
8. **Request Payout**: Submit a payout request (`POST /earnings/request-payout`) for the current outstanding balance. The admin receives a Telegram notification. One pending request allowed at a time.

### Key Constraints

- Vendors can only claim unclaimed `pending` orders.
- State transitions are guarded: cannot skip from `pending` to `processing` without `claimed`.
- Only the claiming vendor (or an admin) can progress or unclaim an order.
- `delivered_orders_count` on the vendor profile is permanent and never decremented, even if clients delete delivered orders.

## Admin Journey

Administrators have full control over the platform, managing users, finances, and system operations.

### Workflow Diagram (Admin)

```
[Start] --> [Login] --> [Admin Dashboard]
Admin Dashboard --> [Manage Users] --> [Invite / Freeze / Unfreeze / Delete Accounts]
Admin Dashboard --> [Manage Client Links] --> [Create / Toggle / Delete Links]
Admin Dashboard --> [Billing & Ledger] --> [View Daily Ledgers]
Admin Dashboard --> [Topup Requests] --> [Approve / Reject Topups]
Admin Dashboard --> [Refund Requests] --> [Approve / Reject Refunds]
Admin Dashboard --> [Vendor Payouts] --> [Record Payout / Fulfil Payout Request]
Admin Dashboard --> [Announcements] --> [Create / Toggle / Delete]
Admin Dashboard --> [Payment Settings] --> [Update Payment Methods]
Admin Dashboard --> [Pricing] --> [Set Client / Vendor Pricing]
```

### Key Steps

1. **Authentication**: Login redirects to `/admin/dashboard`.
2. **User Management**: Invite, freeze, unfreeze, or delete accounts (`/admin/accounts`). Inviting an admin-role user requires super-admin privilege.
3. **Client Links**: Manage public upload links for clients (`/admin/client-links`).
4. **Billing**: View daily ledgers and financial summaries (`/admin/billing`). Ledger payouts use each vendor's individual `payout_rate`, not a global flat rate.
5. **Topup Requests**: Approve or reject client topup requests (`/admin/topup`). Approval adds the requested slots to the client's account.
6. **Refund Requests**: Process client refund requests (`/admin/refunds`). Approval decrements `slots_consumed` by one and may reactivate a suspended client. The entire approve operation is atomic.
7. **Vendor Payouts**: Record a payout (`POST /admin/finance/payouts`). The system guards against overpayment — recording a payout larger than the vendor's outstanding balance is rejected with an error. If the payout fulfils a vendor's self-submitted request, pass `payout_request_id` to mark it as fulfilled.
8. **Announcements**: Broadcast messages to users (`/admin/announcements`).
9. **Payment Settings**: Configure active payment methods (`/admin/payment-settings`).
10. **Pricing**: Adjust pricing for clients and vendors (`/admin/pricing`).

### Key Constraints

- Admin invite creation (creating another admin account) is restricted to super-admins.
- Account deletion is atomic: all side effects (order cancellation, link revocation, refund auto-rejection) happen in a single transaction or roll back completely.
- Deleting a client account forfeits their credits — no slot restoration occurs. This is by design.
- The payout overpayment guard uses the live `earned - paid` balance at request time.

## Order Lifecycle

The order lifecycle is central to PlagExpert's operation, managed via the `OrderStatus` enum:

```
Pending --> Claimed --> Processing --> Delivered
   |          |            |            |
   v          v            v            v
Cancelled  Cancelled   Cancelled    (Final)
```

- **Pending**: Order created by client, awaiting vendor claim.
- **Claimed**: Vendor has claimed the order, locking it to them.
- **Processing**: Vendor has started work on the documents.
- **Delivered**: Reports uploaded and order completed, client notified.
- **Cancelled**: Order cancelled (by client if unclaimed, by admin action, or on account deletion).

### Key Lifecycle Rules

- Clients can cancel/delete only unclaimed `pending` orders, restoring credits.
- Vendors can unclaim orders before starting processing.
- Admin account deletion cancels active orders at any stage; credits are forfeited (not restored).
- Audit logs track all state transitions for accountability.
- Delivered order files are only purged after the client has downloaded them (`is_downloaded=true`).

## Payout Flow

### Admin-Initiated Payout

1. Admin opens Finance → Payouts.
2. Selects a vendor, enters the amount.
3. System verifies amount ≤ vendor's outstanding balance.
4. `VendorPayout` record is created with `paid_at=now()`.
5. Optionally, a matching `VendorPayoutRequest` is marked `fulfilled`.

### Vendor-Initiated Payout Request

1. Vendor clicks Request Payout on the Earnings page.
2. System checks no pending request already exists for this vendor.
3. Current balance (`earned - paid`) is snapshotted as `amount_requested`.
4. `VendorPayoutRequest` created with `status=pending`.
5. Admin receives a Telegram notification.
6. Admin records the payout and passes `payout_request_id` to fulfil the request.

## Credit (Slot) Accounting

| Event | Effect on `slots_consumed` |
|---|---|
| File uploaded (dashboard or link) | +1 per file |
| Client deletes an unclaimed order | −`files_count` |
| Admin approves a refund | −1 |
| Account deleted | No change (credits forfeited) |

`slots` (the credit ceiling) is set by admins and increased by approved top-up requests.

## Best Practices in Workflows

- **Separation of Concerns**: Distinct dashboards and routes for each role prevent overlap and confusion.
- **State Management**: Enums and guarded transitions ensure order status integrity.
- **Atomic Operations**: Account deletion, refund approval, payout recording, and portal number assignment are each wrapped in a database transaction.
- **Credit Accounting**: Strict deduction logic and the forfeiture policy on deletion prevent credit leaks or gaming.
- **Throttling**: Public endpoints are rate-limited to prevent abuse.
- **Notifications**: Automated Telegram alerts keep users informed of critical updates.
- **Auditability**: Every significant action (claim, delete, freeze, payout) is logged with context for troubleshooting.
