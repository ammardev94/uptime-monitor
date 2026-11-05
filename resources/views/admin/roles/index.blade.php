@extends('layouts.app')
@section('title', 'Roles')
@section('content')
@include('admin._tabs')
<div class="card">
  <div class="card-head" style="padding-bottom:12px"><h2 style="margin:0">Roles</h2><a href="{{ route('admin.roles.create') }}" class="btn primary sm">Add role</a></div>
  <div class="table-wrap"><table>
    <thead><tr><th>Role</th><th>Can do</th><th class="num">Users</th><th></th></tr></thead>
    <tbody>
    @foreach($roles as $r)
      <tr>
        <td><div style="font-weight:600">{{ $r->name }}</div>@if($r->description)<div class="small muted">{{ $r->description }}</div>@endif</td>
        <td class="small">
          @if($r->is_admin)<span class="pill up">Everything</span>
          @else
            <span class="tag">View department sites</span>
            @foreach($r->permissions ?? [] as $p)<span class="tag">{{ \App\Models\Role::PERMISSIONS[$p] ?? $p }}</span> @endforeach
          @endif
        </td>
        <td class="num">{{ $r->users_count }}</td>
        <td class="num">
          @if($r->is_admin)<span class="small faint">built-in</span>
          @else
            <a href="{{ route('admin.roles.edit',$r) }}" class="linkbtn">Edit</a>
            &nbsp;<form method="post" action="{{ route('admin.roles.destroy',$r) }}" class="inline" onsubmit="return confirm(@js('Delete role '.$r->name.'?'))">@csrf @method('DELETE')<button class="linkbtn danger">Delete</button></form>
          @endif
        </td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
</div>
@endsection
