<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\DocumentOnlineUser;
use App\Models\DocumentShare;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Html;

class DocumentController extends Controller
{
    private function cekAkses(Document $dokumen)
    {
        $idUser = Auth::id();

        if ($dokumen->owner_id === $idUser) {
            return 'owner';
        }

        $akses = $dokumen->shares()->where('user_id', $idUser)->first();

        if ($akses) {
            return $akses->permission;
        }

        return null;
    }

    public function index()
    {
        $idUser = Auth::id();

        $dokumenPribadi = Document::with('owner')
            ->where('owner_id', $idUser)
            ->latest()
            ->get();

        $dokumenDibagikan = Document::with(['owner', 'shares'])
            ->whereHas('shares', function($query) use ($idUser) {
                $query->where('user_id', $idUser);
            })
            ->latest()
            ->get()
            ->map(function ($dokumen) use ($idUser) {
                $dokumen->hak_akses = $dokumen->shares
                    ->where('user_id', $idUser)
                    ->first()->permission ?? null;
                return $dokumen;
            });

        return view('dashboard', compact('dokumenPribadi', 'dokumenDibagikan'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $dokumenBaru = Document::create([
            'title' => $request->title,
            'content' => '',
            'owner_id' => Auth::id(),
        ]);

        return redirect()->route('document.edit', $dokumenBaru->id);
    }

    public function edit(Document $document)
    {
        $hakAkses = $this->cekAkses($document);

        if ($hakAkses === null) {
            abort(403, 'Kamu tidak memiliki akses ke dokumen ini. Minta pemilik untuk membagikannya.');
        }

        $bisaEdit = in_array($hakAkses, ['owner', 'edit']);
        $adalahPemilik = $hakAkses === 'owner';

        $riwayatVersi = $document->versions()->with('savedBy')->get();

        if ($adalahPemilik) {
            $daftarAkses = $document->shares()->with('user')->get();
        } else {
            $daftarAkses = collect();
        }

        return view('document.edit', compact('document', 'riwayatVersi', 'bisaEdit', 'adalahPemilik', 'daftarAkses'));
    }

    public function update(Request $request, Document $document)
    {
        $hakAkses = $this->cekAkses($document);

        if (!in_array($hakAkses, ['owner', 'edit'])) {
            return response()->json(['error' => 'Akses ditolak'], 403);
        }

        $request->validate([
            'content' => 'nullable|string',
            'title' => 'nullable|string|max:255',
        ]);

        $document->update([
            'content' => $request->content,
            'title' => $request->title ?? $document->title,
        ]);

        \Illuminate\Support\Facades\Cache::put('doc_last_editor_'.$document->id, Auth::id(), now()->addHours(2));

        return response()->json([
            'success' => true,
            'updated_at' => $document->updated_at->diffForHumans(),
            'updated_at_timestamp' => $document->updated_at->timestamp,
        ]);
    }

    public function saveVersion(Request $request, Document $document)
    {
        $hakAkses = $this->cekAkses($document);

        if (!in_array($hakAkses, ['owner', 'edit'])) {
            return response()->json(['error' => 'Akses ditolak'], 403);
        }

        $versiBaru = DocumentVersion::create([
            'document_id' => $document->id,
            'saved_by' => Auth::id(),
            'title' => $document->title,
            'content' => $document->content,
        ]);

        $versiBaru->load('savedBy');

        return response()->json([
            'success' => true,
            'version' => [
                'id' => $versiBaru->id,
                'title' => $versiBaru->title,
                'saved_by_name' => $versiBaru->savedBy->name,
                'created_at' => $versiBaru->created_at->format('d M Y, H:i')
            ]
        ]);
    }

    public function getVersion(DocumentVersion $version)
    {
        $hakAkses = $this->cekAkses($version->document);

        if ($hakAkses === null) {
            abort(403);
        }

        return response()->json([
            'title' => $version->title,
            'content' => $version->content
        ]);
    }

    public function restoreVersion(Document $document, DocumentVersion $version)
    {
        if ($document->owner_id !== Auth::id()) {
            abort(403);
        }

        $document->update([
            'content' => $version->content,
            'title' => $version->title,
        ]);

        return redirect()->route('document.edit', $document->id)
            ->with('success', 'Dokumen berhasil dikembalikan ke versi sebelumnya!');
    }

    public function destroy(Document $document)
    {
        $hakAkses = $this->cekAkses($document);
        if ($hakAkses !== 'owner') {
            abort(403);
        }

        $document->delete();
        return redirect()->route('dashboard');
    }

    public function exportPdf(Document $document)
    {
        if ($this->cekAkses($document) === null) {
            abort(403);
        }

        $pdf = Pdf::loadView('document.export-pdf', compact('document'))
            ->setPaper('a4', 'portrait')
            ->setOption([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
                'dpi' => 150,
            ]);

        $filename = preg_replace('/[^a-zA-Z0-9\-_ ]/', '', $document->title);
        $filename = trim($filename) ?: 'dokumen';

        return $pdf->download($filename . '.pdf');
    }

    public function exportTxt(Document $document)
    {
        if ($this->cekAkses($document) === null) {
            abort(403);
        }

        $filename = preg_replace('/[^a-zA-Z0-9\-_ ]/', '', $document->title);
        $filename = trim($filename) ?: 'dokumen';

        return response($document->content)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '.txt"');
    }

    public function addShare(Request $request, Document $document)
    {
        $hakAkses = $this->cekAkses($document);
        if ($hakAkses !== 'owner') {
            return response()->json(['success' => false, 'error' => 'Unauthorized']);
        }

        $request->validate([
            'email' => 'required|email',
            'permission' => 'required|in:view,edit'
        ]);

        $userDituju = User::where('email', $request->email)->first();
        if (!$userDituju) {
            return response()->json(['success' => false, 'error' => 'User tidak ditemukan']);
        }

        if ($userDituju->id === $document->owner_id) {
            return response()->json(['success' => false, 'error' => 'Tidak bisa membagikan ke pemilik dokumen']);
        }

        DocumentShare::updateOrCreate(
            ['document_id' => $document->id, 'user_id' => $userDituju->id],
            ['permission' => $request->permission]
        );

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $userDituju->id,
                'name' => $userDituju->name,
                'email' => $userDituju->email,
                'initial' => strtoupper(substr($userDituju->name, 0, 1)),
                'color' => ['#ea4335', '#4285f4', '#fbbc05', '#34a853', '#9c27b0', '#ff5722'][$userDituju->id % 6]
            ],
            'permission' => $request->permission
        ]);
    }

    public function removeShare(Document $document, User $user)
    {
        $hakAkses = $this->cekAkses($document);
        if ($hakAkses !== 'owner') {
            return response()->json(['success' => false, 'error' => 'Unauthorized']);
        }

        $document->shares()->where('user_id', $user->id)->delete();

        return response()->json(['success' => true]);
    }

    public function getShares(Document $document)
    {
        if ($document->owner_id !== Auth::id()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $shares = $document->shares()->with('user')->get()->map(function($s) {
            return [
                'user_id' => $s->user->id,
                'name' => $s->user->name,
                'email' => $s->user->email,
                'permission' => $s->permission,
            ];
        });

        return response()->json(['shares' => $shares]);
    }

    public function heartbeat(Request $request, Document $document)
    {
        $idUser = Auth::id();
        DocumentOnlineUser::updateOrCreate(
            ['document_id' => $document->id, 'user_id' => $idUser],
            [
                'last_seen_at' => now(),
                'cursor_top' => $request->cursor_top ?? 0,
                'cursor_left' => $request->cursor_left ?? 0,
            ]
        );

        return response()->json(['success' => true]);
    }

    public function poll(Request $request, Document $document)
    {
        $idUser = Auth::id();

        // 1. Lakukan update status online & kursor (Heartbeat terintegrasi)
        DocumentOnlineUser::updateOrCreate(
            ['document_id' => $document->id, 'user_id' => $idUser],
            [
                'last_seen_at' => now(),
                'cursor_top' => $request->cursor_top ?? 0,
                'cursor_left' => $request->cursor_left ?? 0,
            ]
        );

        // 2. Ambil daftar pengguna online (aktif dalam 6 detik terakhir)
        $penggunaOnline = DocumentOnlineUser::where('document_id', $document->id)
            ->where('last_seen_at', '>=', now()->subSeconds(6))
            ->with('user')
            ->get()
            ->map(function($online) {
                return [
                    'id' => $online->user->id,
                    'name' => $online->user->name,
                    'cursor_top' => $online->cursor_top,
                    'cursor_left' => $online->cursor_left,
                ];
            });

        // 3. Ambil data dokumen terbaru dengan efisien
        $dokumenTerbaru = $document->fresh();
        $pengeditTerakhirId = \Illuminate\Support\Facades\Cache::get('doc_last_editor_'.$document->id);
        $pengeditTerakhir = $pengeditTerakhirId ? User::find($pengeditTerakhirId) : null;

        return response()->json([
            'content' => $dokumenTerbaru->content,
            'title' => $dokumenTerbaru->title,
            'updated_at' => $dokumenTerbaru->updated_at->format('H:i:s'),
            'updated_at_timestamp' => $dokumenTerbaru->updated_at->timestamp,
            'online_users' => $penggunaOnline,
            'current_user_id' => $idUser,
            'last_editor' => $pengeditTerakhir ? ['id' => $pengeditTerakhir->id, 'name' => $pengeditTerakhir->name] : null
        ]);
    }
}
