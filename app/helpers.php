<?php

declare(strict_types=1);

use App\Auth;
use App\Csrf;
use App\DomainRepository;

/**
 * HTML çıktısı için güvenli kaçış.
 */
function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Uygulama içi URL üretir. Boş parametreler atlanır.
 *
 * @param array<string, scalar|null> $params
 */
function url(string $route = 'domains', array $params = []): string
{
    $params = array_filter(
        $params,
        static fn (mixed $value): bool => $value !== null && $value !== ''
    );

    return 'index.php?' . http_build_query(
        array_merge(['route' => $route], $params),
        '',
        '&',
        PHP_QUERY_RFC3986
    );
}

function asset(string $path): string
{
    return $path . '?v=' . APP_VERSION;
}

function redirect(string $route = 'domains', array $params = []): never
{
    header('Location: ' . url($route, $params), true, 303);
    exit;
}

/**
 * @param array<string, mixed> $data
 */
function view(string $template, array $data = [], string $title = 'Domain Portföy'): void
{
    $templatePath = APP_ROOT . '/views/' . $template . '.php';

    if (!is_file($templatePath)) {
        throw new RuntimeException('Görünüm bulunamadı: ' . $template);
    }

    $config = $GLOBALS['appConfig'];
    $authUser = Auth::user();
    $currentRoute = (string)($_GET['route'] ?? 'domains');
    $pageTitle = $title;

    extract($data, EXTR_SKIP);

    ob_start();
    require $templatePath;
    $content = (string)ob_get_clean();

    require APP_ROOT . '/views/layout.php';
}

function abort(int $status, string $message): never
{
    http_response_code($status);
    view('errors/error', ['status' => $status, 'message' => $message], 'Hata');
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

/**
 * @return array<int, array{type: string, message: string}>
 */
function take_flashes(): array
{
    $messages = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);

    return is_array($messages) ? $messages : [];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}

function format_money(mixed $value): string
{
    if ($value === null || $value === '') {
        return '—';
    }

    return '$' . number_format((float)$value, 2, '.', ',');
}

function format_date(?string $value): string
{
    if ($value === null || $value === '') {
        return '—';
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

    return $date instanceof DateTimeImmutable ? $date->format('d.m.Y') : '—';
}

function format_datetime(?string $value): string
{
    if ($value === null || $value === '') {
        return '—';
    }

    $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);

    return $date instanceof DateTimeImmutable ? $date->format('d.m.Y H:i') : '—';
}

/**
 * Bugüne göre kalan gün sayısı. Geçmiş tarihler negatif döner.
 */
function days_until(?string $value): ?int
{
    if ($value === null || $value === '') {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    if (!$date instanceof DateTimeImmutable) {
        return null;
    }

    $today = new DateTimeImmutable('today');

    return (int)$today->diff($date)->format('%r%a');
}

function status_label(string $status): string
{
    return DomainRepository::STATUSES[$status] ?? $status;
}

function status_class(string $status): string
{
    return match ($status) {
        'aktif' => 'badge--active',
        'satilik' => 'badge--sale',
        'satildi' => 'badge--sold',
        'suresi_doldu' => 'badge--expired',
        default => 'badge--neutral',
    };
}

function expiry_class(?string $expiryDate): string
{
    $days = days_until($expiryDate);

    if ($days === null) {
        return '';
    }

    if ($days < 0) {
        return 'date--expired';
    }

    if ($days <= 30) {
        return 'date--soon';
    }

    return '';
}

function current_route(): string
{
    return (string)($_GET['route'] ?? 'domains');
}
