<?php

namespace App\Modules\Administration\Database\Seeders;

use App\Modules\Administration\Models\User;
use App\Modules\Core\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultUserSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->orderBy('id')->first();
        if (! $company) {
            return;
        }

        $supportEmail = trim((string) config('mail.support_email', 'kay@intelygic.com'));

        User::withoutGlobalScopes()->firstOrCreate(
            ['email' => $supportEmail],
            [
                'name' => 'Super Admin',
                'status' => 'active',
                'password' => Hash::make('12345678'),
                'company_id' => $company->id,
            ]
        );
    }
}
