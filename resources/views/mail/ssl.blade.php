@php
ob_start();
@endphp
<p>The SSL certificate for <strong>{{ $site->host() }}</strong> ({{ $site->name }})
{{ $days < 0 ? 'expired on' : 'expires on' }} <strong>{{ $site->ssl_expires_at?->format('j M Y') }}</strong>.</p>
<p>When it expires, visitors (and ad clicks) will see a browser security warning. Please renew it with the hosting provider.</p>
@php
$body = ob_get_clean();
@endphp
@include('mail.layout', ['accent' => '#d97706', 'heading' => '⚠️ SSL certificate '.($days < 0 ? 'expired' : "expires in $days days"), 'body' => $body])
