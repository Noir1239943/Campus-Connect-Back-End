<?php

/**
 * Laravel Octane hard-codes two POSIX-only assumptions that don't hold on
 * Windows (no pcntl extension, no posix extension), which crash
 * `octane:start`/`octane:stop`/`octane:reload` there. Since `composer install`
 * re-extracts vendor/laravel/octane from scratch every time, these fixes must
 * be reapplied after every install/update rather than edited once — this
 * script does that idempotently. See PHP_CLI_SERVER_WORKERS comment in .env
 * for the related php artisan serve limitation this was set up to move past.
 */

if (! str_contains(PHP_OS_FAMILY, 'Windows')) {
    exit(0);
}

$base = __DIR__.'/../vendor/laravel/octane/src';

$signalsFile = $base.'/Commands/Concerns/InteractsWithServers.php';
$signalsFrom = 'return [SIGINT, SIGTERM, SIGHUP];';
$signalsTo = "return extension_loaded('pcntl') ? [SIGINT, SIGTERM, SIGHUP] : [];";

patch($signalsFile, $signalsFrom, $signalsTo, 'signal subscription');

$posixFile = $base.'/PosixExtension.php';
$posixFrom = <<<'PHP'
    public function kill(int $processId, int $signal)
    {
        return posix_kill($processId, $signal);
    }
PHP;
$posixTo = <<<'PHP'
    public function kill(int $processId, int $signal)
    {
        if (! extension_loaded('posix')) {
            if ($signal === 0) {
                $output = @shell_exec('tasklist /FI "PID eq '.$processId.'" /NH');

                return is_string($output) && str_contains($output, (string) $processId);
            }

            @shell_exec('taskkill /F /PID '.$processId.' >NUL 2>&1');

            return true;
        }

        return posix_kill($processId, $signal);
    }
PHP;

patch($posixFile, $posixFrom, $posixTo, 'process inspection');

function patch(string $file, string $from, string $to, string $label): void
{
    if (! file_exists($file)) {
        return;
    }

    $contents = file_get_contents($file);

    if (str_contains($contents, $to)) {
        return;
    }

    if (! str_contains($contents, $from)) {
        fwrite(STDERR, "octane windows fix: could not find expected {$label} code in {$file} — skipping (package version may have changed).\n");

        return;
    }

    file_put_contents($file, str_replace($from, $to, $contents));
    echo "octane windows fix: patched {$label} in ".basename($file).PHP_EOL;
}
