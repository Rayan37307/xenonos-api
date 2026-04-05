<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->text('overview')->nullable();
            $table->text('objectives')->nullable();
            $table->string('priority')->default('medium');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('actual_budget', 12, 2)->nullable();
            $table->integer('progress')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('project_id');
            $table->index('priority');
        });

        Schema::create('project_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type')->default('milestone');
            $table->timestamp('event_date');
            $table->timestamp('end_date')->nullable();
            $table->string('color')->default('#3b82f6');
            $table->boolean('is_completed')->default(false);
            $table->timestamps();

            $table->index('project_id');
            $table->index('event_date');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_events');
        Schema::dropIfExists('project_details');
    }
};
