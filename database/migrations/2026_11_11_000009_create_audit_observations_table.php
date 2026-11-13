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
        Schema::create('audit_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_visit_schedule_id')->constrained('store_visit_schedules')->cascadeOnDelete();
            $table->foreignId('supervisor_id')->constrained('users');
            $table->enum('audit_type', ['treasury', 'cashier']);
            $table->string('personnel_name', 150);
            $table->text('issue_description');
            $table->text('action_required');
            $table->date('target_escalation_date');
            $table->text('resolution_details')->nullable();
            $table->date('date_resolved')->nullable();
            $table->text('areas_for_improvement')->nullable();
            $table->enum('status', ['open', 'escalated', 'resolved'])->default('open');
            $table->timestamps();

            $table->index(['store_visit_schedule_id', 'status']);
            $table->index(['target_escalation_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_observations');
    }
};

