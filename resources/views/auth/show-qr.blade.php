<h2>Scan this QR Code with Google Authenticator</h2>
<p>Or use this code: <strong>{{ auth()->user()->google2fa_secret }}</strong></p>

<!-- Render SVG inline -->
{!! $QR_Image !!}
<a href="/dashboard"><button>Back to Dashboard</button></a>
