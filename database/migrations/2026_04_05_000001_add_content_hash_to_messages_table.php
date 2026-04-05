<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('content_hash', 64)->nullable()->after('message')->index();
        });

        DB::table('messages')->orderBy('id')->chunk(200, function ($rows) {
            foreach ($rows as $row) {
                DB::table('messages')->where('id', $row->id)->update([
                    'content_hash' => hash('sha256', (string) $row->message),
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('content_hash');
        });
    }
};
