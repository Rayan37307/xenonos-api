<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Note: File uploads are handled by Spatie Media Library.
     * This migration is kept for reference but not used.
     */
    public function up(): void
    {
        // Files table is handled by Spatie Media Library
        // This migration is intentionally left empty
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to reverse
    }
};
