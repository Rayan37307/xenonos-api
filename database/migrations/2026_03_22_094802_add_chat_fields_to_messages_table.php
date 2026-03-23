<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('channel_id')->nullable()->after('project_id')->constrained('channels')->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->after('channel_id')->constrained('conversations')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['channel_id']);
            $table->dropForeign(['conversation_id']);
            $table->dropColumn(['channel_id', 'conversation_id']);
        });
    }
};
