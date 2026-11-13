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
        Schema::create('audit_checklist_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_visit_schedule_id')->constrained('store_visit_schedules')->cascadeOnDelete();
            $table->foreignId('audit_checklist_id')->constrained('audit_checklists')->cascadeOnDelete();
            $table->enum('answer', ['yes', 'no']);
            $table->decimal('score', 5, 2)->default(0.00);
            $table->decimal('scale', 5, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('answered_by')->constrained('users');
            $table->timestamps();

            $table->unique(['store_visit_schedule_id', 'audit_checklist_id'], 'visit_checklist_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_checklist_answers');
    }
};

