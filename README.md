# Domain Portföy

PHP 8 ve MySQL ile geliştirilmiş, tek yöneticili ve USD bazlı küçük bir domain portföy yönetim uygulaması. Bu erken aşama proje DeepSeek ve OpenCode desteğiyle geliştirilmiştir; özellikler ve dağıtım hazırlığı gözden geçirilmektedir.

## English overview

Domain Portfolio Manager is an early-stage, single-administrator PHP and MySQL application for tracking domain names, purchase and renewal costs, and expiration dates. It includes search, filtering, a portfolio overview, and visual expiry reminders. The application interface is currently in Turkish.

The project was developed with assistance from DeepSeek and OpenCode. Its installation and security have not yet been independently verified; review the installer before using it, especially on a MySQL server with existing databases or users.
### Features

- Add, search, filter, update, and delete domain records.
- Track registrar, purchase and renewal costs in USD, dates, status, and notes.
- View portfolio totals and visual reminders for upcoming or past expiration dates.
- Manage the portfolio through a single-administrator interface.

### Requirements and setup

Requires PHP 8.1 or later with `pdo_mysql`, `mbstring`, and `intl`, MySQL 8.0 or later, and Apache with `.htaccess` support or the PHP development server.

Before running the installer, review `bin/install.php` and use an isolated MySQL environment. The script uses the names `opencode_test` and `opencode_app`; it may modify an existing MySQL user, administrator account, or local configuration with those names.

```sh
php bin/install.php
```

The installer creates the application database, configures a restricted database user, prompts for administrator credentials, and writes `config/config.php`. The actual configuration file is excluded from version control. The application UI is currently in Turkish. For additional details, see the Turkish instructions below.

**License:** [MIT](LICENSE). This is an early-stage project; installation and security have not been independently verified.

---
## Özellikler

- Tek yönetici hesabıyla giriş
- Domain ekleme, listeleme, arama, filtreleme, düzenleme ve silme
- USD cinsinden alış ve yenileme fiyatı takibi
- Registrar, alış tarihi, bitiş tarihi, durum ve not alanları
- Portföy özeti: toplam domain, toplam alış maliyeti, satılık domainler, 30 gün içinde bitecekler
- Bitiş tarihi yaklaşan veya geçmiş domainler için görsel uyarı
- Sayfalama (sayfa başına 20 kayıt)
- Responsive ve erişilebilir arayüz

## Gereksinimler

- PHP 8.1 veya üzeri
- `pdo_mysql`, `mbstring`, `intl` PHP eklentileri
- MySQL 8.0 veya üzeri
- Apache (`.htaccess` desteği) veya PHP yerleşik sunucusu

## Kurulum

Proje klasöründe PHP komut satırından aşağıdaki komutu çalıştırın:

```powershell
php bin/install.php
```

> Kurulum betiği MySQL üzerinde veritabanı ve kullanıcı oluşturabilir; mevcut aynı isimli kullanıcıyı ve yönetici hesabını değiştirebilir. Kullanmadan önce betiği ve hedef ortamı inceleyin.

Kurulum betiği sırasıyla:

1. MySQL yönetici hesabıyla bağlanır (Laragon varsayılanı: `root`, boş şifre).
2. Yalnızca `opencode_test` adlı yeni veritabanını oluşturur.
3. Yalnızca bu veritabanında `SELECT`, `INSERT`, `UPDATE`, `DELETE` yetkisi olan `opencode_app`@`localhost` kullanıcısını rastgele güçlü bir şifreyle oluşturur.
4. `admins` ve `domains` tablolarını oluşturur.
5. Sizin belirlediğiniz yönetici kullanıcı adı ve şifresiyle tek yönetici hesabını oluşturur.
6. `config/config.php` dosyasını üretir.

MySQL root hesabınızda şifre varsa:

```powershell
php bin\install.php --root-password=ROOT_SIFRENIZ
```

Kurulumdan sonra Laragon üzerinden şu adreslerden birini açın:

- `http://opencode-test.test/`
- `http://localhost/opencode-test/`

## Kullanım

- Giriş yaptıktan sonra **Portföy** ekranında tüm domainler listelenir.
- **Yeni domain** ile kayıt ekleyebilirsiniz.
- Tablodaki **Düzenle** ve **Sil** işlemleriyle kayıtları yönetebilirsiniz.
- Arama alanı domain adı veya registrar içinde arama yapar.
- Durum filtresiyle aktif, satılık, satıldı ve süresi doldu kayıtlarını ayırabilirsiniz.
- Tüm fiyat alanları USD cinsindendir; `12.50` veya `12,50` biçimleri kabul edilir.

## Güvenlik Notları

- Parolalar `password_hash()` / `password_verify()` ile saklanır ve doğrulanır.
- Tüm formlarda CSRF koruması vardır.
- Tüm SQL sorguları hazır ifadelerle (prepared statements) çalıştırılır.
- Çıktılar HTML kaçışından geçirilir.
- Oturum çerezi `HttpOnly` ve `SameSite=Lax` olarak ayarlanır; HTTPS varsa `Secure` etkinleşir.
- 30 dakika hareketsizlik ve 8 saatlik mutlak oturum süresi vardır.
- 5 başarısız girişten sonra 60 saniyelik oturum bazlı bekleme uygulanır.
- Güvenlik başlıkları (CSP, X-Frame-Options, nosniff, Referrer-Policy) gönderilir.
- Hassas klasörler (`app`, `config`, `database`, `views`, `bin`, `storage`) `.htaccess` ile web erişimine kapatılır.
- Yönetici hesabı veritabanında tek satırla sınırlandırılmıştır (`CHECK (id = 1)`).
- `config/config.php` ve günlük dosyaları sürüm kontrolüne eklenmemelidir.

## Yapı

```text
index.php                 Uygulama giriş noktası ve rotalar
app/
  bootstrap.php           Yapılandırma, oturum ve güvenlik başlıkları
  Auth.php                Giriş, oturum ve kilit mekanizması
  Csrf.php                CSRF token üretimi ve doğrulaması
  Database.php            PDO bağlantısı
  DomainRepository.php    Domain veri erişimi ve doğrulama
  icons.php               Arayüz SVG ikonları
  helpers.php             Görünüm ve biçimlendirme yardımcıları
config/
  config.sample.php       Örnek yapılandırma
  config.php              Kurulumla üretilen gerçek yapılandırma
database/
  schema.sql              Tablo tanımları
views/                    HTML görünümleri
assets/                   CSS ve JavaScript
bin/
  install.php             Tek seferlik kurulum betiği
storage/logs/             PHP hata günlükleri
```

## Yönetici Şifresini Sıfırlama

Mevcut domain kayıtları silinmeden yönetici bilgilerini yenilemek için:

```powershell
php bin\install.php --force --admin-user=admin
```

Bu komut yönetici kullanıcı adını ve şifresini günceller, uygulama veritabanı kullanıcısının şifresini yeniler ve `config/config.php` dosyasını tekrar yazar. Domain kayıtlarına dokunmaz.

## Veritabanı

- Veritabanı adı: `opencode_test`
- Uygulama kullanıcısı: `opencode_app`@`localhost`
- Tablolar: `admins`, `domains`

Kurulum betiği yukarıdaki isimleri kullanır. Aynı isimli veritabanı, kullanıcı veya yönetici hesabı zaten varsa betiğin davranışını önceden inceleyin.

## Lisans

Bu proje [MIT lisansı](LICENSE) ile sunulmaktadır.
