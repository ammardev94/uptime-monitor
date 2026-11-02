<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Runs artisan commands in a background process so web requests return instantly
 * (MAMP / shared hosts often kill requests after ~30 seconds).
 */
class Background
{
    public static function phpBinary(): ?string
    {
        $candidates = array_filter([
            env('PHP_CLI_PATH'),
            dirname(PHP_BINARY).'/php',
            str_ends_with(PHP_BINARY, '/php') ? PHP_BINARY : null,
            '/Applications/MAMP/bin/php/php'.PHP_VERSION.'/bin/php',
            '/opt/homebrew/bin/php',
            '/usr/local/bin/php',
            '/usr/bin/php',
        ]);
        foreach ($candidates as $bin) {
            if (@is_file($bin) && @is_executable($bin)) {
                return $bin;
            }
        }

        return null;
    }

    /** @param string[] $commands artisan commands, run one after another */
    public static function artisan(array $commands): bool
    {
        $php = self::phpBinary();
        if (! $php || ! function_exists('exec') || in_array('exec', array_map('trim', explode(',', (string) ini_get('disable_functions'))))) {
            return false;
        }
        $artisan = escapeshellarg(base_path('artisan'));
        $chain = implode(' && ', array_map(fn ($c) => escapeshellarg($php).' '.$artisan.' '.$c, $commands));
        $log = escapeshellarg(storage_path('logs/background.log'));

        try {
            exec('('.$chain.') >> '.$log.' 2>&1 &');

            return true;
        } catch (\Throwable $e) {
            Log::warning('Background run failed: '.$e->getMessage());

            return false;
        }
    }
}
