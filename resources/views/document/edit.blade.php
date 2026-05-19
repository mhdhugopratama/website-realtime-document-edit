@extends('layouts.app')
@section('title', $document->title . ' - GoDocs')


@push('head')
@endpush

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
            <button class="btn-version" onclick="saveVersion()">
                Simpan Versi
            </button>
        @endif

        @if ($adalahPemilik)
            <button class="btn-share" onclick="openShareModal()">
                Bagikan
            </button>
        @endif

        <div class="toolbar-divider"></div>

        <a href="{{ route('document.exportPdf', $document->id) }}" class="btn-export btn-export-pdf" target="_blank"
            title="Unduh sebagai PDF">
            PDF
        </a>

        <a href="{{ route('document.exportTxt', $document->id) }}" class="btn-export btn-export-word"
            title="Unduh sebagai TXT">
            TXT
        </a>
    </div>

    @if (!$bisaEdit)
        <div class="readonly-banner">
            Kamu hanya bisa <strong>melihat</strong> dokumen ini. Minta pemilik untuk memberimu akses edit.
        </div>
    @endif


    <div class="editor-main">

        <div class="editor-area" id="editor-area" style="padding: 20px; display: flex; justify-content: center;">
            <div style="position: relative; width: 100%; max-width: 900px; display: flex; flex: 1;">
                <textarea id="editor" data-doc-id="{{ $document->id }}" data-current-user="{{ Auth::id() }}"
                    data-can-edit="{{ $bisaEdit ? '1' : '0' }}" data-is-owner="{{ $adalahPemilik ? '1' : '0' }}"
                    data-share-url="{{ route('document.share', $document->id) }}"
                    data-remove-share-url="{{ url('/documents/' . $document->id . '/shares') }}"
                    data-update-url="{{ route('document.update', $document->id) }}"
                    data-heartbeat-url="{{ route('document.heartbeat', $document->id) }}"
                    data-poll-url="{{ route('document.poll', $document->id) }}"
                    data-version-url="{{ route('document.saveVersion', $document->id) }}" data-csrf="{{ csrf_token() }}"
                    class="txt-editor" placeholder="Mulai mengetik di sini..." {{ $bisaEdit ? '' : 'readonly' }}>{{ $document->content }}</textarea>
                <div id="cursor-overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; pointer-events: none; z-index: 50; overflow: hidden; border-radius: 4px;"></div>
            </div>
        </div>

        <div class="sidebar">

            <div class="sidebar-section">
                <h4>Sedang Online</h4>
                <div class="online-list" id="online-list">
                    <div class="empty-sidebar">Memuat...</div>
                </div>
            </div>

            <div class="sidebar-section">
                <h4>Riwayat Versi</h4>
            </div>
            <div class="version-list">
                @forelse($riwayatVersi as $versi)
                    <div class="version-item">
                        <div class="v-meta">
                            {{ $versi->created_at->format('d M Y, H:i') }}
                            &bull; oleh {{ $versi->savedBy->name }}
                        </div>
                        <div class="v-title">{{ $versi->title }}</div>
                        <form method="POST"
                            action="{{ route('document.restoreVersion', [$document->id, $versi->id]) }}"
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
            <div class="modal-head" style="background:#ea4335; color:white;">
                <h3 style="margin:0;">Peringatan Konflik!</h3>
            </div>
            <div class="modal-body">
                <p><strong id="conflict-user-name">Seseorang</strong> baru saja mengubah dan menyimpan dokumen ini saat Anda sedang mengetik.</p>
                <p>Ketikan Anda yang belum tersimpan akan bertabrakan dengan perubahan mereka.</p>
                <div style="display:flex; gap:10px; margin-top:20px; justify-content:flex-end;">
                    <button class="btn-restore" style="background:#888;" onclick="resolveConflict('overwrite')">Paksakan Timpa</button>
                    <button class="btn-restore" style="background:#ea4335;" onclick="resolveConflict('reload')">Muat Ketikan Mereka</button>
                </div>
            </div>
        </div>
    </div>

    @if ($adalahPemilik)
        <div class="modal-overlay" id="share-modal">
            <div class="modal-box">
                <div class="modal-head">
                    <h3>Bagikan Dokumen</h3>
                    <button class="modal-close" onclick="closeShareModal()">&times;</button>
                </div>
                <div class="modal-body">

                    <div class="share-form">
                        <input type="email" id="share-email" placeholder="Email pengguna...">
                        <select id="share-perm">
                            <option value="edit">Bisa Edit</option>
                            <option value="view">Hanya Lihat</option>
                        </select>
                        <button class="btn-add-share" onclick="addShare()">
                            Bagikan
                        </button>
                    </div>
                    <div class="share-error" id="share-error"></div>
                    <div class="share-success" id="share-success"></div>


                    <div class="share-title">Yang Sudah Punya Akses</div>
                    <div class="share-list" id="share-list">
                        @forelse($daftarAkses as $akses)
                            <div class="share-item" id="share-item-{{ $akses->user->id }}">
                                <div class="share-avatar"
                                    style="background: {{ ['#ea4335', '#4285f4', '#fbbc05', '#34a853', '#9c27b0', '#ff5722'][$akses->user->id % 6] }}">
                                    {{ strtoupper(substr($akses->user->name, 0, 1)) }}
                                </div>
                                <div class="share-user-info">
                                    <div class="sname">{{ $akses->user->name }}</div>
                                    <div class="semail">{{ $akses->user->email }}</div>
                                </div>
                                <span class="share-perm {{ $akses->permission === 'edit' ? 'perm-edit' : 'perm-view' }}">
                                    {{ $akses->permission === 'edit' ? 'Bisa Edit' : 'Hanya Lihat' }}
                                </span>
                                <button class="btn-remove-share" onclick="hapusAkses({{ $akses->user->id }}, this)"
                                    title="Hapus akses">&times;</button>
                            </div>
                        @empty
                            <div id="no-shares-msg"
                                style="font-size:13px; color:#bbb; text-align:center; padding:16px 0;">
                                Belum ada yang diberi akses.
                            </div>
                        @endforelse
                    </div>

                </div>
            </div>
        </div>
    @endif

@endsection

@section('scripts')
    <script>
        const USER_COLORS = [
            '#ea4335', '#4285f4', '#fbbc05', '#34a853', '#9c27b0', '#ff5722', '#00bcd4', '#e91e63'
        ];

        function getUserColor(userId) {
            return USER_COLORS[userId % USER_COLORS.length];
        }

        const editorEl = document.getElementById('editor');
        const inputJudul = document.getElementById('doc-title');
        const statusSimpan = document.getElementById('save-status');
        const daftarOnline = document.getElementById('online-list');
        const toast = document.getElementById('toast');

        const updateUrl = editorEl.dataset.updateUrl;
        const heartbeatUrl = editorEl.dataset.heartbeatUrl;
        const pollUrl = editorEl.dataset.pollUrl;
        const versionUrl = editorEl.dataset.versionUrl;
        const shareUrl = editorEl.dataset.shareUrl;
        const hapusShareUrl = editorEl.dataset.removeShareUrl;
        const tokenCsrf = editorEl.dataset.csrf;
        const idUserSekarang = parseInt(editorEl.dataset.currentUser);
        const bisaEdit = editorEl.dataset.canEdit === '1';
        const adalahPemilik = editorEl.dataset.isOwner === '1';

        let sedangMengetik = false;
        let timerMengetik = null;
        let kontenTerakhir = editorEl.value;
        let judulTerakhir = inputJudul.value;
        let timestampTerakhir = {{ $document->updated_at->timestamp }};
        let modeKonflik = false;
        let kontenKonflikMasuk = '';
        let judulKonflikMasuk = '';

        let waktuSimpanTerakhir = 0;
        let userTerkini = null;

        if (bisaEdit) {
            editorEl.addEventListener('input', () => {
                sedangMengetik = true;
                clearTimeout(timerMengetik);

                const sekarang = Date.now();
                if (sekarang - waktuSimpanTerakhir > 1000) {
                    simpanOtomatis();
                    waktuSimpanTerakhir = sekarang;
                }

                timerMengetik = setTimeout(() => {
                    sedangMengetik = false;
                    simpanOtomatis();
                    waktuSimpanTerakhir = Date.now();
                }, 400);
            });
        }

        inputJudul.addEventListener('input', () => {
            sedangMengetik = true;
            clearTimeout(timerMengetik);

            timerMengetik = setTimeout(() => {
                sedangMengetik = false;
                simpanOtomatis();
            }, 400);
        });

        async function simpanOtomatis() {
            if (modeKonflik) return;

            const content = editorEl.value;
            const title = inputJudul.value;

            if (content === kontenTerakhir && title === judulTerakhir) return;

            statusSimpan.textContent = 'Menyimpan...';
            statusSimpan.className = 'save-status saving';

            try {
                const res = await fetch(updateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': tokenCsrf,
                    },
                    body: JSON.stringify({
                        content,
                        title
                    }),
                });
                const data = await res.json();

                if (data.success) {
                    kontenTerakhir = content;
                    judulTerakhir = title;
                    if (data.updated_at_timestamp) {
                        timestampTerakhir = data.updated_at_timestamp;
                    }
                    statusSimpan.textContent = '✓ Tersimpan ' + data.updated_at;
                    statusSimpan.className = 'save-status saved';
                }
            } catch (e) {
                statusSimpan.textContent = 'Gagal menyimpan';
                statusSimpan.className = 'save-status';
            }
        }

        function tampilkanModalKonflik(namaPengedit, konten, judul) {
            modeKonflik = true;
            kontenKonflikMasuk = konten;
            judulKonflikMasuk = judul;
            document.getElementById('conflict-user-name').textContent = namaPengedit;
            document.getElementById('conflict-modal').classList.add('open');
        }

        function selesaikanKonflik(aksi) {
            document.getElementById('conflict-modal').classList.remove('open');
            modeKonflik = false;
            
            if (aksi === 'reload') {
                const selStart = editorEl.selectionStart;
                const selEnd = editorEl.selectionEnd;
                
                editorEl.value = kontenKonflikMasuk;
                inputJudul.value = judulKonflikMasuk;
                kontenTerakhir = kontenKonflikMasuk;
                judulTerakhir = judulKonflikMasuk;
                
                editorEl.setSelectionRange(selStart, selEnd);
                
                statusSimpan.textContent = 'Diperbarui ke versi terbaru';
                statusSimpan.className = 'save-status saved';
            } else {
                simpanOtomatis();
            }
        }

        async function cekPembaruanServer() {
            if (modeKonflik) return;
            try {
                const res = await fetch(pollUrl);
                const data = await res.json();

                perbaruiDaftarOnline(data.online_users, data.current_user_id);
                tampilkanKursorRemote(data.online_users, data.current_user_id);

                if (data.updated_at_timestamp && data.updated_at_timestamp > timestampTerakhir) {
                    if (data.last_editor && data.last_editor.id !== idUserSekarang) {
                        const kontenSekarang = editorEl.value;
                        if (kontenSekarang !== data.content && kontenSekarang !== kontenTerakhir) {
                            tampilkanModalKonflik(data.last_editor.name, data.content, data.title);
                            timestampTerakhir = data.updated_at_timestamp;
                            return;
                        }
                    }
                    timestampTerakhir = data.updated_at_timestamp;
                }

                if (!sedangMengetik && data.content !== kontenTerakhir) {
                    const selStart = editorEl.selectionStart;
                    const selEnd = editorEl.selectionEnd;
                    const isFocused = document.activeElement === editorEl;
                    
                    const lengthDiff = data.content.length - kontenTerakhir.length;
                    
                    editorEl.value = data.content;
                    kontenTerakhir = data.content;

                    if (data.title !== inputJudul.value) {
                        inputJudul.value = data.title;
                        judulTerakhir = data.title;
                    }
                    
                    if (isFocused) {
                        editorEl.setSelectionRange(
                            Math.max(0, selStart + lengthDiff), 
                            Math.max(0, selEnd + lengthDiff)
                        );
                    }

                    statusSimpan.textContent = '↺ Diperbarui ' + data.updated_at;
                    statusSimpan.className = 'save-status saved';
                }
            } catch (e) {}
        }

        let kursorTopSaya = 0;
        let kursorLeftSaya = 0;

        function dapatkanKoordinatKursor(element) {
            let div = document.createElement('div');
            document.body.appendChild(div);

            const style = window.getComputedStyle(element);
            div.style.position = 'absolute';
            div.style.top = '0';
            div.style.left = '-9999px';
            div.style.whiteSpace = 'pre-wrap';
            div.style.wordWrap = 'break-word';
            
            const properties = ['fontFamily', 'fontSize', 'fontWeight', 'fontStyle', 'letterSpacing', 'textTransform', 'wordSpacing', 'textIndent', 'lineHeight', 'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft', 'borderTopWidth', 'borderRightWidth', 'borderBottomWidth', 'borderLeftWidth', 'boxSizing'];
            properties.forEach(prop => div.style[prop] = style[prop]);
            
            div.style.width = element.offsetWidth + 'px';
            
            div.textContent = element.value.substring(0, element.selectionEnd);
            
            const span = document.createElement('span');
            span.textContent = element.value.substring(element.selectionEnd) || '.';
            div.appendChild(span);
            
            const coordinates = {
                top: span.offsetTop,
                left: span.offsetLeft,
            };
            
            document.body.removeChild(div);
            return coordinates;
        }

        async function kirimStatusOnline() {
            if (document.activeElement === editorEl) {
                const pos = dapatkanKoordinatKursor(editorEl);
                kursorTopSaya = pos.top;
                kursorLeftSaya = pos.left;
            }
            try {
                await fetch(heartbeatUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': tokenCsrf,
                    },
                    body: JSON.stringify({
                        cursor_top: kursorTopSaya,
                        cursor_left: kursorLeftSaya,
                    }),
                });
            } catch (e) {}
        }

        function perbaruiDaftarOnline(users, myId) {
            if (!users || users.length === 0) {
                daftarOnline.innerHTML = '<div class="empty-sidebar">Tidak ada yang online</div>';
                return;
            }

            let html = '';
            users.forEach(user => {
                const isMe = user.id === myId;
                const color = getUserColor(user.id);
                html += `
            <div class="online-user">
                <div class="online-dot" style="background:${color};"></div>
                <span>${user.name}${isMe ? ' <em style="color:#888;font-size:11px;">(kamu)</em>' : ''}</span>
            </div>
        `;
            });
            daftarOnline.innerHTML = html;
        }

        function tampilkanKursorRemote(users, myId) {
            const overlay = document.getElementById('cursor-overlay');
            if (!overlay) return;

            const activeIds = new Set();

            if (users) {
                users.forEach(user => {
                    if (user.id === myId) return;
                    if (user.cursor_top == null || user.cursor_left == null) return;
                    if (user.cursor_top === 0 && user.cursor_left === 0) return;

                    activeIds.add(user.id);
                    const cursorId = 'cursor-user-' + user.id;
                    let cursorEl = document.getElementById(cursorId);

                    if (!cursorEl) {
                        const color = getUserColor(user.id);
                        cursorEl = document.createElement('div');
                        cursorEl.id = cursorId;
                        cursorEl.className = 'remote-cursor';

                        const label = document.createElement('div');
                        label.className = 'remote-cursor-label';
                        label.textContent = user.name;
                        label.style.background = color;

                        const caret = document.createElement('div');
                        caret.className = 'remote-cursor-caret';
                        caret.style.background = color;

                        cursorEl.appendChild(label);
                        cursorEl.appendChild(caret);
                        overlay.appendChild(cursorEl);
                    }

                    cursorEl.style.top = (user.cursor_top - editorEl.scrollTop) + 'px';
                    cursorEl.style.left = (user.cursor_left - editorEl.scrollLeft) + 'px';
                });
            }

            userTerkini = users;

            Array.from(overlay.children).forEach(child => {
                if (child.id && child.id.startsWith('cursor-user-')) {
                    const idStr = child.id.replace('cursor-user-', '');
                    if (!activeIds.has(parseInt(idStr))) {
                        overlay.removeChild(child);
                    }
                }
            });
        }

        editorEl.addEventListener('scroll', () => {
            if (userTerkini) {
                tampilkanKursorRemote(userTerkini, idUserSekarang);
            }
        });

        setInterval(kirimStatusOnline, 600);
        setInterval(cekPembaruanServer, 600);
        kirimStatusOnline();
        cekPembaruanServer();

        async function simpanVersi() {
            await simpanOtomatis();

            try {
                const res = await fetch(versionUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': tokenCsrf
                    },
                });
                const data = await res.json();

                if (data.success) {
                    tampilkanToast('✓ ' + data.message);
                    setTimeout(() => location.reload(), 1500);
                }
            } catch (e) {
                tampilkanToast('Gagal menyimpan versi', 'error');
            }
        }

        function tampilkanToast(pesan, tipe = 'success') {
            toast.textContent = pesan;
            toast.style.background = tipe === 'success' ? '#34a853' : '#ea4335';
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }

        function bukaModalShare() {
            document.getElementById('share-modal').classList.add('open');
            document.getElementById('share-email').focus();
        }

        function tutupModalShare() {
            document.getElementById('share-modal').classList.remove('open');
        }

        document.getElementById('share-modal')?.addEventListener('click', function(e) {
            if (e.target === this) tutupModalShare();
        });

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') tutupModalShare();
        });

        async function tambahAkses() {
            const email = document.getElementById('share-email').value;
            const permission = document.getElementById('share-perm').value;
            const errEl = document.getElementById('share-error');
            const sucEl = document.getElementById('share-success');
            const btn = document.querySelector('.btn-add-share');

            errEl.style.display = 'none';
            sucEl.style.display = 'none';

            if (!email) return;

            btn.disabled = true;
            btn.textContent = 'Memproses...';

            try {
                const res = await fetch(shareUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': tokenCsrf
                    },
                    body: JSON.stringify({
                        email,
                        permission
                    })
                });
                const data = await res.json();

                btn.disabled = false;
                btn.textContent = 'Bagikan';

                if (!res.ok) {
                    errEl.textContent = data.error || 'Terjadi kesalahan.';
                    errEl.style.display = 'block';
                    return;
                }

                sucEl.textContent = data.message;
                sucEl.style.display = 'block';
                document.getElementById('share-email').value = '';

                const targetList = document.getElementById('share-list');
                const emptyMsg = document.getElementById('no-shares-msg');
                if (emptyMsg) emptyMsg.remove();

                const existing = document.getElementById('share-item-' + data.user.id);
                if (existing) {
                    existing.querySelector('.share-perm').textContent = data.permission === 'edit' ?
                        'Bisa Edit' : 'Hanya Lihat';
                    existing.querySelector('.share-perm').className = 'share-perm ' + (data.permission ===
                        'edit' ? 'perm-edit' : 'perm-view');
                } else {
                    const permClass = data.permission === 'edit' ? 'perm-edit' : 'perm-view';
                    const permText = data.permission === 'edit' ? 'Bisa Edit' : 'Hanya Lihat';
                    const html = `
                            <div class="share-item" id="share-item-${data.user.id}">
                                <div class="share-avatar" style="background: ${data.user.color}">${data.user.initial}</div>
                                <div class="share-user-info">
                                    <div class="sname">${data.user.name}</div>
                                    <div class="semail">${data.user.email}</div>
                                </div>
                                <span class="share-perm ${permClass}">${permText}</span>
                                <button class="btn-remove-share" onclick="hapusAkses(${data.user.id}, this)" title="Hapus akses">&times;</button>
                            </div>
                        `;
                    targetList.insertAdjacentHTML('beforeend', html);
                }

            } catch (e) {
                btn.disabled = false;
                btn.textContent = 'Bagikan';
                errEl.textContent = 'Gagal menghubungi server.';
                errEl.style.display = 'block';
            }
        }

        async function hapusAkses(userId, btnEl) {
            if (!confirm('Hapus akses untuk pengguna ini?')) return;

            try {
                const res = await fetch(hapusShareUrl + '/' + userId, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': tokenCsrf
                    }
                });

                if (res.ok) {
                    document.getElementById('share-item-' + userId)?.remove();

                    const list = document.getElementById('share-list');
                    if (list && list.children.length === 0) {
                        list.innerHTML = `<div id="no-shares-msg"
                    style="font-size:13px; color:#bbb; text-align:center; padding:16px 0;">
                    Belum ada yang diberi akses.
                </div>`;
                    }
                }
            } catch (e) {
                showToast('Gagal menghapus akses');
            }
        }
    </script>
@endsection
