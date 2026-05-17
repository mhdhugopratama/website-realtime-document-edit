<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_online_users', function (Blueprint $table) {
            // Posisi kursor dalam pixel, relatif ke area editor yang bisa di-scroll
            $table->float('cursor_top')->nullable()->after('last_seen_at');
            $table->float('cursor_left')->nullable()->after('cursor_top');
        });
    }

    public function down(): void
    {
        Schema::table('document_online_users', function (Blueprint $table) {
            $table->dropColumn(['cursor_top', 'cursor_left']);
        });
    }
};
