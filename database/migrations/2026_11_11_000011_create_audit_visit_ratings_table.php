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
        Schema::create('audit_visit_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_visit_schedule_id')->unique()->constrained('store_visit_schedules')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores');
            $table->foreignId('supervisor_id')->constrained('users');
            $table->enum('audit_type', ['treasury', 'cashier']);
            $table->date('visit_date');
            $table->decimal('total_possible_weight', 5, 2)->default(100.00);
            $table->decimal('total_earned_score', 5, 2)->default(0.00);
            $table->decimal('final_percentage', 5, 2)->default(0.00);
            $table->string('rating_grade', 50)->nullable();
            $table->text('summary_remarks')->nullable();
            $table->timestamp('rated_at')->useCurrent();
            $table->timestamps();

            $table->index(['store_id', 'visit_date']);
            $table->index(['audit_type', 'final_percentage']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_visit_ratings');
    }
};

