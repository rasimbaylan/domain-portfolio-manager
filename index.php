<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\Auth;
use App\Csrf;
use App\DomainRepository;

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$route = trim((string)($_GET['route'] ?? 'domains'));
if ($route === '') {
    $route = 'domains';
}

$formDefaults = [
    'domain' => '',
    'registrar' => '',
    'purchase_price' => '',
    'renewal_price' => '',
    'purchase_date' => '',
    'expiry_date' => '',
    'status' => 'aktif',
    'notes' => '',
];

try {
    if ($method === 'POST' && !Csrf::isValid($_POST['_csrf'] ?? null)) {
        abort(419, 'Oturum doğrulaması başarısız oldu. Sayfayı yenileyip tekrar deneyin.');
    }

    if ($route === 'login') {
        if ($method !== 'GET' && $method !== 'POST') {
            abort(405, 'Bu işlem için izin verilen yöntem kullanılmadı.');
        }

        if (Auth::check()) {
            redirect('domains');
        }

        $loginError = null;
        $loginUsername = '';

        if ($method === 'POST') {
            $loginUsername = trim((string)($_POST['username'] ?? ''));
            $password = (string)($_POST['password'] ?? '');

            if ($loginUsername === '' || $password === '') {
                $loginError = 'Kullanıcı adı ve şifre zorunludur.';
            } elseif (strlen($loginUsername) > 50 || strlen($password) > 1024) {
                $loginError = 'Kullanıcı adı veya şifre geçersiz.';
            } elseif (Auth::isLockedOut()) {
                $loginError = 'Çok fazla başarısız deneme yapıldı. Lütfen 1 dakika sonra tekrar deneyin.';
            } elseif (Auth::attempt($loginUsername, $password)) {
                flash('success', 'Hoş geldiniz, ' . $loginUsername . '.');
                redirect('domains');
            } else {
                $loginError = 'Kullanıcı adı veya şifre hatalı.';
            }
        }

        view('login', [
            'error' => $loginError,
            'username' => $loginUsername,
        ], 'Yönetici Girişi');
        exit;
    }

    if ($route === 'logout') {
        if ($method !== 'POST') {
            abort(405, 'Çıkış yapmak için formu kullanın.');
        }

        Auth::logout();
        flash('success', 'Güvenli bir şekilde çıkış yaptınız.');
        redirect('login');
    }

    if (!Auth::check()) {
        flash('info', 'Devam etmek için lütfen giriş yapın.');
        redirect('login');
    }

    $domains = new DomainRepository();
    $statuses = DomainRepository::STATUSES;

    switch ($route) {
        case 'domains':
            if ($method !== 'GET') {
                abort(405, 'Bu işlem için izin verilen yöntem kullanılmadı.');
            }

            $search = trim((string)($_GET['q'] ?? ''));
            if (mb_strlen($search, 'UTF-8') > 100) {
                $search = mb_substr($search, 0, 100, 'UTF-8');
            }

            $status = (string)($_GET['status'] ?? '');
            if (!isset($statuses[$status])) {
                $status = '';
            }

            $page = max(1, (int)($_GET['page'] ?? 1));
            $result = $domains->paginate($search, $status, $page);

            view('domains/index', [
                'result' => $result,
                'stats' => $domains->stats(),
                'q' => $search,
                'status' => $status,
                'statuses' => $statuses,
            ], 'Portföy');
            exit;

        case 'domains.create':
            if ($method !== 'GET') {
                abort(405, 'Bu işlem için izin verilen yöntem kullanılmadı.');
            }

            view('domains/form', [
                'domain' => $formDefaults,
                'errors' => [],
                'mode' => 'create',
                'statuses' => $statuses,
            ], 'Yeni domain');
            exit;

        case 'domains.store':
            if ($method !== 'POST') {
                abort(405, 'Bu işlem için izin verilen yöntem kullanılmadı.');
            }

            [$data, $errors] = $domains->validate($_POST);

            if ($errors !== []) {
                http_response_code(422);
                view('domains/form', [
                    'domain' => array_merge($data, array_intersect_key($_POST, $formDefaults)),
                    'errors' => $errors,
                    'mode' => 'create',
                    'statuses' => $statuses,
                ], 'Yeni domain');
                exit;
            }

            try {
                $domains->create($data);
            } catch (PDOException $exception) {
                if ((string)$exception->getCode() === '23000') {
                    http_response_code(422);
                    $errors['domain'] = 'Bu domain zaten portföyde kayıtlı.';
                    view('domains/form', [
                        'domain' => array_merge($data, array_intersect_key($_POST, $formDefaults)),
                        'errors' => $errors,
                        'mode' => 'create',
                        'statuses' => $statuses,
                    ], 'Yeni domain');
                    exit;
                }
                throw $exception;
            }

            flash('success', $data['domain'] . ' portföye eklendi.');
            redirect('domains');

        case 'domains.edit':
            if ($method !== 'GET') {
                abort(405, 'Bu işlem için izin verilen yöntem kullanılmadı.');
            }

            $id = (int)($_GET['id'] ?? 0);
            if ($id < 1) {
                abort(404, 'Düzenlenecek domain bulunamadı.');
            }

            $domain = $domains->find($id);
            if ($domain === null) {
                abort(404, 'Düzenlenecek domain bulunamadı.');
            }

            view('domains/form', [
                'domain' => $domain,
                'errors' => [],
                'mode' => 'edit',
                'statuses' => $statuses,
            ], 'Domain düzenle');
            exit;

        case 'domains.update':
            if ($method !== 'POST') {
                abort(405, 'Bu işlem için izin verilen yöntem kullanılmadı.');
            }

            $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
            if ($id < 1) {
                abort(404, 'Güncellenecek domain bulunamadı.');
            }

            $existing = $domains->find($id);
            if ($existing === null) {
                abort(404, 'Güncellenecek domain bulunamadı.');
            }

            [$data, $errors] = $domains->validate($_POST, $id);

            if ($errors !== []) {
                http_response_code(422);
                view('domains/form', [
                    'domain' => array_merge($existing, $data, array_intersect_key($_POST, $formDefaults), ['id' => $id]),
                    'errors' => $errors,
                    'mode' => 'edit',
                    'statuses' => $statuses,
                ], 'Domain düzenle');
                exit;
            }

            try {
                $domains->update($id, $data);
            } catch (PDOException $exception) {
                if ((string)$exception->getCode() === '23000') {
                    http_response_code(422);
                    $errors['domain'] = 'Bu domain zaten portföyde kayıtlı.';
                    view('domains/form', [
                        'domain' => array_merge($existing, $data, array_intersect_key($_POST, $formDefaults), ['id' => $id]),
                        'errors' => $errors,
                        'mode' => 'edit',
                        'statuses' => $statuses,
                    ], 'Domain düzenle');
                    exit;
                }
                throw $exception;
            }

            flash('success', $data['domain'] . ' güncellendi.');
            redirect('domains');

        case 'domains.delete':
            if ($method !== 'POST') {
                abort(405, 'Silmek için formu kullanın.');
            }

            $id = (int)($_POST['id'] ?? 0);
            if ($id < 1) {
                abort(404, 'Silinecek domain bulunamadı.');
            }

            $domain = $domains->find($id);
            if ($domain === null) {
                abort(404, 'Silinecek domain bulunamadı.');
            }

            $domains->delete($id);
            flash('success', $domain['domain'] . ' portföyden silindi.');
            redirect('domains');

        default:
            abort(404, 'Aradığınız sayfa bulunamadı.');
    }
} catch (PDOException $exception) {
    error_log('Veritabanı hatası: ' . $exception->getMessage());
    abort(500, 'Veritabanı işlemi sırasında bir hata oluştu. Lütfen daha sonra tekrar deneyin.');
} catch (Throwable $exception) {
    error_log('Uygulama hatası: ' . $exception->getMessage());
    abort(500, 'Beklenmeyen bir hata oluştu. Lütfen daha sonra tekrar deneyin.');
}
