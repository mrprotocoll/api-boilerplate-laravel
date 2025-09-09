<?php

declare(strict_types=1);

namespace Modules\V1\Logging\Services;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Modules\V1\Logging\Enums\LogEventEnum;
use Modules\V1\Logging\Enums\LogNameEnum;
use Modules\V1\Logging\Model\ActivityLog;
use Modules\V1\User\Models\User;

final class ActivityLogger
{
    private ?string $logName = null;

    private ?Model $user = null;

    private ?Model $subject = null;

    private array $properties = [];

    private ?string $batchUuid = null;

    private ?string $event = null;

    private array $oldValues = [];

    private array $newValues = [];

    public function __construct(
        private CentralizedLogger $fileLogger
    ) {}

    /**
     * Set the log name for grouping activities.
     */
    public function name(LogNameEnum $logName): self
    {
        $this->logName = $logName->value;

        return $this;
    }

    /**
     * Set the user who performed the activity.
     */
    public function causedBy(?Model $causer): self
    {
        $this->user = $causer;

        return $this;
    }

    /**
     * Set the subject of the activity.
     */
    public function performedOn(?Model $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Set the event type.
     */
    public function event(LogEventEnum $event): self
    {
        $this->event = $event->value;

        return $this;
    }

    /**
     * Set additional properties.
     */
    public function withProperties(array $properties): self
    {
        $this->properties = array_merge($this->properties, $properties);

        return $this;
    }

    /**
     * Set a single property.
     */
    public function withProperty(string $key, $value): self
    {
        $this->properties[$key] = $value;

        return $this;
    }

    /**
     * Set old values for tracking changes.
     */
    public function withOldValues(array $oldValues): self
    {
        $this->oldValues = $oldValues;

        return $this;
    }

    /**
     * Set new values for tracking changes.
     */
    public function withNewValues(array $newValues): self
    {
        $this->newValues = $newValues;

        return $this;
    }

    /**
     * Set values for model changes tracking.
     */
    public function withModelChanges(Model $model): self
    {
        if ($model->wasRecentlyCreated) {
            $this->newValues = $model->getAttributes();
            $this->event = $this->event ?? 'created';
        } else {
            $this->oldValues = $model->getOriginal();
            $this->newValues = $model->getChanges();
            $this->event = $this->event ?? 'updated';
        }

        return $this;
    }

    /**
     * Set batch UUID for grouping related activities.
     */
    public function inBatch(?string $batchUuid = null): self
    {
        $this->batchUuid = $batchUuid ?? Str::uuid()->toString();

        return $this;
    }

    /**
     * Log the activity to database and file.
     */
    public function log(string $description): ActivityLog
    {
        // Prepare activity data
        $activityData = [
            'log_name' => $this->logName,
            'description' => $description,
            'subject_type' => $this->subject?->getMorphClass(),
            'subject_id' => $this->subject?->getKey(),
            'event' => $this->event,
            'causer_id' => $this->getCauser()?->getKey(),
            'causer_type' => $this->getCauser()?->getMorphClass(),
            'properties' => $this->properties ?: null,
            'old_values' => $this->oldValues ?: null,
            'new_values' => $this->newValues ?: null,
            'batch_uuid' => $this->batchUuid,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'session_id' => $this->getSessionId(),
            'request_id' => Request::header('X-Request-ID', uniqid('req_')),
        ];

        // Create database record
        $activityLog = ActivityLog::create($activityData);

        // Also log to file for debugging
        $this->logToFile($description, $activityData);

        // Reset state for next use
        $this->reset();

        return $activityLog;
    }

    /**
     * Quick methods for common activities.
     */
    public function created(Model $model, ?string $description = null): ActivityLog
    {
        return $this->performedOn($model)
            ->event(LogEventEnum::CREATE)
            ->withModelChanges($model)
            ->log($description ?? ucfirst(class_basename($model)) . ' created');
    }

    public function updated(Model $model, ?string $description = null): ActivityLog
    {
        return $this->performedOn($model)
            ->event(LogEventEnum::UPDATE)
            ->withModelChanges($model)
            ->log($description ?? ucfirst(class_basename($model)) . ' updated');
    }

    public function deleted(Model $model, ?string $description = null): ActivityLog
    {
        return $this->performedOn($model)
            ->event(LogEventEnum::DELETE)
            ->withOldValues($model->getOriginal())
            ->log($description ?? ucfirst(class_basename($model)) . ' deleted');
    }

    public function restored(Model $model, ?string $description = null): ActivityLog
    {
        return $this->performedOn($model)
            ->event(LogEventEnum::RESTORE)
            ->log($description ?? ucfirst(class_basename($model)) . ' restored');
    }

    public function login(Model $user, array $properties = []): ActivityLog
    {
        return $this->name('auth')
            ->causedBy($user)
            ->event(LogEventEnum::LOGIN)
            ->withProperties($properties)
            ->log('User logged in');
    }

    public function logout(Model $user, array $properties = []): ActivityLog
    {
        return $this->log('auth')
            ->causedBy($user)
            ->event(LogEventEnum::LOGOUT)
            ->withProperties($properties)
            ->log('User logged out');
    }

    public function failedLogin(string $email, array $properties = []): ActivityLog
    {
        return $this->log('auth')
            ->event('failed_login')
            ->withProperties(array_merge(['email' => $email], $properties))
            ->log('Failed login attempt');
    }

    /**
     * Batch logging for multiple activities.
     */
    public function batch(callable $callback): string
    {
        $batchUuid = Str::uuid()->toString();
        $this->inBatch($batchUuid);

        $callback($this);

        return $batchUuid;
    }

    /**
     * Get activities for a specific model.
     */
    public function getActivitiesFor(Model $model): \Illuminate\Database\Eloquent\Collection
    {
        return ActivityLog::forSubject($model)
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get activities by a specific user.
     */
    public function getActivitiesBy(Model $user): \Illuminate\Database\Eloquent\Collection
    {
        return ActivityLog::causedBy($user)
            ->with(['subject'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get activities by log name.
     */
    public function getActivitiesByLog(string $logName): \Illuminate\Database\Eloquent\Collection
    {
        return ActivityLog::inLog($logName)
            ->with(['user', 'subject'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Clean up old activities.
     */
    public function cleanup(int $daysToKeep = 90): int
    {
        $deletedCount = ActivityLog::where('created_at', '<', now()->subDays($daysToKeep))
            ->delete();

        $this->fileLogger->info('Activity log cleanup completed', [
            'deleted_records' => $deletedCount,
            'days_kept' => $daysToKeep,
        ], 'activity');

        return $deletedCount;
    }

    /**
     * Get the current user (user who performed the action).
     */
    private function getCauser()
    {
        return $this->user ?? User::active();
    }

    /**
     * Get session ID safely.
     */
    private function getSessionId(): ?string
    {
        try {
            return Request::hasSession() && Request::session()->isStarted()
                ? Request::session()->getId()
                : null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Log to file as well as database.
     */
    private function logToFile(string $description, array $data): void
    {
        $this->fileLogger->activity($description, null, [
            'log_name' => $data['log_name'],
            'event' => $data['event'],
            'subject_type' => $data['subject_type'],
            'subject_id' => $data['subject_id'],
            'causer_id' => $data['causer_id'],
            'properties' => $data['properties'],
            'has_changes' => ! empty($data['old_values']) && ! empty($data['new_values']),
        ]);
    }

    /**
     * Reset the logger state.
     */
    private function reset(): void
    {
        $this->logName = null;
        $this->user = null;
        $this->subject = null;
        $this->properties = [];
        $this->batchUuid = null;
        $this->event = null;
        $this->oldValues = [];
        $this->newValues = [];
    }
}
