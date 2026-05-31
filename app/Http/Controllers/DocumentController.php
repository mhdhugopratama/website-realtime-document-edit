<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\DocumentOnlineUser;
use App\Models\DocumentShare;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Barryvdh\DomPDF\Facade\Pdf;

class DocumentController extends Controller
{
    private function cekAkses(Document $dokumen)
    {
        if ($dokumen->owner_id === Auth::id()) return 'owner';
        $akses = $dokumen->shares()->where('user_id', Auth::id())->first();
        return $akses ? $akses->permission : null;
    }

    public function index()
    {
        $idUser = Auth::id();
        $dokumenPribadi = Document::with('owner')->where('owner_id', $idUser)->latest()->get();
        $dokumenDibagikan = Document::with(['owner', 'shares'])->whereHas('shares', fn($q) => $q->where('user_id', $idUser))->latest()->get()->map(function($d) use ($idUser) {
            $d->hak_akses = $d->shares->where('user_id', $idUser)->first()->permission ?? null;
            return $d;
        });
        return view('dashboard', compact('dokumenPribadi', 'dokumenDibagikan'));
    }

    public function store(Request $request)
    {
        $request->validate(['title' => 'required|string|max:255']);
        $dokumenBaru = Document::create(['title' => $request->title, 'content' => '', 'owner_id' => Auth::id()]);
        return redirect()->route('document.edit', $dokumenBaru->id);
    }

    public function edit(Document $document)
    {
        if (($hakAkses = $this->cekAkses($document)) === null) abort(403, 'Kamu tidak memiliki akses ke dokumen ini.');
        $bisaEdit = in_array($hakAkses, ['owner', 'edit']);
        $adalahPemilik = $hakAkses === 'owner';
        $riwayatVersi = $document->versions()->with('savedBy')->get();
        $daftarAkses = $adalahPemilik ? $document->shares()->with('user')->get() : collect();
        return view('document.edit', compact('document', 'riwayatVersi', 'bisaEdit', 'adalahPemilik', 'daftarAkses'));
    }

    public function update(Request $request, Document $document)
    {
        if (!in_array($this->cekAkses($document), ['owner', 'edit'])) return response()->json(['error' => 'Akses ditolak'], 403);
        $request->validate(['konten' => 'nullable|string', 'judul' => 'nullable|string|max:255']);
        $document->update(['content' => $request->konten, 'title' => $request->judul ?? $document->title]);
        Cache::put('doc_last_editor_' . $document->id, Auth::id(), now()->addHours(2));
        return response()->json(['success' => true, 'updated_at' => $document->updated_at->diffForHumans(), 'updated_at_timestamp' => $document->updated_at->timestamp]);
    }

    public function saveVersion(Request $request, Document $document)
    {
        if (!in_array($this->cekAkses($document), ['owner', 'edit'])) return response()->json(['error' => 'Akses ditolak'], 403);
        $versi = DocumentVersion::create(['document_id' => $document->id, 'saved_by' => Auth::id(), 'title' => $document->title, 'content' => $document->content]);
        $versi->load('savedBy');
        return response()->json(['success' => true, 'version' => ['id' => $versi->id, 'title' => $versi->title, 'saved_by_name' => $versi->savedBy->name, 'created_at' => $versi->created_at->format('d M Y, H:i')]]);
    }

    public function getVersion(DocumentVersion $version)
    {
        if ($this->cekAkses($version->document) === null) abort(403);
        return response()->json(['title' => $version->title, 'content' => $version->content]);
    }

    public function restoreVersion(Document $document, DocumentVersion $version)
    {
        if ($document->owner_id !== Auth::id()) abort(403);
        $document->update(['content' => $version->content, 'title' => $version->title]);
        return redirect()->route('document.edit', $document->id)->with('success', 'Dokumen berhasil dikembalikan!');
    }

    public function destroy(Document $document)
    {
        if ($this->cekAkses($document) !== 'owner') abort(403);
        $document->delete();
        return redirect()->route('dashboard');
    }

    public function exportPdf(Document $document)
    {
        if ($this->cekAkses($document) === null) abort(403);
        $namaFile = trim(preg_replace('/[^a-zA-Z0-9\-_ ]/', '', $document->title)) ?: 'dokumen';
        return Pdf::loadView('document.export-pdf', compact('document'))->setPaper('a4')->setOption(['isHtml5ParserEnabled' => true, 'defaultFont' => 'DejaVu Sans'])->download($namaFile . '.pdf');
    }

    public function exportTxt(Document $document)
    {
        if ($this->cekAkses($document) === null) abort(403);
        $namaFile = trim(preg_replace('/[^a-zA-Z0-9\-_ ]/', '', $document->title)) ?: 'dokumen';
        return response($document->content)->header('Content-Type', 'text/plain')->header('Content-Disposition', 'attachment; filename="' . $namaFile . '.txt"');
    }

    public function share(Request $request, Document $document)
    {
        if ($this->cekAkses($document) !== 'owner') return response()->json(['success' => false, 'error' => 'Unauthorized']);
        $request->validate(['email' => 'required|email', 'permission' => 'required|in:view,edit']);
        $userDituju = User::where('email', $request->email)->first();
        if (!$userDituju) return response()->json(['success' => false, 'error' => 'User tidak ditemukan']);
        if ($userDituju->id === $document->owner_id) return response()->json(['success' => false, 'error' => 'Tidak bisa membagikan ke pemilik dokumen']);
        DocumentShare::updateOrCreate(['document_id' => $document->id, 'user_id' => $userDituju->id], ['permission' => $request->permission]);
        return response()->json(['success' => true, 'pesan' => 'Akses berhasil dibagikan!', 'user' => ['id' => $userDituju->id, 'name' => $userDituju->name, 'email' => $userDituju->email, 'initial' => strtoupper(substr($userDituju->name, 0, 1)), 'color' => ['#ea4335', '#4285f4', '#fbbc05', '#34a853', '#9c27b0', '#ff5722'][$userDituju->id % 6]], 'permission' => $request->permission]);
    }

    public function removeShare(Document $document, User $user)
    {
        if ($this->cekAkses($document) !== 'owner') return response()->json(['success' => false, 'error' => 'Unauthorized']);
        $document->shares()->where('user_id', $user->id)->delete();
        return response()->json(['success' => true]);
    }

    public function poll(Request $request, Document $document)
    {
        $idUser = Auth::id();
        DocumentOnlineUser::updateOrCreate(
            ['document_id' => $document->id, 'user_id' => $idUser],
            ['last_seen_at' => now(), 'cursor_top' => $request->posisi_kursor_atas ?? 0, 'cursor_left' => $request->posisi_kursor_kiri ?? 0]
        );
        $penggunaOnline = DocumentOnlineUser::where('document_id', $document->id)->where('last_seen_at', '>=', now()->subSeconds(6))->with('user')->get()->map(fn($o) => ['id' => $o->user->id, 'name' => $o->user->name, 'cursor_top' => $o->cursor_top, 'cursor_left' => $o->cursor_left]);
        $dokumen = $document->fresh();
        $pengeditId = Cache::get('doc_last_editor_' . $document->id);
        $pengedit = $pengeditId ? User::find($pengeditId) : null;
        return response()->json(['konten' => $dokumen->content, 'judul' => $dokumen->title, 'updated_at' => $dokumen->updated_at->format('H:i:s'), 'updated_at_timestamp' => $dokumen->updated_at->timestamp, 'pengguna_online' => $penggunaOnline, 'id_user_saya' => $idUser, 'pengedit_terakhir' => $pengedit ? ['id' => $pengedit->id, 'nama' => $pengedit->name] : null]);
    }
}
