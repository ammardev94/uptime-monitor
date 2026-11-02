<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
@hasSection('refresh')<meta http-equiv="refresh" content="@yield('refresh')">@endif
<title>@hasSection('title')@yield('title') · @endif{{ config('app.name') }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><circle cx='16' cy='16' r='12' fill='%2316a34a'/></svg>">
<style>
:root{
  --bg:#f7f7f8;--surface:#fff;--surface-2:#f4f4f5;--border:#e4e4e7;--text:#18181b;--muted:#71717a;--faint:#a1a1aa;
  --primary:#18181b;--primary-text:#fff;
  --up:#16a34a;--up-bg:#dcfce7;--down:#dc2626;--down-bg:#fee2e2;--warn:#d97706;--warn-bg:#fef3c7;--none:#d4d4d8;--none-bg:#f4f4f5;
  --radius:10px;--shadow:0 1px 2px rgba(0,0,0,.04);
}
@media (prefers-color-scheme: dark){:root{
  --bg:#0b0b0d;--surface:#151518;--surface-2:#1d1d21;--border:#2a2a30;--text:#f4f4f5;--muted:#a1a1aa;--faint:#71717a;
  --primary:#f4f4f5;--primary-text:#0b0b0d;
  --up:#22c55e;--up-bg:#052e16;--down:#f87171;--down-bg:#450a0a;--warn:#fbbf24;--warn-bg:#422006;--none:#3f3f46;--none-bg:#1d1d21;
}}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font:14px/1.5 Inter,system-ui,-apple-system,Segoe UI,sans-serif;-webkit-font-smoothing:antialiased}
a{color:inherit}
.mono{font-family:"JetBrains Mono",ui-monospace,monospace;font-size:12.5px}
.muted{color:var(--muted)} .faint{color:var(--faint)} .small{font-size:12.5px}
.wrap{max-width:1180px;margin:0 auto;padding:0 20px}
header.top{background:var(--surface);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:10}
header.top .wrap{display:flex;align-items:center;gap:24px;height:58px}
.brand{display:flex;align-items:center;gap:10px;font-weight:700;letter-spacing:-.01em;text-decoration:none;font-size:15px}
.brand .pulse{width:10px;height:10px;border-radius:50%;background:var(--up);box-shadow:0 0 0 4px var(--up-bg)}
nav.main{display:flex;gap:4px;flex:1}
nav.main a{text-decoration:none;color:var(--muted);padding:7px 12px;border-radius:7px;font-weight:500}
nav.main a:hover{background:var(--surface-2);color:var(--text)}
nav.main a.active{background:var(--surface-2);color:var(--text)}
.badge-count{display:inline-block;min-width:18px;padding:0 6px;margin-left:4px;border-radius:9px;background:var(--down);color:#fff;font-size:11px;font-weight:600;text-align:center;line-height:18px}
main{padding:28px 0 60px}
h1{font-size:22px;letter-spacing:-.02em;margin:0}
h2{font-size:15px;margin:0 0 14px;letter-spacing:-.01em}
.page-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:22px;flex-wrap:wrap}
.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow)}
.card-pad{padding:20px}
.card + .card,.mt{margin-top:18px}
.grid-stats .card{margin-top:0}
.grid-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:18px}
.stat{padding:16px 18px}
.stat .label{color:var(--muted);font-size:12px;font-weight:500;text-transform:uppercase;letter-spacing:.05em}
.stat .value{font-size:26px;font-weight:700;letter-spacing:-.02em;margin-top:4px;font-variant-numeric:tabular-nums}
.stat .value.up{color:var(--up)} .stat .value.down{color:var(--down)} .stat .value.warn{color:var(--warn)}
table{width:100%;border-collapse:collapse}
th{text-align:left;font-size:11.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);font-weight:600;padding:10px 14px;border-bottom:1px solid var(--border);white-space:nowrap}
td{padding:12px 14px;border-bottom:1px solid var(--border);vertical-align:middle}
tr:last-child td{border-bottom:0}
tbody tr:hover{background:var(--surface-2)}
.num{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}
.table-wrap{overflow-x:auto}
.dot{display:inline-block;width:10px;height:10px;border-radius:50%;flex-shrink:0}
.dot.up{background:var(--up);box-shadow:0 0 0 3px var(--up-bg)}
.dot.down{background:var(--down);box-shadow:0 0 0 3px var(--down-bg);animation:blink 1.4s infinite}
.dot.partial{background:var(--warn);box-shadow:0 0 0 3px var(--warn-bg)}
.dot.pending,.dot.paused{background:var(--none);box-shadow:0 0 0 3px var(--none-bg)}
@keyframes blink{50%{opacity:.4}}
.pill{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:99px;font-size:12px;font-weight:600;white-space:nowrap}
.pill.up{background:var(--up-bg);color:var(--up)} .pill.down{background:var(--down-bg);color:var(--down)}
.pill.partial{background:var(--warn-bg);color:var(--warn)} .pill.pending,.pill.paused{background:var(--none-bg);color:var(--muted)}
.tag{display:inline-block;padding:1px 7px;border-radius:5px;background:var(--surface-2);border:1px solid var(--border);color:var(--muted);font-size:11px;font-weight:500}
.site-cell{display:flex;align-items:center;gap:12px}
.site-cell a{text-decoration:none;font-weight:600}
.site-cell a:hover{text-decoration:underline}
.btn{display:inline-flex;align-items:center;gap:6px;border:1px solid var(--border);background:var(--surface);color:var(--text);padding:8px 14px;border-radius:8px;font:500 13px Inter,sans-serif;cursor:pointer;text-decoration:none;white-space:nowrap}
.btn:hover{background:var(--surface-2)}
.btn.primary{background:var(--primary);color:var(--primary-text);border-color:var(--primary)}
.btn.primary:hover{opacity:.88}
.btn.danger{color:var(--down)}
.btn.sm{padding:5px 10px;font-size:12px}
.btn-row{display:flex;gap:8px;flex-wrap:wrap}
.linkbtn{background:none;border:0;padding:0;color:var(--muted);font:inherit;font-size:12.5px;cursor:pointer;text-decoration:underline}
.linkbtn.danger{color:var(--down)}
form.inline{display:inline}
label{display:block;font-weight:500;font-size:13px;margin-bottom:6px}
.hint{color:var(--muted);font-size:12px;margin-top:4px}
input[type=text],input[type=url],input[type=email],input[type=password],input[type=number],select,textarea{width:100%;padding:9px 11px;border:1px solid var(--border);border-radius:8px;background:var(--surface);color:var(--text);font:14px Inter,sans-serif}
input:focus,select:focus,textarea:focus{outline:2px solid var(--primary);outline-offset:-1px}
.form-grid{display:grid;grid-template-columns:2fr 3fr 1fr;gap:14px;align-items:end}
.form-grid .full{grid-column:1/-1}
.check{display:flex;align-items:center;gap:8px;font-weight:400}
.flash{padding:12px 16px;border-radius:8px;margin-bottom:18px;font-weight:500}
.flash.ok{background:var(--up-bg);color:var(--up)}
.flash.err{background:var(--down-bg);color:var(--down)}
.empty{padding:48px 20px;text-align:center;color:var(--muted)}
.uptime-bar{display:flex;gap:3px;height:34px;align-items:stretch}
.uptime-bar span{flex:1;border-radius:3px;background:var(--none)}
.uptime-bar span.up{background:var(--up)} .uptime-bar span.down{background:var(--down)} .uptime-bar span.partial{background:var(--warn)}
.uptime-bar span:hover{opacity:.7}
.tabs{display:flex;gap:4px;margin-bottom:-1px}
.tabs a{padding:8px 14px;text-decoration:none;color:var(--muted);font-weight:500;border-bottom:2px solid transparent}
.tabs a.active{color:var(--text);border-color:var(--text)}
.card-head{display:flex;justify-content:space-between;align-items:center;padding:14px 18px 0;border-bottom:1px solid var(--border);gap:12px;flex-wrap:wrap}
.url-cell{max-width:440px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.url-cell a{text-decoration:none} .url-cell a:hover{text-decoration:underline}
.err-text{color:var(--down);font-size:12px}
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:18px}
.code-up{color:var(--up)} .code-down{color:var(--down)}
.chart{width:100%;height:120px;display:block}
@media (max-width:820px){.form-grid,.two-col{grid-template-columns:1fr}nav.main a{padding:7px 8px}.hide-sm{display:none}}
</style>
</head>
<body>
@auth
<header class="top">
  <div class="wrap">
    <a href="{{ route('dashboard') }}" class="brand"><span class="pulse"></span>{{ config('app.name') }}</a>
    <nav class="main">
      <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard','sites.*') ? 'active' : '' }}">Monitors</a>
      @php $openCount = \App\Models\Incident::whereNull('resolved_at')->count(); @endphp
      <a href="{{ route('incidents') }}" class="{{ request()->routeIs('incidents') ? 'active' : '' }}">Incidents @if($openCount)<span class="badge-count">{{ $openCount }}</span>@endif</a>
      <a href="{{ route('settings') }}" class="{{ request()->routeIs('settings') ? 'active' : '' }}">Settings</a>
    </nav>
    <form method="post" action="{{ route('logout') }}" class="inline">@csrf<button class="linkbtn">Log out</button></form>
  </div>
</header>
@endauth
<main>
  <div class="wrap">
    @if(session('ok'))<div class="flash ok">{{ session('ok') }}</div>@endif
    @if($errors->any())<div class="flash err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
    @yield('content')
  </div>
</main>
</body>
</html>
