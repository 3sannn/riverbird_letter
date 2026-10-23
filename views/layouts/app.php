<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(config('app.name')) ?></title>
    <link rel="icon" href="<?= url('/assets/images/riverbird-logo.png') ?>" type="image/png">
    <link rel="stylesheet" href="<?= url('/assets/css/app.css') ?>">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand">
            <img src="<?= url('/assets/images/riverbird-logo.png') ?>" alt="RiverBird">
            <span>RiverBird<br>Letter Portal</span>
        </div>
        <nav class="nav">
            <a href="<?= url('/') ?>" class="<?= $_SERVER['REQUEST_URI'] === '/' || str_ends_with($_SERVER['REQUEST_URI'], '/index.php') ? 'active' : '' ?>">Dashboard</a>
            <a href="<?= url('/clients') ?>">Clients</a>
            <a href="<?= url('/documents') ?>">Documents</a>
            <a href="<?= url('/documents/create?type=invoice') ?>">New invoice</a>
            <a href="<?= url('/documents/create?type=proposal') ?>">New proposal</a>
            <?php if (($user['role'] ?? '') === 'administrator'): ?>
                <a href="<?= url('/users') ?>">Users</a>
            <?php endif; ?>
        </nav>
    </aside>
    <div class="main">
        <header class="topbar">
            <div><?= e($user['full_name'] ?? '') ?> <span class="badge"><?= e($user['role'] ?? '') ?></span></div>
            <form method="post" action="<?= url('/logout') ?>">
                <?= \App\Support\Csrf::field() ?>
                <button type="submit" class="btn btn-ghost">Sign out</button>
            </form>
        </header>
        <main class="content">
            <?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
            <?php if ($msg = flash('error')): ?><div class="alert alert-error"><?= e($msg) ?></div><?php endif; ?>
            <?= $content ?>
        </main>
    </div>
</div>
</body>
</html>
