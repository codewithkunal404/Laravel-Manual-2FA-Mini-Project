<?php

namespace App\Http\Controllers;

use App\Models\User;
use Auth;
use Hash;
use Illuminate\Http\Request;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Writer;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
class AuthController extends Controller
{

    protected $google2fa;
    // Register


    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }
    public function showRegisterForm()
    {
        return view('auth.register');
    }


        // Show 2FA input
    public function show2faForm()
    {
        if (!session('2fa:user:id')) {
            return redirect('/login');
        }
        return view('auth.2fa');
    }

      // Verify 2FA
    public function verify2fa(Request $request)
    {
        $request->validate(['one_time_password'=>'required']);

        $user = User::find(session('2fa:user:id'));

        $valid = $this->google2fa->verifyKey($user->google2fa_secret, $request->one_time_password);

        if ($valid) {
            Auth::login($user);
            session()->forget('2fa:user:id');
            return redirect('/dashboard');
        }

        return back()->withErrors(['one_time_password'=>'Invalid 2FA code']);
    }


    public function register(Request $request)
    {
        $request->validate([
            'name'=>'required|string',
            'email'=>'required|email|unique:users,email',
            'password'=>'required|confirmed|min:6',
        ]);

        $user = User::create([
            'name'=>$request->name,
            'email'=>$request->email,
            'password'=>Hash::make($request->password),
        ]);

        Auth::login($user);

        return redirect('/dashboard');
    }

      // Login
     public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'=>'required|email',
            'password'=>'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors(['email'=>'Invalid credentials']);
        }

        if ($user->google2fa_secret) {
            session(['2fa:user:id'=>$user->id]);
            return redirect('/2fa');
        }

        Auth::login($user);
        return redirect('/dashboard');
    }


       // Dashboard
    public function dashboard()
    {
        return view('dashboard');
    }

    // Logout
    public function logout()
    {
        Auth::logout();
        return redirect('/login');
    }

    // Enable 2FA
 

public function enable2fa(Request $request)
{
    $user = Auth::user();
    $google2fa = new Google2FA();

    // Generate 2FA secret
    $user->google2fa_secret = $google2fa->generateSecretKey();
    $user->save();

    // Generate otpauth URL for Google Authenticator
    $google2fa_url = "otpauth://totp/MyLaravelApp:{$user->email}?secret={$user->google2fa_secret}&issuer=MyLaravelApp";

    // Generate QR code SVG
    $renderer = new ImageRenderer(
        new RendererStyle(200),
        new SvgImageBackEnd()
    );

    $writer = new Writer($renderer);
    $QR_Image = $writer->writeString($google2fa_url);

    // Pass SVG QR code to view
    return view('auth.show-qr', compact('QR_Image'));
}
}
