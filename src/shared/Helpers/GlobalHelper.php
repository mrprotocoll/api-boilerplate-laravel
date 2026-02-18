<?php

declare(strict_types=1);

namespace Shared\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use stdClass;

final class GlobalHelper
{
    /**
     * Convert an image to a base64 encoded string with a data URI scheme.
     *
     * @param  string  $image  The binary image data.
     */
    public static function convertToBase64(string $image): string
    {
        return 'data:image/png;base64,' . base64_encode($image);
    }

    /**
     * Generate custom numbers such as order numbers or invoice numbers.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model  The model instance to count the existing records.
     * @param  string|null  $prefix  An optional prefix for the generated code.
     */
    public static function generateCode(Model $model, $prefix = null): string
    {
        // Get the current date in the format 'YmdHis' (YearMonthDayHourMinuteSecond)
        $datePart = now()->format('YmdHis');

        // Get the total count of data for the current date
        $modelCount = $model->count() + 1;

        // Determine the number of digits needed for the order count
        $numberOfDigits = max(3, intval(log10($modelCount)) + 1);

        // Format the order count with leading zeros
        $formattedCount = mb_str_pad((string) $modelCount, $numberOfDigits, '0', STR_PAD_LEFT);

        // Use provided prefix or model prefix
        $prefix = $prefix ?: $model->prefix;

        // Assemble the new order number
        $newNumber = "{$prefix}-{$datePart}{$formattedCount}";

        return $newNumber;
    }

    public static function generateRandomCharacters(int $length = 12): string
    {
        // Define characters to ensure complexity
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=';

        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, mb_strlen($characters) - 1)];
        }

        return $password;
    }

    /**
     * Generate consistent background color based on staff ID
     */
    public static function generateBackgroundColor(string $key = '1'): string
    {
        $colors = [
            'f97316', 'ef4444', '10b981', '6366f1', '8b5cf6',
            'ec4899', 'f59e0b', '84cc16', '06b6d4', '3b82f6',
        ];

        if ($key) {
            $hash = crc32($key);
            $index = abs($hash) % count($colors);

            return $colors[$index];
        }

        return '3b82f6';
    }

    /**
     * Get avatar URL
     */
    public static function generateAvatarUrl(string $initials, string $key = '1'): ?string
    {
        $backgroundColor = self::generateBackgroundColor($key);

        return "https://ui-avatars.com/api/?name={$initials}&background={$backgroundColor}&color=fff&size=128";
    }

    /**
     * Get initials from first and last name
     */
    public static function getInitials(?string $firstName, ?string $lastName, string $default = 'SU'): string
    {
        $initials = '';

        if (!empty($firstName)) {
            $initials .= mb_strtoupper(mb_substr($firstName, 0, 1));
        }

        if (!empty($lastName)) {
            $initials .= mb_strtoupper(mb_substr($lastName, 0, 1));
        }

        return $initials ?: $default;
    }

    /**
     * Convert an associative array to an object recursively.
     *
     * @param  array  $array  The associative array to convert.
     */
    public static function object(array $array): object
    {
        $obj = new stdClass();
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $obj->{$k} = self::object($v);

                continue;
            }
            $obj->{$k} = $v;
        }

        return $obj;
    }

    /**
     * Clean a JSON string by removing surrounding triple backticks and the "json" keyword.
     *
     * @param  string  $jsonString  The JSON string to clean.
     */
    public static function cleanJsonString(string $jsonString): string
    {
        // Remove surrounding triple backticks
        $jsonString = trim($jsonString, " \t\n\r\0\x0B`");

        // Remove "json" keyword
        $jsonString = str_ireplace('json', '', $jsonString);

        return $jsonString;
    }

    /**
     * Validate if a link is a valid website URL.
     *
     * @param  string  $link  The link to validate.
     */
    public static function validateWebsiteLink(string $link): bool
    {
        // Use FILTER_VALIDATE_URL filter to check if the link is a valid URL
        return false !== filter_var($link, FILTER_VALIDATE_URL);
    }

    /**
     * Encrypt a value.
     *
     * @param  mixed  $value  The value to encrypt.
     */
    public static function encrypt(mixed $value): string
    {
        return Crypt::encrypt($value);
    }

    /**
     * Decrypt a value.
     *
     * @param  mixed  $value  The value to decrypt.
     * @return mixed
     */
    public static function decrypt(mixed $value)
    {
        return Crypt::decrypt($value);
    }

    /**
     * Extract the domain from a link.
     *
     * @param  string  $link  The link from which to extract the domain.
     */
    public static function extractDomain(string $link): ?string
    {
        $url = str_replace(['www.', 'https://', 'http://', ' '], [''], $link);
        $url = explode('/', $url)[0] ?? null;

        return $url;
    }

    /**
     * Generate a prefix based on the name.
     *
     * Examples:
     *   "Oxford International School" => "OIS"
     *   "Federal Government College"  => "FGC"
     *   "Unity High"                  => "UH"
     */
    public static function generatePrefix(string $name): string
    {
        // Remove special characters, keep words only
        $cleanName = preg_replace('/[^a-zA-Z0-9\s]/', '', $name);

        // Break into words
        $words = preg_split('/\s+/', trim($cleanName));

        $prefix = '';

        if (count($words) <= 3) {
            // Take first letter of up to 3 words
            foreach ($words as $index => $word) {
                $prefix .= mb_strtoupper(mb_substr($word, 0, 1));
                if ($index >= 2) {
                    break;
                }
            }
        } else {
            // Take first letter of first 2 words and last word
            $prefix .= mb_strtoupper(mb_substr($words[0], 0, 1)); // first word
            $prefix .= mb_strtoupper(mb_substr($words[1], 0, 1)); // second word
            $prefix .= mb_strtoupper(mb_substr(end($words), 0, 1)); // last word
        }

        return $prefix;
    }

}
