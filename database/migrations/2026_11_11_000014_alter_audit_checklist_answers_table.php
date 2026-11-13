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
        Schema::table('audit_checklist_answers', function (Blueprint $table) {
            $table->foreignId('visit_audit_id')->nullable()->after('id')->constrained('visit_audits')->cascadeOnDelete();
            $table->string('category', 150)->nullable()->after('audit_checklist_id');
            $table->string('activity', 255)->nullable()->after('category');
            $table->string('sub_activity', 255)->nullable()->after('activity');
            $table->decimal('weight', 5, 2)->default(0.00)->after('sub_activity');
            $table->decimal('earned_weight', 5, 2)->default(0.00)->after('answer');
            $table->string('personnel_in_charge', 150)->nullable()->after('remarks');

            // Make previous schedule FK and answer nullable for backwards compatibility
            $table->unsignedBigInteger('store_visit_schedule_id')->nullable()->change();
            $table->string('answer', 50)->nullable()->change();

            // Add unique index on visit_audit_id + audit_checklist_id
            $table->unique(['visit_audit_id', 'audit_checklist_id'], 'visit_audit_checklist_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_checklist_answers', function (Blueprint $table) {
            $table->dropUnique('visit_audit_checklist_unique');
            $table->dropForeign(['visit_audit_id']);
            $table->dropColumn([
                'visit_audit_id',
                'category',
                'activity',
                'sub_activity',
                'weight',
                'earned_weight',
                'personnel_in_charge',
            ]);
        });
    }
};

