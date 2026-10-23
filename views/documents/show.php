<h1><?= e($document['title']) ?></h1>
<div class="toolbar">
    <span class="badge"><?= e($document['status']) ?></span>
    <?php if ($document['document_number']): ?><span class="badge"><?= e($document['document_number']) ?></span><?php endif; ?>
    <span class="spacer"></span>
    <?php if (in_array($user['role'], ['administrator','staff'], true)): ?>
        <a class="btn" href="<?= url('/documents/edit?id=' . (int) $document['id']) ?>">Open in editor</a>
        <form method="post" action="<?= url('/documents/duplicate') ?>" style="display:inline">
            <?= \App\Support\Csrf::field() ?>
            <input type="hidden" name="id" value="<?= (int) $document['id'] ?>">
            <button class="btn" type="submit">Duplicate</button>
        </form>
    <?php endif; ?>
    <a class="btn btn-primary" href="<?= url('/documents/pdf?id=' . (int) $document['id']) ?>">Download PDF</a>
</div>

<div class="card" style="margin-bottom:16px;overflow:auto">
    <div class="a4-preview"><?= $preview_html ?></div>
</div>

<?php if ($document['document_type'] === 'invoice' && in_array($document['status'], ['issued','paid','partially_paid','overdue'], true) && in_array($user['role'], ['administrator','staff'], true)): ?>
<div class="card form-grid">
    <h2>Record payment</h2>
    <form method="post" action="<?= url('/documents/payment') ?>" class="form-row">
        <?= \App\Support\Csrf::field() ?>
        <input type="hidden" name="document_id" value="<?= (int) $document['id'] ?>">
        <div><label>Date</label><input type="date" name="payment_date" value="<?= date('Y-m-d') ?>" required></div>
        <div><label>Amount (₹)</label><input name="amount" required></div>
        <div><label>Method</label><input name="payment_method" placeholder="UPI, bank transfer…"></div>
        <div><label>Reference</label><input name="reference"></div>
        <div style="grid-column:1/-1"><label>Remarks</label><input name="remarks"></div>
        <button type="submit" class="btn btn-primary">Add payment</button>
    </form>
    <?php if ($payments): ?>
        <table class="table">
            <thead><tr><th>Date</th><th>Amount</th><th>Method</th><th>Reference</th></tr></thead>
            <tbody>
            <?php foreach ($payments as $p): ?>
                <tr>
                    <td><?= e($p['payment_date']) ?></td>
                    <td>₹<?= e(number_format((float)$p['amount'], 2)) ?></td>
                    <td><?= e($p['payment_method'] ?? '—') ?></td>
                    <td><?= e($p['reference'] ?? '—') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php endif; ?>
