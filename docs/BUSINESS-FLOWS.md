# Business Flows & Workflows - PlagExpert (Portal)

This document outlines the main features and user journeys for PlagExpert (Portal), detailing the workflows for Clients, Vendors, and Administrators. These flows represent the core business processes of the plagiarism checking and document processing service.

## Main Features

PlagExpert is designed to facilitate a seamless document processing ecosystem with distinct roles and responsibilities:

- **Clients**: Upload PDF/DOC files for plagiarism checking, track order status, manage credits, and request top-ups or refunds.
- **Vendors**: Claim client orders, process documents, upload AI and plagiarism reports, and track earnings.
- **Administrators**: Oversee the platform, manage users, set pricing, handle billing, monitor system health, and resolve issues like refunds or account freezes.
- **Integrations**: Telegram for notifications and OTP login, Cloudflare R2 for file storage, and Cloudflare Turnstile for bot protection on public endpoints.

## Client Journey

Clients interact with the system to submit documents for processing and retrieve reports. Their journey includes both dashboard-based and public link interactions.

### Workflow Diagram (Client)

```
[Start] --> [Login via OTP] --> [Dashboard Access]
Dashboard Access --> [Upload Files] --> [Order Created] --> [Credit Deduction]
Order Created --> [Track Status] --> [Order Delivered] --> [Download Reports]
Dashboard Access --> [Request Topup] --> [Admin Approval] --> [Credit Added]
Dashboard Access --> [Request Refund] --> [Admin Review] --> [Credit Restored or Denied]
```

### Key Steps

1. **Authentication**: Clients log in using OTP (often via Telegram integration) at `/login`.
2. **Dashboard**: Access at `/client/dashboard` to view orders, credits, and subscription details.
3. **Upload (Dashboard)**: Upload files via dashboard (`/client/dashboard/upload`), consuming slot credits per file.
4. **Upload (Public Link)**: Use client-specific links (`/u/{token}`) for uploads without dashboard access.
5. **Order Tracking**: Track order status via dashboard or public tracking URL (`/track/{token_view}`).
6. **Report Download**: Download reports once delivered (`/download/{token_view}` or via dashboard).
7. **Credit Management**:
   - Delete unclaimed orders to restore credits.
   - Request top-ups (`/client/topup`) for additional slots.
   - Request refunds for completed orders if issues arise.
8. **Telegram Notifications**: Receive updates on order acceptance and completion if linked.

### Key Constraints

- Credits are deducted per file uploaded, not per order.
- Clients cannot delete orders once claimed by vendors or in processing status.
- Public upload links are throttled (30 requests/minute) to prevent abuse.

## Vendor Journey

Vendors are responsible for processing client orders, transitioning them through the workflow stages, and uploading reports.

### Workflow Diagram (Vendor)

```
[Start] --> [Login] --> [Dashboard Access]
Dashboard Access --> [View Pending Orders] --> [Claim Order]
Claim Order --> [Start Processing] --> [Upload Reports]
Upload Reports --> [Deliver Order] --> [Earnings Updated]
Dashboard Access --> [View Earnings]
```

### Key Steps

1. **Authentication**: Login at `/login`, redirected to `/dashboard` for vendor role.
2. **Dashboard**: View pending orders available for claiming.
3. **Claim Order**: Claim a pending order (`/orders/{order}/claim`), changing status to `claimed`.
4. **Start Processing**: Transition to `processing` status (`/orders/{order}/status`) once work begins.
5. **Upload Reports**: Upload AI and plagiarism reports (`/orders/{order}/report`). AI report can be skipped with a reason.
6. **Deliver Order**: Mark order as `delivered` once reports are complete, notifying the client.
7. **Earnings**: Track earnings from completed orders at `/earnings`.

### Key Constraints

- Vendors can only claim unclaimed `pending` orders.
- State transitions are guarded: cannot skip from `pending` to `processing` without `claimed`.
- Only the claiming vendor can progress or unclaim an order.

## Admin Journey

Administrators have full control over the platform, managing users, finances, and system operations.

### Workflow Diagram (Admin)

```
[Start] --> [Login] --> [Admin Dashboard]
Admin Dashboard --> [Manage Users] --> [Create/Freeze/Unfreeze/Delete Accounts]
Admin Dashboard --> [Manage Client Links] --> [Create/Toggle/Delete Links]
Admin Dashboard --> [Billing & Ledger] --> [View Daily Ledgers]
Admin Dashboard --> [Topup Requests] --> [Approve/Reject Topups]
Admin Dashboard --> [Refund Requests] --> [Approve/Reject Refunds]
Admin Dashboard --> [Vendor Payouts] --> [Process Payouts]
Admin Dashboard --> [Announcements] --> [Create/Toggle/Delete Announcements]
Admin Dashboard --> [Payment Settings] --> [Update Payment Methods]
Admin Dashboard --> [Pricing] --> [Set Client/Vendor Pricing]
```

### Key Steps

1. **Authentication**: Login redirects to `/admin/dashboard`.
2. **User Management**: Create, freeze, unfreeze, or delete accounts (`/admin/accounts`).
3. **Client Links**: Manage public upload links for clients (`/admin/client-links`).
4. **Billing**: View daily ledgers and financial summaries (`/admin/billing`).
5. **Topup Requests**: Approve or reject client topup requests (`/admin/topup`).
6. **Refund Requests**: Process refund requests (`/admin/refunds`).
7. **Vendor Payouts**: Manage payouts to vendors (`/admin/finance/payouts`).
8. **Announcements**: Broadcast messages to users (`/admin/announcements`).
9. **Payment Settings**: Configure active payment methods (`/admin/payment-settings`).
10. **Pricing**: Adjust pricing for clients and vendors (`/admin/pricing`).

### Key Constraints

- Account deletion requires password confirmation for security.
- Freezing/unfreezing logs reasons in audit logs for traceability.
- Soft-deleted accounts with active orders cancel those orders and restore credits.

## Order Lifecycle

The order lifecycle is central to PlagExpert's operation, managed via the `OrderStatus` enum:

```
Pending --> Claimed --> Processing --> Delivered
   |          |            |            |
   v          v            v            v
Cancelled   Cancelled    Cancelled    (Final)
```

- **Pending**: Order created by client, awaiting vendor claim.
- **Claimed**: Vendor has claimed the order, locking it to them.
- **Processing**: Vendor has started work on the documents.
- **Delivered**: Reports uploaded and order completed, client notified.
- **Cancelled**: Order cancelled (by client if unclaimed, or by admin action).

### Key Lifecycle Rules

- Clients can cancel/delete only unclaimed `pending` orders, restoring credits.
- Vendors can unclaim orders before starting processing.
- Admin actions (e.g., account deletion) can cancel orders at any stage, with credit restoration.
- Audit logs track all state transitions for accountability.

## Best Practices in Workflows

- **Separation of Concerns**: Distinct dashboards and routes for each role prevent overlap and confusion.
- **State Management**: Enums and guarded transitions ensure order status integrity.
- **Credit Accounting**: Strict deduction and restoration logic prevents credit leaks or duplication.
- **Throttling**: Public endpoints are rate-limited to prevent abuse.
- **Notifications**: Automated Telegram alerts keep users informed of critical updates.
- **Auditability**: Every significant action (claim, delete, freeze) is logged with context for troubleshooting.
