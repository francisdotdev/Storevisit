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
        Schema::table('audit_checklists', function (Blueprint $table) {
            $table->string('category', 150)->nullable()->after('audit_type');
            $table->softDeletes()->after('updated_by');
        });

        // Backfill category from title if title exists
        DB::table('audit_checklists')->whereNull('category')->update([
            'category' => DB::raw('title')
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_checklists', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn('category');
        });
    }
};

