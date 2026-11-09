<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteCreatorTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $name, string $email): User
    {
        return User::factory()->create(['name' => $name, 'email' => $email, 'role_id' => Role::admin()->id]);
    }

    private function site(string $name, ?User $creator = null): Site
    {
        $site = new Site(['name' => $name, 'url' => 'https://'.str()->slug($name).'.test/', 'interval_minutes' => 5, 'auto_discover' => false]);
        $site->created_by = $creator?->id;
        $site->save();

        return $site;
    }

    public function test_command_links_sites_without_creator_to_first_admin(): void
    {
        $first = $this->admin('First Admin', 'first@example.test');
        $second = $this->admin('Second Admin', 'second@example.test');
        $orphan = $this->site('Orphan');
        $owned = $this->site('Owned', $second);

        $this->artisan('monitor:link-sites-to-admin')
            ->expectsOutputToContain('Linked 1 site(s) to First Admin')
            ->assertExitCode(0);

        $this->assertSame($first->id, $orphan->fresh()->created_by);
        $this->assertSame($second->id, $owned->fresh()->created_by, 'sites that already have a creator are left alone');
    }

    public function test_command_can_target_a_specific_admin(): void
    {
        $this->admin('First Admin', 'first@example.test');
        $second = $this->admin('Second Admin', 'second@example.test');
        $orphan = $this->site('Orphan');

        $this->artisan('monitor:link-sites-to-admin', ['--admin' => 'second@example.test'])->assertExitCode(0);

        $this->assertSame($second->id, $orphan->fresh()->created_by);
    }

    public function test_command_refuses_a_non_admin_email(): void
    {
        $viewer = User::factory()->create(['email' => 'viewer@example.test', 'role_id' => Role::where('is_admin', false)->first()->id]);
        $orphan = $this->site('Orphan');

        $this->artisan('monitor:link-sites-to-admin', ['--admin' => $viewer->email])->assertExitCode(1);

        $this->assertNull($orphan->fresh()->created_by);
    }

    public function test_command_is_a_no_op_when_every_site_has_a_creator(): void
    {
        $admin = $this->admin('First Admin', 'first@example.test');
        $this->site('Owned', $admin);

        $this->artisan('monitor:link-sites-to-admin')
            ->expectsOutputToContain('Nothing to do')
            ->assertExitCode(0);
    }

    public function test_dashboard_shows_who_added_each_site(): void
    {
        $admin = $this->admin('Olivia Example', 'olivia@example.test');
        $this->site('Owned', $admin);
        $this->site('Orphan');

        // View as a different Admin, so the creator's name can't come from the nav bar
        $viewer = $this->admin('Someone Else', 'else@example.test');

        $this->actingAs($viewer)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Added by')
            ->assertSee('Olivia Example');
    }
}
