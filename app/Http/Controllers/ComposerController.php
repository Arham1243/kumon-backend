<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;
use Illuminate\Validation\ValidationException;

class ComposerController extends Controller
{
    /** @var list<string> */
    private const ALLOWED_COMMANDS = ['install', 'update'];

    /** @var list<string> */
    private const ALLOWED_FLAGS = [
        '--no-dev',
        '--optimize-autoloader',
        '-o',
        '--with-all-dependencies',
        '-W',
        '--prefer-dist',
        '--dry-run',
    ];

    public function index()
    {
        return view('dev-tools.composer');
    }

    public function run(Request $request)
    {
        $validated = $request->validate([
            'command' => ['required', 'string', 'in:install,update'],
            'flags' => ['nullable', 'string', 'max:500'],
        ]);

        $command = $validated['command'];
        $extraFlags = $this->parseAllowedFlags($validated['flags'] ?? '');

        $processCommand = array_merge(
            $this->composerExecutable(),
            [$command, '--no-interaction'],
            $extraFlags
        );

        try {
            $result = Process::path(base_path())
                ->timeout(900)
                ->run($processCommand);

            $output = trim($result->output()."\n".$result->errorOutput());

            return response()->json([
                'success' => $result->successful(),
                'output' => $output !== '' ? $output : ($result->successful()
                    ? 'Composer finished successfully.'
                    : 'Composer failed with no output.'),
                'exit_code' => $result->exitCode(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'output' => $e->getMessage(),
                'exit_code' => 1,
            ]);
        }
    }

    /**
     * @return list<string>
     */
    private function composerExecutable(): array
    {
        $phar = base_path('composer.phar');
        if (is_file($phar)) {
            return [PHP_BINARY, $phar];
        }

        return ['composer'];
    }

    /**
     * @return list<string>
     */
    private function parseAllowedFlags(string $flags): array
    {
        $flags = trim($flags);
        if ($flags === '') {
            return [];
        }

        $tokens = preg_split('/\s+/', $flags) ?: [];
        $parsed = [];

        foreach ($tokens as $token) {
            if ($token === '') {
                continue;
            }
            if (! in_array($token, self::ALLOWED_FLAGS, true)) {
                throw ValidationException::withMessages([
                    'flags' => 'Unsupported flag: '.$token.'. Allowed: '.implode(', ', self::ALLOWED_FLAGS),
                ]);
            }
            $parsed[] = $token;
        }

        return $parsed;
    }
}
