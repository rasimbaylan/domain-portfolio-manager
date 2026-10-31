<?php

declare(strict_types=1);

/** @var string $content */
/** @var array<string, mixed> $config */
/** @var array{id: int, username: string}|null $authUser */
/** @var string $pageTitle */
/** @var string $currentRoute */

$flashes = take_flashes();
$isDomainsActive = in_array($currentRoute, ['domains', 'domains.edit'], true);
$isCreateActive = $currentRoute === 'domains.create';
$appName = (string)($config['app']['name'] ?? 'Domain Portföy');
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#0b1424">
    <title><?= e($pageTitle) ?> · <?= e($appName) ?></title>
    <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>">
</head>
<body class="<?= $authUser !== null ? 'app-body' : 'auth-body' ?>">
<a class="skip-link" href="#main-content">İçeriğe geç</a>

<?php if ($authUser === null): ?>
    <main id="main-content" class="auth-page">
        <?php if ($flashes !== []): ?>
            <div class="auth-flashes" aria-live="polite">
                <?php foreach ($flashes as $flashMessage): ?>
                    <div class="alert alert--<?= e($flashMessage['type']) ?>" role="<?= $flashMessage['type'] === 'error' ? 'alert' : 'status' ?>">
                        <?= icon(match ($flashMessage['type']) {
                            'success' => 'check',
                            'error' => 'alert',
                            default => 'info',
                        }) ?>
                        <span><?= e($flashMessage['message']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?= $content ?>
    </main>
<?php else: ?>
    <div class="app-shell">
        <aside class="sidebar">
            <a class="sidebar-brand" href="<?= e(url('domains')) ?>">
                <span class="brand-mark" aria-hidden="true"><?= icon('globe') ?></span>
                <span class="brand-copy">
                    <strong>Domain Portföy</strong>
                    <small>Yönetim paneli</small>
                </span>
            </a>

            <div class="sidebar-section">
                <span class="sidebar-label">Çalışma alanı</span>
                <nav class="sidebar-nav" aria-label="Ana menü">
                    <a class="side-link<?= $isDomainsActive ? ' is-active' : '' ?>" href="<?= e(url('domains')) ?>"<?= $isDomainsActive ? ' aria-current="page"' : '' ?>>
                        <?= icon('layers') ?>
                        <span>Portföy</span>
                    </a>
                    <a class="side-link<?= $isCreateActive ? ' is-active' : '' ?>" href="<?= e(url('domains.create')) ?>"<?= $isCreateActive ? ' aria-current="page"' : '' ?>>
                        <?= icon('plus') ?>
                        <span>Yeni domain</span>
                    </a>
                </nav>
            </div>

            <div class="sidebar-footer">
                <div class="currency-card">
                    <span class="currency-icon" aria-hidden="true"><?= icon('dollar') ?></span>
                    <span class="currency-copy">
                        <span class="currency-label">Para birimi</span>
                        <strong>USD</strong>
                    </span>
                </div>
                <div class="sidebar-security">
                    <?= icon('shield') ?>
                    <span>Güvenli oturum · v<?= e(APP_VERSION) ?></span>
                </div>
            </div>
        </aside>

        <div class="app-main">
            <header class="topbar">
                <div class="topbar-left">
                    <span class="topbar-crumb">Panel</span>
                    <span class="topbar-sep" aria-hidden="true">/</span>
                    <span class="topbar-page"><?= e($pageTitle) ?></span>
                </div>

                <div class="topbar-right">
                    <span class="session-pill">
                        <span class="session-dot" aria-hidden="true"></span>
                        Güvenli oturum
                    </span>

                    <div class="topbar-user">
                        <span class="avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($authUser['username'], 0, 1, 'UTF-8'), 'UTF-8')) ?></span>
                        <span class="topbar-user-copy">
                            <strong><?= e($authUser['username']) ?></strong>
                            <small>Yönetici</small>
                        </span>
                    </div>

                    <form method="post" action="<?= e(url('logout')) ?>" class="inline-form">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn--ghost btn--sm logout-btn">
                            <?= icon('logout') ?>
                            <span>Çıkış</span>
                        </button>
                    </form>
                </div>
            </header>

            <main id="main-content" class="app-content">
                <?php if ($flashes !== []): ?>
                    <div class="flash-stack" aria-live="polite">
                        <?php foreach ($flashes as $flashMessage): ?>
                            <div class="alert alert--<?= e($flashMessage['type']) ?>" role="<?= $flashMessage['type'] === 'error' ? 'alert' : 'status' ?>">
                                <?= icon(match ($flashMessage['type']) {
                                    'success' => 'check',
                                    'error' => 'alert',
                                    default => 'info',
                                }) ?>
                                <span><?= e($flashMessage['message']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?= $content ?>
            </main>

            <footer class="app-footer">
                <span>© <?= e(date('Y')) ?> <?= e($appName) ?></span>
                <span>Tüm tutarlar USD cinsindendir.</span>
            </footer>
        </div>
    </div>
<?php endif; ?>

<script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</body>
</html>
