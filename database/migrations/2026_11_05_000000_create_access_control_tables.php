<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->string('description')->nullable();
            $table->boolean('is_admin')->default(false);   // full access; the built-in Admin role
            $table->json('permissions')->nullable();       // extra actions for non-admin roles, see Role::PERMISSIONS
            $table->timestamps();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // A site can belong to several departments
        Schema::create('department_site', function (Blueprint $table) {
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->primary(['department_id', 'site_id']);
        });

        // Each user has one role and at most one department
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('password')->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->after('role_id')->constrained()->nullOnDelete();
        });

        $now = now();
        $adminId = DB::table('roles')->insertGetId([
            'name' => 'Admin', 'description' => 'Full access: users, roles, departments, sites and settings.',
            'is_admin' => true, 'permissions' => json_encode([]), 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('roles')->insert([
            'name' => 'Viewer', 'description' => 'Can view the sites of their department.',
            'is_admin' => false, 'permissions' => json_encode([]), 'created_at' => $now, 'updated_at' => $now,
        ]);

        // Everyone who could log in before had full access, so existing users become Admins
        DB::table('users')->update(['role_id' => $adminId]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('role_id');
        });
        Schema::dropIfExists('department_site');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('roles');
    }
};
