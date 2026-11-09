@extends('layouts.app')
@section('title', $user->exists ? 'Edit '.$user->name : 'Add user')
@section('content')
@include('admin._tabs')
<div class="card card-pad" style="max-width:640px">
  <h2>{{ $user->exists ? 'Edit '.$user->name : 'Add user' }}</h2>
  <form method="post" action="{{ $user->exists ? route('admin.users.update',$user) : route('admin.users.store') }}">@csrf @if($user->exists) @method('PUT') @endif
    <div style="display:grid;gap:16px">
      <div><label>Name</label><input type="text" name="name" value="{{ old('name',$user->name) }}" required></div>
      <div><label>Email</label><input type="email" name="email" value="{{ old('email',$user->email) }}" required></div>
      <div><label>Role</label>
        <select name="role_id" required>
          <option value="">Choose a role…</option>
          @foreach($roles as $r)<option value="{{ $r->id }}" @selected(old('role_id',$user->role_id)==$r->id)>{{ $r->name }}{{ $r->is_admin ? ' (full access)' : '' }}</option>@endforeach
        </select>
        <div class="hint">Admins see every site and are never assigned to a department.</div></div>
      <div><label>Password @if($user->exists)<span class="muted">(leave blank to keep the current one)</span>@endif</label><input type="password" name="password" autocomplete="new-password" @required(! $user->exists)></div>
      <div><label>Confirm password</label><input type="password" name="password_confirmation" autocomplete="new-password" @required(! $user->exists)>
        @unless($user->exists)<div class="hint">The new user will be emailed a welcome message with their email and this password.</div>@endunless</div>
      <div class="btn-row"><button class="btn primary">{{ $user->exists ? 'Save' : 'Create user' }}</button><a href="{{ route('admin.users.index') }}" class="btn">Cancel</a></div>
    </div>
  </form>
</div>
@endsection
