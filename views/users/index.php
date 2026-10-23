<h1>Users</h1>
<div class="toolbar">
    <span class="spacer"></span>
    <a href="<?= url('/users/create') ?>" class="btn btn-primary">Add user</a>
</div>
<div class="card">
    <table class="table">
        <thead><tr><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Active</th><th>Last login</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= e($u['full_name']) ?></td>
                <td><?= e($u['username']) ?></td>
                <td><?= e($u['email']) ?></td>
                <td><?= e($u['role']) ?></td>
                <td><?= $u['is_active'] ? 'Yes' : 'No' ?></td>
                <td><?= e($u['last_login_at'] ?? '—') ?></td>
                <td>
                    <?php if ((int)$u['id'] !== (int)$user['id']): ?>
                    <form method="post" action="<?= url('/users/toggle') ?>">
                        <?= \App\Support\Csrf::field() ?>
                        <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                        <button class="btn btn-ghost" type="submit"><?= $u['is_active'] ? 'Deactivate' : 'Activate' ?></button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
