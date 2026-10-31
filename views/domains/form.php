<?php

declare(strict_types=1);

/** @var array<string, mixed> $domain */
/** @var array<string, string> $errors */
/** @var string $mode */
/** @var array<string, string> $statuses */

$isEdit = $mode === 'edit';
$domainId = (int)($domain['id'] ?? 0);
$formAction = $isEdit ? url('domains.update', ['id' => $domainId]) : url('domains.store');
$fieldError = static fn (string $key): ?string => $errors[$key] ?? null;
$statusKey = (string)($domain['status'] ?? 'aktif');
$statusLabel = $statuses[$statusKey] ?? $statusKey;
?>
<section class="page-head">
    <div>
        <a class="back-link" href="<?= e(url('domains')) ?>">
            <?= icon('arrow-left') ?>
            Portföye dön
        </a>
        <span class="eyebrow"><?= $isEdit ? 'Kayıt düzenleme' : 'Yeni kayıt' ?></span>
        <h1><?= $isEdit ? 'Domain düzenle' : 'Yeni domain ekle' ?></h1>
        <p class="page-subtitle">
            <?= $isEdit
                ? 'Kayıt bilgilerini güncelleyin. Tüm tutarlar USD cinsindendir.'
                : 'Portföyünüze yeni bir alan adı ekleyin. Tüm tutarlar USD cinsindendir.' ?>
        </p>
    </div>
</section>

<div class="detail-layout">
    <section class="panel form-panel">
        <div class="panel-head">
            <div>
                <h2>Domain bilgileri</h2>
                <p class="panel-subtitle">Zorunlu alanlar yıldız ile işaretlenmiştir.</p>
            </div>
            <span class="required-note"><span class="required">*</span> Zorunlu alan</span>
        </div>

        <form method="post" action="<?= e($formAction) ?>" class="record-form">
            <?= csrf_field() ?>
            <?php if ($isEdit): ?>
                <input type="hidden" name="id" value="<?= e($domainId) ?>">
            <?php endif; ?>

            <div class="form-section">
                <div class="form-section-head">
                    <h3>Temel bilgiler</h3>
                    <p>Domain kimliği ve mevcut durumu</p>
                </div>

                <div class="form-grid">
                    <div class="field field--full">
                        <?php $error = $fieldError('domain'); ?>
                        <label for="domain">Domain adı <span class="required" aria-hidden="true">*</span></label>
                        <input
                            type="text"
                            id="domain"
                            name="domain"
                            value="<?= e($domain['domain'] ?? '') ?>"
                            maxlength="253"
                            placeholder="example.com"
                            autocomplete="off"
                            autocapitalize="none"
                            spellcheck="false"
                            required
                            autofocus
                            <?= $error !== null ? 'aria-invalid="true" aria-describedby="domain-error"' : 'aria-describedby="domain-hint"' ?>
                            class="<?= $error !== null ? 'is-invalid' : '' ?>"
                        >
                        <?php if ($error !== null): ?>
                            <p class="field-error" id="domain-error"><?= e($error) ?></p>
                        <?php else: ?>
                            <p class="field-hint" id="domain-hint">Protokol veya yol olmadan girin. Örn. example.com</p>
                        <?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="registrar">Registrar</label>
                        <input
                            type="text"
                            id="registrar"
                            name="registrar"
                            value="<?= e($domain['registrar'] ?? '') ?>"
                            maxlength="150"
                            placeholder="Örn. Namecheap"
                        >
                    </div>

                    <div class="field">
                        <?php $error = $fieldError('status'); ?>
                        <label for="status">Durum</label>
                        <select
                            id="status"
                            name="status"
                            class="<?= $error !== null ? 'is-invalid' : '' ?>"
                            <?= $error !== null ? 'aria-invalid="true" aria-describedby="status-error"' : '' ?>
                        >
                            <?php foreach ($statuses as $optionKey => $optionLabel): ?>
                                <option value="<?= e($optionKey) ?>"<?= $statusKey === $optionKey ? ' selected' : '' ?>>
                                    <?= e($optionLabel) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($error !== null): ?>
                            <p class="field-error" id="status-error"><?= e($error) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-head">
                    <h3>Finansal bilgiler</h3>
                    <p>Tüm tutarlar USD</p>
                </div>

                <div class="form-grid">
                    <div class="field">
                        <?php $error = $fieldError('purchase_price'); ?>
                        <label for="purchase_price">Alış fiyatı</label>
                        <div class="input-prefix <?= $error !== null ? 'is-invalid' : '' ?>">
                            <span aria-hidden="true">$</span>
                            <input
                                type="text"
                                id="purchase_price"
                                name="purchase_price"
                                value="<?= e($domain['purchase_price'] ?? '') ?>"
                                inputmode="decimal"
                                placeholder="0.00"
                                <?= $error !== null ? 'aria-invalid="true" aria-describedby="purchase-price-error"' : '' ?>
                            >
                        </div>
                        <?php if ($error !== null): ?>
                            <p class="field-error" id="purchase-price-error"><?= e($error) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="field">
                        <?php $error = $fieldError('renewal_price'); ?>
                        <label for="renewal_price">Yenileme fiyatı</label>
                        <div class="input-prefix <?= $error !== null ? 'is-invalid' : '' ?>">
                            <span aria-hidden="true">$</span>
                            <input
                                type="text"
                                id="renewal_price"
                                name="renewal_price"
                                value="<?= e($domain['renewal_price'] ?? '') ?>"
                                inputmode="decimal"
                                placeholder="0.00"
                                <?= $error !== null ? 'aria-invalid="true" aria-describedby="renewal-price-error"' : '' ?>
                            >
                        </div>
                        <?php if ($error !== null): ?>
                            <p class="field-error" id="renewal-price-error"><?= e($error) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-head">
                    <h3>Tarihler ve notlar</h3>
                    <p>Yenileme takibi için tarihleri girin</p>
                </div>

                <div class="form-grid">
                    <div class="field">
                        <?php $error = $fieldError('purchase_date'); ?>
                        <label for="purchase_date">Alış tarihi</label>
                        <input
                            type="date"
                            id="purchase_date"
                            name="purchase_date"
                            value="<?= e($domain['purchase_date'] ?? '') ?>"
                            class="<?= $error !== null ? 'is-invalid' : '' ?>"
                            <?= $error !== null ? 'aria-invalid="true" aria-describedby="purchase-date-error"' : '' ?>
                        >
                        <?php if ($error !== null): ?>
                            <p class="field-error" id="purchase-date-error"><?= e($error) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="field">
                        <?php $error = $fieldError('expiry_date'); ?>
                        <label for="expiry_date">Bitiş tarihi</label>
                        <input
                            type="date"
                            id="expiry_date"
                            name="expiry_date"
                            value="<?= e($domain['expiry_date'] ?? '') ?>"
                            class="<?= $error !== null ? 'is-invalid' : '' ?>"
                            <?= $error !== null ? 'aria-invalid="true" aria-describedby="expiry-date-error"' : '' ?>
                        >
                        <?php if ($error !== null): ?>
                            <p class="field-error" id="expiry-date-error"><?= e($error) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="field field--full">
                        <?php $error = $fieldError('notes'); ?>
                        <label for="notes">Notlar</label>
                        <textarea
                            id="notes"
                            name="notes"
                            rows="5"
                            maxlength="2000"
                            placeholder="Yenileme sağlayıcısı, satış görüşmeleri veya hatırlatmalar..."
                            class="<?= $error !== null ? 'is-invalid' : '' ?>"
                            <?= $error !== null ? 'aria-invalid="true" aria-describedby="notes-error"' : 'aria-describedby="notes-hint"' ?>
                        ><?= e($domain['notes'] ?? '') ?></textarea>
                        <?php if ($error !== null): ?>
                            <p class="field-error" id="notes-error"><?= e($error) ?></p>
                        <?php else: ?>
                            <p class="field-hint" id="notes-hint">En fazla 2000 karakter.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn--primary">
                    <?= icon($isEdit ? 'check' : 'plus') ?>
                    <?= $isEdit ? 'Değişiklikleri kaydet' : 'Domaini ekle' ?>
                </button>
                <a class="btn btn--ghost" href="<?= e(url('domains')) ?>">Vazgeç</a>
            </div>
        </form>
    </section>

    <aside class="detail-aside">
        <section class="panel side-panel">
            <div class="panel-head">
                <div>
                    <h2>Kayıt özeti</h2>
                    <p class="panel-subtitle"><?= $isEdit ? 'Mevcut kayıt bilgileri' : 'Yeni kayıt' ?></p>
                </div>
            </div>
            <div class="side-body">
                <div class="summary-row">
                    <span>Durum</span>
                    <span class="badge <?= e(status_class($statusKey)) ?>"><?= e($statusLabel) ?></span>
                </div>
                <div class="summary-row">
                    <span>Para birimi</span>
                    <strong>USD</strong>
                </div>

                <?php if ($isEdit): ?>
                    <div class="summary-divider"></div>
                    <div class="summary-row">
                        <span>Kayıt no</span>
                        <strong>#<?= e($domainId) ?></strong>
                    </div>
                    <div class="summary-row">
                        <span>Oluşturulma</span>
                        <span><?= e(format_datetime($domain['created_at'] ?? null)) ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Son güncelleme</span>
                        <span><?= e(format_datetime($domain['updated_at'] ?? null)) ?></span>
                    </div>
                <?php else: ?>
                    <p class="summary-note">
                        Kayıt oluşturulduğunda tarih ve durum bilgileri burada görünecek.
                    </p>
                <?php endif; ?>
            </div>
        </section>

        <section class="panel side-panel side-panel--tip">
            <div class="tip-icon" aria-hidden="true"><?= icon('shield') ?></div>
            <h3>Güvenli kayıt</h3>
            <p>Portföy verileri hazır SQL ifadeleriyle saklanır ve yalnızca yetkili yönetici erişebilir.</p>
            <ul class="tip-list">
                <li><?= icon('check') ?> Tüm tutarlar USD olarak saklanır.</li>
                <li><?= icon('check') ?> Domain adı protokolsüz girilmelidir.</li>
                <li><?= icon('check') ?> Bitiş tarihi yaklaşan kayıtlar vurgulanır.</li>
            </ul>
        </section>
    </aside>
</div>
