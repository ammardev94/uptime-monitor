<div class="page-head">
  <div>
    <h1>Users, roles &amp; departments</h1>
    <div class="muted">Admins see and manage everything. Everyone else only sees the sites linked to their department, and can do only what their role allows.</div>
  </div>
</div>
<div class="tabs" style="margin-bottom:18px;border-bottom:1px solid var(--border)">
  <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">Users</a>
  <a href="{{ route('admin.roles.index') }}" class="{{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">Roles</a>
  <a href="{{ route('admin.departments.index') }}" class="{{ request()->routeIs('admin.departments.*') ? 'active' : '' }}">Departments &amp; sites</a>
  <a href="{{ route('admin.assignments.index') }}" class="{{ request()->routeIs('admin.assignments.*') ? 'active' : '' }}">Assign users to departments</a>
</div>
