<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'telegram_chat_id')) {
                $table->string('telegram_chat_id', 64)->nullable()->after('phone');
            }

            if (! Schema::hasColumn('users', 'telegram_link_token')) {
                $table->string('telegram_link_token', 80)->nullable()->after(
                    Schema::hasColumn('users', 'telegram_chat_id') ? 'telegram_chat_id' : 'phone'
                );
            }

            if (! Schema::hasColumn('users', 'telegram_connected_at')) {
                $table->timestamp('telegram_connected_at')->nullable()->after('telegram_link_token');
            }
        });

        // Add the lookup index only when the token column exists and the index has not already been created.
        if (Schema::hasColumn('users', 'telegram_link_token')) {
            Schema::table('users', function (Blueprint $table) {
                $connection = Schema::getConnection();
                $schemaManager = method_exists($connection, 'getDoctrineSchemaManager')
                    ? $connection->getDoctrineSchemaManager()
                    : null;

                if ($schemaManager) {
                    $indexes = $schemaManager->listTableIndexes('users');
                    if (! array_key_exists('users_telegram_link_token_index', $indexes)) {
                        $table->index('telegram_link_token');
                    }
                    return;
                }

                // Fallback for environments without Doctrine: attempt to create the conventional Laravel index name once.
                try {
                    $table->index('telegram_link_token');
                } catch (\Throwable) {
                    // Ignore duplicate-index race conditions during startup retries.
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'telegram_link_token')) {
                try {
                    $table->dropIndex(['telegram_link_token']);
                } catch (\Throwable) {
                    // Ignore when the index was never created or has already been removed.
                }
            }

            $columnsToDrop = array_values(array_filter([
                Schema::hasColumn('users', 'telegram_chat_id') ? 'telegram_chat_id' : null,
                Schema::hasColumn('users', 'telegram_link_token') ? 'telegram_link_token' : null,
                Schema::hasColumn('users', 'telegram_connected_at') ? 'telegram_connected_at' : null,
            ]));

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
