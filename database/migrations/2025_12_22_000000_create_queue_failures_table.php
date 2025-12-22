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
            $table->longText('payload')->nullable();
            $table->text('exception_message');
            $table->longText('stack_trace');
            $table->timestamp('failed_at')->useCurrent();
            $table->string('environment')->default(config('app.env'));
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
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


