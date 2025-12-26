<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Add performance indexes and new columns for improved accuracy.
     */
    public function up(): void
    {
        Schema::table('queue_failures', function (Blueprint $table) {
            // New columns for improved functionality
            if (!Schema::hasColumn('queue_failures', 'priority_score')) {
                $table->unsignedTinyInteger('priority_score')->default(50)->after('is_recurring');
            }
            
            if (!Schema::hasColumn('queue_failures', 'exception_context')) {
                $table->json('exception_context')->nullable()->after('exception_message');
            }
            
            // Performance indexes for common filter queries
            // Composite index for the most common dashboard query
            $table->index(['failed_at', 'resolved_at'], 'idx_failures_timeline');
            
            // Composite index for filtered listing
            $table->index(['queue', 'failed_at'], 'idx_failures_queue_time');
            $table->index(['connection', 'failed_at'], 'idx_failures_connection_time');
            $table->index(['environment', 'failed_at'], 'idx_failures_env_time');
            
            // Index for recurring failure queries
            $table->index(['is_recurring', 'resolved_at', 'failed_at'], 'idx_failures_recurring');
            
            // Index for priority-based queries
            $table->index(['priority_score', 'failed_at'], 'idx_failures_priority');
            
            // Index for job-based analytics
            $table->index(['job_name', 'failed_at'], 'idx_failures_job_time');
            
            // Index for exception-based filtering
            $table->index(['exception_class', 'failed_at'], 'idx_failures_exception_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('queue_failures', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex('idx_failures_timeline');
            $table->dropIndex('idx_failures_queue_time');
            $table->dropIndex('idx_failures_connection_time');
            $table->dropIndex('idx_failures_env_time');
            $table->dropIndex('idx_failures_recurring');
            $table->dropIndex('idx_failures_priority');
            $table->dropIndex('idx_failures_job_time');
            $table->dropIndex('idx_failures_exception_time');
            
            // Drop columns
            $table->dropColumn(['priority_score', 'exception_context']);
        });
    }
};
