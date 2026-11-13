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
        Schema::create('visit_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_visit_schedule_id')->unique()->constrained('store_visit_schedules')->cascadeOnDelete();
            $table->foreignId('supervisor_id')->constrained('users');
            $table->enum('audit_type', ['treasury', 'cashier']);
            $table->enum('status', ['draft', 'submitted', 'reopened'])->default('draft');
            $table->timestamp('actual_started_at')->nullable();
            $table->timestamp('actual_ended_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->text('summary_remarks')->nullable();
            $table->foreignId('reopened_by')->nullable()->constrained('users');
            $table->timestamp('reopened_at')->nullable();
            $table->text('reopen_reason')->nullable();
            $table->timestamps();

            $table->index(['supervisor_id', 'status']);
            $table->index(['audit_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visit_audits');
    }
};

