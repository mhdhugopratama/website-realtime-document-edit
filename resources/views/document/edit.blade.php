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
            {{ $canEdit ? '' : 'readonly' }}>

        <span class="save-status" id="save-status">Tersimpan</span>

        @if ($canEdit)
            <button class="btn-version" onclick="saveVersion()">
                Simpan Versi
            </button>
        @endif

        @if ($isOwner)
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

    @if (!$canEdit)
        <div class="readonly-banner">
            Kamu hanya bisa <strong>melihat</strong> dokumen ini. Minta pemilik untuk memberimu akses edit.
        </div>
    @endif


    <div class="editor-main">

        <div class="editor-area" id="editor-area" style="padding: 20px;">
            <textarea id="editor" data-doc-id="{{ $document->id }}" data-current-user="{{ Auth::id() }}"
                data-can-edit="{{ $canEdit ? '1' : '0' }}" data-is-owner="{{ $isOwner ? '1' : '0' }}"
                data-share-url="{{ route('document.share', $document->id) }}"
                data-remove-share-url="{{ url('/documents/' . $document->id . '/shares') }}"
                data-update-url="{{ route('document.update', $document->id) }}"
                data-heartbeat-url="{{ route('document.heartbeat', $document->id) }}"
                data-poll-url="{{ route('document.poll', $document->id) }}"
                data-version-url="{{ route('document.saveVersion', $document->id) }}" data-csrf="{{ csrf_token() }}"
                class="txt-editor" placeholder="Mulai mengetik di sini..." {{ $canEdit ? '' : 'readonly' }}>{{ $document->content }}</textarea>
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
                @forelse($versions as $version)
                    <div class="version-item">
                        <div class="v-meta">
                            {{ $version->created_at->format('d M Y, H:i') }}
                            &bull; oleh {{ $version->savedBy->name }}
                        </div>
                        <div class="v-title">{{ $version->title }}</div>
                        <form method="POST"
                            action="{{ route('document.restoreVersion', [$document->id, $version->id]) }}"
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

    @if ($isOwner)
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
                        @forelse($shares as $share)
                            <div class="share-item" id="share-item-{{ $share->user->id }}">
                                <div class="share-avatar"
                                    style="background: {{ ['#ea4335', '#4285f4', '#fbbc05', '#34a853', '#9c27b0', '#ff5722'][$share->user->id % 6] }}">
                                    {{ strtoupper(substr($share->user->name, 0, 1)) }}
                                </div>
                                <div class="share-user-info">
                                    <div class="sname">{{ $share->user->name }}</div>
                                    <div class="semail">{{ $share->user->email }}</div>
                                </div>
                                <span class="share-perm {{ $share->permission === 'edit' ? 'perm-edit' : 'perm-view' }}">
                                    {{ $share->permission === 'edit' ? 'Bisa Edit' : 'Hanya Lihat' }}
                                </span>
                                <button class="btn-remove-share" onclick="removeShare({{ $share->user->id }}, this)"
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
        const titleInput = document.getElementById('doc-title');
        const saveStatus = document.getElementById('save-status');
        const onlineList = document.getElementById('online-list');
        const toast = document.getElementById('toast');

        const updateUrl = editorEl.dataset.updateUrl;
        const heartbeatUrl = editorEl.dataset.heartbeatUrl;
        const pollUrl = editorEl.dataset.pollUrl;
        const versionUrl = editorEl.dataset.versionUrl;
        const shareUrl = editorEl.dataset.shareUrl;
        const removeShareBaseUrl = editorEl.dataset.removeShareUrl;
        const csrfToken = editorEl.dataset.csrf;
        const currentUserId = parseInt(editorEl.dataset.currentUser);
        const canEdit = editorEl.dataset.canEdit === '1';
        const isOwner = editorEl.dataset.isOwner === '1';

        let isTyping = false;
        let typingTimer = null;
        let lastContent = editorEl.value;
        let lastTitle = titleInput.value;
        let lastTimestamp = {{ $document->updated_at->timestamp }};
        let isConflictMode = false;
        let incomingConflictContent = '';
        let incomingConflictTitle = '';

        let lastSaveTime = 0;

        if (canEdit) {
            editorEl.addEventListener('input', () => {
                isTyping = true;
                clearTimeout(typingTimer);

                const now = Date.now();
                if (now - lastSaveTime > 1000) {
                    autoSave();
                    lastSaveTime = now;
                }

                typingTimer = setTimeout(() => {
                    isTyping = false;
                    autoSave();
                    lastSaveTime = Date.now();
                }, 800);
            });
        }

        titleInput.addEventListener('input', () => {
            isTyping = true;
            clearTimeout(typingTimer);

            typingTimer = setTimeout(() => {
                isTyping = false;
                autoSave();
            }, 800);
        });

        async function autoSave() {
            if (isConflictMode) return;

            const content = editorEl.value;
            const title = titleInput.value;

            if (content === lastContent && title === lastTitle) return;

            saveStatus.textContent = 'Menyimpan...';
            saveStatus.className = 'save-status saving';

            try {
                const res = await fetch(updateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        content,
                        title
                    }),
                });
                const data = await res.json();

                if (data.success) {
                    lastContent = content;
                    lastTitle = title;
                    if (data.updated_at_timestamp) {
                        lastTimestamp = data.updated_at_timestamp;
                    }
                    saveStatus.textContent = '✓ Tersimpan ' + data.updated_at;
                    saveStatus.className = 'save-status saved';
                }
            } catch (e) {
                saveStatus.textContent = 'Gagal menyimpan';
                saveStatus.className = 'save-status';
            }
        }

        function showConflictModal(editorName, content, title) {
            isConflictMode = true;
            incomingConflictContent = content;
            incomingConflictTitle = title;
            document.getElementById('conflict-user-name').textContent = editorName;
            document.getElementById('conflict-modal').classList.add('open');
        }

        function resolveConflict(action) {
            document.getElementById('conflict-modal').classList.remove('open');
            isConflictMode = false;
            
            if (action === 'reload') {
                const selStart = editorEl.selectionStart;
                const selEnd = editorEl.selectionEnd;
                
                editorEl.value = incomingConflictContent;
                titleInput.value = incomingConflictTitle;
                lastContent = incomingConflictContent;
                lastTitle = incomingConflictTitle;
                
                editorEl.setSelectionRange(selStart, selEnd);
                
                saveStatus.textContent = 'Diperbarui ke versi terbaru';
                saveStatus.className = 'save-status saved';
            } else {
                autoSave();
            }
        }

        async function pollServer() {
            if (isConflictMode) return;
            try {
                const res = await fetch(pollUrl);
                const data = await res.json();

                updateOnlineList(data.online_users, data.current_user_id);
                // Cursor melayang dihilangkan untuk TXT editor agar lebih simpel
                // renderRemoteCursors tidak dipanggil lagi

                if (data.updated_at_timestamp && data.updated_at_timestamp > lastTimestamp) {
                    if (data.last_editor && data.last_editor.id !== currentUserId) {
                        const currentContent = editorEl.value;
                        if (currentContent !== data.content && currentContent !== lastContent) {
                            showConflictModal(data.last_editor.name, data.content, data.title);
                            lastTimestamp = data.updated_at_timestamp;
                            return;
                        }
                    }
                    lastTimestamp = data.updated_at_timestamp;
                }

                if (!isTyping && data.content !== lastContent) {
                    const selStart = editorEl.selectionStart;
                    const selEnd = editorEl.selectionEnd;
                    
                    editorEl.value = data.content;
                    lastContent = data.content;

                    if (data.title !== titleInput.value) {
                        titleInput.value = data.title;
                        lastTitle = data.title;
                    }
                    
                    if (document.activeElement === editorEl) {
                        editorEl.setSelectionRange(selStart, selEnd);
                    }

                    saveStatus.textContent = '↺ Diperbarui ' + data.updated_at;
                    saveStatus.className = 'save-status saved';
                }
            } catch (e) {}
        }

        async function sendHeartbeat() {
            try {
                await fetch(heartbeatUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        cursor_top: 0,
                        cursor_left: 0,
                    }),
                });
            } catch (e) {}
        }

        function updateOnlineList(users, myId) {
            if (!users || users.length === 0) {
                onlineList.innerHTML = '<div class="empty-sidebar">Tidak ada yang online</div>';
                return;
            }

            let html = '';
            users.forEach(user => {
                const isMe = user.id === myId;
                const color = getUserColor(user.id);
                // Kita tambahkan teks "sedang mengetik" kalau last_seen baru saja
                html += `
            <div class="online-user">
                <div class="online-dot" style="background:${color};"></div>
                <span>${user.name}${isMe ? ' <em style="color:#888;font-size:11px;">(kamu)</em>' : ''}</span>
            </div>
        `;
            });
            onlineList.innerHTML = html;
        }

        setInterval(sendHeartbeat, 1000);
        setInterval(pollServer, 1000);
        sendHeartbeat();
        pollServer();

        async function saveVersion() {
            await autoSave();

            try {
                const res = await fetch(versionUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                });
                const data = await res.json();

                if (data.success) {
                    showToast('✓ ' + data.message);
                    setTimeout(() => location.reload(), 1500);
                }
            } catch (e) {
                showToast('Gagal menyimpan versi');
            }
        }

        function showToast(message) {
            toast.textContent = message;
            toast.style.display = 'block';
            setTimeout(() => {
                toast.style.display = 'none';
            }, 3000);
        }

        const PAPER_SIZES = {
            a4: {
                w: 794,
                h: 1123
            },
            a3: {
                w: 1123,
                h: 1587
            },
            a5: {
                w: 559,
                h: 794
            },
            letter: {
                w: 816,
                h: 1056
            },
            legal: {
                w: 816,
                h: 1344
            },
        };

        const MARGIN_PRESETS = {
            normal: {
                v: 96,
                h: 96
            },
            narrow: {
                v: 48,
                h: 48
            },
            wide: {
                v: 96,
                h: 192
            },
            none: {
                v: 24,
                h: 24
            },
        };

        const DOC_KEY = 'page_layout_doc_' + editorEl.dataset.docId;

        let pageOrientation = 'portrait';
        let pageZoom = 100;

        function applyPageLayout() {
            const ed = document.querySelector('.ck.ck-editor__editable_inline');
            if (!ed) return;

            const paper = document.getElementById('lt-paper')?.value || 'a4';
            const margin = document.getElementById('lt-margin')?.value || 'normal';
            const spacing = document.getElementById('lt-spacing')?.value || '1.5';

            const font = document.getElementById('lt-font')?.value || "'Times New Roman', serif";

            const size = PAPER_SIZES[paper] || PAPER_SIZES.a4;
            const marg = MARGIN_PRESETS[margin] || MARGIN_PRESETS.normal;

            let w = size.w,
                h = size.h;
            if (pageOrientation === 'landscape') {
                [w, h] = [h, w];
            }

            ed.style.setProperty('width', w + 'px', 'important');
            ed.style.setProperty('min-height', h + 'px', 'important');
            ed.style.setProperty('padding', `${marg.v}px ${marg.h}px`, 'important');
            ed.style.setProperty('line-height', spacing, 'important');
            ed.style.setProperty('font-family', font, 'important');

            localStorage.setItem(DOC_KEY, JSON.stringify({
                paper,
                margin,
                spacing,
                orientation: pageOrientation,
                zoom: pageZoom,
                font
            }));

            setTimeout(() => {
                const overlay = document.getElementById('cursor-overlay');
                const edEl = document.querySelector('.ck-editor__editable');
                if (overlay && edEl) {
                    overlay.style.top = edEl.offsetTop + 'px';
                    overlay.style.left = edEl.offsetLeft + 'px';
                    overlay.style.width = edEl.offsetWidth + 'px';
                }
            }, 100);
        }

        function setOrientation(dir) {
            pageOrientation = dir;

            document.getElementById('lt-portrait')?.classList.toggle('active', dir === 'portrait');
            document.getElementById('lt-landscape')?.classList.toggle('active', dir === 'landscape');

            applyPageLayout();
        }

        function adjustZoom(delta) {
            pageZoom = Math.min(200, Math.max(50, pageZoom + delta));
            document.getElementById('lt-zoom-val').textContent = pageZoom + '%';

            const wrapper = document.getElementById('editor-zoom-wrapper');
            if (wrapper) {
                wrapper.style.transformOrigin = 'top center';
                wrapper.style.transform = `scale(${pageZoom / 100})`;
                const ckMain = document.querySelector('.ck-editor__main');
                if (ckMain) {
                    wrapper.style.height = (ckMain.scrollHeight * pageZoom / 100) + 'px';
                }
            }

            try {
                const s = JSON.parse(localStorage.getItem(DOC_KEY) || '{}');
                s.zoom = pageZoom;
                localStorage.setItem(DOC_KEY, JSON.stringify(s));
            } catch (e) {}
        }

        (function loadSavedLayout() {
            try {
                const saved = JSON.parse(localStorage.getItem(DOC_KEY) || '{}');
                if (saved.paper) {
                    const el = document.getElementById('lt-paper');
                    if (el) el.value = saved.paper;
                }
                if (saved.margin) {
                    const el = document.getElementById('lt-margin');
                    if (el) el.value = saved.margin;
                }
                if (saved.spacing) {
                    const el = document.getElementById('lt-spacing');
                    if (el) el.value = saved.spacing;
                }
                if (saved.font) {
                    const el = document.getElementById('lt-font');
                    if (el) el.value = saved.font;
                }
                if (saved.orientation) {
                    pageOrientation = saved.orientation;
                    setOrientation(saved.orientation);
                }
                if (saved.zoom) {
                    pageZoom = saved.zoom;
                    adjustZoom(0);
                }
            } catch (e) {}
        })();

        function openShareModal() {
            document.getElementById('share-modal').classList.add('open');
            document.getElementById('share-email').focus();
        }

        function closeShareModal() {
            document.getElementById('share-modal').classList.remove('open');
            document.getElementById('share-email').value = '';
            document.getElementById('share-error').style.display = 'none';
            document.getElementById('share-success').style.display = 'none';
        }

        document.getElementById('share-modal')?.addEventListener('click', function(e) {
            if (e.target === this) closeShareModal();
        });

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeShareModal();
        });

        async function addShare() {
            const email = document.getElementById('share-email').value.trim();
            const perm = document.getElementById('share-perm').value;
            const errEl = document.getElementById('share-error');
            const okEl = document.getElementById('share-success');

            errEl.style.display = 'none';
            okEl.style.display = 'none';

            if (!email) {
                errEl.textContent = 'Masukkan email terlebih dahulu.';
                errEl.style.display = 'block';
                return;
            }

            try {
                const res = await fetch(shareUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        email,
                        permission: perm
                    }),
                });
                const data = await res.json();

                if (!res.ok) {
                    errEl.textContent = data.error || 'Terjadi kesalahan.';
                    errEl.style.display = 'block';
                    return;
                }

                okEl.textContent = data.message;
                okEl.style.display = 'block';
                document.getElementById('share-email').value = '';

                const noMsg = document.getElementById('no-shares-msg');
                if (noMsg) noMsg.remove();

                const s = data.share;
                const colors = ['#ea4335', '#4285f4', '#fbbc05', '#34a853', '#9c27b0', '#ff5722'];
                const color = colors[s.user_id % colors.length];
                const initial = s.name.charAt(0).toUpperCase();

                document.getElementById('share-item-' + s.user_id)?.remove();

                const item = document.createElement('div');
                item.className = 'share-item';
                item.id = 'share-item-' + s.user_id;
                item.innerHTML = `
            <div class="share-avatar" style="background:${color}">${initial}</div>
            <div class="share-user-info">
                <div class="sname">${s.name}</div>
                <div class="semail">${s.email}</div>
            </div>
            <span class="share-perm ${s.permission === 'edit' ? 'perm-edit' : 'perm-view'}">
                ${s.permission === 'edit' ? 'Bisa Edit' : 'Hanya Lihat'}
            </span>
            <button class="btn-remove-share"
                    onclick="removeShare(${s.user_id}, this)"
                    title="Hapus akses">&times;</button>
        `;
                document.getElementById('share-list').appendChild(item);

            } catch (e) {
                errEl.textContent = 'Gagal menghubungi server.';
                errEl.style.display = 'block';
            }
        }

        async function removeShare(userId, btnEl) {
            if (!confirm('Hapus akses pengguna ini?')) return;

            try {
                const res = await fetch(removeShareBaseUrl + '/' + userId, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
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
