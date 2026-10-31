-- Domain Portföy veritabanı şeması
-- Yalnızca opencode_test veritabanında çalıştırılır.

CREATE TABLE IF NOT EXISTS admins (
    id TINYINT UNSIGNED NOT NULL,
    username VARCHAR(50) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admins_username (username),
    CONSTRAINT chk_admins_single_row CHECK (id = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS domains (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    domain VARCHAR(253) NOT NULL,
    registrar VARCHAR(150) NULL,
    purchase_price DECIMAL(12, 2) NULL,
    renewal_price DECIMAL(12, 2) NULL,
    purchase_date DATE NULL,
    expiry_date DATE NULL,
    status ENUM('aktif', 'satilik', 'satildi', 'suresi_doldu') NOT NULL DEFAULT 'aktif',
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_domains_domain (domain),
    KEY idx_domains_expiry (expiry_date),
    KEY idx_domains_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
