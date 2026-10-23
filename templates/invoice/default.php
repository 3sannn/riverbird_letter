<?php
/** @var array $document @var array $items @var array $company @var array $content */
$logoUrl = url('/assets/images/riverbird-logo.png');
$number = $document['document_number'] ?? 'DRAFT';
$date = $document['document_date'] ?? '';
$clientName = $document['contact_person'] ?? $document['client_name'] ?? '';
$business = $document['business_name'] ?? '';
$address = $document['client_address'] ?? '';
?>
<link rel="stylesheet" href="<?= url('/assets/css/document.css') ?>">
<div class="doc-header">
    <div class="company">
        <img src="<?= e($logoUrl) ?>" alt="RiverBird" style="height:42px;margin-bottom:8px">
        <h1><?= e($company['name']) ?></h1>
        <p><?= e($company['tagline']) ?></p>
    </div>
    <div class="meta">
        <div class="doc-title">INVOICE</div>
        <div data-field="document_date"><?= e($date) ?></div>
        <div><strong>INVOICE NO.</strong> <?= e($number) ?></div>
    </div>
</div>

<div class="doc-columns">
    <div>
        <h3>Bill to</h3>
        <div data-field="client"><?= e(strtoupper($clientName)) ?><br><?= e($business) ?><br><?= nl2br(e($address)) ?></div>
    </div>
    <div>
        <h3>Ship to</h3>
        <div><?= e(strtoupper($clientName)) ?><br><?= e($business) ?><br><?= nl2br(e($address)) ?></div>
    </div>
</div>

<table class="doc-table">
    <thead><tr><th>S.no</th><th>Description</th><th class="num">Amount (₹)</th></tr></thead>
    <tbody data-items-body>
    <?php $i = 1; foreach ($items as $item): if (!trim($item['description'] ?? '')) continue; ?>
        <tr>
            <td><?= $i++ ?></td>
            <td><?= e($item['description']) ?></td>
            <td class="num"><?= e(number_format((float)$item['line_total'], 0)) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<div class="doc-totals">
    <table style="width:100%">
        <tr><td>Total</td><td data-field="total">₹<?= e(number_format((float)($document['total_amount'] ?? 0), 0)) ?></td></tr>
    </table>
</div>

<?php if (!empty($document['remarks'])): ?>
<div class="doc-remarks"><strong>Remarks / Payment Instructions:</strong> <span data-field="remarks"><?= e($document['remarks']) ?></span></div>
<?php endif; ?>

<table class="doc-totals" style="margin-top:20px">
    <tr><td>Discount</td><td><?= e(number_format((float)($document['discount_amount'] ?? 0), 2)) ?></td></tr>
    <tr><td>Amount received</td><td><?= e(number_format((float)($document['amount_received'] ?? 0), 0)) ?></td></tr>
    <tr><td>Shipping / handling</td><td><?= e(number_format((float)($document['shipping_amount'] ?? 0), 2)) ?></td></tr>
    <tr class="grand"><td>Paid</td><td>₹<?= e(number_format((float)($document['amount_received'] ?? 0), 0)) ?></td></tr>
</table>
