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
    private function getPermission(Document $document)
    {
        $userId = Auth::id();

        if ($document->owner_id === $userId) {
            return 'owner';
        }

        $share = $document->shares()->where('user_id', $userId)->first();

        if ($share) {
            return $share->permission;
        }

        return null;
    }

    public function index()
    {
        $userId = Auth::id();

        $myDocs = Document::with('owner')
            ->where('owner_id', $userId)
            ->latest()
            ->get();

        $sharedDocs = Document::with(['owner', 'shares'])
            ->whereHas('shares', function($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->latest()
            ->get()
            ->map(function ($doc) use ($userId) {
                $doc->my_permission = $doc->shares
                    ->where('user_id', $userId)
                    ->first()->permission ?? null;
                return $doc;
            });

        return view('dashboard', compact('myDocs', 'sharedDocs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $document = Document::create([
            'title' => $request->title,
            'content' => '',
            'owner_id' => Auth::id(),
        ]);

        return redirect()->route('document.edit', $document->id);
    }

    public function edit(Document $document)
    {
        $permission = $this->getPermission($document);

        if ($permission === null) {
            abort(403, 'Kamu tidak memiliki akses ke dokumen ini. Minta pemilik untuk membagikannya.');
        }

        $canEdit = in_array($permission, ['owner', 'edit']);
        $isOwner = $permission === 'owner';

        $versions = $document->versions()->with('savedBy')->get();

        if ($isOwner) {
            $shares = $document->shares()->with('user')->get();
        } else {
            $shares = collect();
        }

        return view('document.edit', compact('document', 'versions', 'canEdit', 'isOwner', 'shares'));
    }

    public function update(Request $request, Document $document)
    {
        $permission = $this->getPermission($document);

        if (!in_array($permission, ['owner', 'edit'])) {
            return response()->json(['error' => 'Tidak punya izin edit'], 403);
        }

        $request->validate([
            'content' => 'nullable|string',
            'title' => 'nullable|string|max:255',
        ]);

        $document->update([
            'content' => $request->content,
            'title' => $request->title ?? $document->title,
        ]);

        \Illuminate\Support\Facades\Cache::put('doc_last_editor_'.$document->id, [
            'id' => Auth::id(),
            'name' => Auth::user()->name,
        ], now()->addHours(2));

        return response()->json([
            'success' => true,
            'updated_at' => $document->updated_at->diffForHumans(),
            'updated_at_timestamp' => $document->updated_at->timestamp,
        ]);
    }

    public function saveVersion(Document $document)
    {
        $permission = $this->getPermission($document);

        if (!in_array($permission, ['owner', 'edit'])) {
            return response()->json(['error' => 'Tidak punya izin'], 403);
        }

        DocumentVersion::create([
            'document_id' => $document->id,
            'saved_by' => Auth::id(),
            'content' => $document->content,
            'title' => $document->title,
        ]);

        return response()->json(['success' => true, 'message' => 'Versi berhasil disimpan!']);
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
        if ($document->owner_id !== Auth::id()) {
            abort(403);
        }

        $document->delete();

        return redirect()->route('dashboard')->with('success', 'Dokumen berhasil dihapus!');
    }

    public function exportPdf(Document $document)
    {
        if ($this->getPermission($document) === null) {
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
        if ($this->getPermission($document) === null) {
            abort(403);
        }

        $filename = preg_replace('/[^a-zA-Z0-9\-_ ]/', '', $document->title);
        $filename = trim($filename) ?: 'dokumen';

        return response($document->content)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '.txt"');
    }

    public function share(Request $request, Document $document)
    {
        if ($document->owner_id !== Auth::id()) {
            return response()->json(['error' => 'Hanya pemilik yang bisa berbagi'], 403);
        }

        $request->validate([
            'email' => 'required|email|exists:users,email',
            'permission' => 'required|in:view,edit',
        ]);

        $targetUser = User::where('email', $request->email)->first();

        if ($targetUser->id === Auth::id()) {
            return response()->json(['error' => 'Tidak bisa berbagi ke diri sendiri'], 422);
        }

        DocumentShare::updateOrCreate(
            ['document_id' => $document->id, 'user_id' => $targetUser->id],
            ['permission' => $request->permission]
        );

        return response()->json([
            'success' => true,
            'message' => "Berhasil dibagikan ke {$targetUser->name}",
            'share' => [
                'user_id' => $targetUser->id,
                'name' => $targetUser->name,
                'email' => $targetUser->email,
                'permission' => $request->permission,
            ],
        ]);
    }

    public function removeShare(Document $document, User $user)
    {
        if ($document->owner_id !== Auth::id()) {
            return response()->json(['error' => 'Hanya pemilik yang bisa mengubah akses'], 403);
        }

        DocumentShare::where('document_id', $document->id)
            ->where('user_id', $user->id)
            ->delete();

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
        if ($this->getPermission($document) === null) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        DocumentOnlineUser::updateOrCreate(
            ['document_id' => $document->id, 'user_id' => Auth::id()],
            [
                'last_seen_at' => now(),
                'cursor_top' => $request->cursor_top,
                'cursor_left' => $request->cursor_left,
            ]
        );

        return response()->json(['success' => true]);
    }

    public function poll(Document $document)
    {
        if ($this->getPermission($document) === null) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $onlineUsers = DocumentOnlineUser::where('document_id', $document->id)
            ->where('last_seen_at', '>=', now()->subSeconds(10))
            ->with('user')
            ->get()
            ->map(function($ou) {
                return [
                    'id' => $ou->user->id,
                    'name' => $ou->user->name,
                    'cursor_top' => $ou->cursor_top,
                    'cursor_left' => $ou->cursor_left,
                ];
            });

        $doc = $document->fresh();
        $lastEditor = \Illuminate\Support\Facades\Cache::get('doc_last_editor_'.$document->id);

        return response()->json([
            'content' => $doc->content,
            'title' => $doc->title,
            'updated_at' => $doc->updated_at->diffForHumans(),
            'updated_at_timestamp' => $doc->updated_at->timestamp,
            'last_editor' => $lastEditor,
            'online_users' => $onlineUsers,
            'current_user_id' => Auth::id(),
        ]);
    }
}
