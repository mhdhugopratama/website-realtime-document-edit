<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentShare extends Model
{
    protected $fillable = ['document_id', 'user_id', 'permission'];

    // Dokumen yang di-share
    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    // User yang mendapat akses
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
