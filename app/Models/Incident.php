<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Incident extends Model
{
    protected $fillable = ['site_id', 'page_id', 'reason', 'status_code', 'started_at', 'resolved_at'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'resolved_at' => 'datetime'];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function scopeVisibleTo($query, User $user)
    {
        return $user->isAdmin() ? $query : $query->whereIn('site_id', Site::visibleTo($user)->select('id'));
    }

    public function duration(): string
    {
        return $this->started_at->diffForHumans($this->resolved_at ?? now(), ['syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE, 'parts' => 2]);
    }
}
