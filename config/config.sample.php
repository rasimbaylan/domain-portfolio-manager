<?php

declare(strict_types=1);

/*
 * Bu dosya yalnızca örnektir.
 * Gerçek yapılandırma bin/install.php tarafından config/config.php olarak oluşturulur.
 */
return [
    'app' => [
        'name' => 'Domain Portföy',
        'env' => 'production',
        'timezone' => 'Europe/Istanbul',
        'session_name' => 'opencode_portfolio',
        'session_idle_timeout' => 1800,
        'session_max_lifetime' => 28800,
    ],
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'opencode_test',
        'user' => 'opencode_app',
        'password' => '',
    ],
];
