@extends('layouts.app')
@section('title', 'Assign users to departments')
@section('content')
@include('admin._tabs')
<div class="card">
  <div class="card-head" style="padding-bottom:12px">
    <div><h2 style="margin:0">Assign users to departments</h2>
      <div class="small muted">Each user belongs to one department and sees only its sites. Users with no department see no sites. Admins aren't listed because they see every site.</div></div>
  </div>
  @if($users->isEmpty())
    <div class="empty">No non-admin users yet. <a href="{{ route('admin.users.create') }}">Add a user</a>.</div>
  @elseif($departments->isEmpty())
    <div class="empty">No departments yet. <a href="{{ route('admin.departments.create') }}">Add a department</a> first.</div>
  @else
  <form method="post" action="{{ route('admin.assignments.update') }}">@csrf @method('PUT')
    <div class="table-wrap"><table>
      <thead><tr><th>User</th><th>Role</th><th style="width:320px">Department</th></tr></thead>
      <tbody>
      @foreach($users as $u)
        <tr>
          <td><div style="font-weight:600">{{ $u->name }}</div><div class="small muted">{{ $u->email }}</div></td>
          <td>{{ $u->role?->name ?? '—' }}</td>
          <td>
            <select name="assignments[{{ $u->id }}]">
              <option value="">— No department (no site access) —</option>
              @foreach($departments as $d)<option value="{{ $d->id }}" @selected(old('assignments.'.$u->id, $u->department_id)==$d->id)>{{ $d->name }}</option>@endforeach
            </select>
          </td>
        </tr>
      @endforeach
      </tbody>
    </table></div>
    <div class="card-pad" style="border-top:1px solid var(--border)"><button class="btn primary">Save assignments</button></div>
  </form>
  @endif
</div>
@endsection
