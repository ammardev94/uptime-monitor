@extends('layouts.app')
@section('title', $role->exists ? 'Edit '.$role->name : 'Add role')
@section('content')
@include('admin._tabs')
<div class="card card-pad" style="max-width:720px">
  <h2>{{ $role->exists ? 'Edit role '.$role->name : 'Add role' }}</h2>
  <form method="post" action="{{ $role->exists ? route('admin.roles.update',$role) : route('admin.roles.store') }}">@csrf @if($role->exists) @method('PUT') @endif
    <div style="display:grid;gap:16px">
      <div><label>Name</label><input type="text" name="name" value="{{ old('name',$role->name) }}" required maxlength="80"></div>
      <div><label>Description <span class="muted">(optional)</span></label><input type="text" name="description" value="{{ old('description',$role->description) }}" maxlength="255"></div>
      <div>
        <label>Permissions</label>
        <div class="hint" style="margin:0 0 8px">Users with this role can always view their department's sites. These are extra actions, and they only apply to those sites.</div>
        @php $chosen = old('permissions', $role->permissions ?? []); @endphp
        <div style="display:grid;gap:8px">
          @foreach(\App\Models\Role::PERMISSIONS as $key => $label)
            <label class="check"><input type="checkbox" name="permissions[]" value="{{ $key }}" @checked(in_array($key, $chosen))> {{ $label }}</label>
          @endforeach
        </div>
      </div>
      <div class="btn-row"><button class="btn primary">{{ $role->exists ? 'Save' : 'Create role' }}</button><a href="{{ route('admin.roles.index') }}" class="btn">Cancel</a></div>
    </div>
  </form>
</div>
@endsection
