<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        return view('profile', compact('user'));
    }

    public function updateName(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        Auth::user()->update(['name' => $request->name]);

        return back()->with('success_name', 'Nama berhasil diperbarui!');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ]);

        if (!Hash::check($request->current_password, Auth::user()->password)) {
            return back()
                ->withErrors(['current_password' => 'Password saat ini tidak cocok.'])
                ->withInput();
        }

        Auth::user()->update([
            'password' => Hash::make($request->new_password),
        ]);

        return back()->with('success_password', 'Password berhasil diubah!');
    }
}
