@extends('layouts.app')
@section('title', 'Departments')
@section('content')
@include('admin._tabs')
<div class="card">
  <div class="card-head" style="padding-bottom:12px"><h2 style="margin:0">Departments</h2><a href="{{ route('admin.departments.create') }}" class="btn primary sm">Add department</a></div>
  @if($departments->isEmpty())
    <div class="empty">No departments yet. Add one, then link sites to it and assign users.</div>
  @else
  <div class="table-wrap"><table>
    <thead><tr><th>Department</th><th>Sites</th><th class="num">Users</th><th></th></tr></thead>
    <tbody>
    @foreach($departments as $d)
      <tr>
        <td><div style="font-weight:600">{{ $d->name }}</div>@if($d->description)<div class="small muted">{{ $d->description }}</div>@endif</td>
        <td class="small">@forelse($d->sites as $s)<a href="{{ route('sites.show',$s) }}" class="tag" style="text-decoration:none">{{ $s->name }}</a> @empty<span class="muted">No sites</span>@endforelse</td>
        <td class="num">{{ $d->users_count }}</td>
        <td class="num">
          <a href="{{ route('admin.departments.edit',$d) }}" class="linkbtn">Edit</a>
          &nbsp;<form method="post" action="{{ route('admin.departments.destroy',$d) }}" class="inline" onsubmit="return confirm(@js('Delete department '.$d->name.'? Its '.$d->users_count.' user(s) will lose access to its sites.'))">@csrf @method('DELETE')<button class="linkbtn danger">Delete</button></form>
        </td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
  @endif
</div>
@if($unassignedSites->isNotEmpty())
<div class="card card-pad mt">
  <h2>Sites without a department</h2>
  <p class="small muted" style="margin-top:-6px">Only Admins can see these sites.</p>
  @foreach($unassignedSites as $s)<a href="{{ route('sites.edit',$s) }}" class="tag" style="text-decoration:none">{{ $s->name }}</a> @endforeach
</div>
@endif
@endsection
