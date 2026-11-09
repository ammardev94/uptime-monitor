<?php

namespace Tests\Feature;

use App\Mail\PagesDownMail;
use App\Mail\SiteDownMail;
use App\Models\Page;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Site;
use App\Models\User;
use App\Services\UptimeChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class AdminSiteDownAlertTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sleep::fake();
        Mail::fake();
    }

    private function admin(string $email = 'owner@example.test'): User
    {
        return User::factory()->create(['email' => $email, 'role_id' => Role::admin()->id]);
    }

    private function site(?User $creator, string $status = 'up', ?string $alertEmails = null): Site
    {
        $site = new Site(['name' => 'Example', 'url' => 'https://example.test/', 'interval_minutes' => 5, 'auto_discover' => false, 'status' => $status, 'alert_emails' => $alertEmails]);
        $site->created_by = $creator?->id;
        $site->save();
        Page::create(['site_id' => $site->id, 'url' => 'https://example.test/', 'source' => 'home', 'status' => $status]);

        return $site;
    }

    private function homeReturns(int $code): void
    {
        Http::fake(['example.test/*' => Http::response('', $code)]);
    }

    public function test_admin_can_create_another_admin_user(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('admin.users.store'), [
            'name' => 'New Admin', 'email' => 'new-admin@example.test', 'role_id' => Role::admin()->id,
            'password' => 'secret-pass', 'password_confirmation' => 'secret-pass',
        ])->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'new-admin@example.test')->first();
        $this->assertTrue($user->isAdmin());
        $this->assertNull($user->department_id);
    }

    public function test_admin_who_added_the_site_is_emailed_when_it_goes_down(): void
    {
        $owner = $this->admin();
        $site = $this->site($owner);
        $this->homeReturns(503);

        app(UptimeChecker::class)->checkSite($site);

        $this->assertSame('down', $site->fresh()->status);
        Mail::assertSent(SiteDownMail::class, fn (SiteDownMail $m) => $m->hasTo($owner->email) && $m->site->is($site) && str_contains($m->reason, '503'));
    }

    public function test_creator_is_emailed_only_once_while_the_site_stays_down(): void
    {
        $site = $this->site($this->admin());
        $this->homeReturns(500);

        app(UptimeChecker::class)->checkSite($site);
        app(UptimeChecker::class)->checkSite($site->fresh());

        Mail::assertSent(SiteDownMail::class, 1);
    }

    public function test_no_creator_email_when_only_an_inner_page_fails(): void
    {
        $site = $this->site($this->admin());
        Page::create(['site_id' => $site->id, 'url' => 'https://example.test/about', 'source' => 'sitemap', 'status' => 'up']);
        Http::fake([
            'example.test/about' => Http::response('', 500),
            'example.test/*' => Http::response('ok', 200),
        ]);

        app(UptimeChecker::class)->checkSite($site);

        $this->assertSame('up', $site->fresh()->status);
        Mail::assertNotSent(SiteDownMail::class);
    }

    public function test_non_admin_creator_is_not_emailed(): void
    {
        $viewer = User::factory()->create(['role_id' => Role::where('is_admin', false)->first()->id]);
        $site = $this->site($viewer);
        $this->homeReturns(500);

        app(UptimeChecker::class)->checkSite($site);

        Mail::assertNotSent(SiteDownMail::class);
    }

    public function test_creator_already_on_the_alert_list_gets_only_the_regular_alert(): void
    {
        $owner = $this->admin();
        Setting::put('alert_emails', 'Owner@Example.test');
        $site = $this->site($owner);
        $this->homeReturns(500);

        app(UptimeChecker::class)->checkSite($site);

        Mail::assertSent(PagesDownMail::class, fn (PagesDownMail $m) => $m->hasTo('Owner@Example.test'));
        Mail::assertNotSent(SiteDownMail::class);
    }
}
