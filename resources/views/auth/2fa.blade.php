<h2>Enter 2FA Code</h2>
<form method="POST" action="/2fa">
    @csrf
    <input type="text" name="one_time_password" placeholder="Authenticator Code" required><br><br>
    <button type="submit">Verify</button>
</form>
