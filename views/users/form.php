<h1>New user</h1>
<form method="post" action="<?= url('/users') ?>" class="card form-grid">
    <?= \App\Support\Csrf::field() ?>
    <div class="form-row">
        <div><label>Full name</label><input name="full_name" required></div>
        <div><label>Username</label><input name="username" required></div>
    </div>
    <div class="form-row">
        <div><label>Email</label><input type="email" name="email" required></div>
        <div>
            <label>Role</label>
            <select name="role">
                <option value="staff">Staff</option>
                <option value="viewer">Viewer</option>
                <option value="administrator">Administrator</option>
            </select>
        </div>
    </div>
    <div><label>Password (min 10 characters)</label><input type="password" name="password" required minlength="10"></div>
    <label><input type="checkbox" name="is_active" checked> Active</label>
    <div class="toolbar">
        <button type="submit" class="btn btn-primary">Create user</button>
        <a href="<?= url('/users') ?>" class="btn">Cancel</a>
    </div>
</form>
