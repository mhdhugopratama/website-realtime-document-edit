@extends('layouts.app')
@section('title', 'Dashboard - GoDocs')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
@endsection

@section('content')
    <div class="dashboard-wrap">


        @if (session('success'))
            <div class="alert-success-box">{{ session('success') }}</div>
        @endif


        <form class="new-doc-form" method="POST" action="{{ route('document.store') }}">
            @csrf
            <input type="text" name="title" placeholder="Judul dokumen baru..." required>
            <button type="submit" class="btn btn-primary">+ Buat Dokumen</button>
        </form>


        <div class="section-header">
            Dokumen Saya
            <span class="count">{{ $dokumenPribadi->count() }}</span>
        </div>

        <div class="doc-list">
            @forelse($dokumenPribadi as $dokumen)
                <div class="doc-item">
                    <div class="doc-info">
                        <h3>
                            {{ $dokumen->title }}
                            <span class="badge badge-owner">Pemilik</span>
                        </h3>
                        <div class="meta">
                            Diperbarui {{ $dokumen->updated_at->diffForHumans() }}
                            &bull; {{ $dokumen->shares()->count() }} orang diberi akses
                        </div>
                    </div>
                    <div class="doc-actions">
                        <a href="{{ route('document.edit', $dokumen->id) }}" class="btn btn-primary">Buka</a>
                        <form method="POST" action="{{ route('document.destroy', $dokumen->id) }}"
                            onsubmit="return confirm('Yakin hapus dokumen \'{{ addslashes($dokumen->title) }}\'?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Hapus</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <div class="icon"></div>
                    <p>Belum ada dokumen.</p>
                    <small>Buat dokumen pertamamu di kolom di atas!</small>
                </div>
            @endforelse
        </div>


        <div class="section-header">
            Dibagikan ke Saya
            <span class="count">{{ $dokumenDibagikan->count() }}</span>
        </div>

        <div class="doc-list">
            @forelse($dokumenDibagikan as $dokumen)
                <div class="doc-item">
                    <div class="doc-info">
                        <h3>
                            {{ $dokumen->title }}
                            @if ($dokumen->hak_akses === 'edit')
                                <span class="badge badge-edit">Bisa Edit</span>
                            @else
                                <span class="badge badge-view">Hanya Lihat</span>
                            @endif
                        </h3>
                        <div class="meta">
                            Milik {{ $dokumen->owner->name }}
                            &bull; Diperbarui {{ $dokumen->updated_at->diffForHumans() }}
                        </div>
                    </div>
                    <div class="doc-actions">
                        <a href="{{ route('document.edit', $dokumen->id) }}" class="btn btn-primary">Buka</a>
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <div class="icon"></div>
                    <p>Belum ada dokumen yang dibagikan ke kamu.</p>
                    <small>Minta rekan untuk membagikan dokumennya kepadamu.</small>
                </div>
            @endforelse
        </div>

    </div>
@endsection
