<?php

namespace NikunjKothiya\QueueMonitor\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class QueueFailure extends Model
{
    use HasFactory;

    protected $table = 'queue_failures';

    protected $fillable = [
        'uuid',
        'connection',
        'queue',
        'job_name',
        'job_class',
        'payload',
        'exception_class',
        'exception_message',
        'exception_context',
        'file',
        'line',
        'stack_trace',
        'group_hash',
        'hostname',
        'failed_at',
        'environment',
        'resolved_at',
        'resolution_notes',
        'resolved_by',
        'retry_count',
        'last_retried_at',
        'is_recurring',
        'priority_score',
        'modified_payload',
        'retried_by',
        'retry_notes',
    ];

    protected $casts = [
        'failed_at' => 'datetime',
        'resolved_at' => 'datetime',
        'last_retried_at' => 'datetime',
        'is_recurring' => 'boolean',
        'exception_context' => 'array',
        'priority_score' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->environment)) {
                $model->environment = app()->environment();
            }
            if (empty($model->failed_at)) {
                $model->failed_at = Carbon::now();
            }
        });
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'resolved_by');
    }

    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->whereNull('resolved_at');
    }

    public function scopeRecent(Builder $query, int $limit = 20): Builder
    {
        return $query->latest('failed_at')->limit($limit);
    }

    public function isResolved(): bool
    {
        return (bool) $this->resolved_at;
    }

    /**
     * Scope to filter only recurring failures.
     */
    public function scopeRecurring(Builder $query): Builder
    {
        return $query->where('is_recurring', true);
    }

    /**
     * Scope to filter by minimum priority score.
     */
    public function scopeCritical(Builder $query, int $minScore = 80): Builder
    {
        return $query->where('priority_score', '>=', $minScore);
    }

    /**
     * Scope to filter high priority failures.
     */
    public function scopeHighPriority(Builder $query): Builder
    {
        return $query->where('priority_score', '>=', 60);
    }

    /**
     * Scope to order by priority (highest first).
     */
    public function scopeByPriority(Builder $query): Builder
    {
        return $query->orderByDesc('priority_score')->orderByDesc('failed_at');
    }

    /**
     * Get the user who retried this job.
     */
    public function retrier(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'retried_by');
    }

    /**
     * Check if this failure is critical priority.
     */
    public function isCritical(): bool
    {
        return ($this->priority_score ?? 50) >= 80;
    }

    /**
     * Check if this failure is high priority.
     */
    public function isHighPriority(): bool
    {
        return ($this->priority_score ?? 50) >= 60;
    }

    /**
     * Get priority label for display.
     */
    public function getPriorityLabel(): string
    {
        $score = $this->priority_score ?? 50;
        
        return match (true) {
            $score >= 80 => 'Critical',
            $score >= 60 => 'High',
            $score >= 40 => 'Medium',
            default => 'Low',
        };
    }

    /**
     * Get priority color for UI.
     */
    public function getPriorityColor(): string
    {
        $score = $this->priority_score ?? 50;
        
        return match (true) {
            $score >= 80 => 'red',
            $score >= 60 => 'orange',
            $score >= 40 => 'yellow',
            default => 'gray',
        };
    }
}


