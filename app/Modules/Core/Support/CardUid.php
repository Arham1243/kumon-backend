<?php

namespace App\Modules\Core\Support;

final class CardUid
{
    public const PATTERN = '/^[0-9A-F]{8,20}$/';

    public static function normalize(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $s = trim((string) $value);
        if ($s === '') {
            return null;
        }

        $s = str_replace([' ', ':', '-'], '', $s);
        if (str_starts_with(strtolower($s), '0x')) {
            $s = substr($s, 2);
        }

        $s = strtoupper($s);

        return $s === '' ? null : $s;
    }

    public static function isValid(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        $s = trim((string) $value);
        if ($s === '') {
            return false;
        }

        return (bool) preg_match('/^[0-9A-Fa-f]{8,20}$/', $s);
    }
}
