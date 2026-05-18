<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = ['title', 'content', 'owner_id'];

    // Relasi: dokumen punya banyak versi/riwayat
    public function versions()
    {
        return $this->hasMany(DocumentVersion::class)->latest();
    }

    // Relasi: pemilik dokumen
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    // Relasi: user yang sedang online di dokumen ini
    public function onlineUsers()
    {
        return $this->hasMany(DocumentOnlineUser::class);
    }

    // Relasi: daftar user yang mendapat akses (sharing)
    public function shares()
    {
        return $this->hasMany(DocumentShare::class);
    }
}

