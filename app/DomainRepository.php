<?php

declare(strict_types=1);

namespace App;

use DateTimeImmutable;
use PDO;

final class DomainRepository
{
    public const STATUSES = [
        'aktif' => 'Aktif',
        'satilik' => 'Satılık',
        'satildi' => 'Satıldı',
        'suresi_doldu' => 'Süresi doldu',
    ];

    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::pdo();
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, pages: int, per_page: int}
     */
    public function paginate(string $search, string $status, int $page, int $perPage = 20): array
    {
        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = '(domain LIKE :search OR registrar LIKE :search)';
            $params[':search'] = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
        }

        if (isset(self::STATUSES[$status])) {
            $where[] = 'status = :status';
            $params[':status'] = $status;
        }

        $whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);

        $countStmt = $this->pdo->prepare('SELECT COUNT(*) FROM domains' . $whereSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $perPage = max(1, min($perPage, 100));
        $pages = max(1, (int)ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * $perPage;

        $sql = 'SELECT id, domain, registrar, purchase_price, renewal_price, purchase_date, expiry_date, status, notes, created_at, updated_at'
            . ' FROM domains' . $whereSql
            . ' ORDER BY (expiry_date IS NULL) ASC, expiry_date ASC, domain ASC'
            . ' LIMIT :limit OFFSET :offset';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage,
        ];
    }

    /**
     * @return array{total: int, total_cost: float, for_sale: int, expiring: int}
     */
    public function stats(): array
    {
        $stmt = $this->pdo->query(
            "SELECT
                COUNT(*) AS total,
                COALESCE(SUM(purchase_price), 0) AS total_cost,
                COALESCE(SUM(status = 'satilik'), 0) AS for_sale,
                COALESCE(SUM(expiry_date IS NOT NULL AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)), 0) AS expiring
             FROM domains"
        );
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total' => (int)($row['total'] ?? 0),
            'total_cost' => (float)($row['total_cost'] ?? 0),
            'for_sale' => (int)($row['for_sale'] ?? 0),
            'expiring' => (int)($row['expiring'] ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM domains WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $domain = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($domain) ? $domain : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO domains
                (domain, registrar, purchase_price, renewal_price, purchase_date, expiry_date, status, notes)
             VALUES
                (:domain, :registrar, :purchase_price, :renewal_price, :purchase_date, :expiry_date, :status, :notes)'
        );
        $stmt->execute($this->bindableData($data));

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE domains SET
                domain = :domain,
                registrar = :registrar,
                purchase_price = :purchase_price,
                renewal_price = :renewal_price,
                purchase_date = :purchase_date,
                expiry_date = :expiry_date,
                status = :status,
                notes = :notes
             WHERE id = :id
             LIMIT 1'
        );

        $params = $this->bindableData($data);
        $params[':id'] = $id;
        $stmt->execute($params);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM domains WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function domainExists(string $domain, ?int $ignoreId = null): bool
    {
        if ($ignoreId === null) {
            $stmt = $this->pdo->prepare('SELECT id FROM domains WHERE domain = :domain LIMIT 1');
            $stmt->execute([':domain' => $domain]);
        } else {
            $stmt = $this->pdo->prepare('SELECT id FROM domains WHERE domain = :domain AND id <> :id LIMIT 1');
            $stmt->bindValue(':domain', $domain, PDO::PARAM_STR);
            $stmt->bindValue(':id', $ignoreId, PDO::PARAM_INT);
            $stmt->execute();
        }

        return $stmt->fetchColumn() !== false;
    }

    /**
     * @param array<string, mixed> $input
     * @return array{0: array<string, mixed>, 1: array<string, string>}
     */
    public function validate(array $input, ?int $ignoreId = null): array
    {
        $errors = [];

        $data = [
            'domain' => '',
            'registrar' => null,
            'purchase_price' => null,
            'renewal_price' => null,
            'purchase_date' => null,
            'expiry_date' => null,
            'status' => 'aktif',
            'notes' => null,
        ];

        $rawDomain = trim((string)($input['domain'] ?? ''));
        if ($rawDomain === '') {
            $errors['domain'] = 'Domain adı zorunludur.';
        } else {
            $domain = $this->normalizeDomain($rawDomain);
            if ($domain === null) {
                $errors['domain'] = 'Geçerli bir alan adı girin (ör. example.com). Protokol, yol veya boşluk kullanmayın.';
            } elseif ($this->domainExists($domain, $ignoreId)) {
                $errors['domain'] = 'Bu domain zaten portföyde kayıtlı.';
            } else {
                $data['domain'] = $domain;
            }
        }

        $registrar = trim((string)($input['registrar'] ?? ''));
        if (mb_strlen($registrar, 'UTF-8') > 150) {
            $errors['registrar'] = 'Registrar en fazla 150 karakter olabilir.';
        } else {
            $data['registrar'] = $registrar === '' ? null : $registrar;
        }

        $status = (string)($input['status'] ?? 'aktif');
        if (!isset(self::STATUSES[$status])) {
            $errors['status'] = 'Geçersiz durum seçimi.';
        } else {
            $data['status'] = $status;
        }

        $data['purchase_price'] = $this->parsePrice(
            $input['purchase_price'] ?? null,
            'purchase_price',
            'Alış fiyatı',
            $errors
        );
        $data['renewal_price'] = $this->parsePrice(
            $input['renewal_price'] ?? null,
            'renewal_price',
            'Yenileme fiyatı',
            $errors
        );

        $data['purchase_date'] = $this->parseDate(
            $input['purchase_date'] ?? null,
            'purchase_date',
            'Alış tarihi',
            $errors
        );
        $data['expiry_date'] = $this->parseDate(
            $input['expiry_date'] ?? null,
            'expiry_date',
            'Bitiş tarihi',
            $errors
        );

        $notes = trim((string)($input['notes'] ?? ''));
        if (mb_strlen($notes, 'UTF-8') > 2000) {
            $errors['notes'] = 'Notlar en fazla 2000 karakter olabilir.';
        } else {
            $data['notes'] = $notes === '' ? null : $notes;
        }

        if (!isset($errors['purchase_date'], $errors['expiry_date'])
            && $data['purchase_date'] !== null
            && $data['expiry_date'] !== null
            && $data['expiry_date'] < $data['purchase_date']
        ) {
            $errors['expiry_date'] = 'Bitiş tarihi, alış tarihinden önce olamaz.';
        }

        return [$data, $errors];
    }

    private function normalizeDomain(string $value): ?string
    {
        $value = trim($value);

        if ($value === ''
            || preg_match('#^[a-z][a-z0-9+.-]*://#i', $value) === 1
            || strpbrk($value, "/?#@\\ \t\n\r") !== false
        ) {
            return null;
        }

        $value = rtrim($value, '.');

        if (preg_match('/[^\x00-\x7F]/', $value) === 1 && function_exists('idn_to_ascii')) {
            $ascii = idn_to_ascii($value, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            if ($ascii === false) {
                return null;
            }
            $value = $ascii;
        }

        $value = strtolower($value);

        if (strlen($value) > 253) {
            return null;
        }

        $label = '[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?';
        $pattern = '/^(?:' . $label . '\.)+(?:[a-z]{2,63}|xn--[a-z0-9-]{2,59})$/';

        if (preg_match($pattern, $value) !== 1) {
            return null;
        }

        return $value;
    }

    /**
     * @param array<string, string> $errors
     */
    private function parsePrice(mixed $value, string $field, string $label, array &$errors): ?string
    {
        $raw = str_replace(["\u{00A0}", ' '], '', trim((string)$value));

        if ($raw === '') {
            return null;
        }

        if (str_contains($raw, ',') && str_contains($raw, '.')) {
            if (strrpos($raw, ',') > strrpos($raw, '.')) {
                $raw = str_replace('.', '', $raw);
                $raw = str_replace(',', '.', $raw);
            } else {
                $raw = str_replace(',', '', $raw);
            }
        } elseif (str_contains($raw, ',')) {
            $raw = str_replace(',', '.', $raw);
        }

        if (preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', $raw) !== 1) {
            $errors[$field] = $label . ' geçerli bir USD tutarı olmalı (ör. 12.50).';
            return null;
        }

        return number_format((float)$raw, 2, '.', '');
    }

    /**
     * @param array<string, string> $errors
     */
    private function parseDate(mixed $value, string $field, string $label, array &$errors): ?string
    {
        $raw = trim((string)$value);

        if ($raw === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $raw);
        if (!$date instanceof DateTimeImmutable || $date->format('Y-m-d') !== $raw) {
            $errors[$field] = $label . ' geçerli bir tarih olmalı (GG.AA.YYYY).';
            return null;
        }

        return $raw;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function bindableData(array $data): array
    {
        return [
            ':domain' => $data['domain'],
            ':registrar' => $data['registrar'],
            ':purchase_price' => $data['purchase_price'],
            ':renewal_price' => $data['renewal_price'],
            ':purchase_date' => $data['purchase_date'],
            ':expiry_date' => $data['expiry_date'],
            ':status' => $data['status'],
            ':notes' => $data['notes'],
        ];
    }
}
