<?php

declare(strict_types=1);

namespace Shared\Helpers;

final class StringHelper
{
    /**
     * Convert a string to a title case, replacing underscores with spaces.
     *
     * @param string $case The string to convert.
     *
     * @return string
     */
    public static function toTitleCase(string $case): string
    {
        return ucwords(str_replace('_', ' ', mb_strtolower($case)));
    }

}
