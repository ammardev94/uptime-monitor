<?php

if (! function_exists('local_time')) {
    function local_time($dt, string $format = 'j M, H:i'): string
    {
        return $dt ? $dt->copy()->timezone(config('app.display_timezone'))->format($format) : '—';
    }
}
