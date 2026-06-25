<?php

namespace App\Jobs\Telegram;

use App\Models\TelegramMessage;
use App\Services\TelegramService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Queued job to send a Telegram message and optionally record it
 * in telegram_messages for later editing.
 *
 * Usage:
 *   SendTelegramMessageJob::dispatch(
 *       chatId: $chatId,
 *       message: $messageBuilder->orderCreatedForClient($order),
 *       subjectType: Order::class,
 *       subjectId: $order->id,
 *       messageType: 'order.created',
 *   );
 */
class SendTelegramMessageJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public bool $deleteWhenMissingModels = true;
    public int  $tries   = 3;
    public int  $timeout = 30;
    public array $backoff = [10, 30, 60];

    /**
     * @param  string       $chatId       Telegram chat ID to send to
     * @param  array        $message      Message array from TelegramMessageBuilder (text + reply_markup + parse_mode)
     * @param  string|null  $subjectType  Model class name for the polymorphic subject
     * @param  int|null     $subjectId    Model primary key for the polymorphic subject
     * @param  string|null  $messageType  E.g. 'order.created', 'payment.pending'
     */
    public function __construct(
        public readonly string $chatId,
        public readonly array $message,
        public readonly ?string $subjectType = null,
        public readonly ?int $subjectId = null,
        public readonly ?string $messageType = null,
    ) {}

    public function handle(TelegramService $telegram): void
    {
        $text       = $this->message['text'] ?? '';
        $extraOpts  = array_diff_key($this->message, ['text' => null]);

        $messageId = $telegram->sendMessage($this->chatId, $text, $extraOpts);

        if (! $messageId) {
            Log::warning('SendTelegramMessageJob: sendMessage returned false.', [
                'chat_id'      => $this->chatId,
                'message_type' => $this->messageType,
            ]);
            // Throw so the job retries
            throw new \RuntimeException('Telegram sendMessage failed for chat_id: ' . $this->chatId);
        }

        // Record sent message for future editing (only when subject is known)
        if ($this->subjectType && $this->subjectId && $this->messageType) {
            try {
                TelegramMessage::create([
                    'subject_type' => $this->subjectType,
                    'subject_id'   => $this->subjectId,
                    'chat_id'      => $this->chatId,
                    'message_id'   => (string) $messageId,
                    'message_type' => $this->messageType,
                    'meta'         => isset($this->message['reply_markup'])
                        ? ['reply_markup' => $this->message['reply_markup']]
                        : null,
                ]);
            } catch (\Throwable $e) {
                Log::warning('SendTelegramMessageJob: failed to record TelegramMessage.', [
                    'chat_id'      => $this->chatId,
                    'message_type' => $this->messageType,
                    'error'        => $e->getMessage(),
                ]);
            }
        }
    }
}
