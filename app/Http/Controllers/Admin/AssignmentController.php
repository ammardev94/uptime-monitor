<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** "Assign users to departments" — Admins are deliberately left out: they see every site. */
class AssignmentController extends Controller
{
    public function index()
    {
        return view('admin.assignments', [
            'users' => User::nonAdmin()->with(['role', 'department'])->orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'assignments' => 'required|array',
            'assignments.*' => 'nullable|integer|exists:departments,id',
        ]);
        $assignments = $request->input('assignments');

        $users = User::nonAdmin()->whereIn('id', array_keys($assignments))->get()->keyBy('id');
        if ($users->count() !== count($assignments)) {
            throw ValidationException::withMessages(['assignments' => 'Admins cannot be assigned to a department.']);
        }

        $changed = 0;
        foreach ($assignments as $userId => $departmentId) {
            $user = $users[$userId];
            $user->department_id = $departmentId ?: null;
            if ($user->isDirty('department_id')) {
                $user->save();
                $changed++;
            }
        }

        return back()->with('ok', $changed ? "Updated $changed assignment(s)." : 'No changes.');
    }
}
