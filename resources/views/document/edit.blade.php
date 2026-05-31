@extends('layouts.app')
@section('title', $document->title . ' - GoDocs')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/editor.css') }}">
@endsection

@section('content')

    <div class="editor-toolbar">
        <a href="{{ route('dashboard') }}" class="btn-back">Kembali</a>

        <input type="text" id="doc-title" value="{{ $document->title }}" placeholder="Judul dokumen..."
            {{ $bisaEdit ? '' : 'readonly' }}>

        <span class="save-status" id="save-status">Tersimpan</span>

        @if ($bisaEdit)
            <button class="btn-version" onclick="simpanVersi()">Simpan Versi</button>
        @endif

        @if ($adalahPemilik)
            <button class="btn-share" onclick="bukaModalShare()">Bagikan</button>
        @endif

        <div class="toolbar-divider"></div>

        <a href="{{ route('document.exportPdf', $document->id) }}" class="btn-export btn-export-pdf" target="_blank">PDF</a>
        <a href="{{ route('document.exportTxt', $document->id) }}" class="btn-export btn-export-word">TXT</a>
    </div>

    @if (!$bisaEdit)
        <div class="readonly-banner">
            Kamu hanya bisa <strong>melihat</strong> dokumen ini. Minta pemilik untuk memberimu akses edit.
        </div>
    @endif

    {{-- Area utama: textarea editor + sidebar --}}
    <div class="editor-main">

        <div class="editor-area" id="editor-area" style="padding:20px;display:flex;justify-content:center;">
            <div style="position:relative;width:100%;max-width:900px;display:flex;flex:1;">
                <textarea id="editor"
                    class="txt-editor"
                    placeholder="Mulai mengetik di sini..."
                    {{ $bisaEdit ? '' : 'readonly' }}
                    data-update-url="{{ route('document.update', $document->id) }}"
                    data-poll-url="{{ route('document.poll', $document->id) }}"
                    data-version-url="{{ route('document.saveVersion', $document->id) }}"
                    data-share-url="{{ route('document.share', $document->id) }}"
                    data-remove-share-url="{{ url('/documents/' . $document->id . '/shares') }}"
                    data-current-user="{{ Auth::id() }}"
                    data-can-edit="{{ $bisaEdit ? '1' : '0' }}"
                    data-is-owner="{{ $adalahPemilik ? '1' : '0' }}"
                    data-csrf="{{ csrf_token() }}"
                >{{ $document->content }}</textarea>
                <div id="cursor-overlay" style="position:absolute;top:0;left:0;right:0;bottom:0;pointer-events:none;z-index:50;overflow:hidden;"></div>
            </div>
        </div>

        <div class="sidebar">
            <div class="sidebar-section">
                <h4>Sedang Online</h4>
                <div class="online-list" id="online-list">
                    <div class="empty-sidebar">Memuat...</div>
                </div>
            </div>

            <div class="sidebar-section"><h4>Riwayat Versi</h4></div>
            <div class="version-list">
                @forelse($riwayatVersi as $versi)
                    <div class="version-item">
                        <div class="v-meta">{{ $versi->created_at->format('d M Y, H:i') }} &bull; oleh {{ $versi->savedBy->name }}</div>
                        <div class="v-title">{{ $versi->title }}</div>
                        <form method="POST" action="{{ route('document.restoreVersion', [$document->id, $versi->id]) }}"
                            onsubmit="return confirm('Kembalikan dokumen ke versi ini?')">
                            @csrf
                            <button type="submit" class="btn-restore">Pulihkan</button>
                        </form>
                    </div>
                @empty
                    <div class="empty-sidebar">Belum ada versi tersimpan.<br>Klik "Simpan Versi" untuk menyimpan.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="toast" id="toast"></div>

    <div class="modal-overlay" id="conflict-modal">
        <div class="modal-box" style="max-width:400px;">
            <div class="modal-head" style="background:#ea4335;color:white;"><h3 style="margin:0;">Peringatan Konflik!</h3></div>
            <div class="modal-body">
                <p><strong id="conflict-user-name">Seseorang</strong> baru saja mengubah dokumen ini saat kamu sedang mengetik.</p>
                <div style="display:flex;gap:10px;margin-top:20px;justify-content:flex-end;">
                    <button class="btn-restore" style="background:#888;" onclick="selesaikanKonflik('overwrite')">Paksakan Timpa</button>
                    <button class="btn-restore" style="background:#ea4335;" onclick="selesaikanKonflik('reload')">Muat Ketikan Mereka</button>
                </div>
            </div>
        </div>
    </div>

    @if ($adalahPemilik)
        <div class="modal-overlay" id="share-modal">
            <div class="modal-box">
                <div class="modal-head">
                    <h3>Bagikan Dokumen</h3>
                    <button class="modal-close" onclick="tutupModalShare()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="share-form">
                        <input type="email" id="share-email" placeholder="Email pengguna...">
                        <select id="share-perm">
                            <option value="edit">Bisa Edit</option>
                            <option value="view">Hanya Lihat</option>
                        </select>
                        <button class="btn-add-share" onclick="tambahAkses()">Bagikan</button>
                    </div>
                    <div class="share-error" id="share-error"></div>
                    <div class="share-success" id="share-success"></div>

                    <div class="share-title">Yang Sudah Punya Akses</div>
                    <div class="share-list" id="share-list">
                        @forelse($daftarAkses as $akses)
                            <div class="share-item" id="share-item-{{ $akses->user->id }}">
                                <div class="share-avatar" style="background:{{ ['#ea4335','#4285f4','#fbbc05','#34a853','#9c27b0','#ff5722'][$akses->user->id % 6] }}">
                                    {{ strtoupper(substr($akses->user->name, 0, 1)) }}
                                </div>
                                <div class="share-user-info">
                                    <div class="sname">{{ $akses->user->name }}</div>
                                    <div class="semail">{{ $akses->user->email }}</div>
                                </div>
                                <span class="share-perm {{ $akses->permission === 'edit' ? 'perm-edit' : 'perm-view' }}">
                                    {{ $akses->permission === 'edit' ? 'Bisa Edit' : 'Hanya Lihat' }}
                                </span>
                                <button class="btn-remove-share" onclick="hapusAkses({{ $akses->user->id }}, this)" title="Hapus akses">&times;</button>
                            </div>
                        @empty
                            <div id="no-shares-msg" style="font-size:13px;color:#bbb;text-align:center;padding:16px 0;">Belum ada yang diberi akses.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif

@endsection

@section('scripts')
    <script>
        let timestampTerakhir = {{ $document->updated_at->timestamp }};
    </script>
    <script src="{{ asset('js/editor.js') }}"></script>
@endsection
