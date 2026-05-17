<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel ini untuk menyimpan user yang sedang online di dokumen tertentu
        Schema::create('document_online_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('last_seen_at'); // kapan terakhir kali user aktif
            $table->unique(['document_id', 'user_id']); // satu user hanya satu baris per dokumen
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_online_users');
    }
};
