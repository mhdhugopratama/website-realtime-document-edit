<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ProfileController;

Route::middleware('guest')->group(function () {
    Route::get('/login',    [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',   [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register',[AuthController::class, 'register']);
});
Route::post('/logout',  [AuthController::class, 'logout'])->name('logout');

Route::get('/add-col', function() {
    if (!\Illuminate\Support\Facades\Schema::hasColumn('documents', 'last_editor_id')) {
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE documents ADD COLUMN last_editor_id BIGINT UNSIGNED NULL AFTER owner_id');
        return "Added";
    }
    return "Exists";
});

Route::middleware('auth')->group(function () {

    Route::get('/',           [DocumentController::class, 'index'])->name('dashboard');
    Route::post('/documents', [DocumentController::class, 'store'])->name('document.store');

    Route::get('/profile',           [ProfileController::class, 'show'])->name('profile');
    Route::post('/profile/name',     [ProfileController::class, 'updateName'])->name('profile.updateName');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.updatePassword');

    Route::get('/documents/{document}/edit', [DocumentController::class, 'edit'])->name('document.edit');
    Route::delete('/documents/{document}',   [DocumentController::class, 'destroy'])->name('document.destroy');

    Route::get('/documents/{document}/export/pdf',  [DocumentController::class, 'exportPdf'])->name('document.exportPdf');
    Route::get('/documents/{document}/export/txt',  [DocumentController::class, 'exportTxt'])->name('document.exportTxt');

    Route::post('/documents/{document}/shares',          [DocumentController::class, 'share'])->name('document.share');
    Route::delete('/documents/{document}/shares/{user}', [DocumentController::class, 'removeShare'])->name('document.removeShare');

    Route::post('/documents/{document}/version',           [DocumentController::class, 'saveVersion'])->name('document.saveVersion');
    Route::post('/documents/{document}/restore/{version}', [DocumentController::class, 'restoreVersion'])->name('document.restoreVersion');

    Route::post('/api/documents/{document}/update',    [DocumentController::class, 'update'])->name('document.update');
    Route::post('/api/documents/{document}/poll',       [DocumentController::class, 'poll'])->name('document.poll');
});
