<?php

declare(strict_types=1);

namespace Shared\Helpers;

use Carbon\Carbon;
use DateTimeInterface;

final class DateTimeHelper
{
    public static string $timestamp;

    /**
     * Set the current timestamp.
     */
    public static function setTimestamp(): void
    {
        self::$timestamp = Carbon::now()->timestamp;
    }

    /**
     * Convert a date to a datetime string.
     *
     * @param  \Carbon\Carbon|DateTimeInterface  $date  The date to be converted.
     */
    public static function dateTime($date): string
    {
        return $date->toDateTimeString();
    }
}
