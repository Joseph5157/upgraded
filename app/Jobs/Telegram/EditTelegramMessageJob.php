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
 * Edit a previously sent Telegram message when the subject's state changes.
 *
 * Finds all recorded TelegramMessages for the given subject/type, then
 * calls editMessageText to update them all.
 *
 * Usage:
 *   EditTelegramMessageJob::dispatch(
 *       subjectType: ClientPayment::class,
 *       subjectId: $payment->id,
 *       messageType: 'payment.pending',
 *       newText: $messageBuilder->paymentApproved($payment)['text'],
 *       newReplyMarkup: [],  // empty = remove buttons
 *   );
 */
class EditTelegramMessageJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public bool $deleteWhenMissingModels = true;
    public int  $tries   = 2;
    public int  $timeout = 20;

    /**
     * @param  string      $subjectType    Model class name
     * @param  int         $subjectId      Model primary key
     * @param  string      $messageType    Type to look up in telegram_messages
     * @param  string      $newText        New message text (HTML)
     * @param  array|null  $newReplyMarkup New reply_markup or null to leave unchanged; [] to remove buttons
     */
    public function __construct(
        public readonly string $subjectType,
        public readonly int $subjectId,
        public readonly string $messageType,
        public readonly string $newText,
        public readonly ?array $newReplyMarkup = null,
    ) {}

    public function handle(TelegramService $telegram): void
    {
        $records = TelegramMessage::where('subject_type', $this->subjectType)
            ->where('subject_id', $this->subjectId)
            ->where('message_type', $this->messageType)
            ->get();

        if ($records->isEmpty()) {
            Log::info('EditTelegramMessageJob: no messages found to edit.', [
                'subject_type' => $this->subjectType,
                'subject_id'   => $this->subjectId,
                'message_type' => $this->messageType,
            ]);
            return;
        }

        foreach ($records as $record) {
            $replyMarkup = $this->newReplyMarkup;

            // null means "leave buttons unchanged" — pass the stored markup
            if ($replyMarkup === null) {
                $replyMarkup = $record->meta['reply_markup'] ?? null;
            }

            $result = $telegram->editMessageText(
                $record->chat_id,
                $record->message_id,
                $this->newText,
                $replyMarkup !== null ? $replyMarkup : null,
            );

            if ($result) {
                Log::info('EditTelegramMessageJob: message edited.', [
                    'chat_id'    => $record->chat_id,
                    'message_id' => $record->message_id,
                ]);
            } else {
                Log::warning('EditTelegramMessageJob: editMessageText failed.', [
                    'chat_id'    => $record->chat_id,
                    'message_id' => $record->message_id,
                ]);
            }
        }
    }
}
