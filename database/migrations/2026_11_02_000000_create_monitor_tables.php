<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');                       // homepage URL
            $table->unsignedSmallInteger('interval_minutes')->default(5);
            $table->boolean('auto_discover')->default(true); // read sitemap / crawl homepage
            $table->string('keyword')->nullable();       // optional text that must appear on the homepage
            $table->string('alert_emails')->nullable();  // extra recipients for this site (comma separated)
            $table->boolean('is_paused')->default(false);
            $table->string('status')->default('pending'); // pending | up | down | paused
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_discovered_at')->nullable();
            $table->timestamp('ssl_expires_at')->nullable();
            $table->boolean('ssl_alert_sent')->default(false);
            $table->timestamps();
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('url', 2048);
            $table->string('source')->default('manual');   // home | sitemap | crawl | manual
            $table->boolean('is_active')->default(true);
            $table->string('status')->default('pending');  // pending | up | down
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->unsignedInteger('response_ms')->nullable();
            $table->string('error', 500)->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamps();
            $table->index(['site_id', 'is_active']);
        });

        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('reason', 500);
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['site_id', 'resolved_at']);
        });

        // One row per check run of a site (lightweight history for uptime % and response chart)
        Schema::create('site_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('pages_total');
            $table->unsignedInteger('pages_up');
            $table->unsignedInteger('avg_response_ms')->nullable();
            $table->timestamp('created_at')->index();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('site_checks');
        Schema::dropIfExists('incidents');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('sites');
    }
};
