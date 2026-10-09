<?php

namespace App\Modules\Core\Database\Seeders;

use App\Enums\CompanyTimezone;
use App\Modules\Core\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        Company::query()->firstOrCreate(
            ['name' => 'Kumon'],
            [
                'timezone' => CompanyTimezone::defaultValue(),
                'date_format' => 'mm/dd/yy',
            ]
        );
    }
}
