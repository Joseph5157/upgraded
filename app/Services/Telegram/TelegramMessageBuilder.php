<?php

namespace App\Services\Telegram;

use App\Models\ClientPayment;
use App\Models\Order;
use App\Models\TelegramActionToken;
use App\Models\User;
use App\Models\VendorPayout;

/**
 * Centralised message templates for all Portal Telegram notifications.
 *
 * Each method returns an array ready to spread into TelegramService::sendMessage():
 *   ['text' => '...', 'reply_markup' => [...], 'parse_mode' => 'HTML']
 *
 * Inline keyboard buttons either carry:
 *   - 'url'            → safe, always opens the portal
 *   - 'callback_data'  → a:uuid token (from TelegramActionTokenService::callbackData)
 */
class TelegramMessageBuilder
{
    public function __construct(
        protected readonly TelegramActionTokenService $tokenService,
    ) {}

    // ──────────────────────────────────────────────────────────────
    // A. Client — Order Created
    // ──────────────────────────────────────────────────────────────

    /**
     * Sent to a client when they create a new order.
     */
    public function orderCreatedForClient(Order $order, ?TelegramActionToken $cancelToken = null): array
    {
        $text = "<b>New Order Created</b>\n"
            . "Order: <code>{$order->order_number}</code>\n"
            . "Credits deducted: 1\n"
            . "Current balance: {$order->client->credit_balance}\n"
            . "Status: Pending assignment";

        $buttons = [
            [
                ['text' => 'View Order', 'url' => $this->portalUrl("/orders/{$order->id}")],
                ['text' => 'Check Credits', 'url' => $this->portalUrl('/client-panel')],
            ],
        ];

        if ($cancelToken) {
            $buttons[] = [
                ['text' => 'Cancel Request', 'callback_data' => $this->tokenService->callbackData($cancelToken)],
                ['text' => 'Contact Support', 'url' => $this->portalUrl('/support')],
            ];
        }

        return $this->build($text, $buttons);
    }

    // ──────────────────────────────────────────────────────────────
    // B. Client — Report Ready
    // ──────────────────────────────────────────────────────────────

    /**
     * Sent to a client when their report is completed and approved.
     *
     * @param  string  $downloadUrl  Signed temporary URL (expires per config telegram.download_link_ttl_minutes)
     */
    public function reportReadyForClient(Order $order, string $downloadUrl): array
    {
        $text = "<b>Your Report is Ready</b>\n"
            . "Order: <code>{$order->order_number}</code>\n"
            . "Status: Completed";

        $buttons = [
            [
                ['text' => 'Download Report', 'url' => $downloadUrl],
                ['text' => 'Raise Issue', 'url' => $this->portalUrl('/support')],
            ],
            [
                ['text' => 'View Order', 'url' => $this->portalUrl("/orders/{$order->id}")],
                ['text' => 'Check Credits', 'url' => $this->portalUrl('/client-panel')],
            ],
        ];

        return $this->build($text, $buttons);
    }

    // ──────────────────────────────────────────────────────────────
    // C. Admin — Payment Pending Approval
    // ──────────────────────────────────────────────────────────────

    /**
     * Sent to admin when a payment needs approval.
     *
     * $approveRequestToken is the first-step token; tapping it sends the confirmation message.
     * $rejectToken is direct — rejection requires a reason in the portal.
     */
    public function paymentPending(
        ClientPayment $payment,
        TelegramActionToken $approveRequestToken,
        TelegramActionToken $rejectToken,
    ): array {
        $client  = $payment->client;
        $amount  = number_format($payment->amount_received, 2);
        $credits = $payment->credits_added;
        $mode    = ucfirst($payment->payment_mode ?? 'unknown');
        $ref     = $payment->transaction_id ?? '—';

        $text = "<b>Payment Pending Approval</b>\n"
            . "Client: {$client->name}\n"
            . "Amount: ₹{$amount}\n"
            . "Credits requested: {$credits}\n"
            . "Mode: {$mode}\n"
            . "Reference: {$ref}";

        $buttons = [
            [
                ['text' => 'Approve Credits', 'callback_data' => $this->tokenService->callbackData($approveRequestToken)],
                ['text' => 'Reject',           'callback_data' => $this->tokenService->callbackData($rejectToken)],
            ],
            [
                ['text' => 'Open Payment', 'url' => $this->portalUrl("/admin/finance/client-payments/{$payment->id}")],
            ],
        ];

        return $this->build($text, $buttons);
    }

    /**
     * Confirmation step after admin taps "Approve Credits".
     *
     * $confirmToken is the final action token; tapping it executes the payment.
     */
    public function paymentApprovalConfirm(ClientPayment $payment, TelegramActionToken $confirmToken): array
    {
        $client  = $payment->client;
        $amount  = number_format($payment->amount_received, 2);
        $credits = $payment->credits_added;

        $text = "<b>Confirm Payment Approval?</b>\n"
            . "Client: {$client->name}\n"
            . "Amount: ₹{$amount}\n"
            . "Credits to add: {$credits}\n\n"
            . "<i>This will add credits and record the payment.</i>";

        $buttons = [
            [
                ['text' => 'Confirm Approval', 'callback_data' => $this->tokenService->callbackData($confirmToken)],
                ['text' => 'Cancel',           'url'           => $this->portalUrl("/admin/finance/client-payments/{$payment->id}")],
            ],
        ];

        return $this->build($text, $buttons);
    }

    /**
     * Updated message after payment is approved (replaces the pending message).
     */
    public function paymentApproved(ClientPayment $payment): array
    {
        $client  = $payment->client;
        $amount  = number_format($payment->amount_received, 2);
        $credits = $payment->credits_added;

        $text = "<b>Payment Approved</b>\n"
            . "Client: {$client->name}\n"
            . "Amount: ₹{$amount}\n"
            . "Credits added: {$credits}\n"
            . "Status: Approved";

        return $this->build($text, []);
    }

    // ──────────────────────────────────────────────────────────────
    // D. Vendor — Work Assignment
    // ──────────────────────────────────────────────────────────────

    /**
     * Sent to a vendor when they are assigned an order.
     *
     * @param  TelegramActionToken  $acceptToken  Vendor assignment accept token
     * @param  TelegramActionToken  $rejectToken  Vendor assignment reject token
     */
    public function vendorAssigned(
        Order $order,
        TelegramActionToken $acceptToken,
        TelegramActionToken $rejectToken,
    ): array {
        $deadline = $order->sla_deadline
            ? $order->sla_deadline->format('D d M, g:i A')
            : 'As discussed';

        $text = "<b>New Work Assigned</b>\n"
            . "Order: <code>{$order->order_number}</code>\n"
            . "Task: {$order->report_type}\n"
            . "Deadline: {$deadline}";

        $buttons = [
            [
                ['text' => 'Accept Work',  'callback_data' => $this->tokenService->callbackData($acceptToken)],
                ['text' => 'Reject Work',  'callback_data' => $this->tokenService->callbackData($rejectToken)],
            ],
            [
                ['text' => 'Open Upload Page', 'url' => $this->portalUrl("/vendor/orders/{$order->id}")],
            ],
        ];

        return $this->build($text, $buttons);
    }

    // ──────────────────────────────────────────────────────────────
    // E. Admin — Vendor Report Submitted
    // ──────────────────────────────────────────────────────────────

    /**
     * Sent to admin when a vendor submits a report for review.
     */
    public function vendorReportSubmittedForAdmin(
        Order $order,
        User $vendor,
        TelegramActionToken $approveRequestToken,
        TelegramActionToken $failToken,
        TelegramActionToken $reworkToken,
    ): array {
        $text = "<b>Vendor Report Submitted</b>\n"
            . "Order: <code>{$order->order_number}</code>\n"
            . "Vendor: {$vendor->name}\n"
            . "Task: {$order->report_type}";

        $buttons = [
            [
                ['text' => 'Approve Report', 'callback_data' => $this->tokenService->callbackData($approveRequestToken)],
                ['text' => 'Mark Failed',    'callback_data' => $this->tokenService->callbackData($failToken)],
            ],
            [
                ['text' => 'Send Rework',  'callback_data' => $this->tokenService->callbackData($reworkToken)],
                ['text' => 'Open Review',  'url'           => $this->portalUrl("/admin/orders/{$order->id}")],
            ],
        ];

        return $this->build($text, $buttons);
    }

    // ──────────────────────────────────────────────────────────────
    // F. Admin — Daily Summary
    // ──────────────────────────────────────────────────────────────

    /**
     * Daily summary message.
     *
     * $summary must have keys: date, uploaded, completed, credits_used,
     *   credits_remaining, payments_received, vendor_payable, vendor_paid, pending_reports
     */
    public function dailySummary(array $summary): array
    {
        $date              = $summary['date'] ?? now()->format('d M Y');
        $uploaded          = $summary['uploaded'] ?? 0;
        $completed         = $summary['completed'] ?? 0;
        $creditsUsed       = $summary['credits_used'] ?? 0;
        $creditsRemaining  = $summary['credits_remaining'] ?? 0;
        $paymentsReceived  = number_format($summary['payments_received'] ?? 0, 2);
        $vendorPayable     = number_format($summary['vendor_payable'] ?? 0, 2);
        $vendorPaid        = number_format($summary['vendor_paid'] ?? 0, 2);
        $pendingReports    = $summary['pending_reports'] ?? 0;

        $text = "<b>Portal PlagExpert Daily Summary</b>\n"
            . "Date: {$date}\n\n"
            . "Files uploaded: {$uploaded}\n"
            . "Files completed: {$completed}\n"
            . "Credits used: {$creditsUsed}\n"
            . "Credits remaining across clients: {$creditsRemaining}\n"
            . "Payments received: ₹{$paymentsReceived}\n"
            . "Vendor payable generated: ₹{$vendorPayable}\n"
            . "Vendor paid: ₹{$vendorPaid}\n"
            . "Pending reports: {$pendingReports}";

        $buttons = [
            [
                ['text' => 'Full Report',     'url' => $this->portalUrl('/admin/finance/dashboard')],
                ['text' => 'Pending Orders',  'url' => $this->portalUrl('/admin/orders?status=pending')],
            ],
            [
                ['text' => 'Vendor Payables', 'url' => $this->portalUrl('/admin/finance/vendor-earnings')],
                ['text' => 'Client Balances', 'url' => $this->portalUrl('/admin/finance/client-balances')],
            ],
        ];

        return $this->build($text, $buttons);
    }

    // ──────────────────────────────────────────────────────────────
    // G. Client — Low Credit Alert
    // ──────────────────────────────────────────────────────────────

    public function lowCreditAlert(int $remainingCredits): array
    {
        $text = "<b>Low Credit Alert</b>\n"
            . "Your remaining credits: <b>{$remainingCredits}</b>";

        $buttons = [
            [
                ['text' => 'View Statement',  'url' => $this->portalUrl('/subscription')],
                ['text' => 'Contact Admin',   'url' => $this->portalUrl('/support')],
            ],
        ];

        return $this->build($text, $buttons);
    }

    // ──────────────────────────────────────────────────────────────
    // H. Client — Credits Added
    // ──────────────────────────────────────────────────────────────

    public function creditsAdded(int $creditsAdded, int $newBalance): array
    {
        $text = "<b>Credits Added</b>\n"
            . "Credits added: <b>{$creditsAdded}</b>\n"
            . "New balance: <b>{$newBalance}</b>";

        $buttons = [
            [
                ['text' => 'View Statement', 'url' => $this->portalUrl('/subscription')],
            ],
        ];

        return $this->build($text, $buttons);
    }

    // ──────────────────────────────────────────────────────────────
    // Vendor Payout — Mark Paid Confirmation
    // ──────────────────────────────────────────────────────────────

    public function vendorPayoutMarkPaid(
        VendorPayout $payout,
        User $vendor,
        TelegramActionToken $confirmToken,
    ): array {
        $amount = number_format($payout->amount, 2);

        $text = "<b>Confirm Vendor Payout?</b>\n"
            . "Vendor: {$vendor->name}\n"
            . "Amount: ₹{$amount}\n\n"
            . "<i>This will mark the payout as paid.</i>";

        $buttons = [
            [
                ['text' => 'Confirm Paid', 'callback_data' => $this->tokenService->callbackData($confirmToken)],
                ['text' => 'Cancel',       'url'           => $this->portalUrl("/admin/finance/payouts/{$payout->id}")],
            ],
        ];

        return $this->build($text, $buttons);
    }

    // ──────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────

    /**
     * Build the final message array ready for TelegramService::sendMessage().
     */
    protected function build(string $text, array $inlineKeyboardRows): array
    {
        $message = [
            'text'       => $text,
            'parse_mode' => 'HTML',
        ];

        if ($inlineKeyboardRows !== []) {
            $message['reply_markup'] = ['inline_keyboard' => $inlineKeyboardRows];
        }

        return $message;
    }

    /**
     * Build an absolute portal URL.
     */
    protected function portalUrl(string $path): string
    {
        return rtrim(config('app.url'), '/') . $path;
    }
}
