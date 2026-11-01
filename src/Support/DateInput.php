<?php

declare(strict_types=1);

namespace App\Support;

use DateTimeImmutable;

final class DateInput
{
    /** Parses a strict YYYY-MM-DD string to midnight; returns null for anything else. */
    public static function parse(mixed $value): ?DateTimeImmutable
    {
        if (!is_string($value) || preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) !== 1) {
            return null;
        }
        if (!checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return null;
        }

        return new DateTimeImmutable($value . ' 00:00:00');
    }
}
