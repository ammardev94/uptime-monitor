@extends('layouts.app')
@section('title', 'Users')
@section('content')
@include('admin._tabs')
<div class="card">
  <div class="card-head" style="padding-bottom:12px"><h2 style="margin:0">Users</h2><a href="{{ route('admin.users.create') }}" class="btn primary sm">Add user</a></div>
  <div class="table-wrap"><table>
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Department</th><th></th></tr></thead>
    <tbody>
    @foreach($users as $u)
      <tr>
        <td style="font-weight:600">{{ $u->name }} @if($u->is(auth()->user()))<span class="tag">you</span>@endif</td>
        <td class="muted">{{ $u->email }}</td>
        <td>@if($u->role)<span class="pill {{ $u->isAdmin() ? 'up' : 'pending' }}">{{ $u->role->name }}</span>@else<span class="err-text">No role</span>@endif</td>
        <td>
          @if($u->isAdmin())<span class="muted small">All sites</span>
          @elseif($u->department){{ $u->department->name }}
          @else<span class="err-text">None — sees no sites</span>@endif
        </td>
        <td class="num">
          <a href="{{ route('admin.users.edit',$u) }}" class="linkbtn">Edit</a>
          @unless($u->is(auth()->user()))
          &nbsp;<form method="post" action="{{ route('admin.users.destroy',$u) }}" class="inline" onsubmit="return confirm(@js('Delete '.$u->name.'?'))">@csrf @method('DELETE')<button class="linkbtn danger">Delete</button></form>
          @endunless
        </td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
</div>
<p class="hint">Departments are set on the <a href="{{ route('admin.assignments.index') }}">Assign users to departments</a> page.</p>
@endsection
