<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Bu betik yalnızca komut satırından çalıştırılabilir.');
}

define('APP_ROOT', dirname(__DIR__));

const DB_NAME = 'opencode_test';
const DB_USER = 'opencode_app';
const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;

/**
 * @param array<string, mixed> $options
 */
function optionOrEnv(array $options, string $option, string $envName): ?string
{
    if (isset($options[$option]) && is_string($options[$option]) && $options[$option] !== '') {
        return $options[$option];
    }

    $env = getenv($envName);
    if (is_string($env) && $env !== '') {
        return $env;
    }

    return null;
}

function prompt(string $label, ?string $default = null): string
{
    echo $label;
    $line = fgets(STDIN);

    if ($line === false) {
        fail('Girdi okunamadı.');
    }

    $value = trim($line);

    if ($value === '') {
        return $default ?? '';
    }

    return $value;
}

function promptSecret(string $label): string
{
    echo $label;

    if (DIRECTORY_SEPARATOR !== '\\' && function_exists('shell_exec')) {
        shell_exec('stty -echo');
        $line = fgets(STDIN);
        shell_exec('stty echo');
        echo PHP_EOL;
    } else {
        echo '(yazarken görünür) ';
        $line = fgets(STDIN);
    }

    if ($line === false) {
        fail('Girdi okunamadı.');
    }

    return trim($line);
}

function fail(string $message): never
{
    fwrite(STDERR, PHP_EOL . 'HATA: ' . $message . PHP_EOL);
    exit(1);
}

function info(string $message): void
{
    echo $message . PHP_EOL;
}

$options = getopt('', [
    'root-user::',
    'root-password::',
    'admin-user::',
    'admin-password::',
    'force',
]);

if ($options === false) {
    fail('Komut satırı seçenekleri okunamadı.');
}

$configDir = APP_ROOT . '/config';
$configPath = $configDir . '/config.php';
$force = array_key_exists('force', $options);

if (is_file($configPath) && !$force) {
    fail('config/config.php zaten mevcut. Yeniden kurmak için --force kullanın. Mevcut yapılandırma korunur.');
}

$rootUser = optionOrEnv($options, 'root-user', 'MYSQL_ROOT_USER') ?? 'root';
$rootPassword = optionOrEnv($options, 'root-password', 'MYSQL_ROOT_PASSWORD') ?? '';

info('');
info('Domain Portföy kurulumu');
info('=======================');
info('Yalnızca "' . DB_NAME . '" veritabanı ve "' . DB_USER . '" MySQL kullanıcısı oluşturulacak.');
info('');

try {
    $rootPdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4',
        $rootUser,
        $rootPassword,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $exception) {
    fail('MySQL yönetici bağlantısı kurulamadı. --root-user ve --root-password seçeneklerini kontrol edin. Ayrıntı: ' . $exception->getMessage());
}

try {
    $existingDb = $rootPdo->prepare(
        'SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = :name LIMIT 1'
    );
    $existingDb->execute([':name' => DB_NAME]);

    if ($existingDb->fetchColumn() !== false && !$force) {
        fail('"' . DB_NAME . '" veritabanı zaten mevcut. Verilerinizi korumak için kurulum durduruldu. Devam etmek için --force kullanın.');
    }

    $existingUser = $rootPdo->prepare(
        'SELECT User FROM mysql.user WHERE User = :user AND Host = :host LIMIT 1'
    );
    $existingUser->execute([':user' => DB_USER, ':host' => 'localhost']);

    if ($existingUser->fetchColumn() !== false && !$force) {
        fail('"' . DB_USER . '" MySQL kullanıcısı zaten mevcut. Mevcut kullanıcıya dokunulmadı. Devam etmek için --force kullanın.');
    }

    $rootPdo->exec(
        'CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
    );

    $dbPassword = bin2hex(random_bytes(24));
    $quotedPassword = $rootPdo->quote($dbPassword);

    $rootPdo->exec(
        "CREATE USER IF NOT EXISTS '" . DB_USER . "'@'localhost' IDENTIFIED BY " . $quotedPassword
    );
    $rootPdo->exec(
        "ALTER USER '" . DB_USER . "'@'localhost' IDENTIFIED BY " . $quotedPassword
    );
    $rootPdo->exec(
        'GRANT SELECT, INSERT, UPDATE, DELETE ON `' . DB_NAME . "`.* TO '" . DB_USER . "'@'localhost'"
    );

    $schema = file_get_contents(APP_ROOT . '/database/schema.sql');
    if ($schema === false) {
        fail('database/schema.sql okunamadı.');
    }

    $dbPdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        $rootUser,
        $rootPassword,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    foreach (explode(';', $schema) as $statement) {
        $statement = trim($statement);
        if ($statement !== '') {
            $dbPdo->exec($statement);
        }
    }

    info('Veritabanı ve tablolar hazır.');

    $adminUser = optionOrEnv($options, 'admin-user', 'OPENCODE_ADMIN_USER');
    if ($adminUser === null) {
        $adminUser = prompt('Yönetici kullanıcı adı [admin]: ', 'admin');
    }

    if (preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $adminUser) !== 1) {
        fail('Kullanıcı adı 3-50 karakter olmalı ve yalnızca harf, rakam, nokta, tire ve alt çizgi içermelidir.');
    }

    $adminPassword = optionOrEnv($options, 'admin-password', 'OPENCODE_ADMIN_PASSWORD');
    if ($adminPassword === null) {
        $adminPassword = promptSecret('Yönetici şifresi (en az 12 karakter): ');
        $passwordAgain = promptSecret('Yönetici şifresi (tekrar): ');

        if (!hash_equals($adminPassword, $passwordAgain)) {
            fail('Şifreler birbiriyle eşleşmiyor.');
        }
    }

    if (strlen($adminPassword) < 12 || strlen($adminPassword) > 200) {
        fail('Şifre 12-200 karakter arasında olmalıdır.');
    }

    $passwordHash = password_hash($adminPassword, PASSWORD_DEFAULT);
    if ($passwordHash === false) {
        fail('Şifre özeti oluşturulamadı.');
    }

    $adminPdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        $dbPassword,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $adminCount = (int)$adminPdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();

    if ($adminCount > 0 && !$force) {
        fail('Yönetici hesabı zaten mevcut. Mevcut hesabı korumak için kurulum durduruldu.');
    }

    if ($adminCount > 0) {
        $updateAdmin = $adminPdo->prepare(
            'UPDATE admins SET username = :username, password_hash = :hash WHERE id = 1 LIMIT 1'
        );
        $updateAdmin->execute([':username' => $adminUser, ':hash' => $passwordHash]);
        info('Mevcut yönetici hesabı --force ile güncellendi.');
    } else {
        $insertAdmin = $adminPdo->prepare(
            'INSERT INTO admins (id, username, password_hash) VALUES (1, :username, :hash)'
        );
        $insertAdmin->execute([':username' => $adminUser, ':hash' => $passwordHash]);
        info('Yönetici hesabı oluşturuldu.');
    }

    if (!is_dir($configDir) && !mkdir($configDir, 0700, true) && !is_dir($configDir)) {
        fail('config klasörü oluşturulamadı.');
    }

    $config = [
        'app' => [
            'name' => 'Domain Portföy',
            'env' => 'production',
            'timezone' => 'Europe/Istanbul',
            'session_name' => 'opencode_portfolio',
            'session_idle_timeout' => 1800,
            'session_max_lifetime' => 28800,
        ],
        'db' => [
            'host' => DB_HOST,
            'port' => DB_PORT,
            'name' => DB_NAME,
            'user' => DB_USER,
            'password' => $dbPassword,
        ],
    ];

    $configContent = "<?php\n\n"
        . "declare(strict_types=1);\n\n"
        . "// Bu dosya kurulum betiği tarafından oluşturuldu. Sırları paylaşmayın.\n"
        . "return " . var_export($config, true) . ";\n";

    if (file_put_contents($configPath, $configContent, LOCK_EX) === false) {
        fail('config/config.php yazılamadı.');
    }

    @chmod($configPath, 0600);

    $logDir = APP_ROOT . '/storage/logs';
    if (!is_dir($logDir) && !mkdir($logDir, 0700, true) && !is_dir($logDir)) {
        fail('storage/logs klasörü oluşturulamadı.');
    }
} catch (PDOException $exception) {
    fail('Veritabanı işlemi başarısız oldu: ' . $exception->getMessage());
}

info('');
info('Kurulum tamamlandı.');
info('Veritabanı: ' . DB_NAME);
info('MySQL kullanıcısı: ' . DB_USER . ' (yalnızca bu veritabanında SELECT/INSERT/UPDATE/DELETE yetkili)');
info('Yönetici kullanıcı adı: ' . $adminUser);
info('');
info('Uygulama adresleri:');
info('- http://opencode-test.test/');
info('- http://localhost/opencode-test/');
info('');
info('Güvenlik: config/config.php dosyasını paylaşmayın ve sürüm kontrolüne eklemeyin.');
