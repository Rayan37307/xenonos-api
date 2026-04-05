<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->enum('status', ['active', 'inactive'])->default('active')->after('address');
            $table->text('notes')->nullable()->after('status');
            $table->softDeletes();

            $table->index('company_name');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex(['company_name']);
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
            $table->dropColumn(['status', 'notes', 'deleted_at']);
        });
    }
};
