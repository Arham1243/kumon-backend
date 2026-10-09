<?php

use App\Enums\CompanyTimezone;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $default = CompanyTimezone::defaultValue();
        $allowed = array_flip(CompanyTimezone::allowedValues());

        DB::table('admin_companies')
            ->orderBy('id')
            ->each(function ($row) use ($default, $allowed) {
                $tz = $row->timezone;
                if ($tz === null || $tz === '' || ! isset($allowed[$tz])) {
                    DB::table('admin_companies')
                        ->where('id', $row->id)
                        ->update(['timezone' => $default]);
                }
            });
    }

    public function down(): void
    {
        //
    }
};
