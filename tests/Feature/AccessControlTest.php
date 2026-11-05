<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Incident;
use App\Models\Page;
use App\Models\Role;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    private Department $sales;

    private Department $support;

    private Site $salesSite;

    private Site $supportSite;

    private Site $unassignedSite;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sales = Department::create(['name' => 'Sales']);
        $this->support = Department::create(['name' => 'Support']);
        $this->salesSite = $this->site('Sales Site');
        $this->supportSite = $this->site('Support Site');
        $this->unassignedSite = $this->site('Orphan Site');
        $this->salesSite->departments()->attach($this->sales);
        $this->supportSite->departments()->attach($this->support);
    }

    private function site(string $name): Site
    {
        $site = Site::create(['name' => $name, 'url' => 'https://'.str()->slug($name).'.test/', 'interval_minutes' => 5, 'auto_discover' => false]);
        Page::create(['site_id' => $site->id, 'url' => $site->url, 'source' => 'home']);

        return $site;
    }

    private function admin(): User
    {
        return User::factory()->create(['role_id' => Role::admin()->id]);
    }

    private function member(?Department $department, array $permissions = []): User
    {
        $role = Role::create(['name' => 'Role '.uniqid(), 'permissions' => $permissions]);

        return User::factory()->create(['role_id' => $role->id, 'department_id' => $department?->id]);
    }

    public function test_admin_sees_every_site(): void
    {
        $this->actingAs($this->admin())->get('/')
            ->assertOk()->assertSee('Sales Site')->assertSee('Support Site')->assertSee('Orphan Site');
    }

    public function test_user_only_sees_sites_of_their_department(): void
    {
        $user = $this->member($this->sales);

        $this->actingAs($user)->get('/')
            ->assertOk()->assertSee('Sales Site')->assertDontSee('Support Site')->assertDontSee('Orphan Site');
        $this->get(route('sites.show', $this->salesSite))->assertOk();
        $this->get(route('sites.show', $this->supportSite))->assertForbidden();
        $this->get(route('sites.show', $this->unassignedSite))->assertForbidden();
    }

    public function test_user_without_department_sees_no_sites(): void
    {
        $user = $this->member(null, array_keys(Role::PERMISSIONS));

        $this->actingAs($user)->get('/')
            ->assertOk()->assertDontSee('Sales Site')->assertSee('assigned to a department');
        $this->get(route('sites.show', $this->salesSite))->assertForbidden();
        $this->post(route('sites.check', $this->salesSite))->assertForbidden();
    }

    public function test_site_in_several_departments_is_visible_to_each(): void
    {
        $this->salesSite->departments()->attach($this->support);

        $this->actingAs($this->member($this->support))->get(route('sites.show', $this->salesSite))->assertOk();
    }

    public function test_non_admin_cannot_reach_admin_pages_or_settings(): void
    {
        $this->actingAs($this->member($this->sales, array_keys(Role::PERMISSIONS)));

        foreach (['admin.users.index', 'admin.roles.index', 'admin.departments.index', 'admin.assignments.index', 'settings'] as $route) {
            $this->get(route($route))->assertForbidden();
        }
        $this->post(route('admin.departments.store'), ['name' => 'Hack'])->assertForbidden();
        $this->post(route('sites.store'), ['name' => 'X', 'url' => 'https://x.test', 'interval_minutes' => 5])->assertForbidden();
        $this->delete(route('sites.destroy', $this->salesSite))->assertForbidden();
    }

    public function test_viewer_role_is_read_only(): void
    {
        $this->actingAs($this->member($this->sales));
        $page = $this->salesSite->pages()->first();

        $this->post(route('sites.check', $this->salesSite))->assertForbidden();
        $this->post(route('sites.discover', $this->salesSite))->assertForbidden();
        $this->post(route('sites.pause', $this->salesSite))->assertForbidden();
        $this->get(route('sites.edit', $this->salesSite))->assertForbidden();
        $this->post(route('pages.store', $this->salesSite), ['urls' => '/lp'])->assertForbidden();
        $this->patch(route('pages.toggle', $page))->assertForbidden();
        $this->delete(route('pages.destroy', $page))->assertForbidden();
        $this->get(route('incidents'))->assertForbidden();
        $this->get(route('sites.show', $this->salesSite))->assertDontSee('Check now')->assertDontSee('Add landing pages');
    }

    public function test_role_permissions_only_apply_to_own_department(): void
    {
        $this->actingAs($this->member($this->sales, ['sites.pause', 'pages.manage']));

        $this->post(route('sites.pause', $this->salesSite))->assertRedirect();
        $this->assertTrue($this->salesSite->fresh()->is_paused);
        $this->post(route('pages.store', $this->salesSite), ['urls' => '/lp/offer'])->assertRedirect();
        $this->assertSame(2, $this->salesSite->pages()->count());

        $this->post(route('sites.pause', $this->supportSite))->assertForbidden();
        $this->post(route('pages.store', $this->supportSite), ['urls' => '/lp/offer'])->assertForbidden();
    }

    public function test_editor_cannot_change_site_departments(): void
    {
        $this->actingAs($this->member($this->sales, ['sites.edit']));

        $this->put(route('sites.update', $this->salesSite), [
            'name' => 'Renamed', 'url' => $this->salesSite->url, 'interval_minutes' => 10,
            'departments' => [$this->support->id],
        ])->assertRedirect();

        $this->assertSame('Renamed', $this->salesSite->fresh()->name);
        $this->assertSame([$this->sales->id], $this->salesSite->departments()->pluck('departments.id')->all());
    }

    public function test_admin_assigns_sites_to_departments(): void
    {
        $this->actingAs($this->admin());

        $this->put(route('sites.update', $this->unassignedSite), [
            'name' => 'Orphan Site', 'url' => $this->unassignedSite->url, 'interval_minutes' => 5,
            'departments' => [$this->sales->id, $this->support->id],
        ])->assertRedirect();
        $this->assertSame(2, $this->unassignedSite->departments()->count());

        $this->put(route('admin.departments.update', $this->sales), ['name' => 'Sales', 'sites' => [$this->supportSite->id]])->assertRedirect();
        $this->assertSame([$this->supportSite->id], $this->sales->sites()->pluck('sites.id')->all());
    }

    public function test_incidents_are_scoped_to_department(): void
    {
        foreach ([$this->salesSite, $this->supportSite] as $site) {
            Incident::create(['site_id' => $site->id, 'page_id' => $site->pages()->first()->id, 'reason' => "Down at {$site->name}", 'started_at' => now()]);
        }

        $this->actingAs($this->member($this->sales, ['incidents.view']))->get(route('incidents'))
            ->assertOk()->assertSee('Down at Sales Site')->assertDontSee('Down at Support Site');
    }

    public function test_assignment_page_excludes_admins(): void
    {
        $admin = $this->admin();
        $user = $this->member(null);

        $this->actingAs($admin)->get(route('admin.assignments.index'))
            ->assertOk()->assertSee($user->email)->assertDontSee($admin->email);

        $this->put(route('admin.assignments.update'), ['assignments' => [$user->id => $this->support->id]])->assertRedirect();
        $this->assertSame($this->support->id, $user->fresh()->department_id);

        $this->put(route('admin.assignments.update'), ['assignments' => [$admin->id => $this->sales->id]])->assertSessionHasErrors('assignments');
        $this->assertNull($admin->fresh()->department_id);
    }

    public function test_admin_manages_users_and_roles(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        $viewer = Role::where('name', 'Viewer')->first();

        $this->post(route('admin.roles.store'), ['name' => 'Operator', 'permissions' => ['sites.check', 'bogus']])->assertSessionHasErrors('permissions.1');
        $this->post(route('admin.roles.store'), ['name' => 'Operator', 'permissions' => ['sites.check']])->assertRedirect();

        $this->post(route('admin.users.store'), [
            'name' => 'Sam', 'email' => 'sam@example.com', 'role_id' => $viewer->id,
            'password' => 'secret-pass', 'password_confirmation' => 'secret-pass',
        ])->assertRedirect();
        $sam = User::where('email', 'sam@example.com')->first();
        $sam->update(['department_id' => $this->sales->id]);

        // Promoting to Admin removes the department
        $this->put(route('admin.users.update', $sam), ['name' => 'Sam', 'email' => 'sam@example.com', 'role_id' => Role::admin()->id])->assertRedirect();
        $this->assertTrue($sam->fresh()->isAdmin());
        $this->assertNull($sam->fresh()->department_id);

        // Roles in use and the Admin role cannot be deleted
        $this->delete(route('admin.roles.destroy', Role::admin()))->assertSessionHasErrors('role');
        $this->delete(route('admin.users.destroy', $admin))->assertSessionHasErrors('user');
    }

    public function test_last_admin_cannot_be_demoted(): void
    {
        $admin = $this->admin();
        $viewer = Role::where('name', 'Viewer')->first();

        $this->actingAs($admin)->put(route('admin.users.update', $admin), ['name' => $admin->name, 'email' => $admin->email, 'role_id' => $viewer->id])
            ->assertSessionHasErrors('role_id');
        $this->assertTrue($admin->fresh()->isAdmin());
    }

    public function test_admin_pages_render(): void
    {
        $member = $this->member($this->sales);
        $this->actingAs($this->admin());

        foreach ([
            route('admin.users.index'), route('admin.users.create'), route('admin.users.edit', $member),
            route('admin.roles.index'), route('admin.roles.create'), route('admin.roles.edit', $member->role),
            route('admin.departments.index'), route('admin.departments.create'), route('admin.departments.edit', $this->sales),
            route('admin.assignments.index'), route('settings'), route('incidents'),
            route('sites.show', $this->salesSite), route('sites.edit', $this->salesSite),
        ] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get(route('admin.roles.edit', Role::admin()))->assertRedirect(route('admin.roles.index'));
    }

    public function test_create_user_command_makes_an_admin(): void
    {
        $this->artisan('monitor:create-user', ['email' => 'boss@example.com', '--password' => 'secret-pass'])->assertSuccessful();

        $this->assertTrue(User::where('email', 'boss@example.com')->first()->isAdmin());
    }
}
