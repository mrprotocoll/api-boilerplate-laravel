<?php

namespace Modules\V1\Logging\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\V1\Logging\Services\CentralizedLogger;

/**
 * @method static void emergency(string $message, array $context = [], ?string $channel = null)
 * @method static void alert(string $message, array $context = [], ?string $channel = null)
 * @method static void critical(string $message, array $context = [], ?string $channel = null)
 * @method static void error(string $message, array $context = [], ?string $channel = null)
 * @method static void warning(string $message, array $context = [], ?string $channel = null)
 * @method static void notice(string $message, array $context = [], ?string $channel = null)
 * @method static void info(string $message, array $context = [], ?string $channel = null)
 * @method static void debug(string $message, array $context = [], ?string $channel = null)
 * @method static void exception(\Throwable $exception, array $context = [], ?string $channel = null)
 * @method static void api(string $action, array $data = [], ?string $channel = null)
 * @method static void activity(string $action, ?int $userId = null, array $data = [], ?string $channel = null)
 * @method static void query(string $sql, array $bindings = [], float $time = 0, ?string $channel = null)
 * @method static void security(string $event, array $data = [], ?string $channel = null)
 * @method static void performance(string $metric, float $value, array $context = [], ?string $channel = null)
 * @method static void structured(string $event, array $data = [], ?string $channel = null)
 * @method static void bulk(array $entries)
 * @method static CentralizedLogger setContext(array $context)
 * @method static CentralizedLogger clearContext()
 * @method static CentralizedLogger withContext(array $context)
 * @method static CentralizedLogger channel(string $channel)
 */
class AppLogger extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'centralized.logger';
    }
}
