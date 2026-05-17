@extends('layouts.app')
@section('title', 'Login - GoDocs')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection

@section('content')
<div class="auth-box">
    <h1>GoDocs</h1>
    <p>Masuk untuk mulai mengedit dokumen bersama</p>

    @if($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="/login">
        @csrf
        <div class="form-group">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" class="form-control"
                   value="{{ old('email') }}" placeholder="contoh@email.com" required autofocus>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" class="form-control"
                   placeholder="Password" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%; padding: 10px;">
            Masuk
        </button>
    </form>

    <div class="auth-link">
        Belum punya akun? <a href="{{ route('register') }}">Daftar sekarang</a>
    </div>
</div>
@endsection
