<h1>Dashboard</h1>
<div class="grid-stats">
    <div class="card stat"><div class="label">Clients</div><div class="value"><?= (int) $stats['total_clients'] ?></div></div>
    <div class="card stat"><div class="label">Documents</div><div class="value"><?= (int) $stats['total_documents'] ?></div></div>
    <div class="card stat"><div class="label">Drafts</div><div class="value"><?= (int) $stats['drafts'] ?></div></div>
    <div class="card stat"><div class="label">Issued invoices</div><div class="value"><?= (int) $stats['issued_invoices'] ?></div></div>
    <div class="card stat"><div class="label">Paid</div><div class="value"><?= (int) $stats['paid_invoices'] ?></div></div>
    <div class="card stat"><div class="label">Unpaid</div><div class="value"><?= (int) $stats['unpaid_invoices'] ?></div></div>
</div>

<div class="toolbar">
    <a class="btn btn-primary" href="<?= url('/documents/create?type=invoice') ?>">New invoice</a>
    <a class="btn" href="<?= url('/documents/create?type=proposal') ?>">New proposal</a>
    <a class="btn" href="<?= url('/documents/create?type=quotation') ?>">New quotation</a>
    <a class="btn" href="<?= url('/documents/create?type=letter') ?>">New letter</a>
    <a class="btn" href="<?= url('/clients/create') ?>">Add client</a>
</div>

<div style="display:grid;grid-template-columns:1.2fr 1fr;gap:16px">
    <div class="card">
        <h2>Recently modified documents</h2>
        <table class="table">
            <thead><tr><th>Document</th><th>Client</th><th>Status</th><th>Updated</th></tr></thead>
            <tbody>
            <?php foreach ($recent_documents as $doc): ?>
                <tr>
                    <td><a href="<?= url('/documents/show?id=' . (int) $doc['id']) ?>"><?= e($doc['title']) ?></a><br><small><?= e($doc['document_number'] ?? $doc['document_type']) ?></small></td>
                    <td><?= e($doc['client_name'] ?? '—') ?></td>
                    <td><span class="badge badge-<?= e($doc['status']) ?>"><?= e($doc['status']) ?></span></td>
                    <td><?= e($doc['updated_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$recent_documents): ?><tr><td colspan="4">No documents yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card">
        <h2>Recent activity</h2>
        <table class="table">
            <thead><tr><th>Action</th><th>User</th><th>When</th></tr></thead>
            <tbody>
            <?php foreach ($recent_activity as $row): ?>
                <tr>
                    <td><?= e($row['action']) ?></td>
                    <td><?= e($row['full_name'] ?? 'System') ?></td>
                    <td><?= e($row['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$recent_activity): ?><tr><td colspan="3">No activity logged yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
