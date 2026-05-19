<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentOnlineUser extends Model
{
    protected $fillable = ['document_id', 'user_id', 'last_seen_at', 'cursor_top', 'cursor_left'];

    public $timestamps = false;

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
