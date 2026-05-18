<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentOnlineUser extends Model
{
    protected $fillable = ['document_id', 'user_id', 'last_seen_at', 'cursor_top', 'cursor_left'];

    public $timestamps = false; // kita pakai last_seen_at sendiri

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    // Relasi: user yang online
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi: dokumen yang dibuka
    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
