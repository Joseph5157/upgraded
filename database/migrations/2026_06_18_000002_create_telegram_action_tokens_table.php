<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_action_tokens', function (Blueprint $table) {
            $table->id();
            $table->uuid('token')->unique();

            // The portal user the action was created for (e.g. the admin who will receive the button)
            $table->foreignId('created_for_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // The Telegram user ID whose tap is allowed to redeem this token
            $table->string('telegram_user_id', 64)->nullable()->index();

            // Dot-separated action string, e.g. payment.approve.confirm
            $table->string('action_type')->index();

            // Polymorphic subject: the Order, ClientPayment, VendorPayout, etc.
            $table->nullableMorphs('subject');

            // Arbitrary extra data needed to execute the action
            $table->json('payload')->nullable();

            // Portal role required to redeem, e.g. 'admin', 'vendor', 'client'
            $table->string('required_role', 32)->nullable();

            $table->timestamp('expires_at')->index();
            $table->timestamp('used_at')->nullable();

            $table->foreignId('used_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // active | used | expired | revoked
            $table->string('status', 16)->default('active')->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_action_tokens');
    }
};
