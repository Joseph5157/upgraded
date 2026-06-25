<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tracks Telegram messages we have sent so they can be edited later
     * when the underlying subject's state changes (e.g. payment approved).
     *
     * This supersedes the basic `telegram_sent_messages` table, which
     * lacked subject polymorphism and message_type tracking.
     */
    public function up(): void
    {
        Schema::create('telegram_messages', function (Blueprint $table) {
            $table->id();

            // Polymorphic subject: Order, ClientPayment, VendorPayout, etc.
            $table->nullableMorphs('subject');

            $table->string('chat_id', 64)->index();
            $table->string('message_id', 64)->index();

            // Classifies what was sent, e.g. order.created, payment.pending, report.ready
            $table->string('message_type', 64)->index();

            // Snapshot of the reply_markup or extra data useful for re-editing
            $table->json('meta')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_messages');
    }
};
