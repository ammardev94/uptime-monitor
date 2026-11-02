<?php

namespace App\Services;

use App\Models\Page;
use App\Models\Setting;
use App\Models\Site;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Finds the pages of a site: sitemap.xml (incl. robots.txt + sitemap indexes),
 * falling back to the links on the homepage. Manual pages are never touched.
 */
class PageDiscovery
{
    private int $max;
    private array $visitedSitemaps = [];

    public function discover(Site $site): array
    {
        $this->max = (int) Setting::get('max_pages_per_site');
        $this->visitedSitemaps = [];
        $home = UrlHelper::normalize($site->url);
        $found = [];
        $method = 'none';

        if ($site->auto_discover) {
            foreach ($this->sitemapCandidates($site) as $sitemapUrl) {
                $this->readSitemap($sitemapUrl, $site->host(), $found, 0);
                if (count($found) >= $this->max) {
                    break;
                }
            }
            $method = $found ? 'sitemap' : 'none';

            if (! $found) {
                $found = $this->crawlHomepage($home, $site->host());
                $method = $found ? 'crawl' : 'none';
            }
        }

        $found = array_slice(array_values(array_unique($found)), 0, $this->max);
        $source = $method === 'sitemap' ? 'sitemap' : 'crawl';

        // Homepage is always monitored
        Page::updateOrCreate(['site_id' => $site->id, 'url' => $home], ['source' => 'home', 'is_active' => true]);

        $added = 0;
        foreach ($found as $url) {
            if ($url === $home) {
                continue;
            }
            $page = Page::firstOrNew(['site_id' => $site->id, 'url' => $url]);
            if (! $page->exists) {
                $page->source = $source;
                $added++;
            }
            if ($page->source !== 'manual') {
                $page->is_active = true;
            }
            $page->save();
        }

        // Pages that disappeared from the sitemap/crawl are deactivated (kept for history)
        $deactivated = 0;
        if ($site->auto_discover && $found) {
            $deactivated = $site->pages()
                ->whereIn('source', ['sitemap', 'crawl'])
                ->where('is_active', true)
                ->whereNotIn('url', array_merge($found, [$home]))
                ->update(['is_active' => false]);
        }

        $site->update(['last_discovered_at' => now()]);

        return ['method' => $method, 'found' => count($found), 'added' => $added, 'deactivated' => $deactivated];
    }

    private function client()
    {
        return Http::withHeaders(['User-Agent' => UptimeChecker::userAgent(), 'Accept' => '*/*'])
            ->timeout((int) Setting::get('timeout_seconds'))
            ->withOptions(['allow_redirects' => ['max' => 5]]);
    }

    private function sitemapCandidates(Site $site): array
    {
        $p = parse_url($site->url);
        $origin = $p['scheme'].'://'.$p['host'].(isset($p['port']) ? ':'.$p['port'] : '');
        $list = [];

        try {
            $robots = $this->client()->get($origin.'/robots.txt');
            if ($robots->successful() && preg_match_all('/^\s*sitemap:\s*(\S+)/im', $robots->body(), $m)) {
                $list = $m[1];
            }
        } catch (Throwable) {
        }

        return array_values(array_unique(array_merge($list, [
            $origin.'/sitemap.xml',
            $origin.'/sitemap_index.xml',
            $origin.'/wp-sitemap.xml',
        ])));
    }

    private function readSitemap(string $url, string $host, array &$found, int $depth): void
    {
        if ($depth > 3 || isset($this->visitedSitemaps[$url]) || count($this->visitedSitemaps) > 50 || count($found) >= $this->max) {
            return;
        }
        $this->visitedSitemaps[$url] = true;

        try {
            $res = $this->client()->get($url);
        } catch (Throwable) {
            return;
        }
        if (! $res->successful()) {
            return;
        }
        $xml = $res->body();
        if (str_starts_with($xml, "\x1f\x8b")) {
            $xml = @gzdecode($xml) ?: '';
        }
        if (! preg_match_all('#<loc>\s*(?:<!\[CDATA\[)?\s*(.*?)\s*(?:\]\]>)?\s*</loc>#is', $xml, $m)) {
            return;
        }

        if (stripos($xml, '<sitemapindex') !== false) {
            foreach ($m[1] as $child) {
                // skip image/video sub-sitemaps
                if (! preg_match('/(image|video)/i', $child)) {
                    $this->readSitemap(trim($child), $host, $found, $depth + 1);
                }
            }

            return;
        }

        foreach ($m[1] as $loc) {
            $u = UrlHelper::normalize($loc);
            if ($u && UrlHelper::sameHost($u, $host) && UrlHelper::looksLikePage($u)) {
                $found[] = $u;
                if (count($found) >= $this->max) {
                    return;
                }
            }
        }
    }

    private function crawlHomepage(string $home, string $host): array
    {
        try {
            $res = $this->client()->get($home);
        } catch (Throwable) {
            return [];
        }
        if (! $res->successful()) {
            return [];
        }
        $base = (string) ($res->effectiveUri() ?? $home);
        preg_match_all('/<a\s[^>]*href\s*=\s*["\']([^"\']+)["\']/i', $res->body(), $m);

        $found = [];
        foreach ($m[1] as $href) {
            $u = UrlHelper::resolve($href, $base);
            if ($u && UrlHelper::sameHost($u, $host) && UrlHelper::looksLikePage($u)) {
                $found[] = $u;
            }
        }

        return array_slice(array_values(array_unique($found)), 0, $this->max);
    }
}
