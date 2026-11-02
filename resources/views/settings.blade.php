@extends('layouts.app')
@section('title', 'Settings')
@section('content')
<div class="page-head"><div><h1>Settings</h1></div></div>
<div class="two-col">
<div class="card card-pad">
  <h2>Alerts &amp; checks</h2>
  <form method="post" action="{{ route('settings.update') }}">@csrf
    <div style="display:grid;gap:16px">
      <div><label>Alert emails</label><input type="text" name="alert_emails" value="{{ old('alert_emails',$values['alert_emails']) }}" placeholder="you@brande.ae, team@brande.ae">
        <div class="hint">Comma-separated. Receives every down, recovery and SSL alert.</div></div>
      <div><label>Request timeout (seconds)</label><input type="number" name="timeout_seconds" min="5" max="60" value="{{ old('timeout_seconds',$values['timeout_seconds']) }}">
        <div class="hint">A page that takes longer than this counts as down.</div></div>
      <div><label>Pages checked in parallel</label><input type="number" name="concurrency" min="1" max="30" value="{{ old('concurrency',$values['concurrency']) }}"></div>
      <div><label>Max pages per site</label><input type="number" name="max_pages_per_site" min="1" max="2000" value="{{ old('max_pages_per_site',$values['max_pages_per_site']) }}">
        <div class="hint">Limit for auto-discovered pages (manual pages always count).</div></div>
      <div><label>Warn about SSL expiry (days before)</label><input type="number" name="ssl_warning_days" min="1" max="60" value="{{ old('ssl_warning_days',$values['ssl_warning_days']) }}"></div>
      <label class="check"><input type="checkbox" name="treat_403_as_up" value="1" @checked($values['treat_403_as_up']==='1')> Treat 401 / 403 / 429 as “up”</label>
      <div class="hint" style="margin-top:-10px">Turn on if a client's firewall (e.g. Cloudflare bot protection) blocks the monitor — the server is responding, it's just refusing bots.</div>
      <div><button class="btn primary">Save settings</button></div>
    </div>
  </form>
</div>
<div>
<div class="card card-pad">
  <h2>Email delivery</h2>
  <table class="small">
    <tr><td class="muted">Mailer</td><td class="mono">{{ config('mail.default') }}</td></tr>
    <tr><td class="muted">SMTP host</td><td class="mono">{{ config('mail.mailers.smtp.host') }}:{{ config('mail.mailers.smtp.port') }}</td></tr>
    <tr><td class="muted">From</td><td class="mono">{{ config('mail.from.address') }}</td></tr>
  </table>
  @if(config('mail.default') === 'log')
    <p class="err-text">MAIL_MAILER is “log” — emails are written to storage/logs instead of being sent. Set SMTP details in the .env file.</p>
  @endif
  <p class="hint">Mail server is configured in the <span class="mono">.env</span> file (see README).</p>
  <form method="post" action="{{ route('settings.test') }}">@csrf<button class="btn">Send test email</button></form>
</div>
<div class="card card-pad mt">
  <h2>How checks work</h2>
  <ul class="muted small" style="padding-left:18px;margin:0">
    <li>Every page is requested with GET; HTTP 400+ , timeouts, DNS, SSL and connection errors count as down.</li>
    <li>A failed page is re-checked after 3 seconds before alerting, to avoid false alarms.</li>
    <li>One email per site per check, listing every page that went down; another when they recover.</li>
    <li>Sitemaps are re-scanned nightly so new landing pages are picked up automatically.</li>
  </ul>
</div>
</div>
</div>
@endsection
