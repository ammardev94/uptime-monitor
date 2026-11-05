@extends('layouts.app')
@section('title', $site->name)
@section('refresh', '120')
@section('content')
@php
  $labels = ['up'=>'Up','down'=>'Down','partial'=>'Partly down','pending'=>'Pending','paused'=>'Paused'];
  $sslDays = $site->ssl_expires_at ? (int) floor(now()->diffInDays($site->ssl_expires_at, false)) : null;
  $last = $checks->last();
@endphp
<div class="page-head">
  <div>
    <div class="small muted"><a href="{{ route('dashboard') }}">Monitors</a> /</div>
    <div style="display:flex;align-items:center;gap:12px;margin-top:4px">
      <span class="dot {{ $site->status }}"></span><h1>{{ $site->name }}</h1>
      <span class="pill {{ $site->status }}">{{ $labels[$site->status] ?? $site->status }}</span>
    </div>
    <div class="muted" style="margin-top:4px"><a href="{{ $site->url }}" target="_blank" rel="noopener">{{ $site->url }}</a> · checked every {{ $site->interval_minutes }} min · last {{ $site->last_checked_at?->diffForHumans() ?? 'never' }}</div>
  </div>
  <div class="btn-row">
    @can('check', $site)
    <form method="post" action="{{ route('sites.check',$site) }}" class="inline" onsubmit="this.querySelector('button').textContent='Checking…'">@csrf<button class="btn primary">Check now</button></form>
    <form method="post" action="{{ route('sites.discover',$site) }}" class="inline" onsubmit="this.querySelector('button').textContent='Scanning…'">@csrf<button class="btn">Re-scan pages</button></form>
    @endcan
    @can('pause', $site)
    <form method="post" action="{{ route('sites.pause',$site) }}" class="inline">@csrf<button class="btn">{{ $site->is_paused ? 'Resume' : 'Pause' }}</button></form>
    @endcan
    @can('update', $site)
    <a href="{{ route('sites.edit',$site) }}" class="btn">Edit</a>
    @endcan
  </div>
</div>

<div class="grid-stats">
  @foreach([24=>'Uptime 24h',168=>'Uptime 7d',720=>'Uptime 30d'] as $h=>$l)
    @php $u = $site->uptime($h); @endphp
    <div class="card stat"><div class="label">{{ $l }}</div><div class="value {{ $u !== null && $u < 99 ? 'down' : ($u !== null ? 'up' : '') }}">{{ $u === null ? '—' : $u.'%' }}</div></div>
  @endforeach
  <div class="card stat"><div class="label">Avg response</div><div class="value">{{ $last?->avg_response_ms ? number_format($last->avg_response_ms) : '—' }}@if($last?->avg_response_ms)<span class="small muted"> ms</span>@endif</div></div>
  <div class="card stat"><div class="label">SSL expires</div><div class="value {{ $sslDays !== null && $sslDays <= 14 ? 'warn' : '' }}">{{ $sslDays === null ? '—' : $sslDays }}<span class="small muted"> {{ $sslDays !== null ? 'days' : '' }}</span></div></div>
</div>

<div class="card card-pad">
  <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:10px">
    <h2 style="margin:0">Last 24 hours</h2>
    <span class="small muted"><span class="dot up" style="width:8px;height:8px;box-shadow:none"></span> all up &nbsp; <span class="dot partial" style="width:8px;height:8px;box-shadow:none"></span> some pages down &nbsp; <span class="dot down" style="width:8px;height:8px;box-shadow:none;animation:none"></span> down</span>
  </div>
  <div class="uptime-bar">
    @foreach($buckets as $b)<span class="{{ $b['state'] }}" title="{{ local_time($b['from'],'H:i') }}–{{ local_time($b['to'],'H:i') }}: {{ ['up'=>'All up','down'=>'Down','partial'=>'Some pages down','none'=>'No data'][$b['state']] }}"></span>@endforeach
  </div>
  <div style="display:flex;justify-content:space-between" class="small faint mt"><span>24h ago</span><span>now</span></div>

  @php $pts = $checks->whereNotNull('avg_response_ms')->values(); @endphp
  @if($pts->count() > 1)
    @php
      $max = max(200, $pts->max('avg_response_ms')) * 1.15; $n = $pts->count();
      $coords = $pts->map(fn($c,$i) => round($i/($n-1)*1000,1).','.round(110 - $c->avg_response_ms/$max*100,1))->implode(' ');
    @endphp
    <div class="mt" style="display:flex;justify-content:space-between;align-items:baseline"><h2 style="margin:0">Response time</h2><span class="small muted">peak {{ number_format($pts->max('avg_response_ms')) }} ms · low {{ number_format($pts->min('avg_response_ms')) }} ms</span></div>
    <svg class="chart" viewBox="0 0 1000 120" preserveAspectRatio="none">
      <polygon points="0,120 {{ $coords }} 1000,120" fill="var(--up)" opacity=".12"/>
      <polyline points="{{ $coords }}" fill="none" stroke="var(--up)" stroke-width="2" vector-effect="non-scaling-stroke"/>
    </svg>
  @endif
</div>

<div class="card mt">
  <div class="card-head">
    <div class="tabs">
      <a href="?filter=all" class="{{ $filter==='all'?'active':'' }}">All pages ({{ $counts['all'] }})</a>
      <a href="?filter=down" class="{{ $filter==='down'?'active':'' }}">Down ({{ $counts['down'] }})</a>
      <a href="?filter=paused" class="{{ $filter==='paused'?'active':'' }}">Excluded ({{ $counts['paused'] }})</a>
    </div>
    <span class="small muted" style="padding-bottom:10px">Pages last scanned {{ $site->last_discovered_at?->diffForHumans() ?? 'never' }}</span>
  </div>
  @if($pages->isEmpty())
    <div class="empty">No pages here.</div>
  @else
  <div class="table-wrap"><table>
    <thead><tr><th>Status</th><th>Page</th><th class="hide-sm">Found via</th><th class="num">Code</th><th class="num">Response</th><th class="hide-sm">Checked</th><th></th></tr></thead>
    <tbody>
    @foreach($pages as $page)
      @php $st = $page->is_active ? $page->status : 'paused'; @endphp
      <tr>
        <td><span class="pill {{ $st }}">{{ $page->is_active ? ($labels[$page->status] ?? $page->status) : 'Excluded' }}</span></td>
        <td class="url-cell"><a href="{{ $page->url }}" target="_blank" rel="noopener" class="mono" title="{{ $page->url }}">{{ $page->path() }}</a>
          @if($page->error && $page->is_active)<div class="err-text">{{ $page->error }}</div>@endif</td>
        <td class="hide-sm"><span class="tag">{{ $page->source }}</span></td>
        <td class="num mono {{ $page->status_code && $page->status_code < 400 ? 'code-up' : ($page->status_code ? 'code-down' : '') }}">{{ $page->status_code ?? '—' }}</td>
        <td class="num">{{ $page->response_ms ? number_format($page->response_ms).' ms' : '—' }}</td>
        <td class="hide-sm small muted">{{ $page->last_checked_at?->diffForHumans(short: true) ?? '—' }}</td>
        <td class="num">
          @can('managePages', $site)
          <form method="post" action="{{ route('pages.toggle',$page) }}" class="inline">@csrf @method('PATCH')<button class="linkbtn">{{ $page->is_active ? 'Exclude' : 'Include' }}</button></form>
          @if($page->source !== 'home')
          &nbsp;<form method="post" action="{{ route('pages.destroy',$page) }}" class="inline" onsubmit="return confirm('Remove this page?')">@csrf @method('DELETE')<button class="linkbtn danger">Remove</button></form>
          @endif
          @endcan
        </td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
  @endif
</div>

<div class="{{ auth()->user()->can('managePages', $site) ? 'two-col' : '' }} mt">
  @can('managePages', $site)
  <div class="card card-pad">
    <h2>Add landing pages manually</h2>
    <form method="post" action="{{ route('pages.store',$site) }}">@csrf
      <textarea name="urls" rows="4" placeholder="One URL per line, e.g.&#10;https://{{ $site->host() }}/lp/summer-offer&#10;/thank-you" required></textarea>
      <div class="hint">Useful for PPC landing pages that aren't in the sitemap. Paths starting with / use this site's domain.</div>
      <button class="btn primary mt">Add pages</button>
    </form>
  </div>
  @endcan
  <div class="card card-pad">
    <h2>Recent incidents</h2>
    @forelse($incidents as $inc)
      <div style="display:flex;gap:10px;padding:8px 0;border-bottom:1px solid var(--border)">
        <span class="dot {{ $inc->resolved_at ? 'up' : 'down' }}" style="margin-top:6px"></span>
        <div style="min-width:0">
          <div class="mono" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $inc->page?->path() }}</div>
          <div class="small muted">{{ $inc->reason }}</div>
          <div class="small faint">{{ local_time($inc->started_at) }} · {{ $inc->resolved_at ? 'down for '.$inc->duration() : 'ongoing ('.$inc->duration().')' }}</div>
        </div>
      </div>
    @empty
      <div class="muted">No incidents. 🎉</div>
    @endforelse
  </div>
</div>
@endsection
