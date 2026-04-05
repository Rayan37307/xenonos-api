<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('activitylog.table_name');
        $callback = function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->nullableMorphs('subject', 'subject');
            $table->string('event')->nullable();
            $table->nullableMorphs('causer', 'causer');
            $table->json('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->timestamps();

            $table->index('log_name');
        };

        if ($connection = config('activitylog.database_connection')) {
            Schema::connection($connection)->create($tableName, $callback);
        } else {
            Schema::create($tableName, $callback);
        }
    }

    public function down(): void
    {
        $tableName = config('activitylog.table_name');

        if ($connection = config('activitylog.database_connection')) {
            Schema::connection($connection)->dropIfExists($tableName);
        } else {
            Schema::dropIfExists($tableName);
        }
    }
};
