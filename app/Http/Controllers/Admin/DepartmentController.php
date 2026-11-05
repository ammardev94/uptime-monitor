<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function index()
    {
        return view('admin.departments.index', [
            'departments' => Department::withCount('users')->with(['sites' => fn ($q) => $q->orderBy('name')])->orderBy('name')->get(),
            'unassignedSites' => Site::doesntHave('departments')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.departments.form', ['department' => new Department, 'sites' => Site::orderBy('name')->get(), 'selected' => []]);
    }

    public function store(Request $request)
    {
        [$data, $siteIds] = $this->validated($request, null);
        $department = Department::create($data);
        $department->sites()->sync($siteIds);

        return redirect()->route('admin.departments.index')->with('ok', "Created department {$department->name}.");
    }

    public function edit(Department $department)
    {
        return view('admin.departments.form', [
            'department' => $department,
            'sites' => Site::orderBy('name')->get(),
            'selected' => $department->sites()->pluck('sites.id')->all(),
        ]);
    }

    public function update(Request $request, Department $department)
    {
        [$data, $siteIds] = $this->validated($request, $department);
        $department->update($data);
        $department->sites()->sync($siteIds);

        return redirect()->route('admin.departments.index')->with('ok', "Updated department {$department->name}.");
    }

    public function destroy(Department $department)
    {
        // users.department_id is nulled and site links are removed by the foreign keys
        $department->delete();

        return redirect()->route('admin.departments.index')->with('ok', "Deleted department {$department->name}. Its users no longer have a department.");
    }

    private function validated(Request $request, ?Department $department): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('departments', 'name')->ignore($department?->id)],
            'description' => 'nullable|string|max:255',
            'sites' => 'nullable|array',
            'sites.*' => 'integer|exists:sites,id',
        ]);

        return [
            ['name' => $data['name'], 'description' => $data['description'] ?? null],
            array_map('intval', $data['sites'] ?? []),
        ];
    }
}
