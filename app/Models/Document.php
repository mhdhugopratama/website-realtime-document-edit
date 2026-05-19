<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = ['title', 'content', 'owner_id'];

    public function versions()
    {
        return $this->hasMany(DocumentVersion::class)->latest();
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function onlineUsers()
    {
        return $this->hasMany(DocumentOnlineUser::class);
    }

    public function shares()
    {
        return $this->hasMany(DocumentShare::class);
    }
}

