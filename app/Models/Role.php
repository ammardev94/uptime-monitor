<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    /**
     * Actions a non-admin role can be granted. They only ever apply to sites in the
     * user's own department; viewing those sites needs no permission.
     * Admin roles bypass this list entirely.
     */
    public const PERMISSIONS = [
        'sites.check' => 'Run checks & re-scan pages',
        'sites.pause' => 'Pause / resume monitoring',
        'sites.edit' => 'Edit site settings (name, URL, interval, keyword, alert emails)',
        'pages.manage' => 'Add, exclude & remove pages',
        'incidents.view' => 'View the incident log',
    ];

    protected $fillable = ['name', 'description', 'permissions'];

    protected function casts(): array
    {
        return ['is_admin' => 'boolean', 'permissions' => 'array'];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function allows(string $permission): bool
    {
        return $this->is_admin || in_array($permission, $this->permissions ?? [], true);
    }

    public static function admin(): ?self
    {
        return static::where('is_admin', true)->orderBy('id')->first();
    }
}
