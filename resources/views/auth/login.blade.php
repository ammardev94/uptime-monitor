@extends('layouts.app')
@section('title', 'Log in')
@section('content')
<div style="max-width:380px;margin:8vh auto 0">
  <div class="brand" style="justify-content:center;margin-bottom:20px;font-size:18px"><span class="pulse"></span>{{ config('app.name') }}</div>
  <div class="card card-pad">
    <form method="post" action="{{ url('/login') }}">@csrf
      <div style="display:grid;gap:14px">
        <div><label>Email</label><input type="email" name="email" value="{{ old('email') }}" required autofocus></div>
        <div><label>Password</label><input type="password" name="password" required></div>
        <button class="btn primary" style="justify-content:center">Log in</button>
      </div>
    </form>
  </div>
</div>
@endsection
