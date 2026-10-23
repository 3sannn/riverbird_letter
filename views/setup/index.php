<h1>Initial setup</h1>
<p style="color:var(--muted);margin-top:0">Create the first administrator account. This page is available only while no users exist.</p>
<form method="post" action="<?= url('/setup') ?>" class="form-grid">
    <?= \App\Support\Csrf::field() ?>
    <div>
        <label>Full name</label>
        <input name="full_name" value="Administrator">
    </div>
    <div>
        <label>Username</label>
        <input name="username" required>
    </div>
    <div>
        <label>Email</label>
        <input type="email" name="email" required>
    </div>
    <div>
        <label>Password (min 10 characters)</label>
        <input type="password" name="password" required minlength="10" autocomplete="new-password">
    </div>
    <button type="submit" class="btn btn-primary">Create administrator</button>
</form>
