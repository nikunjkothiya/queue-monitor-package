<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('queue_failures', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable()->index();
            $table->string('connection')->nullable();
            $table->string('queue')->nullable();
            $table->string('job_name');
            $table->string('job_class')->nullable();
            $table->longText('payload')->nullable();
            $table->string('exception_class')->nullable();
            $table->text('exception_message');
            $table->string('file')->nullable();
            $table->integer('line')->nullable();
            $table->longText('stack_trace');
            $table->string('group_hash')->nullable()->index();
            $table->string('hostname')->nullable();
            $table->timestamp('failed_at')->useCurrent();
            $table->string('environment')->default(config('app.env'));
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->unsignedInteger('retry_count')->default(0);
            $table->timestamp('last_retried_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_failures');
    }
};


