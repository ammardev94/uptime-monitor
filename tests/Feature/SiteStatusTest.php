<?php

namespace Tests\Feature;

use App\Mail\PagesDownMail;
use App\Models\Incident;
use App\Models\Page;
use App\Models\Site;
use App\Services\UptimeChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class SiteStatusTest extends TestCase
{
    use RefreshDatabase;

    private Site $site;

    private Page $inner;

    protected function setUp(): void
    {
        parent::setUp();
        Sleep::fake();
        Mail::fake();

        $this->site = Site::create([
            'name' => 'Example', 'url' => 'https://example.test/', 'interval_minutes' => 5,
            'auto_discover' => false, 'alert_emails' => 'ops@example.test',
        ]);
        Page::create(['site_id' => $this->site->id, 'url' => 'https://example.test/', 'source' => 'home']);
        $this->inner = Page::create(['site_id' => $this->site->id, 'url' => 'https://example.test/about', 'source' => 'sitemap']);
    }

    public function test_site_is_up_when_landing_page_loads_even_if_inner_page_fails(): void
    {
        Http::fake([
            'example.test/about' => Http::response('', 500),
            'example.test/*' => Http::response('ok', 200),
        ]);

        app(UptimeChecker::class)->checkSite($this->site);

        $this->assertSame('up', $this->site->fresh()->status);
        $this->assertSame('down', $this->inner->fresh()->status);
        $this->assertSame(1, Incident::where('page_id', $this->inner->id)->whereNull('resolved_at')->count());
        Mail::assertSent(PagesDownMail::class);
    }

    public function test_site_is_down_when_landing_page_fails(): void
    {
        Http::fake([
            'example.test/about' => Http::response('ok', 200),
            'example.test/*' => Http::response('', 500),
        ]);

        app(UptimeChecker::class)->checkSite($this->site);

        $this->assertSame('down', $this->site->fresh()->status);
        $this->assertSame('up', $this->inner->fresh()->status);
    }
}
