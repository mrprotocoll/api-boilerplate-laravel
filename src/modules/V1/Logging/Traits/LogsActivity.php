<?php

declare(strict_types=1);

namespace Modules\V1\Logging\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\V1\Logging\Model\ActivityLog;
use Modules\V1\Logging\Services\ActivityLogger;

final class LogsActivity
{
    private static $recordEvents = ['created', 'updated', 'deleted'];

    private static $logAttributes = ['*'];

    private static $logOnlyDirty = true;

    private static $logName = null;

    private static function bootLogsActivity(): void
    {
        if ( ! app()->runningInConsole() || config('activity.log_in_console', false)) {
            self::created(function ($model): void {
                static::logModelEvent($model, 'created');
            });

            self::updated(function ($model): void {
                if (static::$logOnlyDirty && ! $model->isDirty()) {
                    return;
                }
                static::logModelEvent($model, 'updated');
            });

            self::deleted(function ($model): void {
                static::logModelEvent($model, 'deleted');
            });

            if (method_exists(self::class, 'restored')) {
                self::restored(function ($model): void {
                    static::logModelEvent($model, 'restored');
                });
            }
        }
    }

    private static function logModelEvent($model, string $event): void
    {
        if ( ! in_array($event, self::$recordEvents)) {
            return;
        }

        $logger = app(ActivityLogger::class);

        $logName = self::$logName ?? mb_strtolower(class_basename($model));

        $description = self::getActivityDescription($model, $event);

        $logger->name($logName)
            ->performedOn($model)
            ->event($event)
            ->withModelChanges($model)
            ->withProperties(self::getAdditionalProperties($model, $event))
            ->log($description);
    }

    private static function getActivityDescription($model, string $event): string
    {
        $modelName = class_basename($model);
        $eventName = ucfirst($event);

        return "{$modelName} {$eventName}";
    }

    private static function getAdditionalProperties($model, string $event): array
    {
        // Override this method in your models to add custom properties
        return [];
    }

    /**
     * Get all activity logs for this model.
     */
    public function activities(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get recent activities for this model.
     */
    public function recentActivities(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return $this->activities()
            ->with('user')
            ->limit($limit)
            ->get();
    }

    /**
     * Get activities by event type.
     */
    public function getActivitiesByEvent(string $event): \Illuminate\Database\Eloquent\Collection
    {
        return $this->activities()
            ->where('event', $event)
            ->with('causer')
            ->get();
    }

    /**
     * Get the latest activity for this model.
     */
    public function latestActivity(): ?ActivityLog
    {
        return $this->activities()
            ->with('causer')
            ->first();
    }

    /**
     * Check if model has any activities.
     */
    public function hasActivities(): bool
    {
        return $this->activities()->exists();
    }

    /**
     * Get activity summary for this model.
     */
    public function getActivitySummary(): array
    {
        $activities = $this->activities()
            ->selectRaw('event, COUNT(*) as count')
            ->groupBy('event')
            ->pluck('count', 'event')
            ->toArray();

        return [
            'total_activities' => array_sum($activities),
            'by_event' => $activities,
            'first_activity' => $this->activities()->oldest()->first()?->created_at,
            'latest_activity' => $this->activities()->latest()->first()?->created_at,
        ];
    }
}
