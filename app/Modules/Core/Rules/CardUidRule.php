<?php

namespace App\Modules\Core\Rules;

use App\Modules\Core\Support\CardUid;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CardUidRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! CardUid::isValid($value)) {
            $fail('Card UID must be 8 to 20 hexadecimal characters (for example, 04A1B2C3D4E5F678).');
        }
    }
}
