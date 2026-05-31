const WARNA_PENGGUNA = ['#ea4335', '#4285f4', '#fbbc05', '#34a853', '#9c27b0', '#ff5722', '#00bcd4', '#e91e63'];

function dapatkanWarnaUser(idUser) {
    return WARNA_PENGGUNA[idUser % WARNA_PENGGUNA.length];
}

const editorEl     = document.getElementById('editor');
const inputJudul   = document.getElementById('doc-title');
const statusSimpan = document.getElementById('save-status');
const daftarOnline = document.getElementById('online-list');
const toast        = document.getElementById('toast');

const urlPerbarui     = editorEl.dataset.updateUrl;
const urlPoll         = editorEl.dataset.pollUrl;
const urlVersi        = editorEl.dataset.versionUrl;
const urlBagikan      = editorEl.dataset.shareUrl;
const urlHapusBagikan = editorEl.dataset.removeShareUrl;
const tokenCsrf       = editorEl.dataset.csrf;
const idUserSekarang  = parseInt(editorEl.dataset.currentUser);
const bisaEdit        = editorEl.dataset.canEdit === '1';

let sedangMengetik     = false;
let timerMengetik      = null;
let kontenTerakhir     = editorEl.value;
let judulTerakhir      = inputJudul.value;
let modeKonflik        = false;
let kontenKonflikMasuk = '';
let judulKonflikMasuk  = '';
let waktuSimpanTerakhir = 0;
let userTerkini        = null;
let kursorTopSaya      = 0;
let kursorLeftSaya     = 0;

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
    const konten = editorEl.value;
    const judul  = inputJudul.value;
    if (konten === kontenTerakhir && judul === judulTerakhir) return;
    statusSimpan.textContent = 'Menyimpan...';
    statusSimpan.className = 'save-status saving';
    try {
        const res  = await fetch(urlPerbarui, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': tokenCsrf }, body: JSON.stringify({ konten, judul }) });
        const data = await res.json();
        if (data.success) {
            kontenTerakhir = konten;
            judulTerakhir  = judul;
            if (data.updated_at_timestamp) timestampTerakhir = data.updated_at_timestamp;
            statusSimpan.textContent = '✓ Tersimpan ' + data.updated_at;
            statusSimpan.className   = 'save-status saved';
        }
    } catch (e) {
        statusSimpan.textContent = 'Gagal menyimpan';
        statusSimpan.className   = 'save-status';
    }
}

function tampilkanModalKonflik(namaPengedit, konten, judul) {
    modeKonflik = true;
    kontenKonflikMasuk = konten;
    judulKonflikMasuk  = judul;
    document.getElementById('conflict-user-name').textContent = namaPengedit;
    document.getElementById('conflict-modal').classList.add('open');
}

function selesaikanKonflik(aksi) {
    document.getElementById('conflict-modal').classList.remove('open');
    modeKonflik = false;
    if (aksi === 'reload') {
        editorEl.value     = kontenKonflikMasuk;
        inputJudul.value   = judulKonflikMasuk;
        kontenTerakhir     = kontenKonflikMasuk;
        judulTerakhir      = judulKonflikMasuk;
        statusSimpan.textContent = 'Diperbarui ke versi terbaru';
        statusSimpan.className   = 'save-status saved';
    } else {
        simpanOtomatis();
    }
}

function dapatkanKoordinatKursor(element) {
    const div   = document.createElement('div');
    const style = window.getComputedStyle(element);
    document.body.appendChild(div);
    div.style.cssText = `position:absolute;top:0;left:-9999px;white-space:pre-wrap;word-wrap:break-word;width:${element.offsetWidth}px`;
    ['fontFamily','fontSize','fontWeight','lineHeight','paddingTop','paddingRight','paddingBottom','paddingLeft','borderTopWidth','borderLeftWidth','boxSizing'].forEach(p => div.style[p] = style[p]);
    div.textContent = element.value.substring(0, element.selectionEnd);
    const span = document.createElement('span');
    span.textContent = element.value.substring(element.selectionEnd) || '.';
    div.appendChild(span);
    const koordinat = { atas: span.offsetTop, kiri: span.offsetLeft };
    document.body.removeChild(div);
    return koordinat;
}

async function cekPembaruanServer() {
    if (modeKonflik) return;
    if (document.activeElement === editorEl) {
        const pos  = dapatkanKoordinatKursor(editorEl);
        kursorTopSaya  = pos.atas;
        kursorLeftSaya = pos.kiri;
    }
    try {
        const res  = await fetch(urlPoll, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': tokenCsrf }, body: JSON.stringify({ posisi_kursor_atas: kursorTopSaya, posisi_kursor_kiri: kursorLeftSaya }) });
        const data = await res.json();
        perbaruiDaftarOnline(data.pengguna_online, data.id_user_saya);
        tampilkanKursorRemote(data.pengguna_online, data.id_user_saya);
        if (data.updated_at_timestamp && data.updated_at_timestamp > timestampTerakhir) {
            if (data.pengedit_terakhir && data.pengedit_terakhir.id !== idUserSekarang) {
                if (editorEl.value !== data.konten && editorEl.value !== kontenTerakhir) {
                    tampilkanModalKonflik(data.pengedit_terakhir.nama, data.konten, data.judul);
                    timestampTerakhir = data.updated_at_timestamp;
                    return;
                }
            }
            timestampTerakhir = data.updated_at_timestamp;
        }
        if (!sedangMengetik && data.konten !== kontenTerakhir) {
            const posisiAwal     = editorEl.selectionStart;
            const posisiAkhir    = editorEl.selectionEnd;
            const sedangFokus    = document.activeElement === editorEl;
            const selisihPanjang = data.konten.length - kontenTerakhir.length;
            editorEl.value  = data.konten;
            kontenTerakhir  = data.konten;
            if (data.judul !== inputJudul.value) { inputJudul.value = data.judul; judulTerakhir = data.judul; }
            if (sedangFokus) editorEl.setSelectionRange(Math.max(0, posisiAwal + selisihPanjang), Math.max(0, posisiAkhir + selisihPanjang));
            statusSimpan.textContent = '↺ Diperbarui ' + data.updated_at;
            statusSimpan.className   = 'save-status saved';
        }
    } catch (e) {}
}

function perbaruiDaftarOnline(daftarPengguna, idSaya) {
    if (!daftarPengguna || daftarPengguna.length === 0) {
        daftarOnline.innerHTML = '<div class="empty-sidebar">Tidak ada yang online</div>';
        return;
    }
    daftarOnline.innerHTML = daftarPengguna.map(p => {
        const warna = dapatkanWarnaUser(p.id);
        return `<div class="online-user"><div class="online-dot" style="background:${warna}"></div><span>${p.name}${p.id === idSaya ? ' <em style="color:#888;font-size:11px">(kamu)</em>' : ''}</span></div>`;
    }).join('');
}

function tampilkanKursorRemote(daftarPengguna, idSaya) {
    const overlay = document.getElementById('cursor-overlay');
    if (!overlay) return;
    const idAktif = new Set();
    (daftarPengguna || []).forEach(p => {
        if (p.id === idSaya || !p.cursor_top || !p.cursor_left) return;
        idAktif.add(p.id);
        let elKursor = document.getElementById('kursor-pengguna-' + p.id);
        if (!elKursor) {
            const warna = dapatkanWarnaUser(p.id);
            elKursor = document.createElement('div');
            elKursor.id = 'kursor-pengguna-' + p.id;
            elKursor.className = 'remote-cursor';
            elKursor.innerHTML = `<div class="remote-cursor-label" style="background:${warna}">${p.name}</div><div class="remote-cursor-caret" style="background:${warna}"></div>`;
            overlay.appendChild(elKursor);
        }
        elKursor.style.top  = (p.cursor_top  - editorEl.scrollTop)  + 'px';
        elKursor.style.left = (p.cursor_left - editorEl.scrollLeft) + 'px';
    });
    userTerkini = daftarPengguna;
    Array.from(overlay.children).forEach(anak => {
        if (anak.id?.startsWith('kursor-pengguna-') && !idAktif.has(parseInt(anak.id.replace('kursor-pengguna-', '')))) overlay.removeChild(anak);
    });
}

editorEl.addEventListener('scroll', () => { if (userTerkini) tampilkanKursorRemote(userTerkini, idUserSekarang); });

setInterval(cekPembaruanServer, 1000);
cekPembaruanServer();

async function simpanVersi() {
    await simpanOtomatis();
    try {
        const res  = await fetch(urlVersi, { method: 'POST', headers: { 'X-CSRF-TOKEN': tokenCsrf } });
        const data = await res.json();
        if (data.success) { tampilkanToast('✓ Versi tersimpan!'); setTimeout(() => location.reload(), 1500); }
    } catch (e) { tampilkanToast('Gagal menyimpan versi', 'error'); }
}

function tampilkanToast(pesan, tipe = 'success') {
    toast.textContent = pesan;
    toast.style.background = tipe === 'success' ? '#34a853' : '#ea4335';
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
}

function bukaModalShare()  { document.getElementById('share-modal').classList.add('open');    document.getElementById('share-email').focus(); }
function tutupModalShare() { document.getElementById('share-modal').classList.remove('open'); }
document.getElementById('share-modal')?.addEventListener('click', function(e) { if (e.target === this) tutupModalShare(); });
document.addEventListener('keydown', e => { if (e.key === 'Escape') tutupModalShare(); });

async function tambahAkses() {
    const email  = document.getElementById('share-email').value;
    const izin   = document.getElementById('share-perm').value;
    const errEl  = document.getElementById('share-error');
    const sucEl  = document.getElementById('share-success');
    const btn    = document.querySelector('.btn-add-share');
    errEl.style.display = sucEl.style.display = 'none';
    if (!email) return;
    btn.disabled = true; btn.textContent = 'Memproses...';
    try {
        const res  = await fetch(urlBagikan, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': tokenCsrf }, body: JSON.stringify({ email, permission: izin }) });
        const data = await res.json();
        btn.disabled = false; btn.textContent = 'Bagikan';
        if (!res.ok) { errEl.textContent = data.error || 'Terjadi kesalahan.'; errEl.style.display = 'block'; return; }
        sucEl.textContent = data.pesan; sucEl.style.display = 'block';
        document.getElementById('share-email').value = '';
        document.getElementById('no-shares-msg')?.remove();
        const kelasBadge = data.permission === 'edit' ? 'perm-edit' : 'perm-view';
        const teksIzin   = data.permission === 'edit' ? 'Bisa Edit' : 'Hanya Lihat';
        const existing   = document.getElementById('share-item-' + data.user.id);
        if (existing) {
            existing.querySelector('.share-perm').textContent = teksIzin;
            existing.querySelector('.share-perm').className   = 'share-perm ' + kelasBadge;
        } else {
            document.getElementById('share-list').insertAdjacentHTML('beforeend', `<div class="share-item" id="share-item-${data.user.id}"><div class="share-avatar" style="background:${data.user.color}">${data.user.initial}</div><div class="share-user-info"><div class="sname">${data.user.name}</div><div class="semail">${data.user.email}</div></div><span class="share-perm ${kelasBadge}">${teksIzin}</span><button class="btn-remove-share" onclick="hapusAkses(${data.user.id},this)" title="Hapus akses">&times;</button></div>`);
        }
    } catch (e) { btn.disabled = false; btn.textContent = 'Bagikan'; errEl.textContent = 'Gagal menghubungi server.'; errEl.style.display = 'block'; }
}

async function hapusAkses(userId) {
    if (!confirm('Hapus akses untuk pengguna ini?')) return;
    try {
        const res = await fetch(urlHapusBagikan + '/' + userId, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': tokenCsrf } });
        if (res.ok) {
            document.getElementById('share-item-' + userId)?.remove();
            const list = document.getElementById('share-list');
            if (list && list.children.length === 0) list.innerHTML = '<div id="no-shares-msg" style="font-size:13px;color:#bbb;text-align:center;padding:16px 0">Belum ada yang diberi akses.</div>';
        }
    } catch (e) { tampilkanToast('Gagal menghapus akses'); }
}
