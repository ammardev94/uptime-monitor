<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index()
    {
        return view('admin.roles.index', ['roles' => Role::withCount('users')->orderByDesc('is_admin')->orderBy('name')->get()]);
    }

    public function create()
    {
        return view('admin.roles.form', ['role' => new Role]);
    }

    public function store(Request $request)
    {
        $role = Role::create($this->validated($request, null));

        return redirect()->route('admin.roles.index')->with('ok', "Created role {$role->name}.");
    }

    public function edit(Role $role)
    {
        if ($role->is_admin) {
            return redirect()->route('admin.roles.index')->withErrors(['role' => 'The Admin role always has full access and cannot be changed.']);
        }

        return view('admin.roles.form', ['role' => $role]);
    }

    public function update(Request $request, Role $role)
    {
        abort_if($role->is_admin, 403, 'The Admin role cannot be changed.');
        $role->update($this->validated($request, $role));

        return redirect()->route('admin.roles.index')->with('ok', "Updated role {$role->name}.");
    }

    public function destroy(Role $role)
    {
        if ($role->is_admin) {
            return back()->withErrors(['role' => 'The Admin role cannot be deleted.']);
        }
        if ($role->users()->exists()) {
            return back()->withErrors(['role' => "Role {$role->name} is still assigned to {$role->users()->count()} user(s). Give them another role first."]);
        }
        $role->delete();

        return redirect()->route('admin.roles.index')->with('ok', "Deleted role {$role->name}.");
    }

    private function validated(Request $request, ?Role $role): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('roles', 'name')->ignore($role?->id)],
            'description' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => ['string', Rule::in(array_keys(Role::PERMISSIONS))],
        ]);
        $data['permissions'] = array_values(array_unique($data['permissions'] ?? []));

        return $data;
    }
}
