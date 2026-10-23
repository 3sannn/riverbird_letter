<?php
$isDraft = !$document || $document['status'] === 'draft';
$readonly = !$isDraft || ($user['role'] === 'viewer');
$previewHtml = (new \App\Templates\TemplateRenderer())->render(
    $document_type === 'proposal' ? 'proposal/mano-bakery' : ($document_type === 'quotation' ? 'quotation/default' : ($document_type === 'letter' ? 'letter/default' : 'invoice/default')),
    [
        'document' => $document ?? [
            'title' => 'New document',
            'document_type' => $document_type,
            'document_date' => date('Y-m-d'),
            'remarks' => '',
            'total_amount' => 0,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'amount_received' => 0,
            'client_name' => '',
            'business_name' => '',
            'contact_person' => '',
            'client_address' => '',
        ],
        'items' => $items,
        'content' => json_decode($document['content_json'] ?? '{}', true) ?: [],
        'company' => config('company'),
        'for_pdf' => false,
    ]
);
?>
<div class="editor-panel">
    <form id="document-form" method="post" action="<?= url('/documents/save') ?>" enctype="multipart/form-data" class="form-grid">
        <?= \App\Support\Csrf::field() ?>
        <?php if ($document): ?><input type="hidden" name="id" value="<?= (int) $document['id'] ?>"><?php endif; ?>
        <input type="hidden" name="document_type" value="<?= e($document_type) ?>">

        <?php if ($document && $document['status'] !== 'draft'): ?>
            <div class="alert">This document is <?= e($document['status']) ?>. Financial fields reflect the issued record; use duplicate to create an editable copy.</div>
        <?php endif; ?>

        <div class="form-row">
            <div><label>Title</label><input name="title" value="<?= e($document['title'] ?? ucfirst($document_type)) ?>" <?= $readonly ? 'readonly' : '' ?>></div>
            <div>
                <label>Client</label>
                <select name="client_id" <?= $readonly ? 'disabled' : '' ?>>
                    <option value="">— Select client —</option>
                    <?php foreach ($clients as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= (string)($document['client_id'] ?? '') === (string)$c['id'] ? 'selected' : '' ?>>
                            <?= e($c['name']) ?><?= $c['business_name'] ? ' — ' . e($c['business_name']) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <?php if ($templates): ?>
        <div>
            <label>Template</label>
            <select name="template_id" <?= $readonly ? 'disabled' : '' ?>>
                <option value="">Default</option>
                <?php foreach ($templates as $t): ?>
                    <option value="<?= (int) $t['id'] ?>" <?= (string)($document['template_id'] ?? '') === (string)$t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <div class="form-row">
            <div><label>Document date</label><input type="date" name="document_date" value="<?= e($document['document_date'] ?? date('Y-m-d')) ?>" <?= $readonly ? 'readonly' : '' ?>></div>
            <div><label>Due date</label><input type="date" name="due_date" value="<?= e($document['due_date'] ?? '') ?>" <?= $readonly ? 'readonly' : '' ?>></div>
        </div>

        <?php if (in_array($document_type, ['invoice', 'quotation', 'proposal'], true)): ?>
        <div class="line-items" id="line-items">
            <label>Line items</label>
            <?php foreach ($items as $item): ?>
            <div class="item-row">
                <input name="item_description[]" placeholder="Description" value="<?= e($item['description']) ?>" <?= $readonly ? 'readonly' : '' ?>>
                <input name="item_quantity[]" type="number" step="0.01" min="0" value="<?= e($item['quantity']) ?>" <?= $readonly ? 'readonly' : '' ?>>
                <input name="item_unit_price[]" type="number" step="0.01" min="0" value="<?= e($item['unit_price']) ?>" <?= $readonly ? 'readonly' : '' ?>>
                <?php if (!$readonly): ?><button type="button" class="btn remove-line" title="Remove">×</button><?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if (!$readonly): ?><button type="button" class="btn" id="add-line-item">Add line item</button><?php endif; ?>

        <div class="form-row">
            <div><label>Discount (₹)</label><input name="discount_amount" value="<?= e($document['discount_amount'] ?? '0') ?>" <?= $readonly ? 'readonly' : '' ?>></div>
            <div><label>Tax rate (%)</label><input name="tax_rate" value="<?= e($document['tax_rate'] ?? '0') ?>" <?= $readonly ? 'readonly' : '' ?>></div>
        </div>
        <div><label>Shipping / handling (₹)</label><input name="shipping_amount" value="<?= e($document['shipping_amount'] ?? '0') ?>" <?= $readonly ? 'readonly' : '' ?>></div>
        <?php endif; ?>

        <?php if ($document_type === 'proposal'): ?>
        <div><label>Prepared for</label><input name="prepared_for" value="<?= e(json_decode($document['content_json'] ?? '{}', true)['prepared_for'] ?? '') ?>" <?= $readonly ? 'readonly' : '' ?>></div>
        <div><label>Introduction / body</label><textarea name="content_body" <?= $readonly ? 'readonly' : '' ?>><?= e(json_decode($document['content_json'] ?? '{}', true)['body'] ?? '') ?></textarea></div>
        <div><label>Terms & conditions</label><textarea name="terms" <?= $readonly ? 'readonly' : '' ?>><?= e($document['terms'] ?? '') ?></textarea></div>
        <?php endif; ?>

        <div><label>Remarks / payment instructions</label><textarea name="remarks" <?= $readonly ? 'readonly' : '' ?>><?= e($document['remarks'] ?? '') ?></textarea></div>
        <div><label>Internal notes</label><textarea name="notes" <?= $readonly ? 'readonly' : '' ?>><?= e($document['notes'] ?? '') ?></textarea></div>

        <?php if (!$readonly): ?>
        <div><label>Attachment</label><input type="file" name="attachment" accept=".pdf,.png,.jpg,.jpeg,.doc,.docx"></div>
        <?php endif; ?>

        <div class="toolbar">
            <?php if (!$readonly): ?>
                <button type="submit" class="btn btn-primary">Save draft</button>
            <?php endif; ?>
            <?php if ($document): ?>
                <a class="btn" href="<?= url('/documents/show?id=' . (int) $document['id']) ?>">View</a>
                <a class="btn" href="<?= url('/documents/pdf?id=' . (int) $document['id']) ?>">Download PDF</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if ($document && $document['document_type'] === 'invoice' && $document['status'] === 'draft' && in_array($user['role'], ['administrator','staff'], true)): ?>
        <form method="post" action="<?= url('/documents/issue') ?>" style="margin-top:12px" onsubmit="return confirm('Issue this invoice? A number will be assigned permanently.')">
            <?= \App\Support\Csrf::field() ?>
            <input type="hidden" name="id" value="<?= (int) $document['id'] ?>">
            <button type="submit" class="btn btn-primary">Issue invoice</button>
        </form>
    <?php endif; ?>
</div>
<div class="preview-panel">
    <div class="a4-preview" id="live-preview"><?= $previewHtml ?></div>
</div>
