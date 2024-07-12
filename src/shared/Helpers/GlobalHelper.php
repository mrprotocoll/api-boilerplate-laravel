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
        $formattedCount = str_pad((string) $modelCount, $numberOfDigits, '0', STR_PAD_LEFT);

        // Use provided prefix or model prefix
        $prefix = $prefix ?: $model->prefix;

        // Assemble the new order number
        $newNumber = "{$prefix}{$datePart}{$formattedCount}";

        return $newNumber;
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
}
