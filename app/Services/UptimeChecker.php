<?php

namespace App\Services;

use App\Mail\PagesDownMail;
use App\Mail\PagesRecoveredMail;
use App\Mail\SslExpiringMail;
use App\Models\Incident;
use App\Models\Page;
use App\Models\Setting;
use App\Models\Site;
use App\Models\SiteCheck;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class UptimeChecker
{
    public static function userAgent(): string
    {
        return 'Mozilla/5.0 (compatible; '.Str::slug(config('app.name')).'-uptime/1.0; +'.config('app.url').')';
    }

    /** Check every active page of a site, record results and send alerts. */
    public function checkSite(Site $site): array
    {
        if ($site->pages()->count() === 0) {
            app(PageDiscovery::class)->discover($site);
        }

        $pages = $site->activePages()->get();
        $results = $this->probe($pages, $site);

        // Confirm failures with a second request a few seconds later (avoids false alarms from blips)
        $failed = $pages->filter(fn (Page $p) => ! $results[$p->id]['up']);
        if ($failed->isNotEmpty()) {
            sleep(3);
            foreach ($this->probe($failed, $site) as $id => $retry) {
                $results[$id] = $retry;
            }
        }

        $wentDown = [];
        $recovered = [];
        $times = [];

        foreach ($pages as $page) {
            $r = $results[$page->id];
            $previous = $page->status;
            $newStatus = $r['up'] ? 'up' : 'down';

            if ($r['ms'] !== null && $r['up']) {
                $times[] = $r['ms'];
            }

            if ($newStatus === 'down' && $previous !== 'down') {
                Incident::create([
                    'site_id' => $site->id,
                    'page_id' => $page->id,
                    'reason' => $r['error'],
                    'status_code' => $r['code'],
                    'started_at' => now(),
                ]);
                $wentDown[] = ['page' => $page, 'reason' => $r['error'], 'code' => $r['code']];
            }

            if ($newStatus === 'up' && $previous === 'down') {
                $incident = $page->openIncident();
                $incident?->update(['resolved_at' => now()]);
                $recovered[] = ['page' => $page, 'duration' => $incident?->duration() ?? '—'];
            }

            $page->fill([
                'status' => $newStatus,
                'status_code' => $r['code'],
                'response_ms' => $r['ms'],
                'error' => $r['up'] ? null : Str::limit($r['error'], 490),
                'last_checked_at' => now(),
            ]);
            if ($previous !== $newStatus) {
                $page->status_changed_at = now();
            }
            $page->save();
        }

        $total = $pages->count();
        $up = collect($results)->where('up', true)->count();
        $site->update([
            'status' => $total === 0 ? 'pending' : ($up === $total ? 'up' : ($up === 0 ? 'down' : 'partial')),
            'last_checked_at' => now(),
        ]);

        SiteCheck::create([
            'site_id' => $site->id,
            'pages_total' => $total,
            'pages_up' => $up,
            'avg_response_ms' => $times ? (int) round(array_sum($times) / count($times)) : null,
            'created_at' => now(),
        ]);

        $this->notify($site, $wentDown, $recovered);

        return ['total' => $total, 'up' => $up, 'went_down' => count($wentDown), 'recovered' => count($recovered)];
    }

    /** @return array<int, array{up: bool, code: ?int, ms: ?int, error: ?string}> keyed by page id */
    private function probe($pages, Site $site): array
    {
        $timeout = (int) Setting::get('timeout_seconds');
        $concurrency = max(1, (int) Setting::get('concurrency'));
        $treat403 = Setting::get('treat_403_as_up') === '1';
        $homeUrl = UrlHelper::normalize($site->url);
        $out = [];

        foreach ($pages->chunk($concurrency) as $chunk) {
            $responses = Http::pool(function (Pool $pool) use ($chunk, $timeout) {
                foreach ($chunk as $page) {
                    $pool->as((string) $page->id)
                        ->withHeaders([
                            'User-Agent' => self::userAgent(),
                            'Accept' => 'text/html,application/xhtml+xml,*/*;q=0.8',
                            'Cache-Control' => 'no-cache',
                        ])
                        ->timeout($timeout)
                        ->connectTimeout(min(10, $timeout))
                        ->withOptions(['allow_redirects' => ['max' => 5]])
                        ->get($page->url);
                }
            });

            foreach ($chunk as $page) {
                $res = $responses[(string) $page->id] ?? null;

                if (! $res instanceof Response) {
                    $msg = $res instanceof Throwable ? $res->getMessage() : 'No response';
                    $out[$page->id] = ['up' => false, 'code' => null, 'ms' => null, 'error' => $this->cleanError($msg)];
                    continue;
                }

                $code = $res->status();
                $ms = isset($res->handlerStats()['total_time']) ? (int) round($res->handlerStats()['total_time'] * 1000) : null;
                $up = $code < 400 || ($treat403 && in_array($code, [401, 403, 429]));
                $error = $up ? null : "HTTP $code ".$res->reason();

                if ($up && $site->keyword && $page->url === $homeUrl && stripos($res->body(), $site->keyword) === false) {
                    $up = false;
                    $error = 'Keyword "'.$site->keyword.'" not found on page';
                }

                $out[$page->id] = ['up' => $up, 'code' => $code, 'ms' => $ms, 'error' => $error];
            }
        }

        return $out;
    }

    private function cleanError(string $msg): string
    {
        return match (true) {
            str_contains($msg, 'Could not resolve host') => 'DNS failure: domain does not resolve',
            str_contains($msg, 'timed out'), str_contains($msg, 'Timeout') => 'Timeout: no response within '.Setting::get('timeout_seconds').'s',
            str_contains($msg, 'SSL'), str_contains($msg, 'certificate') => 'SSL error: '.Str::after($msg, 'error: '),
            str_contains($msg, 'Connection refused') => 'Connection refused by server',
            default => Str::limit(preg_replace(['/\s*\(see https?:\S+\)/', '/^cURL error \d+:\s*/'], '', $msg), 300),
        };
    }

    private function notify(Site $site, array $wentDown, array $recovered): void
    {
        $to = $site->recipients();
        if (! $to) {
            return;
        }
        try {
            if ($wentDown) {
                Mail::to($to)->send(new PagesDownMail($site, $wentDown));
            }
            if ($recovered) {
                Mail::to($to)->send(new PagesRecoveredMail($site, $recovered));
            }
        } catch (Throwable $e) {
            Log::error('Uptime alert email failed: '.$e->getMessage());
        }
    }

    /** Check SSL certificate expiry for the site's homepage host and warn once. */
    public function checkSsl(Site $site): ?int
    {
        if (parse_url($site->url, PHP_URL_SCHEME) !== 'https') {
            return null;
        }
        $host = $site->host();
        $ctx = stream_context_create(['ssl' => ['capture_peer_cert' => true, 'verify_peer' => false, 'verify_peer_name' => false, 'SNI_enabled' => true, 'peer_name' => $host]]);
        $client = @stream_socket_client("ssl://$host:443", $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
        if (! $client) {
            return null;
        }
        $cert = stream_context_get_params($client)['options']['ssl']['peer_certificate'] ?? null;
        fclose($client);
        $info = $cert ? openssl_x509_parse($cert) : null;
        if (! $info || empty($info['validTo_time_t'])) {
            return null;
        }

        $expires = now()->setTimestamp($info['validTo_time_t']);
        $days = (int) floor(now()->diffInDays($expires, false));
        $warn = (int) Setting::get('ssl_warning_days');

        $site->ssl_expires_at = $expires;
        if ($days <= $warn && ! $site->ssl_alert_sent && $site->recipients()) {
            try {
                Mail::to($site->recipients())->send(new SslExpiringMail($site, $days));
                $site->ssl_alert_sent = true;
            } catch (Throwable $e) {
                Log::error('SSL alert email failed: '.$e->getMessage());
            }
        } elseif ($days > $warn) {
            $site->ssl_alert_sent = false;
        }
        $site->save();

        return $days;
    }
}
