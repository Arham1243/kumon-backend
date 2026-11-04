<?php

namespace App\Modules\Core\Rules;

use App\Modules\Core\Models\Student;
use App\Modules\Core\Support\CardUid;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

class UniqueCompanyCardUid implements ValidationRule
{
    public function __construct(
        private readonly string|Student|null $ignoreStudent = null
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! CardUid::isValid($value)) {
            return;
        }

        $uid = strtoupper(trim((string) $value));

        $user = Auth::guard('sanctum_admin')->user()
            ?? Auth::guard('admin')->user();

        $companyId = $user?->company_id;
        if (! $companyId) {
            $fail('Unable to validate card uniqueness without a company context.');

            return;
        }

        $query = Student::query()
            ->where('company_id', $companyId)
            ->whereRaw('UPPER(card_uid) = ?', [$uid]);

        $ignoreUuid = $this->resolveIgnoreUuid();
        if ($ignoreUuid) {
            $query->where('uuid', '!=', $ignoreUuid);
        }

        $holder = $query->first();
        if ($holder === null) {
            return;
        }

        $fail("This card is already assigned to {$holder->name} ({$holder->student_id}).");
    }

    private function resolveIgnoreUuid(): ?string
    {
        if ($this->ignoreStudent instanceof Student) {
            return $this->ignoreStudent->uuid;
        }

        if (is_string($this->ignoreStudent) && $this->ignoreStudent !== '') {
            return $this->ignoreStudent;
        }

        return null;
    }
}
