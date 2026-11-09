@php
ob_start();
@endphp
<p style="margin:0 0 14px;">Hi {{ $owner->name }},</p>
<p style="margin:0 0 14px;"><strong>{{ $site->name }}</strong>, a website you added, is down. Its landing page (<a href="{{ $site->url }}">{{ $site->url }}</a>) did not load, and a second check a few seconds later confirmed it.</p>
@if($reason)
<p style="margin:0 0 14px;">Reason: <span style="color:#dc2626;">{{ $reason }}</span></p>
@endif
<p style="margin:18px 0 0;"><a href="{{ route('sites.show', $site) }}" style="display:inline-block;background:#18181b;color:#fff;text-decoration:none;padding:10px 16px;border-radius:6px;">View site in dashboard</a></p>
<p style="color:#71717a;font-size:12px;margin-top:16px;">You're receiving this because you added this website to the uptime monitor as an Admin.</p>
@php
$body = ob_get_clean();
@endphp
@include('mail.layout', ['accent' => '#dc2626', 'heading' => '🔴 '.$site->name.' is DOWN', 'body' => $body])
