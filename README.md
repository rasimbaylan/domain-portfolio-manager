# Domain Portfolio Manager

A lightweight PHP and MySQL application for managing a domain portfolio from a single administrator account. Track domain names, purchase and renewal costs, and expiration dates in one place.

> **Early-stage project:** Installation and security have not been independently verified. Review the installer before using it, especially on a MySQL server with existing databases or users. The application interface is currently in Turkish.

## Features

- Add, search, filter, edit, and delete domain records.
- Record registrars, purchase and expiry dates, status, and notes.
- Track purchase and renewal costs in USD.
- View portfolio totals, domains for sale, and upcoming expirations.
- See visual reminders for domains nearing or past expiration.
- Navigate a paginated, responsive administration interface.

## Requirements

- PHP 8.1 or later with `pdo_mysql`, `mbstring`, and `intl`.
- MySQL 8.0 or later.
- Apache with `.htaccess` support, or the PHP development server.

## Installation

Review `bin/install.php` first and use an isolated MySQL environment. The installer uses the names `opencode_test` and `opencode_app`; it may modify an existing MySQL user, administrator account, or local configuration with those names.

Run the installer from the project directory:

```sh
php bin/install.php
```

The installer creates the database and tables, configures a database user, prompts for administrator credentials, and writes `config/config.php`. The generated configuration and runtime logs are excluded from version control. The installer accepts additional options; review the script before supplying database credentials or running it on an existing environment.

## Project layout

```text
index.php          Application entry point and routes
app/               Authentication, database access, and helpers
assets/            CSS and JavaScript
bin/               Installation script
config/            Example configuration
database/          Database schema
views/             Application views
storage/           Runtime storage
```

## Project status

This is an early development snapshot created with assistance from DeepSeek and OpenCode. Features and deployment readiness remain under review. Contributions and feedback are welcome.

## License

[MIT](LICENSE)