<?php

declare(strict_types=1);

namespace Modules\V1\Logging\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\V1\Logging\Model\ActivityLog;
use Modules\V1\Logging\Services\ActivityLogger;

/**
 * @method static ActivityLogger name(string $logName)
 * @method static ActivityLogger causedBy(?\Illuminate\Database\Eloquent\Model $causer)
 * @method static ActivityLogger performedOn(?\Illuminate\Database\Eloquent\Model $subject)
 * @method static ActivityLogger event(string $event)
 * @method static ActivityLogger withProperties(array $properties)
 * @method static ActivityLogger withProperty(string $key, $value)
 * @method static ActivityLogger withOldValues(array $oldValues)
 * @method static ActivityLogger withNewValues(array $newValues)
 * @method static ActivityLogger withModelChanges(\Illuminate\Database\Eloquent\Model $model)
 * @method static ActivityLogger inBatch(?string $batchUuid = null)
 * @method static ActivityLog log(string $description)
 * @method static ActivityLog created(\Illuminate\Database\Eloquent\Model $model, string $description = null)
 * @method static ActivityLog updated(\Illuminate\Database\Eloquent\Model $model, string $description = null)
 * @method static ActivityLog deleted(\Illuminate\Database\Eloquent\Model $model, string $description = null)
 * @method static ActivityLog restored(\Illuminate\Database\Eloquent\Model $model, string $description = null)
 * @method static ActivityLog login(\Illuminate\Database\Eloquent\Model $user, array $properties = [])
 * @method static ActivityLog logout(\Illuminate\Database\Eloquent\Model $user, array $properties = [])
 * @method static ActivityLog failedLogin(string $email, array $properties = [])
 * @method static string batch(callable $callback)
 * @method static \Illuminate\Database\Eloquent\Collection getActivitiesFor(\Illuminate\Database\Eloquent\Model $model)
 * @method static \Illuminate\Database\Eloquent\Collection getActivitiesBy(\Illuminate\Database\Eloquent\Model $user)
 * @method static \Illuminate\Database\Eloquent\Collection getActivitiesByLog(string $logName)
 * @method static int cleanup(int $daysToKeep = 90)
 */
final class Activity extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return ActivityLogger::class;
    }
}
