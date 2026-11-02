<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    protected $fillable = [
        'site_id', 'url', 'source', 'is_active', 'status', 'status_code',
        'response_ms', 'error', 'last_checked_at', 'status_changed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_checked_at' => 'datetime',
            'status_changed_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function openIncident(): ?Incident
    {
        return $this->incidents()->whereNull('resolved_at')->latest('started_at')->first();
    }

    public function path(): string
    {
        $p = parse_url($this->url, PHP_URL_PATH) ?: '/';
        $q = parse_url($this->url, PHP_URL_QUERY);

        return $q ? "$p?$q" : $p;
    }
}
