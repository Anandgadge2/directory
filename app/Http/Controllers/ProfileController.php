<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class ProfileController extends Controller
{
    /**
     * Show user profile
     */
    public function show()
    {
        $user = Auth::user() ?? session('user');
        if (!$user) {
            return redirect()->route('login');
        }
        
        return view('forest.profile', [
            'user' => $user,
            'hideGlobalFilters' => true
        ]);
    }

    /**
     * Update profile details
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        if (!$user) return redirect()->route('login');

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'contact' => 'required|string|max:15|unique:users,contact,' . $user->id,
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->contact = $request->contact;
        $user->save();

        // Sync with session if user is stored there
        if (session()->has('user')) {
            session()->put('user', $user);
        }

        return back()->with('success', 'Profile updated successfully.');
    }

    /**
     * Change user password
     */
    public function changePassword(Request $request)
    {
        $user = Auth::user();
        if (!$user) return redirect()->route('login');

        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:5|confirmed',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'The provided password does not match your current password.']);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return back()->with('success', 'Password changed successfully.');
    }
}
