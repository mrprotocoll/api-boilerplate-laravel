<?php

declare(strict_types=1);

namespace Modules\V1\Logging\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Modules\V1\User\Models\User;
use Throwable;

final class CentralizedLogger
{
    private string $defaultChannel;

    private array $contextData = [];

    public function __construct(?string $defaultChannel = null)
    {
        $this->defaultChannel = $defaultChannel ?? config('logging.default');
    }

    /**
     * Set global context data that will be included in all logs
     */
    public function setContext(array $context): self
    {
        $this->contextData = array_merge($this->contextData, $context);

        return $this;
    }

    /**
     * Clear global context data
     */
    public function clearContext(): self
    {
        $this->contextData = [];

        return $this;
    }

    /**
     * Log emergency messages
     */
    public function emergency(string $message, array $context = [], ?string $channel = null): void
    {
        $this->log('emergency', $message, $context, $channel);
    }

    /**
     * Log alert messages
     */
    public function alert(string $message, array $context = [], ?string $channel = null): void
    {
        $this->log('alert', $message, $context, $channel);
    }

    /**
     * Log critical messages
     */
    public function critical(string $message, array $context = [], ?string $channel = null): void
    {
        $this->log('critical', $message, $context, $channel);
    }

    /**
     * Log error messages
     */
    public function error(string $message, array $context = [], ?string $channel = null): void
    {
        $this->log('error', $message, $context, $channel);
    }

    /**
     * Log warning messages
     */
    public function warning(string $message, array $context = [], ?string $channel = null): void
    {
        $this->log('warning', $message, $context, $channel);
    }

    /**
     * Log notice messages
     */
    public function notice(string $message, array $context = [], ?string $channel = null): void
    {
        $this->log('notice', $message, $context, $channel);
    }

    /**
     * Log info messages
     */
    public function info(string $message, array $context = [], ?string $channel = null): void
    {
        $this->log('info', $message, $context, $channel);
    }

    /**
     * Log debug messages
     */
    public function debug(string $message, array $context = [], ?string $channel = null): void
    {
        $this->log('debug', $message, $context, $channel);
    }

    /**
     * Log exceptions with full stack trace
     */
    public function exception(Throwable $exception, array $context = [], ?string $channel = null): void
    {
        $exceptionContext = [
            'exception' => [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'code' => $exception->getCode(),
                'trace' => $exception->getTraceAsString(),
                'previous' => $exception->getPrevious() ? $exception->getPrevious()->getMessage() : null,
            ],
        ];

        $mergedContext = array_merge($exceptionContext, $context);
        $this->error($exception->getMessage(), $mergedContext, $channel);
    }

    /**
     * Log API requests/responses
     */
    public function api(string $action, array $data = [], ?string $channel = null): void
    {
        $apiContext = [
            'api_action' => $action,
            'request_id' => Request::header('X-Request-ID', uniqid()),
            'method' => Request::method(),
            'url' => Request::fullUrl(),
            'ip' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'timestamp' => now()->toDateTimeString(),
        ];

        $mergedContext = array_merge($apiContext, $data);
        $this->info("API: {$action}", $mergedContext, $channel ?? 'api');
    }

    /**
     * Log user activities
     */
    public function activity(string $action, ?int $userId = null, array $data = [], ?string $channel = null): void
    {
        $user = $userId ? User::find($userId) : Request::user();

        $activityContext = [
            'activity' => $action,
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'ip' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'timestamp' => now()->toDateTimeString(),
        ];

        $mergedContext = array_merge($activityContext, $data);
        $this->info("Activity: {$action}", $mergedContext, $channel ?? 'activity');
    }

    /**
     * Log database queries with performance metrics
     */
    public function query(string $sql, array $bindings = [], float $time = 0, ?string $channel = null): void
    {
        $queryContext = [
            'sql' => $sql,
            'bindings' => $bindings,
            'execution_time' => $time,
            'slow_query' => $time > 1000, // Mark as slow if > 1 second
        ];

        $level = $time > 1000 ? 'warning' : 'debug';
        $this->log($level, 'Database Query', $queryContext, $channel ?? 'database');
    }

    /**
     * Log security events
     */
    public function security(string $event, array $data = [], ?string $channel = null): void
    {
        $securityContext = [
            'security_event' => $event,
            'ip' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'user_id' => Request::user()?->id,
            'timestamp' => now()->toDateTimeString(),
            'severity' => 'high',
        ];

        $mergedContext = array_merge($securityContext, $data);
        $this->warning("Security: {$event}", $mergedContext, $channel ?? 'security');
    }

    /**
     * Log performance metrics
     */
    public function performance(string $metric, float $value, array $context = [], ?string $channel = null): void
    {
        $performanceContext = [
            'metric' => $metric,
            'value' => $value,
            'unit' => $this->inferUnit($metric),
            'threshold_exceeded' => $this->isThresholdExceeded($metric, $value),
        ];

        $mergedContext = array_merge($performanceContext, $context);
        $this->info("Performance: {$metric}", $mergedContext, $channel ?? 'performance');
    }

    /**
     * Core logging method
     */
    private function log(string $level, string $message, array $context = [], ?string $channel = null): void
    {
        $channel = $channel ?? $this->defaultChannel;

        // Add automatic context data
        $autoContext = $this->getAutoContext();

        // Merge all context data: auto + global + specific
        $finalContext = array_merge($autoContext, $this->contextData, $context);

        // Log to specified channel
        Log::channel($channel)->{$level}($message, $finalContext);
    }

    /**
     * Get automatic context data for every log entry
     */
    private function getAutoContext(): array
    {
        return [
            'timestamp' => now()->toISOString(),
            'environment' => app()->environment(),
            'request_id' => Request::header('X-Request-ID', uniqid('req_')),
            'session_id' => Request::hasSession() ? Request::session()->getId() : null,
            'memory_usage' => $this->formatBytes(memory_get_usage(true)),
            'peak_memory' => $this->formatBytes(memory_get_peak_usage(true)),
        ];
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Infer unit for performance metrics
     */
    private function inferUnit(string $metric): string
    {
        if (str_contains(mb_strtolower($metric), 'time') || str_contains(mb_strtolower($metric), 'duration')) {
            return 'ms';
        }
        if (str_contains(mb_strtolower($metric), 'memory')) {
            return 'bytes';
        }
        if (str_contains(mb_strtolower($metric), 'count') || str_contains(mb_strtolower($metric), 'total')) {
            return 'count';
        }

        return 'unit';
    }

    /**
     * Check if performance threshold is exceeded
     */
    private function isThresholdExceeded(string $metric, float $value): bool
    {
        $thresholds = config('logging.performance_thresholds', [
            'response_time' => 2000, // 2 seconds
            'memory_usage' => 128 * 1024 * 1024, // 128 MB
            'query_time' => 1000, // 1 second
        ]);

        foreach ($thresholds as $threshold => $limit) {
            if (str_contains(mb_strtolower($metric), $threshold)) {
                return $value > $limit;
            }
        }

        return false;
    }

    /**
     * Create a logger with specific context
     */
    public function withContext(array $context): self
    {
        $clone = clone $this;
        $clone->setContext($context);

        return $clone;
    }

    /**
     * Create a logger with specific channel
     */
    public function channel(string $channel): self
    {
        $clone = clone $this;
        $clone->defaultChannel = $channel;

        return $clone;
    }

    /**
     * Bulk log multiple entries
     */
    public function bulk(array $entries): void
    {
        foreach ($entries as $entry) {
            $level = $entry['level'] ?? 'info';
            $message = $entry['message'] ?? 'Bulk log entry';
            $context = $entry['context'] ?? [];
            $channel = $entry['channel'] ?? null;

            $this->log($level, $message, $context, $channel);
        }
    }

    /**
     * Log with structured data (for JSON logs)
     */
    public function structured(string $event, array $data = [], ?string $channel = null): void
    {
        $structuredData = [
            'event' => $event,
            'data' => $data,
            'structured' => true,
        ];

        $this->info($event, $structuredData, $channel);
    }
}
