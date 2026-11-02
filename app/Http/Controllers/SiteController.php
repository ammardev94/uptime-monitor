<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\Page;
use App\Models\Site;
use App\Services\Background;
use App\Services\PageDiscovery;
use App\Services\UptimeChecker;
use App\Services\UrlHelper;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function index()
    {
        $sites = Site::withCount([
            'activePages as pages_total',
            'activePages as pages_down' => fn ($q) => $q->where('status', 'down'),
        ])->orderByRaw("CASE status WHEN 'down' THEN 0 WHEN 'partial' THEN 1 WHEN 'pending' THEN 2 WHEN 'up' THEN 3 ELSE 4 END")
            ->orderBy('name')->get();

        $lastChecks = \App\Models\SiteCheck::query()
            ->whereIn('id', fn ($q) => $q->selectRaw('MAX(id)')->from('site_checks')->groupBy('site_id'))
            ->get()->keyBy('site_id');

        return view('dashboard', [
            'sites' => $sites,
            'lastChecks' => $lastChecks,
            'openIncidents' => Incident::whereNull('resolved_at')->count(),
            'pagesTotal' => Page::where('is_active', true)->count(),
        ]);
    }

    public function store(Request $request, PageDiscovery $discovery, UptimeChecker $checker)
    {
        $data = $this->validated($request);
        $site = Site::create($data);

        if (Background::artisan(["monitor:discover --site={$site->id}", "monitor:run --site={$site->id} --force"])) {
            return redirect()->route('sites.show', $site)
                ->with('ok', "Added {$site->name}. Finding its pages and running the first check in the background — refresh in a minute.");
        }

        set_time_limit(300);
        $r = $discovery->discover($site);
        $checker->checkSsl($site);
        $checker->checkSite($site);

        return redirect()->route('sites.show', $site)
            ->with('ok', "Added {$site->name}. Found {$r['found']} pages via ".($r['method'] === 'none' ? 'homepage only' : $r['method']).' and ran the first check.');
    }

    public function show(Site $site, Request $request)
    {
        $filter = $request->query('filter', 'all');
        $pages = $site->pages()
            ->when($filter === 'down', fn ($q) => $q->where('status', 'down')->where('is_active', true))
            ->when($filter === 'paused', fn ($q) => $q->where('is_active', false))
            ->orderByRaw("CASE WHEN is_active = 0 THEN 3 WHEN status = 'down' THEN 0 WHEN source = 'home' THEN 1 ELSE 2 END")
            ->orderBy('url')->get();

        $checks = $site->checks()->where('created_at', '>=', now()->subHours(24))->orderBy('created_at')->get();

        // 48 half-hour buckets for the uptime bar
        $buckets = [];
        for ($i = 47; $i >= 0; $i--) {
            $from = now()->subMinutes(($i + 1) * 30);
            $to = now()->subMinutes($i * 30);
            $in = $checks->filter(fn ($c) => $c->created_at->gt($from) && $c->created_at->lte($to));
            $buckets[] = [
                'from' => $from, 'to' => $to,
                'state' => $in->isEmpty() ? 'none' : ($in->every(fn ($c) => $c->pages_up === $c->pages_total) ? 'up' : ($in->contains(fn ($c) => $c->pages_up === 0) ? 'down' : 'partial')),
            ];
        }

        return view('sites.show', [
            'site' => $site,
            'pages' => $pages,
            'filter' => $filter,
            'checks' => $checks,
            'buckets' => $buckets,
            'incidents' => $site->incidents()->with('page')->latest('started_at')->limit(15)->get(),
            'counts' => [
                'all' => $site->pages()->count(),
                'down' => $site->activePages()->where('status', 'down')->count(),
                'paused' => $site->pages()->where('is_active', false)->count(),
            ],
        ]);
    }

    public function edit(Site $site)
    {
        return view('sites.edit', ['site' => $site]);
    }

    public function update(Request $request, Site $site)
    {
        $data = $this->validated($request);
        $urlChanged = UrlHelper::normalize($data['url']) !== UrlHelper::normalize($site->url);
        $site->update($data);

        if ($urlChanged) {
            app(PageDiscovery::class)->discover($site);
        }

        return redirect()->route('sites.show', $site)->with('ok', 'Site updated.');
    }

    public function destroy(Site $site)
    {
        $site->delete();

        return redirect()->route('dashboard')->with('ok', "Deleted {$site->name}.");
    }

    public function check(Site $site, UptimeChecker $checker)
    {
        if (Background::artisan(["monitor:run --site={$site->id} --force"])) {
            return back()->with('ok', 'Checking all pages in the background — refresh in a moment.');
        }
        set_time_limit(300);
        $r = $checker->checkSite($site);

        return back()->with('ok', "Checked {$r['total']} pages: {$r['up']} up".($r['total'] - $r['up'] ? ', '.($r['total'] - $r['up']).' down' : '').'.');
    }

    public function discover(Site $site, PageDiscovery $discovery, UptimeChecker $checker)
    {
        if (Background::artisan(["monitor:discover --site={$site->id}"])) {
            return back()->with('ok', 'Re-scanning pages in the background — refresh in a moment.');
        }
        set_time_limit(300);
        $r = $discovery->discover($site);
        $checker->checkSsl($site);

        return back()->with('ok', "Discovery via {$r['method']}: {$r['found']} pages found, {$r['added']} new, {$r['deactivated']} no longer listed.");
    }

    public function togglePause(Site $site)
    {
        $site->update(['is_paused' => ! $site->is_paused, 'status' => $site->is_paused ? 'pending' : 'paused']);

        return back()->with('ok', $site->is_paused ? 'Monitoring paused — no checks or alerts.' : 'Monitoring resumed.');
    }

    public function addPage(Request $request, Site $site)
    {
        $request->validate(['urls' => 'required|string']);
        $added = 0;
        $skipped = [];
        foreach (preg_split('/[\s,]+/', $request->input('urls')) as $raw) {
            if ($raw === '') {
                continue;
            }
            $url = str_starts_with($raw, '/') ? UrlHelper::resolve($raw, $site->url) : UrlHelper::normalize($raw);
            if (! $url) {
                $skipped[] = $raw;
                continue;
            }
            $page = Page::firstOrNew(['site_id' => $site->id, 'url' => $url]);
            $page->source = $page->exists ? $page->source : 'manual';
            $page->is_active = true;
            $page->save();
            $added++;
        }

        return back()->with('ok', "Added $added page(s).".($skipped ? ' Skipped invalid: '.implode(', ', $skipped) : ''));
    }

    public function togglePage(Page $page)
    {
        $page->update(['is_active' => ! $page->is_active]);
        if (! $page->is_active) {
            $page->incidents()->whereNull('resolved_at')->update(['resolved_at' => now()]);
            $page->update(['status' => 'pending']);
        }

        return back()->with('ok', $page->is_active ? 'Page will be monitored again.' : 'Page excluded from monitoring.');
    }

    public function destroyPage(Page $page)
    {
        $page->delete();

        return back()->with('ok', 'Page removed.');
    }

    public function incidents(Request $request)
    {
        $open = $request->query('open') === '1';

        return view('incidents', [
            'open' => $open,
            'incidents' => Incident::with(['site', 'page'])
                ->when($open, fn ($q) => $q->whereNull('resolved_at'))
                ->latest('started_at')->paginate(50)->withQueryString(),
        ]);
    }

    private function validated(Request $request): array
    {
        $request->merge(['url' => $this->withScheme((string) $request->input('url'))]);
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'url' => 'required|url:http,https|max:500',
            'interval_minutes' => 'required|integer|in:1,2,3,5,10,15,30,60',
            'keyword' => 'nullable|string|max:200',
            'alert_emails' => 'nullable|string|max:500',
        ]);
        $data['auto_discover'] = $request->boolean('auto_discover');
        $data['url'] = UrlHelper::normalize($data['url']);

        return $data;
    }

    private function withScheme(string $url): string
    {
        $url = trim($url);

        return $url !== '' && ! preg_match('#^https?://#i', $url) ? 'https://'.$url : $url;
    }
}
