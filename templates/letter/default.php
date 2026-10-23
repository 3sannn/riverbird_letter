<?php
/** @var array $document @var array $content @var array $company */
?>
<link rel="stylesheet" href="<?= url('/assets/css/document.css') ?>">
<div class="doc-header">
    <div class="company">
        <h1><?= e($company['name']) ?></h1>
        <p><?= e($company['tagline']) ?></p>
    </div>
    <div class="meta"><?= e($document['document_date'] ?? '') ?></div>
</div>
<div class="doc-section">
    <h2><?= e($document['title']) ?></h2>
    <p><?= nl2br(e($content['body'] ?? '')) ?></p>
</div>
<?php if (!empty($document['notes'])): ?>
<div class="doc-footer"><?= nl2br(e($document['notes'])) ?></div>
<?php endif; ?>
