<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document editor — <?= e(config('app.name')) ?></title>
    <link rel="stylesheet" href="<?= url('/assets/css/app.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/document.css') ?>">
</head>
<body>
<header class="topbar">
    <div><a href="<?= url('/documents') ?>">← Documents</a></div>
    <div class="toolbar" style="margin:0">
        <span class="badge"><?= e($document['status'] ?? 'draft') ?></span>
        <form method="post" action="<?= url('/logout') ?>" style="display:inline"><?= \App\Support\Csrf::field() ?><button class="btn btn-ghost" type="submit">Sign out</button></form>
    </div>
</header>
<?php if ($msg = flash('error')): ?><div class="alert alert-error" style="margin:12px 16px 0"><?= e($msg) ?></div><?php endif; ?>
<?php if ($msg = flash('success')): ?><div class="alert alert-success" style="margin:12px 16px 0"><?= e($msg) ?></div><?php endif; ?>
<div class="editor-shell">
    <?= $content ?>
</div>
<script src="<?= url('/assets/js/document-editor.js') ?>"></script>
</body>
</html>
