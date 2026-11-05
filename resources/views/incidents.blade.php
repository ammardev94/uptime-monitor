@extends('layouts.app')
@section('title', 'Incidents')
@section('refresh', '120')
@section('content')
<div class="page-head"><div><h1>Incidents</h1><div class="muted">Every time a page went down, and how long it took to recover.</div></div>
  <div class="btn-row"><a href="?open=0" class="btn {{ !$open ? 'primary' : '' }}">All</a><a href="?open=1" class="btn {{ $open ? 'primary' : '' }}">Ongoing only</a></div></div>
<div class="card">
  @if($incidents->isEmpty())
    <div class="empty">No incidents{{ $open ? ' right now' : ' yet' }}.</div>
  @else
  <div class="table-wrap"><table>
    <thead><tr><th>Status</th><th>Website</th><th>Page</th><th>Reason</th><th>Started</th><th class="num">Duration</th></tr></thead>
    <tbody>
    @foreach($incidents as $inc)
      <tr>
        <td><span class="pill {{ $inc->resolved_at ? 'up' : 'down' }}">{{ $inc->resolved_at ? 'Resolved' : 'Ongoing' }}</span></td>
        <td><a href="{{ $inc->site ? route('sites.show',$inc->site) : '#' }}" style="font-weight:600;text-decoration:none">{{ $inc->site?->name }}</a></td>
        <td class="url-cell mono"><a href="{{ $inc->page?->url }}" target="_blank" rel="noopener">{{ $inc->page?->path() }}</a></td>
        <td class="small" style="max-width:320px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $inc->reason }}">{{ $inc->reason }}</td>
        <td class="small muted" style="white-space:nowrap">{{ local_time($inc->started_at) }}</td>
        <td class="num" style="white-space:nowrap">{{ $inc->duration() }}</td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
  {{ $incidents->onEachSide(1)->links('pagination.app') }}
  @endif
</div>
@endsection
