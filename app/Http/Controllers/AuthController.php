<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required'],
        ]);

        // Attempt to find user by contact (phone number column in your database)
        $user = User::where('contact', $credentials['phone'])->first();

        if (!$user) {
            return back()->withErrors([
                'phone' => 'The provided phone number does not match our records.',
            ])->onlyInput('phone');
        }

        // Check if user is active (assuming isActive column exists, otherwise remove this check)
        if (isset($user->isActive) && $user->isActive == 0) {
            return back()->withErrors([
                'phone' => 'Your account is inactive. Please contact your administrator.',
            ])->onlyInput('phone');
        }

        // Check if user has login access (Guards with role_id 3 cannot login)
        if ($user->role_id == 3) {
            return back()->withErrors([
                'phone' => 'Guards do not have login access to this system.',
            ])->onlyInput('phone');
        }

        // Attempt authentication using contact column
        if (Auth::attempt(['contact' => $credentials['phone'], 'password' => $credentials['password']])) {
            $request->session()->regenerate();

            // Store user in session
            $request->session()->put('user', Auth::user());
            
            // Store company_id in session if it exists
            if (Auth::user()->company_id) {
                $request->session()->put('company_id', Auth::user()->company_id);
            }

            // Redirect based on role
            return $this->redirectBasedOnRole(Auth::user());
        }

        return back()->withErrors([
            'password' => 'The provided password is incorrect.',
        ])->onlyInput('phone');
    }

    /**
     * Redirect user based on their role
     */
    protected function redirectBasedOnRole($user)
    {
        // SuperAdmin (role_id = 1) - Can see everything
        if ($user->isSuperAdmin()) {
            return redirect()->intended('/analytics/executive');
        }

        // Admin (role_id = 7) - Can see clients, supervisors and guards
        if ($user->isAdmin()) {
            return redirect()->intended('/analytics/executive');
        }

        // Supervisor (role_id = 2) - Can see only guards under them
        if ($user->isSupervisor()) {
            return redirect()->intended('dashboard');
        }

        // Default redirect
        return redirect()->intended('dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        
        $request->session()->invalidate();
        
        $request->session()->regenerateToken();
        
        return redirect()->route('login');
    }

    /* Forgot Password Module */

    public function forgotPassword()
    {
        return view('auth.forgotpassword');
    }

    public function getOTP(Request $request)
    {
        $mobile = $request->mobile;
        $user = User::where('contact', $mobile)->first();

        if ($user) {
            $otp = rand(100000, 999999);
            
            // Temporary storage of OTP as hashed password (as in reference project)
            $user->password = Hash::make($otp);
            $user->save();

            // Send SMS
            $this->sendOTPSMS($mobile, $otp);

            return response()->json(['status' => 'success']);
        }

        return response()->json(['status' => 'error', 'message' => 'User not found']);
    }

    public function verifyOTP(Request $request)
    {
        $mobile = $request->mobile;
        $otp = $request->otp;
        $user = User::where('contact', $mobile)->first();

        if ($user && Hash::check($otp, $user->password)) {
            return response()->json(['status' => 'success']);
        }

        return response()->json(['status' => 'error', 'message' => 'Invalid OTP']);
    }

    public function forgotPasswordAction($phone)
    {
        return view('auth.forgotpasswordaction', ['phone' => $phone]);
    }

    public function forgotPasswordSave(Request $request)
    {
        $request->validate([
            'phone' => 'required',
            'password' => 'required|min:5|confirmed',
        ]);

        $user = User::where('contact', $request->phone)->first();
        if ($user) {
            $user->password = Hash::make($request->password);
            $user->save();
            return redirect()->route('login')->with('success', 'Password reset successfully.');
        }

        return back()->withErrors(['error' => 'User not found.']);
    }

    protected function sendOTPSMS($mobile, $otp)
    {
        $authkey = "335514AgMLkQgS5f0c14a5P1";
        $dlt_te_id = "1207162513952739379";
        $msg = urlencode("Dear User,\nYour OTP for password reset is $otp. Please do not share this OTP.\nRegards,\nTeam Guard Konnect");
        
        $url = "https://control.msg91.com/api/sendhttp.php?authkey=$authkey&mobiles=$mobile&message=$msg&sender=GDKONN&route=4&country=91&DLT_TE_ID=$dlt_te_id";

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        curl_exec($curl);
        curl_close($curl);
    }
}
