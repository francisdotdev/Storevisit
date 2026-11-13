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
        Schema::table('audit_visit_ratings', function (Blueprint $table) {
            $table->foreignId('visit_audit_id')->nullable()->after('id')->unique()->constrained('visit_audits')->cascadeOnDelete();
            $table->decimal('total_earned_weight', 5, 2)->default(0.00)->after('total_possible_weight');
            $table->decimal('score_percentage', 5, 2)->default(0.00)->after('total_earned_weight');
            $table->string('grade', 50)->nullable()->after('score_percentage');

            // Make store_visit_schedule_id nullable for compatibility
            $table->unsignedBigInteger('store_visit_schedule_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_visit_ratings', function (Blueprint $table) {
            $table->dropForeign(['visit_audit_id']);
            $table->dropColumn(['visit_audit_id', 'total_earned_weight', 'score_percentage', 'grade']);
        });
    }
};

