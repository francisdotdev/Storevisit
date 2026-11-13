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
        Schema::table('audit_observations', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->after('id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('visit_audit_id')->nullable()->after('store_id')->constrained('visit_audits')->nullOnDelete();
            $table->unsignedBigInteger('store_visit_schedule_id')->nullable()->change();
            $table->timestamp('observed_at')->nullable()->after('supervisor_id');
            $table->string('status', 30)->default('open')->change();
            $table->foreignId('closed_by')->nullable()->after('areas_for_improvement')->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable()->after('closed_by');
            $table->foreignId('reopened_by')->nullable()->after('closed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('reopened_at')->nullable()->after('reopened_by');
            $table->text('reopen_reason')->nullable()->after('reopened_at');
            $table->softDeletes()->after('updated_at');

            $table->index(['store_id', 'status']);
            $table->index(['audit_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_observations', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropForeign(['store_id']);
            $table->dropForeign(['visit_audit_id']);
            $table->dropForeign(['closed_by']);
            $table->dropForeign(['reopened_by']);
            $table->dropColumn([
                'store_id',
                'visit_audit_id',
                'observed_at',
                'closed_by',
                'closed_at',
                'reopened_by',
                'reopened_at',
                'reopen_reason',
            ]);
        });
    }
};

