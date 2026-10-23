<h1>Documents</h1>
<form method="get" class="toolbar card">
    <input type="search" name="q" placeholder="Search title or number" value="<?= e($filters['q']) ?>">
    <select name="type">
        <option value="">All types</option>
        <?php foreach (['invoice','proposal','quotation','letter','receipt'] as $t): ?>
            <option value="<?= $t ?>" <?= ($filters['type'] ?? '') === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="status">
        <option value="">All statuses</option>
        <?php foreach (['draft','issued','paid','partially_paid','overdue','cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= str_replace('_',' ', $s) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="client_id">
        <option value="">All clients</option>
        <?php foreach ($clients as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= (string)($filters['client_id'] ?? '') === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn" type="submit">Filter</button>
    <span class="spacer"></span>
    <a href="<?= url('/documents/create?type=invoice') ?>" class="btn btn-primary">Create</a>
</form>

<div class="card" style="margin-top:12px">
    <table class="table">
        <thead><tr><th>Document</th><th>Type</th><th>Client</th><th>Status</th><th>Date</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($documents as $doc): ?>
            <tr>
                <td>
                    <a href="<?= url('/documents/show?id=' . (int) $doc['id']) ?>"><?= e($doc['title']) ?></a><br>
                    <small><?= e($doc['document_number'] ?? '—') ?></small>
                </td>
                <td><?= e($doc['document_type']) ?></td>
                <td><?= e($doc['client_name'] ?? '—') ?></td>
                <td><span class="badge"><?= e($doc['status']) ?></span></td>
                <td><?= e($doc['document_date'] ?? '—') ?></td>
                <td>
                    <?php if (in_array($user['role'], ['administrator','staff'], true)): ?>
                        <a href="<?= url('/documents/edit?id=' . (int) $doc['id']) ?>">Edit</a>
                    <?php endif; ?>
                    <a href="<?= url('/documents/pdf?id=' . (int) $doc['id']) ?>">PDF</a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$documents): ?><tr><td colspan="6">No documents match your filters.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
