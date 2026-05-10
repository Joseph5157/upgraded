<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_sent_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('chat_id');
            $table->unsignedBigInteger('message_id');
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamps();

            $table->index('sent_at');
            $table->index('chat_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_sent_messages');
    }
};
