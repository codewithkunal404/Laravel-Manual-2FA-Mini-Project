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
