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
            <span class="count">{{ $myDocs->count() }}</span>
        </div>

        <div class="doc-list">
            @forelse($myDocs as $doc)
                <div class="doc-item">
                    <div class="doc-info">
                        <h3>
                            {{ $doc->title }}
                            <span class="badge badge-owner">Pemilik</span>
                        </h3>
                        <div class="meta">
                            Diperbarui {{ $doc->updated_at->diffForHumans() }}
                            &bull; {{ $doc->shares()->count() }} orang diberi akses
                        </div>
                    </div>
                    <div class="doc-actions">
                        <a href="{{ route('document.edit', $doc->id) }}" class="btn btn-primary">Buka</a>
                        <form method="POST" action="{{ route('document.destroy', $doc->id) }}"
                            onsubmit="return confirm('Yakin hapus dokumen \'{{ addslashes($doc->title) }}\'?')">
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
            <span class="count">{{ $sharedDocs->count() }}</span>
        </div>

        <div class="doc-list">
            @forelse($sharedDocs as $doc)
                <div class="doc-item">
                    <div class="doc-info">
                        <h3>
                            {{ $doc->title }}
                            @if ($doc->my_permission === 'edit')
                                <span class="badge badge-edit">Bisa Edit</span>
                            @else
                                <span class="badge badge-view">Hanya Lihat</span>
                            @endif
                        </h3>
                        <div class="meta">
                            Milik {{ $doc->owner->name }}
                            &bull; Diperbarui {{ $doc->updated_at->diffForHumans() }}
                        </div>
                    </div>
                    <div class="doc-actions">
                        <a href="{{ route('document.edit', $doc->id) }}" class="btn btn-primary">Buka</a>
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
