<?php

namespace App\Console\Commands;

use App\Modules\Administration\Database\Seeders\EmailTemplateSeeder;
use App\Modules\Administration\Database\Seeders\PermissionSeeder;
use App\Modules\Administration\Database\Seeders\RoleSeeder;
use App\Modules\Reports\Database\Seeders\ScheduledJobSeeder;
use Illuminate\Console\Command;

class ReseedData extends Command
{
    protected $signature = 'reseed:data
                            {--only= : Comma-separated steps (see valid steps when omitted)}
                            {--all : Reseed every managed step}';

    protected $description = 'Reseed reference/config data; use --only=<steps> or --all';

    /** @var array<string, callable(): void> */
    protected array $steps = [];

    public function __construct()
    {
        parent::__construct();

        $this->steps = [
            'permissions' => function (): void {
                $this->call(PermissionSeeder::class);
                $this->call(RoleSeeder::class);
            },
            'email_templates' => fn () => $this->call(EmailTemplateSeeder::class),
            'scheduled_jobs' => fn () => $this->call(ScheduledJobSeeder::class),
        ];
    }

    public function handle(): int
    {
        $selected = $this->resolveSteps();

        if ($selected === null) {
            return self::FAILURE;
        }

        foreach ($selected as $key => $step) {
            $step();
            $this->info("  • {$key}");
        }

        $this->info('Reseed completed.');

        return self::SUCCESS;
    }

    /**
     * @return array<string, callable(): void>|null
     */
    protected function resolveSteps(): ?array
    {
        if ($this->option('all')) {
            return $this->stepsForKeys(array_keys($this->steps));
        }

        $only = trim((string) $this->option('only'));

        if ($only === '') {
            $this->error('Specify --only=<steps> or --all');
            $this->line('Valid steps: '.implode(', ', array_keys($this->steps)));
            $this->line('Example: php artisan reseed:data --only=permissions,email_templates');
            $this->line('Example: php artisan reseed:data --all');

            return null;
        }

        $keys = array_values(array_unique(array_filter(array_map(
            fn (string $key) => $this->normalizeStepKey($key),
            explode(',', $only)
        ))));

        $selected = [];
        $invalid = [];

        foreach ($keys as $key) {
            if (isset($this->steps[$key])) {
                $selected[$key] = $this->steps[$key];
            } else {
                $invalid[] = $key;
            }
        }

        if ($invalid !== []) {
            $this->error('Unknown step(s): '.implode(', ', $invalid));
            $this->line('Valid steps: '.implode(', ', array_keys($this->steps)));

            return null;
        }

        if ($selected === []) {
            $this->error('No valid steps in --only=');

            return null;
        }

        return $this->stepsForKeys(array_keys($selected));
    }

    /**
     * Preserve the order listed in --only (after de-duplication).
     *
     * @param  list<string>  $keys
     * @return array<string, callable(): void>
     */
    protected function stepsForKeys(array $keys): array
    {
        $selected = [];

        foreach ($keys as $key) {
            if (isset($this->steps[$key])) {
                $selected[$key] = $this->steps[$key];
            }
        }

        return $selected;
    }

    protected function normalizeStepKey(string $key): string
    {
        $normalized = strtolower(trim(str_replace('-', '_', $key)));

        if ($normalized === 'role_permissions') {
            return 'permissions';
        }

        return $normalized;
    }
}
