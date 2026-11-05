<?php

use App\Models\Role;
use App\Models\Site;
use App\Models\SiteCheck;
use App\Models\User;
use App\Services\PageDiscovery;
use App\Services\UptimeChecker;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
| Monitoring commands
*/

Artisan::command('monitor:run {--site= : Only check this site ID} {--force : Ignore the check interval}', function (UptimeChecker $checker) {
    $sites = Site::query()
        ->when($this->option('site'), fn ($q, $id) => $q->whereKey($id))
        ->where('is_paused', false)
        ->get()
        ->filter(fn (Site $s) => $this->option('force') || $s->isDue());

    foreach ($sites as $site) {
        $r = $checker->checkSite($site);
        $this->line(sprintf('%-30s %d/%d up  (%d down, %d recovered)', $site->name, $r['up'], $r['total'], $r['went_down'], $r['recovered']));
    }
    if ($sites->isEmpty()) {
        $this->line('No sites due for a check.');
    }
})->purpose('Check all pages of sites that are due and send alerts');

Artisan::command('monitor:discover {--site= : Only this site ID}', function (PageDiscovery $discovery, UptimeChecker $checker) {
    $sites = Site::query()->when($this->option('site'), fn ($q, $id) => $q->whereKey($id))->get();
    foreach ($sites as $site) {
        $r = $discovery->discover($site);
        $days = $checker->checkSsl($site);
        $this->line(sprintf('%-30s via %-7s found %d, added %d, deactivated %d, SSL %s', $site->name, $r['method'], $r['found'], $r['added'], $r['deactivated'], $days === null ? 'n/a' : "$days days"));
    }
})->purpose('Re-read sitemaps to find new/removed pages and check SSL expiry');

Artisan::command('monitor:prune {--days=90}', function () {
    $n = SiteCheck::where('created_at', '<', now()->subDays((int) $this->option('days')))->delete();
    $this->line("Deleted $n old check records.");
})->purpose('Delete old check history');

Artisan::command('monitor:create-user {email} {--name=Admin} {--password=}', function () {
    $role = Role::admin();
    if (! $role) {
        $this->error('No Admin role found. Run php artisan migrate first.');

        return 1;
    }
    $password = $this->option('password') ?: $this->secret('Password');
    $user = User::updateOrCreate(
        ['email' => $this->argument('email')],
        ['name' => $this->option('name'), 'password' => bcrypt($password), 'role_id' => $role->id, 'department_id' => null]
    );
    $this->info("Admin {$user->email} is ready. You can log in now; add other users under Admin → Users.");
})->purpose('Create or reset an Admin dashboard login');

/*
| Schedule — requires the server cron:  * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
*/
Schedule::command('monitor:run')->everyMinute()->withoutOverlapping(10)->runInBackground();
Schedule::command('monitor:discover')->dailyAt('03:00')->withoutOverlapping();
Schedule::command('monitor:prune')->weekly();
