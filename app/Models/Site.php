<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    protected $fillable = [
        'name', 'url', 'interval_minutes', 'auto_discover', 'keyword', 'alert_emails',
        'is_paused', 'status', 'last_checked_at', 'last_discovered_at', 'ssl_expires_at', 'ssl_alert_sent',
    ];

    protected function casts(): array
    {
        return [
            'auto_discover' => 'boolean',
            'is_paused' => 'boolean',
            'ssl_alert_sent' => 'boolean',
            'last_checked_at' => 'datetime',
            'last_discovered_at' => 'datetime',
            'ssl_expires_at' => 'datetime',
        ];
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public function activePages(): HasMany
    {
        return $this->hasMany(Page::class)->where('is_active', true);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function checks(): HasMany
    {
        return $this->hasMany(SiteCheck::class);
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class);
    }

    /** Sites the user may see: all for admins, their department's sites otherwise, none without a department. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }
        if ($user->department_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('departments', fn ($q) => $q->whereKey($user->department_id));
    }

    public function host(): string
    {
        return strtolower((string) parse_url($this->url, PHP_URL_HOST));
    }

    public function isDue(): bool
    {
        if ($this->is_paused) {
            return false;
        }

        return ! $this->last_checked_at
            || $this->last_checked_at->lte(now()->subMinutes($this->interval_minutes)->addSeconds(20));
    }

    /** Uptime % over the last N hours: share of check runs where every page was up. */
    public function uptime(int $hours = 24): ?float
    {
        $row = $this->checks()
            ->where('created_at', '>=', now()->subHours($hours))
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN pages_up = pages_total THEN 1 ELSE 0 END) as ok')
            ->first();

        return $row && $row->total > 0 ? round($row->ok / $row->total * 100, 2) : null;
    }

    public function recipients(): array
    {
        $global = Setting::get('alert_emails', '');
        $list = array_merge(explode(',', (string) $global), explode(',', (string) $this->alert_emails));

        return array_values(array_unique(array_filter(array_map('trim', $list), fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))));
    }
}
