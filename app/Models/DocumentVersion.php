<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentVersion extends Model
{
    protected $fillable = ['document_id', 'saved_by', 'content', 'title'];

    // Relasi: versi ini disimpan oleh siapa
    public function savedBy()
    {
        return $this->belongsTo(User::class, 'saved_by');
    }

    // Relasi: versi ini milik dokumen mana
    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
