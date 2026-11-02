@extends('layouts.app')
@section('title', 'Monitors')
@section('refresh', '60')
@section('content')
@php
  $up = $sites->where('status','up')->count();
  $issues = $sites->whereIn('status',['down','partial'])->count();
@endphp
<div class="page-head">
  <div>
    <h1>Monitors</h1>
    <div class="muted">Every page of every client site, checked automatically. Auto-refreshes every minute.</div>
  </div>
</div>

<div class="grid-stats">
  <div class="card stat"><div class="label">Websites</div><div class="value">{{ $sites->count() }}</div></div>
  <div class="card stat"><div class="label">Up</div><div class="value up">{{ $up }}</div></div>
  <div class="card stat"><div class="label">Down / partial</div><div class="value {{ $issues ? 'down' : '' }}">{{ $issues }}</div></div>
  <div class="card stat"><div class="label">Pages monitored</div><div class="value">{{ $pagesTotal }}</div></div>
  <div class="card stat"><div class="label">Open incidents</div><div class="value {{ $openIncidents ? 'down' : '' }}">{{ $openIncidents }}</div></div>
</div>

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
      <div class="full" style="display:flex;gap:18px;align-items:center;justify-content:space-between;flex-wrap:wrap">
        <label class="check"><input type="checkbox" name="auto_discover" value="1" checked> Find all pages automatically (sitemap.xml, or homepage links)</label>
        <button class="btn primary">Add &amp; start monitoring</button>
      </div>
    </div>
  </form>
</div>

<div class="card mt">
  @if($sites->isEmpty())
    <div class="empty">No websites yet. Add your first client site above.</div>
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
          <div class="muted small">{{ $site->host() }}</div></div></div></td>
        <td><span class="pill {{ $site->status }}">{{ ['up'=>'Up','down'=>'Down','partial'=>'Partly down','pending'=>'Pending','paused'=>'Paused'][$site->status] ?? $site->status }}</span></td>
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
