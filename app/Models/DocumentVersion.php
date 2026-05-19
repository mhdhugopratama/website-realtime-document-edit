<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentVersion extends Model
{
    protected $fillable = ['document_id', 'saved_by', 'content', 'title'];

    public function savedBy()
    {
        return $this->belongsTo(User::class, 'saved_by');
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
