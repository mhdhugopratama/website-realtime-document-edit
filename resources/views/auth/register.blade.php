@extends('layouts.app')
@section('title', 'Daftar - GoDocs')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection

@section('content')
<div class="auth-box">
    <h1>GoDocs</h1>
    <p>Buat akun untuk mulai berkolaborasi</p>

    @if($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="/register">
        @csrf
        <div class="form-group">
            <label for="name">Nama Lengkap</label>
            <input id="name" type="text" name="name" class="form-control"
                   value="{{ old('name') }}" placeholder="Nama kamu" required autofocus>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" class="form-control"
                   value="{{ old('email') }}" placeholder="contoh@email.com" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" class="form-control"
                   placeholder="Minimal 6 karakter" required>
        </div>
        <div class="form-group">
            <label for="password_confirmation">Ulangi Password</label>
            <input id="password_confirmation" type="password" name="password_confirmation"
                   class="form-control" placeholder="Ketik ulang password" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%; padding: 10px;">
            Daftar
        </button>
    </form>

    <div class="auth-link">
        Sudah punya akun? <a href="{{ route('login') }}">Masuk</a>
    </div>
</div>
@endsection
