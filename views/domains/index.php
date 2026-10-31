<?php

declare(strict_types=1);

/** @var array{items: array<int, array<string, mixed>>, total: int, page: int, pages: int, per_page: int} $result */
/** @var array{total: int, total_cost: float, for_sale: int, expiring: int} $stats */
/** @var string $q */
/** @var string $status */
/** @var array<string, string> $statuses */

$hasFilters = $q !== '' || $status !== '';
?>
<section class="page-head">
    <div>
        <span class="eyebrow">Genel bakış</span>
        <h1>Portföy</h1>
        <p class="page-subtitle">
            Alan adlarınızı, maliyetlerini ve yenileme tarihlerini tek yerden yönetin.
        </p>
    </div>
    <a class="btn btn--primary" href="<?= e(url('domains.create')) ?>">
        <?= icon('plus') ?>
        Yeni domain
    </a>
</section>

<section class="stats-grid" aria-label="Portföy özeti">
    <article class="stat-card">
        <div class="stat-top">
            <span class="stat-icon stat-icon--teal" aria-hidden="true"><?= icon('layers') ?></span>
            <span class="stat-label">Toplam domain</span>
        </div>
        <strong class="stat-value"><?= e($stats['total']) ?></strong>
        <span class="stat-hint">Portföydeki kayıt sayısı</span>
    </article>

    <article class="stat-card">
        <div class="stat-top">
            <span class="stat-icon stat-icon--blue" aria-hidden="true"><?= icon('wallet') ?></span>
            <span class="stat-label">Toplam alış maliyeti</span>
        </div>
        <strong class="stat-value"><?= e(format_money($stats['total_cost'])) ?></strong>
        <span class="stat-hint">USD cinsinden toplam</span>
    </article>

    <article class="stat-card">
        <div class="stat-top">
            <span class="stat-icon stat-icon--amber" aria-hidden="true"><?= icon('tag') ?></span>
            <span class="stat-label">Satılık</span>
        </div>
        <strong class="stat-value"><?= e($stats['for_sale']) ?></strong>
        <span class="stat-hint">Satışa hazır domain</span>
    </article>

    <article class="stat-card<?= $stats['expiring'] > 0 ? ' stat-card--alert' : '' ?>">
        <div class="stat-top">
            <span class="stat-icon stat-icon--rose" aria-hidden="true"><?= icon('calendar') ?></span>
            <span class="stat-label">30 günde bitecek</span>
        </div>
        <strong class="stat-value"><?= e($stats['expiring']) ?></strong>
        <span class="stat-hint">Yenileme planı gerekli</span>
    </article>
</section>

<section class="panel">
    <div class="panel-head">
        <div>
            <h2>Portföy listesi</h2>
            <p class="panel-subtitle">
                <?= $hasFilters
                    ? e($result['total']) . ' sonuç gösteriliyor'
                    : 'Tüm domainler ve yenileme bilgileri' ?>
            </p>
        </div>
        <span class="count-badge"><?= e($result['total']) ?> kayıt</span>
    </div>

    <form method="get" action="index.php" class="filter-bar" role="search">
        <input type="hidden" name="route" value="domains">

        <div class="search-field">
            <?= icon('search') ?>
            <label class="sr-only" for="q">Domain veya registrar ara</label>
            <input
                type="search"
                id="q"
                name="q"
                value="<?= e($q) ?>"
                maxlength="100"
                placeholder="Domain veya registrar ara"
            >
        </div>

        <div class="filter-select">
            <label class="sr-only" for="status">Duruma göre filtrele</label>
            <select id="status" name="status">
                <option value="">Tüm durumlar</option>
                <?php foreach ($statuses as $statusKey => $statusLabel): ?>
                    <option value="<?= e($statusKey) ?>"<?= $status === $statusKey ? ' selected' : '' ?>>
                        <?= e($statusLabel) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn--secondary">Filtrele</button>

        <?php if ($hasFilters): ?>
            <a class="btn btn--ghost" href="<?= e(url('domains')) ?>">Temizle</a>
        <?php endif; ?>
    </form>

    <?php if ($result['items'] === []): ?>
        <div class="empty-state">
            <span class="empty-icon" aria-hidden="true"><?= icon($hasFilters ? 'search' : 'layers') ?></span>
            <h2><?= $hasFilters ? 'Eşleşen domain bulunamadı' : 'Portföyünüz henüz boş' ?></h2>
            <p class="muted">
                <?= $hasFilters
                    ? 'Arama veya durum filtresini değiştirerek tekrar deneyin.'
                    : 'İlk alan adınızı ekleyerek portföyünüzü oluşturmaya başlayın.' ?>
            </p>
            <?php if ($hasFilters): ?>
                <a class="btn btn--secondary" href="<?= e(url('domains')) ?>">Filtreleri temizle</a>
            <?php else: ?>
                <a class="btn btn--primary" href="<?= e(url('domains.create')) ?>"><?= icon('plus') ?> İlk domaini ekle</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <caption class="sr-only">Domain portföyü listesi</caption>
                <thead>
                    <tr>
                        <th scope="col">Domain</th>
                        <th scope="col">Durum</th>
                        <th scope="col" class="text-right">Alış</th>
                        <th scope="col" class="text-right">Yenileme</th>
                        <th scope="col">Bitiş</th>
                        <th scope="col" class="text-right">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result['items'] as $item): ?>
                        <?php
                        $days = days_until($item['expiry_date'] ?? null);
                        $expiryNote = '';
                        if ($days !== null) {
                            if ($days < 0) {
                                $expiryNote = abs($days) . ' gün gecikti';
                            } elseif ($days === 0) {
                                $expiryNote = 'Bugün bitiyor';
                            } elseif ($days <= 30) {
                                $expiryNote = $days . ' gün kaldı';
                            }
                        }

                        $registrar = trim((string)($item['registrar'] ?? ''));
                        $note = trim((string)($item['notes'] ?? ''));
                        ?>
                        <tr>
                            <td>
                                <div class="domain-cell">
                                    <span class="domain-icon" aria-hidden="true"><?= icon('globe') ?></span>
                                    <div class="domain-copy">
                                        <a
                                            class="domain-link"
                                            href="https://<?= e($item['domain']) ?>"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        ><?= e($item['domain']) ?></a>
                                        <span class="domain-meta">
                                            <?= e($registrar !== '' ? $registrar : 'Registrar belirtilmedi') ?>
                                            <?php if ($note !== ''): ?>
                                                <span class="note-badge" title="<?= e($note) ?>">Not</span>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?= e(status_class((string)$item['status'])) ?>">
                                    <?= e(status_label((string)$item['status'])) ?>
                                </span>
                            </td>
                            <td class="text-right money"><?= e(format_money($item['purchase_price'])) ?></td>
                            <td class="text-right money"><?= e(format_money($item['renewal_price'])) ?></td>
                            <td>
                                <span class="date-main <?= e(expiry_class($item['expiry_date'] ?? null)) ?>">
                                    <?= e(format_date($item['expiry_date'] ?? null)) ?>
                                </span>
                                <?php if ($expiryNote !== ''): ?>
                                    <small class="date-note"><?= e($expiryNote) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="row-actions">
                                    <a
                                        class="icon-btn"
                                        href="<?= e(url('domains.edit', ['id' => (int)$item['id']])) ?>"
                                        title="Düzenle"
                                        aria-label="<?= e($item['domain']) ?> domainini düzenle"
                                    ><?= icon('edit') ?></a>

                                    <form
                                        method="post"
                                        action="<?= e(url('domains.delete')) ?>"
                                        class="inline-form"
                                        data-confirm="<?= e($item['domain']) ?> domainini portföyden silmek istediğinize emin misiniz? Bu işlem geri alınamaz."
                                    >
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= e($item['id']) ?>">
                                        <button
                                            type="submit"
                                            class="icon-btn icon-btn--danger"
                                            title="Sil"
                                            aria-label="<?= e($item['domain']) ?> domainini sil"
                                        ><?= icon('trash') ?></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($result['pages'] > 1): ?>
            <nav class="pagination" aria-label="Sayfalama">
                <?php if ($result['page'] > 1): ?>
                    <a class="btn btn--secondary btn--sm" href="<?= e(url('domains', ['q' => $q, 'status' => $status, 'page' => $result['page'] - 1])) ?>">
                        <?= icon('arrow-left') ?> Önceki
                    </a>
                <?php else: ?>
                    <span class="btn btn--secondary btn--sm is-disabled" aria-disabled="true">
                        <?= icon('arrow-left') ?> Önceki
                    </span>
                <?php endif; ?>

                <span class="pagination-info">
                    Sayfa <?= e($result['page']) ?> / <?= e($result['pages']) ?>
                    <span class="muted">· <?= e($result['total']) ?> kayıt</span>
                </span>

                <?php if ($result['page'] < $result['pages']): ?>
                    <a class="btn btn--secondary btn--sm" href="<?= e(url('domains', ['q' => $q, 'status' => $status, 'page' => $result['page'] + 1])) ?>">
                        Sonraki <?= icon('arrow-right') ?>
                    </a>
                <?php else: ?>
                    <span class="btn btn--secondary btn--sm is-disabled" aria-disabled="true">
                        Sonraki <?= icon('arrow-right') ?>
                    </span>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>
