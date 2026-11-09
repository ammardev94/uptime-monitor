@php
ob_start();
@endphp
<p style="margin:0 0 14px;"><strong>{{ $site->name }}</strong> (<a href="{{ $site->url }}">{{ $site->host() }}</a>) has {{ count($items) === 1 ? 'a page' : count($items).' pages' }} that stopped responding. The failure was confirmed with a second check.</p>
<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:13px;">
<tr><th align="left" style="padding:8px;border-bottom:2px solid #e4e4e7;">Page</th><th align="left" style="padding:8px;border-bottom:2px solid #e4e4e7;">Reason</th></tr>
@foreach($items as $item)
<tr><td style="padding:8px;border-bottom:1px solid #f4f4f5;word-break:break-all;"><a href="{{ $item['page']->url }}" style="color:#18181b;">{{ $item['page']->path() }}</a></td>
<td style="padding:8px;border-bottom:1px solid #f4f4f5;color:#dc2626;">{{ $item['reason'] }}</td></tr>
@endforeach
</table>
<p style="margin:18px 0 0;"><a href="{{ route('sites.show', $site) }}" style="display:inline-block;background:#18181b;color:#fff;text-decoration:none;padding:10px 16px;border-radius:6px;">View site in dashboard</a></p>
<p style="color:#71717a;font-size:12px;margin-top:16px;">If these are PPC landing pages, consider pausing the ads pointing to them until they're back up. You'll get another email when they recover.</p>
@php
$body = ob_get_clean();
@endphp
@include('mail.layout', ['accent' => '#dc2626', 'heading' => '🔴 '.$site->name.($site->status === 'down' ? ' is DOWN' : ' has pages down'), 'body' => $body])
