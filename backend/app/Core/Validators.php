<?php

namespace App\Core;

use InvalidArgumentException;

class Validators
{
    /**
     * Validate and sanitize module code (e.g., "INF1005")
     */
    public static function moduleCode(?string $code): ?string
    {
        if (empty($code)) return null;

        if (!preg_match('/^[A-Za-z0-9]{2,10}$/', $code)) {
            throw new InvalidArgumentException('Invalid module code format');
        }

        return strtoupper($code);
    }

    /**
     * Validate email format
     */
    public static function email(?string $email): ?string
    {
        if (empty($email)) return null;

        $sanitized = filter_var(trim($email), FILTER_VALIDATE_EMAIL);

        if (!$sanitized) {
            throw new InvalidArgumentException('Invalid email format');
        }

        return strtolower((string)$sanitized);
    }

    /**
     * Sanitize search input
     */
    public static function search(?string $search): ?string
    {
        if (empty($search)) return null;

        // Clean and truncate in one flow
        $clean = preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $search);
        return trim(mb_substr($clean, 0, 100));
    }

    public static function positiveInt(mixed $value, string $label = 'id'): int
    {
        if ($value === null || $value === '') {
            throw new InvalidArgumentException("$label is required");
        }

        if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 1) {
            throw new InvalidArgumentException("$label must be a positive integer");
        }

        return (int) $value;
    }

    public static function optionalPositiveInt(mixed $value, string $label = 'id'): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return self::positiveInt($value, $label);
    }

    // --- Private Helpers to reduce code duplication ---

    public static function rangeCheck(mixed $val, float $min, float $max, string $label): ?float
    {
        if ($val === null || $val === '') return null;

        $num = (float) $val;
        if ($num < $min || $num > $max) {
            throw new InvalidArgumentException("$label must be between $min and $max");
        }
        return $num;
    }

    public static function boundedText(?string $value, string $label, int $max, bool $required = true): ?string
    {
        $content = trim($value ?? '');

        if ($content === '') {
            if ($required) {
                throw new InvalidArgumentException("$label is required");
            }
            return null;
        }

        if (mb_strlen($content) > $max) {
            throw new InvalidArgumentException("$label cannot exceed $max characters");
        }

        return $content;
    }

    public static function stringCheck(?string $value, string $label, int $max): string
    {
        return self::boundedText($value, $label, $max, true);
    }
}