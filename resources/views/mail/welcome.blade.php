@php
ob_start();
@endphp
<p style="margin:0 0 14px;">Hi {{ $user->name }},</p>
<p style="margin:0 0 14px;">An administrator has created an account for you on <strong>{{ config('app.name') }}</strong>. Use these details to sign in:</p>
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 14px;border:1px solid #e4e4e7;border-radius:6px;font-size:14px;">
  <tr><td style="padding:8px 14px;color:#71717a;">Email</td><td style="padding:8px 14px;"><strong>{{ $user->email }}</strong></td></tr>
  <tr><td style="padding:8px 14px;color:#71717a;border-top:1px solid #e4e4e7;">Password</td><td style="padding:8px 14px;border-top:1px solid #e4e4e7;font-family:Menlo,Consolas,monospace;"><strong>{{ $password }}</strong></td></tr>
  @if($user->role)
  <tr><td style="padding:8px 14px;color:#71717a;border-top:1px solid #e4e4e7;">Role</td><td style="padding:8px 14px;border-top:1px solid #e4e4e7;">{{ $user->role->name }}</td></tr>
  @endif
</table>
<p style="margin:18px 0 0;"><a href="{{ route('login') }}" style="display:inline-block;background:#18181b;color:#fff;text-decoration:none;padding:10px 16px;border-radius:6px;">Sign in</a></p>
<p style="color:#71717a;font-size:12px;margin-top:16px;">Keep this password private. If you didn't expect this email, contact your administrator.</p>
@php
$body = ob_get_clean();
@endphp
@include('mail.layout', ['accent' => '#2563eb', 'heading' => '👋 Welcome, '.$user->name, 'body' => $body])
