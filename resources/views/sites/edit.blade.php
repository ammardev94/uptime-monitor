@extends('layouts.app')
@section('title', 'Edit '.$site->name)
@section('content')
<div class="page-head"><div><div class="small muted"><a href="{{ route('sites.show',$site) }}">{{ $site->name }}</a> /</div><h1>Edit monitor</h1></div></div>
<div class="card card-pad" style="max-width:720px">
  <form method="post" action="{{ route('sites.update',$site) }}">@csrf @method('PUT')
    <div style="display:grid;gap:16px">
      <div><label>Client / site name</label><input type="text" name="name" value="{{ old('name',$site->name) }}" required></div>
      <div><label>Website URL (homepage)</label><input type="text" name="url" value="{{ old('url',$site->url) }}" required></div>
      <div><label>Check every</label><select name="interval_minutes">@foreach([1,2,3,5,10,15,30,60] as $m)<option value="{{ $m }}" @selected(old('interval_minutes',$site->interval_minutes)==$m)>{{ $m }} minutes</option>@endforeach</select></div>
      <div><label>Required keyword on homepage <span class="muted">(optional)</span></label><input type="text" name="keyword" value="{{ old('keyword',$site->keyword) }}" placeholder="e.g. Book a call">
        <div class="hint">Marks the site down if this text disappears — catches blank pages, hacked pages, or “site suspended” screens that still return 200.</div></div>
      <div><label>Extra alert emails for this site <span class="muted">(optional)</span></label><input type="text" name="alert_emails" value="{{ old('alert_emails',$site->alert_emails) }}" placeholder="client@example.com, rakesh@example.com">
        <div class="hint">Sent in addition to the global alert emails in Settings.</div></div>
      @can('admin')
      <div><label>Departments</label>
        @php $chosen = array_map('intval', (array) old('departments', $site->departments()->pluck('departments.id')->all())); @endphp
        @if($departments->isEmpty())<div class="hint">No departments yet — <a href="{{ route('admin.departments.create') }}">create one</a>. Until then only Admins can see this site.</div>
        @else
        <div class="checks">@foreach($departments as $d)<label class="check"><input type="checkbox" name="departments[]" value="{{ $d->id }}" @checked(in_array($d->id, $chosen))> {{ $d->name }}</label>@endforeach</div>
        <div class="hint">Users in these departments can see this site. With none ticked, only Admins can.</div>
        @endif
      </div>
      @endcan
      <label class="check"><input type="checkbox" name="auto_discover" value="1" @checked(old('auto_discover',$site->auto_discover))> Find pages automatically from sitemap.xml / homepage links (re-scanned daily)</label>
      <div class="btn-row"><button class="btn primary">Save</button><a href="{{ route('sites.show',$site) }}" class="btn">Cancel</a></div>
    </div>
  </form>
</div>
@can('delete', $site)
<div class="card card-pad mt" style="max-width:720px">
  <h2>Delete monitor</h2>
  <p class="muted">Removes this site, its pages and all history.</p>
  <form method="post" action="{{ route('sites.destroy',$site) }}" onsubmit="return confirm(@js('Delete '.$site->name.' and all its history?'))">@csrf @method('DELETE')<button class="btn danger">Delete {{ $site->name }}</button></form>
</div>
@endcan
@endsection
