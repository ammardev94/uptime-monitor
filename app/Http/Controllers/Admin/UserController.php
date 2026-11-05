<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        return view('admin.users.index', [
            'users' => User::with(['role', 'department'])->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.users.form', ['user' => new User, 'roles' => Role::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, null);
        $user = User::create($data);
        if ($user->isAdmin()) {
            $user->update(['department_id' => null]);
        }

        return redirect()->route('admin.users.index')->with('ok', "Created {$user->name}.".($user->isAdmin() ? '' : ' Assign them to a department so they can see sites.'));
    }

    public function edit(User $user)
    {
        return view('admin.users.form', ['user' => $user, 'roles' => Role::orderBy('name')->get()]);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validated($request, $user);

        $newRole = Role::find($data['role_id']);
        if ($user->isAdmin() && ! $newRole->is_admin && $this->adminCount() <= 1) {
            return back()->withInput()->withErrors(['role_id' => 'This is the only Admin. Make another user Admin first.']);
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }
        if ($newRole->is_admin) {
            $data['department_id'] = null; // Admins see everything and are not assigned to departments
        }
        $user->update($data);

        return redirect()->route('admin.users.index')->with('ok', "Updated {$user->name}.");
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'You cannot delete your own account.']);
        }
        if ($user->isAdmin() && $this->adminCount() <= 1) {
            return back()->withErrors(['user' => 'You cannot delete the only Admin.']);
        }
        $user->delete();

        return redirect()->route('admin.users.index')->with('ok', "Deleted {$user->name}.");
    }

    private function validated(Request $request, ?User $user): array
    {
        return $request->validate([
            'name' => 'required|string|max:120',
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::min(8)],
            'role_id' => 'required|integer|exists:roles,id',
        ]);
    }

    private function adminCount(): int
    {
        return User::whereHas('role', fn ($q) => $q->where('is_admin', true))->count();
    }
}
