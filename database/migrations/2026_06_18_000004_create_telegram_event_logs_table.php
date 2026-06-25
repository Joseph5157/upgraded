<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Immutable audit log for every Telegram action processed by the webhook.
     */
    public function up(): void
    {
        Schema::create('telegram_event_logs', function (Blueprint $table) {
            $table->id();

            $table->string('telegram_user_id', 64)->nullable()->index();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // E.g. callback.payment.approve.confirm, link.success, link.expired_token
            $table->string('event_type', 100)->index();

            // Polymorphic subject affected by the event
            $table->nullableMorphs('subject');

            // Raw Telegram payload (sanitised — no tokens, no secrets)
            $table->json('request_payload')->nullable();

            // What the action service returned
            $table->json('response_payload')->nullable();

            // success | denied | expired | error
            $table->string('status', 16)->default('success')->index();

            $table->text('error_message')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_event_logs');
    }
};
