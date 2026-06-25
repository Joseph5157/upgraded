# Portal PlagExpert — Telegram Inline Integration Plan for Codex

## Purpose

Add Telegram as a fast action and notification layer for Portal PlagExpert.

Telegram should **not replace the portal**. The portal remains the source of truth for clients, vendors, orders, files, payments, credits, and accounting. Telegram should provide:

- Instant order notifications
- Inline action buttons for admins, clients, and vendors
- Safe approval workflows
- Quick links back to the portal
- Optional inline search for admins/staff
- Optional Telegram Mini App entry points later

This document is written for Codex/Claude Code implementation.

---

## Existing Project Context

Portal PlagExpert is a plagiarism / AI report management portal with:

- Clients who upload files and consume credits
- Vendors who complete plagiarism/AI reports
- Admin/staff who manage payments, credits, assignments, reports, expenses, and statements
- Credit model: currently **1 credit = 1 uploaded file**
- Client balance must reduce when file is uploaded
- Client balance must return when an order/file is cancelled
- Vendor payable must be calculated only for successful/completed files, not failed/rejected files
- Payments include UPI, bank transfer, cash, and Razorpay
- Reports/statements should be daily, weekly, monthly, and on demand
- Admin dashboard should show received money, credit usage, vendor payable, vendor paid, gross profit, expenses, and balances

Assume backend is Laravel. If the actual file names differ, adapt the class names and paths to the current project structure.

---

## Telegram Feature Scope

There are two Telegram concepts we may use:

### 1. Inline Keyboard Buttons — highest priority

These are buttons under bot messages such as:

- `Approve Payment`
- `Reject`
- `View Order`
- `Download Report`
- `Accept Work`
- `Upload Report`
- `Mark Paid`

Telegram callback buttons do not send a visible chat message. Instead, the bot receives a callback query and the backend processes it.

### 2. Inline Mode — later priority

This lets a user type something like:

```text
@PlagExpertBot order PE-1025
@PlagExpertBot client ABC
@PlagExpertBot pending
```

The bot returns selectable results inside Telegram. This is powerful but riskier for data privacy, so only enable for admin/staff after permissions are strict.

---

## Recommended Rollout

### Phase 1 — Telegram Account Linking + Notifications

Implement:

- Link portal user to Telegram user ID
- Store Telegram chat ID
- Send notifications for important events
- Buttons only open portal pages; no sensitive state changes yet

Use cases:

- Client file uploaded
- Credit added
- Low credit alert
- Report completed
- Vendor assigned work
- Vendor uploaded report
- Admin daily summary

### Phase 2 — Safe Inline Buttons

Implement buttons that do not directly change money/accounting state:

- View order
- Open upload page
- Download completed report using signed URL
- Contact support
- Accept vendor work
- Reject vendor work with reason required inside portal

### Phase 3 — Admin Approval Buttons

Implement sensitive actions with token validation and confirmation:

- Approve payment and add credits
- Reject payment
- Approve vendor report
- Mark report failed
- Send report for rework
- Mark vendor payout as paid

Use two-step confirmation for sensitive actions.

### Phase 4 — Inline Mode Search

Admin/staff only:

- Search order status
- Search client balance
- Search vendor payable
- Show pending approvals
- Generate quick quotation text

### Phase 5 — Optional Telegram Mini App

Later, create mobile-friendly Telegram Mini App screens for:

- Client upload
- Vendor report upload
- Admin payment approval
- Quick order dashboard

Do not start here. Mini App is useful after core workflows are stable.

---

## High-Value Use Cases

## A. Client Order Created Notification

Trigger: client uploads a file/order.

Message:

```text
New Order Created
Order: PE-1025
Credits deducted: 1
Current balance: 39
Status: Pending assignment
```

Buttons:

```text
[View Order] [Cancel Request]
[Contact Support]
```

Rules:

- `View Order` opens the portal.
- `Cancel Request` should only be allowed before vendor starts work.
- If cancelled, credit must be returned.
- Cancellation action should require confirmation.

---

## B. Client Report Ready Notification

Trigger: admin approves vendor report and order becomes completed.

Message:

```text
Your Report is Ready
Order: PE-1025
Status: Completed
```

Buttons:

```text
[Download Report] [Raise Issue]
[View Credit Balance]
```

Rules:

- Download link must be signed and short-lived.
- Do not expose permanent storage URLs.
- `Raise Issue` should create an order support ticket/note.

---

## C. Admin Payment Approval

Trigger: client submits payment proof or Razorpay payment needs review.

Message:

```text
Payment Pending Approval
Client: ABC Research
Amount: ₹5,000
Credits requested: 100
Mode: UPI
Reference: UTR123456
```

Buttons:

```text
[Approve Credits] [Reject]
[Ask Clarification] [Open Payment]
```

Rules:

- Only admin/staff with permission can approve.
- Use two-step confirmation:
  - First tap: `Approve Credits`
  - Bot edits message or sends confirmation: `Confirm Add 100 Credits?`
  - Second tap: `Confirm`
- After approval:
  - Add client credits
  - Create payment record
  - Create credit ledger entry
  - Notify client
  - Update Telegram message to show approved status

---

## D. Vendor Work Assignment

Trigger: admin assigns order/file to vendor.

Message:

```text
New Work Assigned
Order: PE-1025
Client: Hidden or masked if required
Task: Plagiarism + AI Report
Deadline: Today 8:00 PM
```

Buttons:

```text
[Accept Work] [Reject Work]
[Open Upload Page]
```

Rules:

- Vendor can only act on orders assigned to them.
- If vendor accepts, mark assignment status as accepted.
- If vendor rejects, require reason in portal or show predefined reasons.
- Do not show client pricing to vendor.

---

## E. Vendor Report Submitted → Admin Review

Trigger: vendor uploads report.

Message to admin:

```text
Vendor Report Submitted
Order: PE-1025
Vendor: Ravi
Task: Plagiarism + AI Report
```

Buttons:

```text
[Approve Report] [Mark Failed]
[Send Rework] [Open Review]
```

Rules:

- `Approve Report` changes order/file status to completed/approved.
- `Mark Failed` should reduce or prevent vendor payable for that item.
- `Send Rework` assigns the report back to vendor without final completion.
- For failed/rework, capture reason.

---

## F. Daily Admin Summary

Trigger: scheduled job every night, or admin command `/summary today`.

Message:

```text
Portal PlagExpert Daily Summary
Date: 18 Jun 2026

Files uploaded: 40
Files completed: 32
Credits used: 40
Credits remaining across clients: 650
Payments received: ₹12,000
Vendor payable generated: ₹3,200
Vendor paid: ₹1,500
Gross profit estimate: ₹____
Pending reports: 8
```

Buttons:

```text
[Full Report] [Pending Orders]
[Vendor Payables] [Client Balances]
```

Rules:

- Telegram summary can show high-level numbers.
- Detailed statement must open portal with authentication.

---

## G. Low Credit Alert

Trigger: client credit balance <= configured threshold.

Message:

```text
Low Credit Alert
Your remaining credits: 5
```

Buttons:

```text
[Buy Credits] [View Statement]
[Contact Admin]
```

Rules:

- Threshold can be global or client-specific.
- Do not spam; send once per threshold crossing unless balance is topped up and falls again.

---

## Data Model Changes

Create migrations for these tables.

### 1. `telegram_accounts`

Purpose: link portal users to Telegram.

Suggested fields:

```php
Schema::create('telegram_accounts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('telegram_user_id')->unique();
    $table->string('chat_id')->nullable()->index();
    $table->string('username')->nullable();
    $table->string('first_name')->nullable();
    $table->string('last_name')->nullable();
    $table->timestamp('linked_at')->nullable();
    $table->timestamp('last_seen_at')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

Important:

- `telegram_user_id` is the identity.
- `chat_id` is where messages are sent.
- Do not trust username as identity because Telegram usernames can change.

---

### 2. `telegram_action_tokens`

Purpose: never place raw sensitive IDs/actions directly in callback payload.

```php
Schema::create('telegram_action_tokens', function (Blueprint $table) {
    $table->id();
    $table->uuid('token')->unique();
    $table->foreignId('created_for_user_id')->nullable()->constrained('users')->nullOnDelete();
    $table->string('telegram_user_id')->nullable()->index();
    $table->string('action_type')->index();
    $table->nullableMorphs('subject'); // order, payment, vendor assignment, payout, ticket, etc.
    $table->json('payload')->nullable();
    $table->string('required_role')->nullable();
    $table->timestamp('expires_at')->index();
    $table->timestamp('used_at')->nullable();
    $table->foreignId('used_by_user_id')->nullable()->constrained('users')->nullOnDelete();
    $table->string('status')->default('active'); // active, used, expired, revoked
    $table->timestamps();
});
```

Allowed `action_type` examples:

```text
order.view
order.cancel.request
order.cancel.confirm
payment.approve.request
payment.approve.confirm
payment.reject.request
vendor.assignment.accept
vendor.assignment.reject
vendor.report.approve.request
vendor.report.approve.confirm
vendor.report.fail.request
vendor.report.rework.request
vendor.payout.mark_paid.request
vendor.payout.mark_paid.confirm
support.issue.create
```

---

### 3. `telegram_messages`

Purpose: track Telegram messages so we can edit old messages after status changes.

```php
Schema::create('telegram_messages', function (Blueprint $table) {
    $table->id();
    $table->nullableMorphs('subject');
    $table->string('chat_id')->index();
    $table->string('message_id')->index();
    $table->string('message_type')->index();
    $table->json('meta')->nullable();
    $table->timestamps();
});
```

Example usage:

- After payment is approved in the portal, edit previous Telegram approval message to show `Approved`.
- After order completed, edit previous vendor assignment message to show `Completed`.

---

### 4. `telegram_event_logs`

Purpose: audit Telegram actions.

```php
Schema::create('telegram_event_logs', function (Blueprint $table) {
    $table->id();
    $table->string('telegram_user_id')->nullable()->index();
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $table->string('event_type')->index();
    $table->nullableMorphs('subject');
    $table->json('request_payload')->nullable();
    $table->json('response_payload')->nullable();
    $table->string('status')->default('success');
    $table->text('error_message')->nullable();
    $table->timestamps();
});
```

---

## Environment Variables

Add these to `.env.example`:

```env
TELEGRAM_BOT_TOKEN=
TELEGRAM_WEBHOOK_SECRET=
TELEGRAM_BOT_USERNAME=PlagExpertBot
TELEGRAM_ENABLED=true
TELEGRAM_ADMIN_CHAT_ID=
TELEGRAM_LOW_CREDIT_THRESHOLD=5
TELEGRAM_ACTION_TOKEN_TTL_MINUTES=30
TELEGRAM_DOWNLOAD_LINK_TTL_MINUTES=15
APP_PUBLIC_URL=https://your-domain.com
```

Rules:

- Never commit real bot token.
- Webhook secret must be long and random.
- Keep Telegram disabled by default in local/testing unless explicitly enabled.

---

## Laravel Structure

Create these files or equivalent.

```text
app/Services/Telegram/TelegramBotService.php
app/Services/Telegram/TelegramMessageBuilder.php
app/Services/Telegram/TelegramActionTokenService.php
app/Services/Telegram/TelegramPermissionService.php
app/Services/Telegram/TelegramInlineQueryService.php
app/Http/Controllers/TelegramWebhookController.php
app/Jobs/Telegram/SendTelegramMessageJob.php
app/Jobs/Telegram/EditTelegramMessageJob.php
app/Listeners/Telegram/SendOrderCreatedTelegramNotification.php
app/Listeners/Telegram/SendReportReadyTelegramNotification.php
app/Listeners/Telegram/SendPaymentPendingTelegramNotification.php
app/Listeners/Telegram/SendVendorAssignmentTelegramNotification.php
app/Listeners/Telegram/SendVendorReportSubmittedTelegramNotification.php
app/Console/Commands/TelegramSetWebhookCommand.php
app/Console/Commands/TelegramSendDailySummaryCommand.php
config/telegram.php
```

---

## Routes

Add webhook route outside CSRF protection or explicitly exclude it.

```php
Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle'])
    ->name('telegram.webhook');
```

Security:

- Validate `X-Telegram-Bot-Api-Secret-Token` header.
- Reject request if header is missing or invalid.
- Log invalid attempts.
- Do not process webhook if `TELEGRAM_ENABLED=false`.

---

## Webhook Setup Command

Create artisan command:

```bash
php artisan telegram:set-webhook
```

It should call Telegram `setWebhook` with:

- URL: `APP_PUBLIC_URL/api/telegram/webhook` or correct project route
- Secret token: `TELEGRAM_WEBHOOK_SECRET`
- Allowed updates:
  - `message`
  - `callback_query`
  - `inline_query`
  - `chosen_inline_result` if needed later

Also create:

```bash
php artisan telegram:delete-webhook
php artisan telegram:test-message {userId}
```

---

## Callback Data Rule

Do **not** use callback data like this:

```text
approve_payment_123
order_cancel_1025
vendor_paid_55
```

Instead:

```text
a:550e8400-e29b-41d4-a716-446655440000
```

Where the UUID maps to a row in `telegram_action_tokens`.

Processing flow:

1. Receive callback query.
2. Parse token from callback data.
3. Find token in database.
4. Check not expired.
5. Check not used.
6. Find linked portal user by Telegram user ID.
7. Check user role and permission.
8. Check subject still exists and is in valid state.
9. Execute action inside DB transaction.
10. Mark token used.
11. Answer callback query.
12. Edit Telegram message to reflect new state.
13. Write audit log.

---

## Sensitive Action Confirmation Flow

For money/accounting actions, require two taps.

Example: payment approval.

### First message

```text
Payment Pending Approval
Client: ABC Research
Amount: ₹5,000
Credits: 100
```

Buttons:

```text
[Approve Credits] [Reject]
```

### After admin taps Approve Credits

Bot edits/sends:

```text
Confirm Payment Approval?
Client: ABC Research
Amount: ₹5,000
Credits to add: 100

This will add credits and record payment.
```

Buttons:

```text
[Confirm Approval] [Cancel]
```

Only `Confirm Approval` performs final mutation.

Sensitive actions requiring confirmation:

- Payment approval
- Payment rejection if it changes status permanently
- Vendor payout marked paid
- Vendor report marked failed
- Order cancellation with credit refund
- Manual credit adjustment

---

## Permission Rules

### Admin

Can:

- Receive all operational alerts
- Approve/reject payments
- Approve/fail/rework vendor reports
- Mark vendor payout paid
- View daily summaries
- Use inline mode search

### Staff

Can only perform actions granted by permission flags:

- `payments.approve`
- `orders.manage`
- `vendor_reports.review`
- `vendor_payouts.manage`
- `reports.view`

### Client

Can:

- View own order
- Download own completed report
- Raise issue for own order
- View own credit statement
- Request cancellation only when allowed

Cannot:

- View vendor details
- View internal pricing/profit
- Approve anything

### Vendor

Can:

- View assigned work
- Accept/reject assigned work
- Open upload page
- See own payout summary

Cannot:

- See client rate
- See admin profit
- See other vendors' assignments
- Mark own report approved/failed

---

## Events to Connect

Wire Telegram notifications to existing domain events if available. If not available, create them.

Suggested events:

```text
OrderCreated
OrderCancelled
CreditsAdded
CreditsLow
PaymentSubmitted
PaymentApproved
VendorAssigned
VendorAssignmentAccepted
VendorReportSubmitted
VendorReportApproved
VendorReportFailed
ReportReadyForClient
VendorPayoutCreated
VendorPayoutPaid
DailySummaryReady
```

Use queued listeners for Telegram delivery.

Do not send Telegram messages directly inside controllers if avoidable.

---

## Message Builder Examples

Create centralized message templates so text is consistent.

Example method names:

```php
TelegramMessageBuilder::paymentPending($payment)
TelegramMessageBuilder::orderCreatedForClient($order)
TelegramMessageBuilder::reportReadyForClient($order)
TelegramMessageBuilder::vendorAssigned($assignment)
TelegramMessageBuilder::vendorReportSubmittedForAdmin($report)
TelegramMessageBuilder::dailySummary($summary)
```

Each builder returns:

```php
[
    'text' => '...',
    'reply_markup' => [
        'inline_keyboard' => [
            [
                ['text' => 'View Order', 'url' => $url],
                ['text' => 'Cancel Request', 'callback_data' => $token],
            ],
        ],
    ],
]
```

---

## Portal URL Rules

For normal portal links:

- Use authenticated portal URL.
- If user is not logged in, redirect to login.

For report downloads:

- Use signed route.
- Link must expire.
- Link must check user ownership/permission.

Example:

```php
URL::temporarySignedRoute(
    'client.reports.download',
    now()->addMinutes(config('telegram.download_link_ttl_minutes')),
    ['order' => $order->id]
);
```

---

## Inline Mode Plan

Enable inline mode only after admin/staff permission is solid.

Supported queries:

```text
order PE-1025
client ABC
vendor Ravi
pending
payments
summary today
quote 100
```

### `order PE-1025`

Returns one result:

```text
PE-1025 — Pending with Vendor
Client: ABC Research
Credits used: 1
Status: Assigned
```

Buttons:

```text
[Open Order]
```

### `client ABC`

Returns matching clients:

```text
ABC Research — 39 credits remaining
```

Buttons:

```text
[Open Client] [Statement]
```

### `pending`

Returns:

```text
Pending reports: 8
Pending payments: 3
Pending vendor reviews: 5
```

Buttons:

```text
[Open Dashboard]
```

### `quote 100`

Returns shareable quotation text:

```text
Plagiarism & AI Report Package
Credits: 100 files
Price: ₹____
Delivery: As discussed
```

Rules:

- Do not expose private business data in group chats unless requester is admin/staff.
- Set inline results as personal/private where applicable.
- For unknown users, return only: `Please link your Telegram account first.`

---

## Telegram Account Linking Flow

Recommended safe flow:

1. User logs into portal.
2. User opens profile/settings page.
3. Portal shows `Link Telegram` button.
4. Generate one-time link token:

```text
https://t.me/PlagExpertBot?start=link_<token>
```

5. User opens bot and taps Start.
6. Bot receives `/start link_<token>`.
7. Backend validates token.
8. Backend links Telegram user ID and chat ID to portal user.
9. Bot sends confirmation.

Message:

```text
Telegram linked successfully.
You will now receive Portal PlagExpert notifications here.
```

Rules:

- Link token expires in 10–15 minutes.
- Token can be used only once.
- User can unlink Telegram from portal.
- Re-linking should invalidate the old Telegram account link unless multiple accounts are intentionally supported.

---

## Bot Commands

Add commands via BotFather and support them in webhook.

```text
start - Link account or open help
help - Show available actions
balance - Client credit balance
orders - Recent client orders or vendor assignments
pending - Admin pending items
summary - Admin daily summary
unlink - Unlink Telegram account
```

Role-specific behavior:

- Client `/balance` returns own balance.
- Vendor `/orders` returns assigned work.
- Admin `/pending` returns pending approvals.
- Unknown user gets link instructions.

---

## Accounting Rules for Telegram Actions

Telegram actions must use the same services as portal actions.

Do not duplicate business logic inside Telegram controller.

For example:

```php
PaymentApprovalService::approve($payment, $adminUser);
VendorReportReviewService::approve($report, $adminUser);
OrderCancellationService::cancel($order, $user);
VendorPayoutService::markPaid($payout, $adminUser);
```

This ensures:

- Credit ledger remains correct
- Payment records remain correct
- Vendor payable remains correct
- Audit trail is consistent
- Portal and Telegram cannot drift apart

---

## Required Services

### `TelegramBotService`

Responsibilities:

- Send message
- Edit message
- Answer callback query
- Answer inline query
- Set webhook
- Delete webhook
- Handle Telegram API errors

Methods:

```php
sendMessage(string $chatId, string $text, array $replyMarkup = []): array
editMessageText(string $chatId, string $messageId, string $text, array $replyMarkup = []): array
answerCallbackQuery(string $callbackQueryId, string $text = '', bool $showAlert = false): array
answerInlineQuery(string $inlineQueryId, array $results, bool $isPersonal = true): array
setWebhook(): array
deleteWebhook(): array
```

### `TelegramActionTokenService`

Responsibilities:

- Create action token
- Validate action token
- Mark token used
- Expire old tokens
- Revoke tokens when subject state changes

### `TelegramPermissionService`

Responsibilities:

- Resolve Telegram user to portal user
- Check role permissions
- Check subject ownership
- Deny unauthorized actions

### `TelegramInlineQueryService`

Responsibilities:

- Parse inline query text
- Return safe results based on role
- Avoid exposing sensitive info

---

## Testing Plan

Create tests for:

### Webhook security

- Reject missing secret token
- Reject invalid secret token
- Accept valid secret token
- Do not process when Telegram disabled

### Linking

- User can link Telegram with valid token
- Expired token fails
- Reused token fails
- Unknown start payload shows help

### Callback actions

- Expired action token fails
- Used action token fails
- Wrong Telegram user fails
- Unauthorized role fails
- Correct admin can approve payment
- Correct client can view own order
- Client cannot view another client's order
- Vendor can accept own assignment
- Vendor cannot accept another vendor's assignment

### Accounting safety

- Payment approval through Telegram adds credits exactly once
- Double tap does not double-add credits
- Order cancellation returns credit exactly once
- Vendor report approved creates payable once
- Failed report does not create payable
- Vendor payout marked paid exactly once

### Inline mode

- Unknown Telegram user gets link prompt
- Client cannot search other clients
- Vendor cannot search client balances
- Admin can search order/client/vendor
- Inline query results are personal where sensitive

---

## UI/UX Notes for Telegram Messages

Keep messages short and action-oriented.

Good:

```text
Payment Pending Approval
Client: ABC Research
Amount: ₹5,000
Credits: 100
Mode: UPI
```

Bad:

```text
Dear admin, there is a new payment request submitted by the client and you are requested to kindly open the dashboard and check the details...
```

Button rules:

- Maximum 2 buttons per row for readability.
- Put destructive buttons after safe buttons.
- Use confirmation for destructive/money actions.
- After action, edit the old message to avoid duplicate decisions.

---

## Security Checklist

- Validate webhook secret header.
- Store Telegram bot token only in env.
- Link Telegram ID to portal user securely.
- Never trust Telegram username for identity.
- Never put raw entity IDs in callback data for sensitive actions.
- Use short-lived action tokens.
- Mark tokens as used inside DB transaction.
- Use idempotency for payment approval and payout actions.
- Do not expose client prices to vendors.
- Do not expose vendor payable to clients.
- Use signed temporary download links.
- Log every Telegram action.
- Rate-limit webhook processing if needed.
- Queue outbound Telegram messages.
- Handle Telegram API failures gracefully.

---

## Codex Implementation Prompt

Use this prompt in Codex:

```text
You are working on the Portal PlagExpert Laravel project. Implement Telegram inline integration as a safe notification and action layer, not as a replacement for the portal.

Read the existing codebase first and identify the models/services for users, clients, vendors, orders/files, payments, credits, reports, vendor assignments, vendor payouts, and accounting ledger. Reuse existing business services wherever possible. Do not duplicate credit/payment/vendor payable logic inside Telegram controllers.

Implement Phase 1 and Phase 2 first:
1. Add config/telegram.php and .env.example variables.
2. Add migrations/models for telegram_accounts, telegram_action_tokens, telegram_messages, and telegram_event_logs.
3. Add TelegramBotService, TelegramMessageBuilder, TelegramActionTokenService, TelegramPermissionService, TelegramWebhookController, and queued send/edit jobs.
4. Add secure POST /telegram/webhook route. Exclude CSRF only for this route. Validate X-Telegram-Bot-Api-Secret-Token.
5. Add artisan commands telegram:set-webhook, telegram:delete-webhook, and telegram:test-message.
6. Add account linking flow using /start link_<token> generated from authenticated portal profile/settings page.
7. Send Telegram notifications for order created, payment submitted, credits added, low credit, vendor assigned, vendor report submitted, and report ready.
8. Add inline keyboard buttons for safe actions: View Order, Download Report, Open Upload Page, Contact Support, Accept Work.
9. All callback_data must use a short token stored in telegram_action_tokens, never raw IDs such as approve_payment_123.
10. Add permission checks based on linked Telegram user and portal role. Client can only access own orders/reports. Vendor can only access assigned work. Admin/staff require explicit permissions.
11. Add tests for webhook secret validation, linking, callback token validation, permission denial, vendor accept work, and signed report download link generation.
12. Keep all Telegram outbound messages queued. Log failures to telegram_event_logs.

After Phase 1 and Phase 2 are working, prepare but do not enable Phase 3 sensitive admin actions unless explicitly requested. Phase 3 actions must have two-step confirmation and idempotency: approve payment/add credits, reject payment, approve vendor report, mark report failed, send rework, and mark vendor payout paid.

Acceptance criteria:
- No Telegram action can change credits/payments/vendor payable without using existing business services.
- Duplicate callback taps cannot double-add credits or double-pay vendors.
- Unknown or unlinked Telegram users cannot access private data.
- Vendors cannot see client rates or admin profit.
- Clients cannot see vendor payable or other clients' data.
- Admin daily summary opens detailed portal pages for sensitive details.
```

---

## Acceptance Criteria

The implementation is acceptable only if:

- Telegram account linking works.
- Webhook secret validation works.
- Basic notifications are queued and delivered.
- Inline keyboard buttons render correctly.
- Safe callback actions are processed securely.
- All callback actions are logged.
- Unauthorized users are denied.
- Expired/used action tokens are denied.
- Payment/credit/vendor payable logic remains centralized in domain services.
- Tests cover the main security and accounting edge cases.

---

## Official Telegram References

- Telegram Bot API: https://core.telegram.org/bots/api
- Inline keyboards and callback buttons: https://core.telegram.org/bots/2-0-intro
- Telegram Mini Apps: https://core.telegram.org/bots/webapps

