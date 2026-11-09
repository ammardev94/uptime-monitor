@extends('layouts.app')
@section('title', 'Monitors')
@section('refresh', '60')
@section('content')
@php
  $up = $sites->where('status','up')->count();
  $issues = $sites->where('status','down')->count();
@endphp
<div class="page-head">
  <div>
    <h1>Monitors</h1>
    <div class="muted">Every page of every client site, checked automatically. Auto-refreshes every minute.</div>
  </div>
</div>

@can('admin')
@php
  $setup = [
    ['done' => \App\Models\Department::exists(), 'text' => 'Create departments', 'url' => route('admin.departments.create')],
    ['done' => \Illuminate\Support\Facades\DB::table('department_site')->exists(), 'text' => 'Link sites to departments', 'url' => route('admin.departments.index')],
    ['done' => \App\Models\User::nonAdmin()->exists(), 'text' => 'Add users and give each a role', 'url' => route('admin.users.create')],
    ['done' => \App\Models\User::nonAdmin()->whereNotNull('department_id')->exists(), 'text' => 'Assign users to departments', 'url' => route('admin.assignments.index')],
  ];
@endphp
@if(collect($setup)->contains('done', false))
<div class="card card-pad" style="margin-bottom:18px">
  <h2>Set up team access</h2>
  <p class="small muted" style="margin:-8px 0 12px">Other users only see the sites of their department. Optional extras like running checks are set per role on the <a href="{{ route('admin.roles.index') }}">Roles</a> page.</p>
  <ol style="margin:0;padding-left:20px;display:grid;gap:6px">
    @foreach($setup as $step)
      <li>@if($step['done'])<span class="code-up">✓</span> <span class="muted">{{ $step['text'] }}</span>@else<a href="{{ $step['url'] }}" style="font-weight:600">{{ $step['text'] }} →</a>@endif</li>
    @endforeach
  </ol>
</div>
@endif
@endcan

<div class="grid-stats">
  <div class="card stat"><div class="label">Websites</div><div class="value">{{ $sites->count() }}</div></div>
  <div class="card stat"><div class="label">Up</div><div class="value up">{{ $up }}</div></div>
  <div class="card stat"><div class="label">Down</div><div class="value {{ $issues ? 'down' : '' }}">{{ $issues }}</div></div>
  <div class="card stat"><div class="label">Pages monitored</div><div class="value">{{ $pagesTotal }}</div></div>
  <div class="card stat"><div class="label">Open incidents</div><div class="value {{ $openIncidents ? 'down' : '' }}">{{ $openIncidents }}</div></div>
</div>

@can('create', App\Models\Site::class)
<div class="card card-pad">
  <h2>Add a website</h2>
  <form method="post" action="{{ route('sites.store') }}" onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').textContent='Finding pages & checking…'">
    @csrf
    <div class="form-grid">
      <div><label>Client / site name</label><input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. AAA Marine" required></div>
      <div><label>Website URL</label><input type="text" name="url" value="{{ old('url') }}" placeholder="https://example.com" required></div>
      <div><label>Check every</label>
        <select name="interval_minutes">
          @foreach([1,2,3,5,10,15,30,60] as $m)<option value="{{ $m }}" @selected(old('interval_minutes',5)==$m)>{{ $m }} min</option>@endforeach
        </select></div>
      @if($departments->isNotEmpty())
      <div class="full"><label>Departments <span class="muted">(users in these departments can see the site)</span></label>
        <div class="checks">@foreach($departments as $d)<label class="check"><input type="checkbox" name="departments[]" value="{{ $d->id }}" @checked(in_array($d->id, (array) old('departments', [])))> {{ $d->name }}</label>@endforeach</div>
      </div>
      @endif
      <div class="full" style="display:flex;gap:18px;align-items:center;justify-content:space-between;flex-wrap:wrap">
        <label class="check"><input type="checkbox" name="auto_discover" value="1" checked> Find all pages automatically (sitemap.xml, or homepage links)</label>
        <button class="btn primary">Add &amp; start monitoring</button>
      </div>
    </div>
  </form>
</div>
@endcan

<div class="card mt">
  @if($sites->isEmpty())
    <div class="empty">
      @if(auth()->user()->isAdmin()) No websites yet. Add your first client site above.
      @elseif(! auth()->user()->department_id) You haven't been assigned to a department yet, so there are no sites to show. Ask an Admin to assign you.
      @else No websites have been added to {{ auth()->user()->department->name }} yet.
      @endif
    </div>
  @else
  <div class="table-wrap">
  <table>
    <thead><tr>
      <th>Website</th><th>Status</th><th class="num">Pages</th><th class="num">Uptime 24h</th><th class="num hide-sm">Uptime 30d</th><th class="num hide-sm">Avg response</th><th class="hide-sm">Last check</th><th class="num">Interval</th>
    </tr></thead>
    <tbody>
    @foreach($sites as $site)
      @php $u24 = $site->uptime(24); $u30 = $site->uptime(720); $last = $lastChecks[$site->id] ?? null; @endphp
      <tr>
        <td><div class="site-cell"><span class="dot {{ $site->status }}"></span><div>
          <a href="{{ route('sites.show',$site) }}">{{ $site->name }}</a>
          <div class="muted small">{{ $site->host() }}@can('admin') · @forelse($site->departments as $d)<span class="tag">{{ $d->name }}</span> @empty<a href="{{ route('sites.edit',$site) }}" class="faint">no department, assign</a>@endforelse @endcan</div></div></div></td>
        <td><span class="pill {{ $site->status }}">{{ ['up'=>'Up','down'=>'Down','pending'=>'Pending','paused'=>'Paused'][$site->status] ?? $site->status }}</span></td>
        <td class="num">
          @if($site->pages_down)<span class="code-down">{{ $site->pages_down }} down</span> · @endif{{ $site->pages_total }}
        </td>
        <td class="num">{{ $u24 === null ? '—' : $u24.'%' }}</td>
        <td class="num hide-sm">{{ $u30 === null ? '—' : $u30.'%' }}</td>
        <td class="num hide-sm">{{ $last?->avg_response_ms ? number_format($last->avg_response_ms).' ms' : '—' }}</td>
        <td class="hide-sm muted small">{{ $site->last_checked_at?->diffForHumans() ?? 'never' }}</td>
        <td class="num muted">{{ $site->interval_minutes }}m</td>
      </tr>
    @endforeach
    </tbody>
  </table>
  </div>
  @endif
</div>
@endsection
