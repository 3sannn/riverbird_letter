<h1>Sign in</h1>
<p style="color:var(--muted);margin-top:0">RiverBird document management portal</p>
<form method="post" action="<?= url('/login') ?>" class="form-grid">
    <?= \App\Support\Csrf::field() ?>
    <div>
        <label for="identifier">Username or email</label>
        <input id="identifier" name="identifier" required value="<?= e(old('identifier')) ?>">
    </div>
    <div>
        <label for="password">Password</label>
        <input id="password" type="password" name="password" required autocomplete="current-password">
    </div>
    <button type="submit" class="btn btn-primary">Sign in</button>
</form>
