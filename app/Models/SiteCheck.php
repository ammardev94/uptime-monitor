<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteCheck extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['site_id', 'pages_total', 'pages_up', 'avg_response_ms', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
