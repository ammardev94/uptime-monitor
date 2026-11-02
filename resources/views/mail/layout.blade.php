<!doctype html>
<html><body style="margin:0;padding:24px;background:#f4f4f5;font-family:-apple-system,Segoe UI,Helvetica,Arial,sans-serif;color:#18181b;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#fff;border-radius:10px;overflow:hidden;border:1px solid #e4e4e7;">
<tr><td style="background:{{ $accent }};height:6px;"></td></tr>
<tr><td style="padding:28px 28px 8px;">
  <div style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#71717a;">{{ config('app.name') }}</div>
  <h1 style="margin:6px 0 4px;font-size:22px;">{{ $heading }}</h1>
  <div style="color:#52525b;font-size:14px;">{{ now()->timezone(config('app.display_timezone'))->format('D, j M Y · H:i T') }}</div>
</td></tr>
<tr><td style="padding:16px 28px 28px;font-size:14px;line-height:1.55;">
  {!! $body !!}
</td></tr>
</table>
<div style="color:#a1a1aa;font-size:12px;margin-top:14px;">Sent by your uptime monitor · <a href="{{ config('app.url') }}" style="color:#a1a1aa;">Open dashboard</a></div>
</td></tr></table>
</body></html>
