<h1>Clients</h1>
<div class="toolbar">
    <form method="get" class="toolbar">
        <input type="search" name="q" placeholder="Search clients" value="<?= e($search) ?>">
        <label><input type="checkbox" name="archived" <?= $archived ? 'checked' : '' ?> onchange="this.form.submit()"> Archived</label>
        <button class="btn" type="submit">Search</button>
    </form>
    <span class="spacer"></span>
    <a href="<?= url('/clients/create') ?>" class="btn btn-primary">Add client</a>
</div>
<div class="card">
    <table class="table">
        <thead><tr><th>Name</th><th>Business</th><th>Contact</th><th>Email</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($clients as $client): ?>
            <tr>
                <td><?= e($client['name']) ?></td>
                <td><?= e($client['business_name'] ?? '—') ?></td>
                <td><?= e($client['contact_person'] ?? '—') ?></td>
                <td><?= e($client['email'] ?? '—') ?></td>
                <td>
                    <a href="<?= url('/clients/edit?id=' . (int) $client['id']) ?>">Edit</a>
                    <?php if (!$client['is_archived']): ?>
                        <form method="post" action="<?= url('/clients/archive') ?>" style="display:inline" onsubmit="return confirm('Archive this client?')">
                            <?= \App\Support\Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= (int) $client['id'] ?>">
                            <button class="btn btn-ghost" type="submit">Archive</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$clients): ?><tr><td colspan="5">No clients found.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
