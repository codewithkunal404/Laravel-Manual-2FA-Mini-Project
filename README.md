# 🚀 Laravel 9 Manual 2FA Project From Scratch

## Create Laravel Project

```
composer create-project laravel/laravel laravel-2fa-manual
cd laravel-2fa-manual

```

-   Configure Database
-   In .env:

```

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_2fa
DB_USERNAME=root
DB_PASSWORD=yourpassword

```

-   Run:

```
php artisan migrate
```

-   Install Google2FA

```
composer require bacon/bacon-qr-code
composer require pragmarx/google2fa

```

### Add 2FA Column to Users Table

```
php artisan make:migration add_google2fa_to_users_table --table=users
```

-   Migration file:

```php
public function up()
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('google2fa_secret')->nullable();
    });
}

```

-   Run migration:

```
php artisan migrate
```

### Update User Model

-   app/Models/User.php:

```php
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'google2fa_secret'
    ];

    protected $hidden = [
        'password', 'remember_token', 'google2fa_secret'
    ];
}
```

### Create Authentication Controller

```
php artisan make:controller AuthController
```

-   app/Http/Controllers/AuthController.php:

```php
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

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    // Register
    public function showRegisterForm()
    {
        return view('auth.register');
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

```

### Routes

-   routes/web.php:

```php
use App\Http\Controllers\AuthController;

Route::get('/register', [AuthController::class, 'showRegisterForm']);
Route::post('/register', [AuthController::class, 'register']);

Route::get('/login', [AuthController::class, 'showLoginForm']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/2fa', [AuthController::class, 'show2faForm']);
Route::post('/2fa', [AuthController::class, 'verify2fa']);

Route::get('/dashboard', [AuthController::class, 'dashboard'])->middleware('auth');
Route::get('/logout', [AuthController::class, 'logout']);

Route::post('/enable-2fa', [AuthController::class, 'enable2fa'])->middleware('auth');
```

### Views

```html
8.1 Register resources/views/auth/register.blade.php:
<form method="POST" action="/register">
    @csrf
    <input type="text" name="name" placeholder="Name" required /><br /><br />
    <input type="email" name="email" placeholder="Email" required /><br /><br />
    <input
        type="password"
        name="password"
        placeholder="Password"
        required
    /><br /><br />
    <input
        type="password"
        name="password_confirmation"
        placeholder="Confirm Password"
        required
    /><br /><br />
    <button type="submit">Register</button>
</form>
<a href="/login">Login</a>

8.2 Login resources/views/auth/login.blade.php:
<form method="POST" action="/login">
    @csrf
    <input type="email" name="email" placeholder="Email" required /><br /><br />
    <input
        type="password"
        name="password"
        placeholder="Password"
        required
    /><br /><br />
    <button type="submit">Login</button>
</form>
<a href="/register">Register</a>

8.3 2FA resources/views/auth/2fa.blade.php:
<h2>Enter 2FA Code</h2>
<form method="POST" action="/2fa">
    @csrf
    <input
        type="text"
        name="one_time_password"
        placeholder="Authenticator Code"
        required
    /><br /><br />
    <button type="submit">Verify</button>
</form>

8.4 Dashboard resources/views/dashboard.blade.php:
<h2>Welcome, {{ auth()->user()->name }}</h2>
<p>Email: {{ auth()->user()->email }}</p>

@if(!auth()->user()->google2fa_secret)
<form method="POST" action="/enable-2fa">
    @csrf
    <button type="submit">Enable 2FA</button>
</form>
@else
<p style="color:green;">2FA is already enabled ✅</p>
@endif

<a href="/logout">Logout</a>

8.5 Show QR Code resources/views/auth/show-qr.blade.php:
<h2>Scan this QR Code with Google Authenticator</h2>
<p>Or use this code: <strong>{{ auth()->user()->google2fa_secret }}</strong></p>
<img src="{{ $QR_Image }}" /><br /><br />
<a href="/dashboard"><button>Back to Dashboard</button></a>
```

### Test the Project

-   Run the server:

```
php artisan serve

```

-   Go to /register → create a user.

-   Login → go to Dashboard → click Enable 2FA.

-   Scan the QR code in Google Authenticator.

-   Logout → login → enter 6-digit 2FA code.

-   Success → redirected to Dashboard.
