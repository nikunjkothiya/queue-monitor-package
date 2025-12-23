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
        // New fields for enhanced features
        'is_recurring',
        'modified_payload',
        'retried_by',
        'retry_notes',
    ];

    protected $casts = [
        'failed_at' => 'datetime',
        'resolved_at' => 'datetime',
        'last_retried_at' => 'datetime',
        'is_recurring' => 'boolean',
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
     * Get the user who retried this job.
     */
    public function retrier(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'retried_by');
    }
}


