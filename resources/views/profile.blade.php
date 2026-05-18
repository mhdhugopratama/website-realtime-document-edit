@extends('layouts.app')
@section('title', 'Profil Saya - GoDocs')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/profile.css') }}">
@endsection

@section('content')
<div class="profile-wrap">


    <div class="profile-header">
        <div class="profile-avatar">
            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
        </div>
        <div class="profile-meta">
            <h2>{{ Auth::user()->name }}</h2>
        </div>
    </div>


    <div class="profile-card">
        <div class="profile-card-head">
            Informasi Akun
        </div>
        <div class="profile-card-body">

            <div class="info-row">
                <div class="info-label">Nama</div>
                <div class="info-value">{{ Auth::user()->name }}</div>
            </div>

            <div class="info-row">
                <div class="info-label">Email</div>
                <div class="info-value">
                    <span class="email-badge">
                        {{ Auth::user()->email }}
                    </span>
                </div>
            </div>

        </div>
    </div>


    <div class="profile-card">
        <div class="profile-card-head">
            Ubah Nama
        </div>
        <div class="profile-card-body">

            @if(session('success_name'))
                <div class="alert-ok">{{ session('success_name') }}</div>
            @endif

            <form method="POST" action="{{ route('profile.updateName') }}">
                @csrf
                <div class="form-group">
                    <label for="name">Nama Baru</label>
                    <input type="text"
                           id="name"
                           name="name"
                           class="form-control"
                           value="{{ old('name', Auth::user()->name) }}"
                           required maxlength="100">
                    @error('name')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn-save">Simpan Nama</button>
            </form>

        </div>
    </div>


    <div class="profile-card">
        <div class="profile-card-head">
            Ganti Password
        </div>
        <div class="profile-card-body">

            @if(session('success_password'))
                <div class="alert-ok">{{ session('success_password') }}</div>
            @endif

            <form method="POST" action="{{ route('profile.updatePassword') }}">
                @csrf

                <div class="form-group">
                    <label for="current_password">Password Saat Ini</label>
                    <input type="password"
                           id="current_password"
                           name="current_password"
                           class="form-control"
                           placeholder="Masukkan password lama..."
                           required>
                    @error('current_password')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="new_password">Password Baru</label>
                    <input type="password"
                           id="new_password"
                           name="new_password"
                           class="form-control"
                           placeholder="Minimal 6 karakter..."
                           required minlength="6">
                    <div class="password-hint">Minimal 6 karakter</div>
                    @error('new_password')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="new_password_confirmation">Konfirmasi Password Baru</label>
                    <input type="password"
                           id="new_password_confirmation"
                           name="new_password_confirmation"
                           class="form-control"
                           placeholder="Ketik ulang password baru..."
                           required minlength="6">
                </div>

                <button type="submit" class="btn-save">Ganti Password</button>
            </form>

        </div>
    </div>

</div>
@endsection
