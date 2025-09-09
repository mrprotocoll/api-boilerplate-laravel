<?php

namespace Modules\V1\Logging\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\V1\User\Models\User;
use Shared\Models\BaseModel;

class ActivityLog extends BaseModel
{
    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'event',
        'user_id',
        'causer_type',
        'properties',
        'old_values',
        'new_values',
        'batch_uuid',
        'ip_address',
        'user_agent',
        'session_id',
        'request_id',
    ];

    protected $casts = [
        'properties' => 'array',
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The subject that the activity is performed on.
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The user who performed the activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope to filter by log name.
     */
    public function scopeInLog(Builder $query, string $logName): Builder
    {
        return $query->where('log_name', $logName);
    }

    /**
     * Scope to filter by causer.
     */
    public function scopeCausedBy(Builder $query, Model $causer): Builder
    {
        return $query->where('user_id', $causer->getKey());
    }

    /**
     * Scope to filter by subject.
     */
    public function scopeForSubject(Builder $query, Model $subject): Builder
    {
        return $query->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey());
    }

    /**
     * Scope to filter by event.
     */
    public function scopeForEvent(Builder $query, string $event): Builder
    {
        return $query->where('event', $event);
    }

    /**
     * Scope to filter by batch.
     */
    public function scopeInBatch(Builder $query, string $batchUuid): Builder
    {
        return $query->where('batch_uuid', $batchUuid);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeBetweenDates(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by recent activities.
     */
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Get changes for this activity.
     */
    public function getChanges(): Collection
    {
        $changes = collect();

        if ($this->old_values && $this->new_values) {
            foreach ($this->new_values as $key => $newValue) {
                $oldValue = $this->old_values[$key] ?? null;

                if ($oldValue !== $newValue) {
                    $changes->put($key, [
                        'old' => $oldValue,
                        'new' => $newValue
                    ]);
                }
            }
        }

        return $changes;
    }

    /**
     * Check if this activity has changes.
     */
    public function hasChanges(): bool
    {
        return $this->getChanges()->isNotEmpty();
    }

    /**
     * Get a human-readable description of the changes.
     */
    public function getChangesDescription(): string
    {
        $changes = $this->getChanges();

        if ($changes->isEmpty()) {
            return 'No changes recorded';
        }

        $descriptions = $changes->map(function ($change, $attribute) {
            $old = is_null($change['old']) ? 'null' : $change['old'];
            $new = is_null($change['new']) ? 'null' : $change['new'];

            return "{$attribute}: {$old} → {$new}";
        });

        return $descriptions->implode(', ');
    }

    /**
     * Get property value by key.
     */
    public function getProperty(string $key, $default = null)
    {
        return data_get($this->properties, $key, $default);
    }

    /**
     * Check if property exists.
     */
    public function hasProperty(string $key): bool
    {
        return array_key_exists($key, $this->properties ?? []);
    }

    /**
     * Get formatted description with placeholders replaced.
     */
    public function getFormattedDescription(): string
    {
        $description = $this->description;

        // Replace common placeholders
        $replacements = [
            ':causer' => $this->user?->name ?? 'System',
            ':subject' => $this->subject?->name ?? $this->subject?->title ?? "ID {$this->subject_id}",
            ':event' => $this->event,
        ];

        // Add custom properties as placeholders
        if ($this->properties) {
            foreach ($this->properties as $key => $value) {
                $replacements[":{$key}"] = is_array($value) ? json_encode($value) : $value;
            }
        }

        return str_replace(array_keys($replacements), array_values($replacements), $description);
    }
}
