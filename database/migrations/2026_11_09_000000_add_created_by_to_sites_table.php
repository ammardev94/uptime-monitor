<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            // Who added the site; Admin creators get a direct email when it goes down.
            $table->foreignId('created_by')->nullable()->after('alert_emails')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
        });
    }
};
