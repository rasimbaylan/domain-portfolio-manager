<?php

declare(strict_types=1);

/** @var string|null $error */
/** @var string $username */
?>
<div class="auth-layout">
    <section class="auth-brand-panel" aria-label="Domain Portföy tanıtımı">
        <div class="auth-brand-top">
            <span class="brand-mark brand-mark--lg" aria-hidden="true"><?= icon('globe') ?></span>
            <span class="auth-brand-name">
                <strong>Domain Portföy</strong>
                <small>Yönetim paneli</small>
            </span>
        </div>

        <div class="auth-hero">
            <span class="eyebrow">Portföy yönetimi</span>
            <h1>Alan adı portföyünüzü tek panelden yönetin.</h1>
            <p>
                Domainlerinizi, alış maliyetlerini ve yenileme tarihlerini tek bir güvenli
                çalışma alanında takip edin.
            </p>

            <ul class="auth-features">
                <li>
                    <span aria-hidden="true"><?= icon('check') ?></span>
                    USD bazlı alış ve yenileme maliyeti takibi
                </li>
                <li>
                    <span aria-hidden="true"><?= icon('check') ?></span>
                    Yaklaşan yenilemeler için görsel uyarılar
                </li>
                <li>
                    <span aria-hidden="true"><?= icon('check') ?></span>
                    Tek yönetici ve güvenli oturum yönetimi
                </li>
            </ul>
        </div>

        <div class="auth-brand-footer">
            <span><?= icon('shield') ?> Güvenli yönetici erişimi</span>
            <span>v<?= e(APP_VERSION) ?></span>
        </div>
    </section>

    <section class="auth-form-panel" aria-label="Yönetici girişi">
        <div class="auth-mobile-brand">
            <span class="brand-mark" aria-hidden="true"><?= icon('globe') ?></span>
            <span class="brand-copy">
                <strong>Domain Portföy</strong>
                <small>Yönetim paneli</small>
            </span>
        </div>

        <div class="auth-card">
            <span class="eyebrow">Yönetici paneli</span>
            <h2>Tekrar hoş geldiniz</h2>
            <p class="auth-intro">Portföyünüzü yönetmek için hesabınıza giriş yapın.</p>

            <?php if ($error !== null): ?>
                <div class="alert alert--error" role="alert">
                    <?= icon('alert') ?>
                    <span><?= e($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= e(url('login')) ?>" class="auth-form">
                <?= csrf_field() ?>

                <div class="field">
                    <label for="username">Kullanıcı adı</label>
                    <div class="input-icon">
                        <span aria-hidden="true"><?= icon('user') ?></span>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            value="<?= e($username) ?>"
                            maxlength="50"
                            autocomplete="username"
                            autocapitalize="none"
                            spellcheck="false"
                            required
                            autofocus
                        >
                    </div>
                </div>

                <div class="field">
                    <label for="password">Şifre</label>
                    <div class="input-icon">
                        <span aria-hidden="true"><?= icon('lock') ?></span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            maxlength="1024"
                            autocomplete="current-password"
                            required
                        >
                    </div>
                </div>

                <button type="submit" class="btn btn--primary btn--block btn--lg">
                    Güvenli giriş
                    <?= icon('arrow-right') ?>
                </button>
            </form>

            <p class="auth-note">
                <?= icon('lock') ?>
                Bu alan yalnızca yetkili yönetici içindir.
            </p>
        </div>

        <p class="auth-copyright">© <?= e(date('Y')) ?> Domain Portföy</p>
    </section>
</div>
