<?php

namespace App\Enums;

use DateTimeZone;

/**
 * Company home timezone (IANA). Stored as a string column with DB default
 * {@see self::AmericaNew_York}; validated against the same identifier set the
 * frontend uses (Intl.supportedValuesOf('timeZone') ≈ PHP listIdentifiers).
 */
enum CompanyTimezone: string
{
    case AmericaNewYork = 'America/New_York';

    public static function default(): self
    {
        return self::AmericaNewYork;
    }

    public static function defaultValue(): string
    {
        return self::default()->value;
    }

    /**
     * @return list<string>
     */
    public static function allowedValues(): array
    {
        static $allowed = null;

        if ($allowed === null) {
            $allowed = DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC);
        }

        return $allowed;
    }

    public static function isValid(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return in_array($value, self::allowedValues(), true);
    }
}
