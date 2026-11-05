@extends('layouts.app')
@section('title', $department->exists ? 'Edit '.$department->name : 'Add department')
@section('content')
@include('admin._tabs')
<div class="card card-pad" style="max-width:820px">
  <h2>{{ $department->exists ? 'Edit department '.$department->name : 'Add department' }}</h2>
  <form method="post" action="{{ $department->exists ? route('admin.departments.update',$department) : route('admin.departments.store') }}">@csrf @if($department->exists) @method('PUT') @endif
    <div style="display:grid;gap:16px">
      <div><label>Name</label><input type="text" name="name" value="{{ old('name',$department->name) }}" required maxlength="80"></div>
      <div><label>Description <span class="muted">(optional)</span></label><input type="text" name="description" value="{{ old('description',$department->description) }}" maxlength="255"></div>
      <div>
        <label>Sites in this department</label>
        <div class="hint" style="margin:0 0 8px">Users in this department can see these sites. A site can belong to more than one department.</div>
        @php $chosen = array_map('intval', old('sites', $selected)); @endphp
        @if($sites->isEmpty())<div class="muted small">No sites yet.</div>@else
        <div class="checks">
          @foreach($sites as $s)
            <label class="check"><input type="checkbox" name="sites[]" value="{{ $s->id }}" @checked(in_array($s->id, $chosen))> {{ $s->name }} <span class="faint small">{{ $s->host() }}</span></label>
          @endforeach
        </div>
        @endif
      </div>
      <div class="btn-row"><button class="btn primary">{{ $department->exists ? 'Save' : 'Create department' }}</button><a href="{{ route('admin.departments.index') }}" class="btn">Cancel</a></div>
    </div>
  </form>
</div>
@endsection
