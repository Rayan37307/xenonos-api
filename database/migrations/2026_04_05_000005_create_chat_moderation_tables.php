<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('reason')->nullable();
            $table->string('flagged_by_type')->nullable();
            $table->unsignedBigInteger('flagged_by_id')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->unique(['message_id', 'user_id']);
            $table->index('is_resolved');
        });

        Schema::create('user_mutes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('muted_user_id')->constrained('users')->onDelete('cascade');
            $table->string('reason')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'muted_user_id']);
            $table->index('expires_at');
        });

        Schema::create('user_bans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('reason')->nullable();
            $table->string('ban_type')->default('temporary');
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_permanent')->default(false);
            $table->string('banned_by_type')->nullable();
            $table->unsignedBigInteger('banned_by_id')->nullable();
            $table->timestamps();

            $table->index('expires_at');
            $table->index('is_permanent');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->boolean('is_deleted')->default(false)->after('is_read');
            $table->text('delete_reason')->nullable()->after('is_deleted');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null')->after('delete_reason');
            $table->timestamp('deleted_at')->nullable()->after('deleted_by');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['is_deleted', 'delete_reason', 'deleted_by', 'deleted_at']);
        });

        Schema::dropIfExists('user_bans');
        Schema::dropIfExists('user_mutes');
        Schema::dropIfExists('message_flags');
    }
};
